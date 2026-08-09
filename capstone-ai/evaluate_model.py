import argparse
import json
from pathlib import Path
from typing import Optional

import joblib
import pandas as pd
from sklearn.model_selection import train_test_split

from train_model import (
    DEFAULT_DATA_PATH,
    METADATA_PATH,
    MODEL_PATH,
    infer_dataset_kind,
    load_json_records,
    prepare_historical_dataframe,
    prepare_legacy_dataframe,
    prepare_merged_incident_dataframe,
    prepare_station_barangay_monthly_dataframe,
)


BASE_DIR = Path(__file__).resolve().parent


def resolve_path(raw_path: Optional[str], fallback: Path) -> Path:
    if not raw_path:
        return fallback

    path = Path(raw_path)
    if path.is_absolute():
        return path

    root_relative = BASE_DIR.parent / path
    if root_relative.exists():
        return root_relative

    local_relative = BASE_DIR / path
    if local_relative.exists():
        return local_relative

    return root_relative


def prepare_dataset(records: list[dict]):
    dataset_kind = infer_dataset_kind(records)

    if dataset_kind == "historical_data":
        return prepare_historical_dataframe(records)
    if dataset_kind == "station_barangay_monthly":
        return prepare_station_barangay_monthly_dataframe(records)
    if dataset_kind == "merged_incident_dataset":
        return prepare_merged_incident_dataframe(records)

    return prepare_legacy_dataframe(records)


def build_point_labels(records: list[dict], dataset_kind: str) -> pd.Series:
    frame = pd.DataFrame(records).copy()

    if dataset_kind == "station_barangay_monthly":
        return frame.apply(
            lambda row: f"{row.get('barangay_name', 'Unknown')} · {row.get('month', 'Unknown')} {row.get('year', '')}".strip(),
            axis=1,
        )

    if dataset_kind == "historical_data":
        return frame.apply(
            lambda row: f"{row.get('month', 'Unknown')} {row.get('year', '')}".strip(),
            axis=1,
        )

    if dataset_kind == "merged_incident_dataset":
        return frame.apply(
            lambda row: f"{row.get('incident_month', 'Unknown')} {row.get('incident_year', '')}".strip(),
            axis=1,
        )

    return pd.Series([f"Record {index + 1}" for index in range(len(frame))])


def to_python_number(value):
    if pd.isna(value):
        return None
    return float(round(float(value), 4))


def main():
    parser = argparse.ArgumentParser(description="Generate a cached evaluation artifact for the saved fire forecast model.")
    parser.add_argument("--dataset", dest="dataset_path", default=None)
    parser.add_argument("--model", dest="model_path", default=None)
    parser.add_argument("--metadata", dest="metadata_path", default=None)
    parser.add_argument("--output", dest="output_path", default=None)
    args = parser.parse_args()

    metadata_path = resolve_path(args.metadata_path, METADATA_PATH)
    model_path = resolve_path(args.model_path, MODEL_PATH)

    metadata = json.loads(metadata_path.read_text(encoding="utf-8"))
    dataset_value = args.dataset_path or metadata.get("dataset_path")
    dataset_path = resolve_path(dataset_value, DEFAULT_DATA_PATH)
    output_path = resolve_path(args.output_path, BASE_DIR / "fire_model_evaluation.json") if args.output_path else None

    records = load_json_records(dataset_path)
    X, y, _feature_columns, prep_metadata = prepare_dataset(records)
    dataset_kind = prep_metadata["dataset_kind"]
    bundle = joblib.load(model_path)

    labels = build_point_labels(records, dataset_kind)

    stratify_target = y if y.nunique() > 1 and y.value_counts().min() >= 2 else None
    X_train, X_test, y_train, y_test, labels_train, labels_test = train_test_split(
        X,
        y,
        labels,
        test_size=0.25,
        random_state=42,
        stratify=stratify_target,
    )

    model = bundle["model"]
    y_pred = model.predict(X_test)

    if hasattr(model, "predict_proba") and y.nunique() > 1:
        y_prob = model.predict_proba(X_test)[:, 1]
    else:
        y_prob = [None] * len(y_test)

    classification_points = [
        {
            "label": str(label),
            "actual": int(actual),
            "predicted": int(predicted),
            "predicted_probability": to_python_number(probability),
        }
        for label, actual, predicted, probability in zip(labels_test.tolist(), y_test.tolist(), y_pred.tolist(), y_prob)
    ]

    regression_metrics = metadata.get("metrics", {}).get("regression_metrics", {})
    regression_models = bundle.get("regression_models", {})
    actual_vs_predicted = None
    residual_pattern = None

    incident_metric = regression_metrics.get("predicted_incident_count")
    incident_model = regression_models.get("predicted_incident_count")

    if incident_metric and incident_model is not None:
        target_column = incident_metric.get("target_column", "incident_count")
        raw_frame = pd.DataFrame(records).copy()
        target_values = pd.to_numeric(raw_frame[target_column], errors="coerce")
        valid_mask = ~target_values.isna()

        if valid_mask.sum() >= 8:
            X_reg = X.loc[valid_mask].reset_index(drop=True)
            y_reg = target_values.loc[valid_mask].reset_index(drop=True)
            labels_reg = labels.loc[valid_mask].reset_index(drop=True)

            X_reg_train, X_reg_test, y_reg_train, y_reg_test, labels_reg_train, labels_reg_test = train_test_split(
                X_reg,
                y_reg,
                labels_reg,
                test_size=0.25,
                random_state=42,
            )

            regression_predictions = incident_model.predict(X_reg_test)
            result_rows = pd.DataFrame(
                {
                    "label": labels_reg_test.tolist(),
                    "actual": y_reg_test.tolist(),
                    "predicted": regression_predictions.tolist(),
                }
            )

            month_order = {
                "January": 1, "February": 2, "March": 3, "April": 4,
                "May": 5, "June": 6, "July": 7, "August": 8,
                "September": 9, "October": 10, "November": 11, "December": 12,
            }

            if dataset_kind == "station_barangay_monthly":
                raw_valid = raw_frame.loc[valid_mask].reset_index(drop=True)
                _, _, _, _, raw_train, raw_test = train_test_split(
                    X_reg,
                    y_reg,
                    raw_valid,
                    test_size=0.25,
                    random_state=42,
                )
                result_rows["year"] = pd.to_numeric(raw_test["year"], errors="coerce").fillna(0)
                result_rows["month_number"] = raw_test["month"].map(month_order).fillna(0)
                result_rows["barangay_name"] = raw_test["barangay_name"].fillna("Unknown")
                result_rows = result_rows.sort_values(["year", "month_number", "barangay_name", "label"])
            else:
                result_rows = result_rows.sort_values(["label"])

            actual_values = [to_python_number(value) for value in result_rows["actual"].tolist()]
            predicted_values = [to_python_number(value) for value in result_rows["predicted"].tolist()]
            residual_values = [
                to_python_number(predicted - actual)
                for actual, predicted in zip(result_rows["actual"].tolist(), result_rows["predicted"].tolist())
            ]

            actual_vs_predicted = {
                "target": "predicted_incident_count",
                "target_column": target_column,
                "metric_label": "Incident count",
                "labels": result_rows["label"].astype(str).tolist(),
                "actual": actual_values,
                "predicted": predicted_values,
            }
            residual_pattern = {
                "target": "predicted_incident_count",
                "target_column": target_column,
                "metric_label": "Prediction error (predicted - actual incidents)",
                "labels": result_rows["label"].astype(str).tolist(),
                "residuals": residual_values,
                "reference_line": 0,
            }

    payload = {
        "available": True,
        "partial": False,
        "trained_at": metadata.get("trained_at"),
        "generated_at": pd.Timestamp.now(tz="UTC").isoformat(),
        "model_type": "RandomForestClassifier + RandomForestRegressor",
        "supports_epoch_learning_curve": False,
        "feature_importance": metadata.get("global_feature_importance", []),
        "classification_performance": {
            "train_rows": metadata.get("metrics", {}).get("train_rows"),
            "test_rows": metadata.get("metrics", {}).get("test_rows"),
            "accuracy": metadata.get("metrics", {}).get("accuracy"),
            "roc_auc": metadata.get("metrics", {}).get("roc_auc"),
            "precision": metadata.get("metrics", {}).get("precision"),
            "recall": metadata.get("metrics", {}).get("recall"),
            "f1_score": metadata.get("metrics", {}).get("f1_score"),
            "brier_score": metadata.get("metrics", {}).get("brier_score"),
            "classification_report": metadata.get("metrics", {}).get("classification_report", {}),
            "test_points": classification_points,
        },
        "cross_validation": {
            "metric": "ROC AUC",
            "mean": metadata.get("metrics", {}).get("cross_val_roc_auc_mean"),
            "scores": metadata.get("metrics", {}).get("cross_val_roc_auc_scores", []),
        },
        "regression_performance": regression_metrics,
        "actual_vs_predicted": actual_vs_predicted,
        "residual_pattern": residual_pattern,
    }

    payload_json = json.dumps(payload, indent=2)

    if output_path:
        output_path.write_text(payload_json, encoding="utf-8")

    print(payload_json)


if __name__ == "__main__":
    main()
