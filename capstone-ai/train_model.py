import json
import math
import sys
from pathlib import Path

import joblib
import pandas as pd
from sklearn.calibration import CalibratedClassifierCV
from sklearn.compose import ColumnTransformer
from sklearn.ensemble import RandomForestClassifier
from sklearn.ensemble import RandomForestRegressor
from sklearn.metrics import accuracy_score, brier_score_loss, classification_report, f1_score, precision_score, recall_score, roc_auc_score
from sklearn.metrics import mean_absolute_error, mean_squared_error, r2_score
from sklearn.model_selection import cross_val_score, train_test_split
from sklearn.pipeline import Pipeline
from sklearn.preprocessing import OneHotEncoder


BASE_DIR = Path(__file__).resolve().parent
DEFAULT_DATA_PATH = BASE_DIR / "historical_data_demo.json"
MODEL_PATH = BASE_DIR / "fire_model.pkl"
METADATA_PATH = BASE_DIR / "fire_model_metadata.json"


LEGACY_TARGET_COLUMN = "fire_occurred"
LEGACY_RAW_FEATURE_COLUMNS = [
    "avg_temp",
    "density",
    "month",
    "humidity",
    "wind_kph",
    "rainfall_mm",
    "past_7_day_incidents",
    "past_30_day_incidents",
    "barangay_risk_score",
]

HISTORICAL_SCHEMA_COLUMNS = [
    "station_id",
    "month",
    "year",
    "incident_count",
    "risk_level",
    "average_temperature",
    "season",
    "near_water",
    "density",
    "materials",
    "peak_hours",
    "accesibility",
    "type_incident",
    "occupancy",
]

STATION_BARANGAY_MONTHLY_COLUMNS = [
    "station_id",
    "barangay_id",
    "barangay_name",
    "month",
    "year",
    "incident_count",
    "estimated_damage",
    "injured_count",
    "death_count",
    "risk_level",
    "average_temperature",
    "average_humidity",
    "average_wind_speed",
    "weather_condition",
    "season",
    "near_water",
    "verified_risk_area_count",
    "verified_hazard_count",
    "verified_risk_count",
    "high_assessment_count",
    "blocked_hydrant_count",
    "combustible_material_count",
    "informal_settlement_count",
    "cooking_fire_activity_count",
    "poor_waste_disposal_count",
    "previous_fire_prone_count",
    "poor_access_count",
    "affected_households",
    "risk_area_pressure_signal",
    "density",
    "materials",
    "peak_hours",
    "accesibility",
    "type_incident",
    "occupancy",
]

RISK_AREA_SIGNAL_COLUMNS = [
    "verified_risk_area_count",
    "verified_hazard_count",
    "verified_risk_count",
    "high_assessment_count",
    "blocked_hydrant_count",
    "combustible_material_count",
    "informal_settlement_count",
    "cooking_fire_activity_count",
    "poor_waste_disposal_count",
    "previous_fire_prone_count",
    "poor_access_count",
    "affected_households",
    "risk_area_pressure_signal",
]

MERGED_INCIDENT_REQUIRED_COLUMNS = [
    "station_id",
    "incident_month",
    "incident_year",
    "incident_status",
    "marker_category",
    "spot_estimated_damage",
    "spot_occupancy",
    "estimated_area",
    "fire_cause",
    "historical_incident_count",
    "historical_risk_level",
    "historical_average_temperature",
    "historical_season",
    "historical_near_water",
    "historical_density",
    "historical_materials",
    "historical_peak_hours",
    "historical_accesibility",
    "historical_type_incident",
    "historical_occupancy",
]

MONTH_ORDER = {
    "January": 1, "February": 2, "March": 3, "April": 4,
    "May": 5, "June": 6, "July": 7, "August": 8,
    "September": 9, "October": 10, "November": 11, "December": 12,
}


def load_json_records(path: Path) -> list[dict]:
    payload = json.loads(path.read_text(encoding="utf-8"))

    if isinstance(payload, list):
        records = payload
    elif isinstance(payload, dict) and isinstance(payload.get("records"), list):
        records = payload["records"]
    else:
        raise ValueError("Expected a list of records or an object with a 'records' array.")

    if not records:
        raise ValueError(f"{path.name} does not contain any records.")

    return records


def infer_dataset_kind(records: list[dict]) -> str:
    sample = records[0]
    if LEGACY_TARGET_COLUMN in sample:
        return "legacy_fire_data"
    if "historical_incident_count" in sample and "spot_estimated_damage" in sample:
        return "merged_incident_dataset"
    if "barangay_id" in sample and "barangay_name" in sample and "incident_count" in sample:
        return "station_barangay_monthly"
    if "risk_level" in sample and "average_temperature" in sample and "incident_count" in sample:
        return "historical_data"
    raise ValueError("Unsupported JSON dataset shape.")


def prepare_legacy_dataframe(records: list[dict]) -> tuple[pd.DataFrame, pd.Series, list[str], dict]:
    df = pd.DataFrame(records).copy()

    missing_columns = [column for column in LEGACY_RAW_FEATURE_COLUMNS + [LEGACY_TARGET_COLUMN] if column not in df.columns]
    if missing_columns:
        raise ValueError(f"Legacy dataset is missing required fields: {', '.join(missing_columns)}")

    for column in LEGACY_RAW_FEATURE_COLUMNS + [LEGACY_TARGET_COLUMN]:
        df[column] = pd.to_numeric(df[column], errors="coerce")

    df = df.dropna(subset=LEGACY_RAW_FEATURE_COLUMNS + [LEGACY_TARGET_COLUMN]).reset_index(drop=True)
    if df.empty:
        raise ValueError("No valid legacy rows left after cleaning.")

    df["month_sin"] = df["month"].apply(lambda value: math.sin(2 * math.pi * value / 12.0))
    df["month_cos"] = df["month"].apply(lambda value: math.cos(2 * math.pi * value / 12.0))
    df["heat_index_proxy"] = (df["avg_temp"] * 0.65) + (df["humidity"] * 0.08) + (df["density"] * 0.4)
    df["dryness_signal"] = (df["avg_temp"] * 1.4) - (df["rainfall_mm"] * 0.9) - (df["humidity"] * 0.35)
    df["recent_activity_signal"] = df["past_7_day_incidents"] * 1.2 + df["past_30_day_incidents"] * 0.4

    feature_columns = [
        "avg_temp",
        "density",
        "humidity",
        "wind_kph",
        "rainfall_mm",
        "past_7_day_incidents",
        "past_30_day_incidents",
        "barangay_risk_score",
        "month_sin",
        "month_cos",
        "heat_index_proxy",
        "dryness_signal",
        "recent_activity_signal",
    ]

    metadata = {
        "dataset_kind": "legacy_fire_data",
        "target_column": LEGACY_TARGET_COLUMN,
        "raw_feature_columns": LEGACY_RAW_FEATURE_COLUMNS,
        "categorical_features": [],
        "numeric_features": feature_columns,
        "target_description": "Binary fire occurrence event.",
    }
    return df[feature_columns], df[LEGACY_TARGET_COLUMN].astype(int), feature_columns, metadata


def prepare_historical_dataframe(records: list[dict]) -> tuple[pd.DataFrame, pd.Series, list[str], dict]:
    df = pd.DataFrame(records).copy()

    missing_columns = [column for column in HISTORICAL_SCHEMA_COLUMNS if column not in df.columns]
    if missing_columns:
        raise ValueError(f"historical_data dataset is missing required fields: {', '.join(missing_columns)}")

    numeric_columns = ["station_id", "year", "incident_count", "average_temperature", "near_water"]
    for column in numeric_columns:
        df[column] = pd.to_numeric(df[column], errors="coerce")

    df = df.dropna(subset=HISTORICAL_SCHEMA_COLUMNS).reset_index(drop=True)
    if df.empty:
        raise ValueError("No valid historical_data rows left after cleaning.")

    df["month_number"] = df["month"].map(MONTH_ORDER)
    df["month_sin"] = df["month_number"].apply(lambda value: math.sin(2 * math.pi * value / 12.0))
    df["month_cos"] = df["month_number"].apply(lambda value: math.cos(2 * math.pi * value / 12.0))
    df["is_dry_season"] = (df["season"] == "dry").astype(int)
    df["high_density_flag"] = (df["density"] == "high").astype(int)
    df["water_access_flag"] = df["near_water"].astype(int)

    # With historical_data, the practical baseline target is "high risk month/event".
    df["target_high_risk_event"] = (
        (df["risk_level"].str.lower() == "high") |
        (df["incident_count"] >= 18)
    ).astype(int)

    feature_columns = [
        "station_id",
        "year",
        "average_temperature",
        "water_access_flag",
        "month_sin",
        "month_cos",
        "is_dry_season",
        "high_density_flag",
        "season",
        "density",
        "materials",
        "peak_hours",
        "accesibility",
        "type_incident",
        "occupancy",
    ]

    metadata = {
        "dataset_kind": "historical_data",
        "target_column": "target_high_risk_event",
        "raw_feature_columns": HISTORICAL_SCHEMA_COLUMNS,
        "categorical_features": ["season", "density", "materials", "peak_hours", "accesibility", "type_incident", "occupancy"],
        "numeric_features": ["station_id", "year", "average_temperature", "water_access_flag", "month_sin", "month_cos", "is_dry_season", "high_density_flag"],
        "target_description": "Binary high-risk month/event target derived from historical_data.risk_level and incident_count.",
    }
    return df[feature_columns], df["target_high_risk_event"], feature_columns, metadata


def prepare_station_barangay_monthly_dataframe(records: list[dict]) -> tuple[pd.DataFrame, pd.Series, list[str], dict]:
    df = pd.DataFrame(records).copy()

    missing_columns = [
        column for column in STATION_BARANGAY_MONTHLY_COLUMNS
        if column not in df.columns and column not in RISK_AREA_SIGNAL_COLUMNS
    ]
    if missing_columns:
        raise ValueError(f"station_barangay_monthly dataset is missing required fields: {', '.join(missing_columns)}")

    numeric_columns = [
        "station_id",
        "barangay_id",
        "year",
        "incident_count",
        "estimated_damage",
        "injured_count",
        "death_count",
        "average_temperature",
        "average_humidity",
        "average_wind_speed",
        "near_water",
        "verified_risk_area_count",
        "verified_hazard_count",
        "verified_risk_count",
        "high_assessment_count",
        "blocked_hydrant_count",
        "combustible_material_count",
        "informal_settlement_count",
        "cooking_fire_activity_count",
        "poor_waste_disposal_count",
        "previous_fire_prone_count",
        "poor_access_count",
        "affected_households",
        "risk_area_pressure_signal",
    ]
    for column in numeric_columns:
        df[column] = pd.to_numeric(df[column], errors="coerce")

    for column in ["weather_condition", "season", "density", "materials", "peak_hours", "accesibility", "type_incident", "occupancy", "barangay_name", "risk_level"]:
        if column not in df.columns:
            df[column] = "unknown"
        df[column] = df[column].fillna("unknown").astype(str).str.strip().replace("", "unknown")

    for column in ["average_humidity", "average_wind_speed", "estimated_damage", "injured_count", "death_count"]:
        if column not in df.columns:
            df[column] = 0
        df[column] = pd.to_numeric(df[column], errors="coerce").fillna(0)

    for column in RISK_AREA_SIGNAL_COLUMNS:
        if column not in df.columns:
            df[column] = 0
        df[column] = pd.to_numeric(df[column], errors="coerce").fillna(0)

    essential_columns = [
        "station_id",
        "barangay_id",
        "barangay_name",
        "month",
        "year",
        "incident_count",
        "risk_level",
        "average_temperature",
        "season",
        "near_water",
        "density",
        "materials",
        "peak_hours",
        "accesibility",
        "type_incident",
        "occupancy",
    ]

    df = df.dropna(subset=essential_columns).reset_index(drop=True)
    if df.empty:
        raise ValueError("No valid station_barangay_monthly rows left after cleaning.")

    df["month_number"] = df["month"].map(MONTH_ORDER)
    df["month_sin"] = df["month_number"].apply(lambda value: math.sin(2 * math.pi * value / 12.0))
    df["month_cos"] = df["month_number"].apply(lambda value: math.cos(2 * math.pi * value / 12.0))
    df["is_dry_season"] = (df["season"] == "dry").astype(int)
    df["high_density_flag"] = (df["density"] == "high").astype(int)
    df["water_access_flag"] = df["near_water"].astype(int)
    df["temperature_signal"] = df["average_temperature"].fillna(0)
    df["humidity_signal"] = df["average_humidity"].fillna(df["average_humidity"].median() if not df["average_humidity"].dropna().empty else 0)
    df["wind_signal"] = df["average_wind_speed"].fillna(df["average_wind_speed"].median() if not df["average_wind_speed"].dropna().empty else 0)
    df["barangay_pressure_signal"] = (
        df["incident_count"] * 1.2
        + df["average_temperature"] * 0.35
        + df["high_density_flag"] * 2.5
        - df["water_access_flag"] * 0.75
        + df["average_wind_speed"].fillna(0) * 0.15
        - df["average_humidity"].fillna(0) * 0.03
        + df["risk_area_pressure_signal"] * 0.9
        + df["high_assessment_count"] * 1.1
        + df["blocked_hydrant_count"] * 0.8
    )

    df["target_high_risk_barangay_month"] = (
        (df["risk_level"].str.lower().isin(["medium", "high"])) |
        (df["incident_count"] >= 1) |
        (df["estimated_damage"].fillna(0) >= 50000)
    ).astype(int)

    feature_columns = [
        "station_id",
        "barangay_id",
        "year",
        "average_temperature",
        "average_humidity",
        "average_wind_speed",
        "water_access_flag",
        "verified_risk_area_count",
        "verified_hazard_count",
        "verified_risk_count",
        "high_assessment_count",
        "blocked_hydrant_count",
        "combustible_material_count",
        "informal_settlement_count",
        "cooking_fire_activity_count",
        "poor_waste_disposal_count",
        "previous_fire_prone_count",
        "poor_access_count",
        "affected_households",
        "risk_area_pressure_signal",
        "month_sin",
        "month_cos",
        "is_dry_season",
        "high_density_flag",
        "temperature_signal",
        "humidity_signal",
        "wind_signal",
        "barangay_pressure_signal",
        "barangay_name",
        "weather_condition",
        "season",
        "density",
        "materials",
        "peak_hours",
        "accesibility",
        "type_incident",
        "occupancy",
    ]

    metadata = {
        "dataset_kind": "station_barangay_monthly",
        "target_column": "target_high_risk_barangay_month",
        "raw_feature_columns": STATION_BARANGAY_MONTHLY_COLUMNS,
        "categorical_features": [
            "barangay_name",
            "weather_condition",
            "season",
            "density",
            "materials",
            "peak_hours",
            "accesibility",
            "type_incident",
            "occupancy",
        ],
        "numeric_features": [
            "station_id",
            "barangay_id",
            "year",
            "average_temperature",
            "average_humidity",
            "average_wind_speed",
            "water_access_flag",
            "verified_risk_area_count",
            "verified_hazard_count",
            "verified_risk_count",
            "high_assessment_count",
            "blocked_hydrant_count",
            "combustible_material_count",
            "informal_settlement_count",
            "cooking_fire_activity_count",
            "poor_waste_disposal_count",
            "previous_fire_prone_count",
            "poor_access_count",
            "affected_households",
            "risk_area_pressure_signal",
            "month_sin",
            "month_cos",
            "is_dry_season",
            "high_density_flag",
            "temperature_signal",
            "humidity_signal",
            "wind_signal",
            "barangay_pressure_signal",
        ],
        "target_description": "Binary high-risk barangay-month target derived from barangay-level monthly risk_level and incident_count.",
        "regression_targets": {
            "predicted_incident_count": "incident_count",
            "predicted_estimated_damage": "estimated_damage",
        },
    }
    return df[feature_columns], df["target_high_risk_barangay_month"], feature_columns, metadata


def normalize_weather_context(value) -> dict:
    if isinstance(value, dict):
        return value

    if isinstance(value, str):
        stripped_value = value.strip()
        if not stripped_value:
            return {}
        try:
            decoded = json.loads(stripped_value)
        except json.JSONDecodeError:
            return {"condition": stripped_value}
        return decoded if isinstance(decoded, dict) else {}

    return {}


def prepare_merged_incident_dataframe(records: list[dict]) -> tuple[pd.DataFrame, pd.Series, list[str], dict]:
    df = pd.DataFrame(records).copy()

    for column in MERGED_INCIDENT_REQUIRED_COLUMNS:
        if column not in df.columns:
            df[column] = None

    missing_columns = [column for column in MERGED_INCIDENT_REQUIRED_COLUMNS if column not in df.columns]
    if missing_columns:
        raise ValueError(f"Merged incident dataset is missing required fields: {', '.join(missing_columns)}")

    weather_series = df.get("weather_context", pd.Series([{}] * len(df))).apply(normalize_weather_context)
    df["weather_condition"] = weather_series.apply(lambda item: str(item.get("condition") or item.get("summary") or "unknown").lower())
    df["weather_temp_c"] = pd.to_numeric(weather_series.apply(lambda item: item.get("temperature_c", item.get("temp_c"))), errors="coerce")
    df["weather_humidity"] = pd.to_numeric(weather_series.apply(lambda item: item.get("humidity")), errors="coerce")
    df["weather_wind_kph"] = pd.to_numeric(weather_series.apply(lambda item: item.get("wind_kph")), errors="coerce")
    df["weather_rainfall_mm"] = pd.to_numeric(weather_series.apply(lambda item: item.get("rainfall_mm")), errors="coerce")

    numeric_columns = [
        "station_id",
        "incident_year",
        "marker_lat",
        "marker_lng",
        "spot_estimated_damage",
        "estimated_area",
        "historical_incident_count",
        "historical_average_temperature",
        "historical_near_water",
        "final_total_damage",
        "dead",
        "injured",
        "weather_temp_c",
        "weather_humidity",
        "weather_wind_kph",
        "weather_rainfall_mm",
    ]

    for column in numeric_columns:
        if column not in df.columns:
            df[column] = None
        df[column] = pd.to_numeric(df[column], errors="coerce")

    categorical_columns = [
        "incident_month",
        "incident_status",
        "marker_category",
        "spot_occupancy",
        "fire_origin",
        "fire_cause",
        "historical_risk_level",
        "historical_season",
        "historical_density",
        "historical_materials",
        "historical_peak_hours",
        "historical_accesibility",
        "historical_type_incident",
        "historical_occupancy",
        "weather_condition",
    ]

    for column in categorical_columns:
        if column not in df.columns:
            df[column] = "unknown"
        df[column] = df[column].fillna("unknown").astype(str).str.strip().replace("", "unknown")

    df["incident_month"] = df["incident_month"].replace("unknown", None)
    df["incident_month_number"] = df["incident_month"].map(MONTH_ORDER)
    if "fire_started" in df.columns:
        fire_started_parsed = pd.to_datetime(df["fire_started"], errors="coerce")
        df.loc[df["incident_month_number"].isna(), "incident_month_number"] = fire_started_parsed.dt.month

    df = df.dropna(subset=["station_id", "incident_year", "incident_month_number"]).reset_index(drop=True)
    if df.empty:
        raise ValueError("No valid merged incident rows left after cleaning.")

    df["month_sin"] = df["incident_month_number"].apply(lambda value: math.sin(2 * math.pi * value / 12.0))
    df["month_cos"] = df["incident_month_number"].apply(lambda value: math.cos(2 * math.pi * value / 12.0))
    df["historical_high_risk_flag"] = (df["historical_risk_level"].str.lower() == "high").astype(int)
    df["historical_water_access_flag"] = df["historical_near_water"].fillna(0).astype(int)
    df["damage_pressure_signal"] = (
        df["spot_estimated_damage"].fillna(0) * 0.55
        + df["historical_incident_count"].fillna(0) * 1200
        + df["weather_wind_kph"].fillna(0) * 450
    )

    responsible_arson = df.get("responsible_arson", pd.Series([""] * len(df))).fillna("").astype(str).str.strip().str.lower()
    has_responsible_arson = ~responsible_arson.isin(["", "0", "false", "no", "none", "n/a", "unknown"])
    casualty_count = df["dead"].fillna(0) + df["injured"].fillna(0)

    df["target_high_impact_incident"] = (
        (casualty_count > 0)
        | (df["final_total_damage"].fillna(0) >= 150000)
        | (has_responsible_arson)
    ).astype(int)

    feature_columns = [
        "station_id",
        "incident_year",
        "marker_lat",
        "marker_lng",
        "spot_estimated_damage",
        "estimated_area",
        "historical_incident_count",
        "historical_average_temperature",
        "historical_water_access_flag",
        "weather_temp_c",
        "weather_humidity",
        "weather_wind_kph",
        "weather_rainfall_mm",
        "month_sin",
        "month_cos",
        "historical_high_risk_flag",
        "damage_pressure_signal",
        "incident_status",
        "marker_category",
        "spot_occupancy",
        "fire_origin",
        "fire_cause",
        "historical_risk_level",
        "historical_season",
        "historical_density",
        "historical_materials",
        "historical_peak_hours",
        "historical_accesibility",
        "historical_type_incident",
        "historical_occupancy",
        "weather_condition",
    ]

    metadata = {
        "dataset_kind": "merged_incident_dataset",
        "target_column": "target_high_impact_incident",
        "raw_feature_columns": MERGED_INCIDENT_REQUIRED_COLUMNS,
        "categorical_features": [
            "incident_status",
            "marker_category",
            "spot_occupancy",
            "fire_origin",
            "fire_cause",
            "historical_risk_level",
            "historical_season",
            "historical_density",
            "historical_materials",
            "historical_peak_hours",
            "historical_accesibility",
            "historical_type_incident",
            "historical_occupancy",
            "weather_condition",
        ],
        "numeric_features": [
            "station_id",
            "incident_year",
            "marker_lat",
            "marker_lng",
            "spot_estimated_damage",
            "estimated_area",
            "historical_incident_count",
            "historical_average_temperature",
            "historical_water_access_flag",
            "weather_temp_c",
            "weather_humidity",
            "weather_wind_kph",
            "weather_rainfall_mm",
            "month_sin",
            "month_cos",
            "historical_high_risk_flag",
            "damage_pressure_signal",
        ],
        "target_description": "Binary high-impact incident target derived from casualties, final damage, or confirmed responsible arson indicators.",
    }
    return df[feature_columns], df["target_high_impact_incident"], feature_columns, metadata


def build_model(y_train: pd.Series, categorical_features: list[str]) -> Pipeline | RandomForestClassifier | CalibratedClassifierCV:
    base_model = RandomForestClassifier(
        n_estimators=250,
        max_depth=10,
        min_samples_leaf=2,
        class_weight="balanced",
        random_state=42,
    )

    class_counts = y_train.value_counts()
    calibrate = len(class_counts) >= 2 and class_counts.min() >= 3

    estimator = CalibratedClassifierCV(base_model, cv=3, method="sigmoid") if calibrate else base_model

    if not categorical_features:
        return estimator

    preprocessor = ColumnTransformer(
        transformers=[
            ("categorical", OneHotEncoder(handle_unknown="ignore"), categorical_features),
        ],
        remainder="passthrough",
    )

    return Pipeline([
        ("preprocessor", preprocessor),
        ("model", estimator),
    ])


def build_regression_model(categorical_features: list[str]) -> Pipeline | RandomForestRegressor:
    estimator = RandomForestRegressor(
        n_estimators=250,
        max_depth=12,
        min_samples_leaf=2,
        random_state=42,
    )

    if not categorical_features:
        return estimator

    preprocessor = ColumnTransformer(
        transformers=[
            ("categorical", OneHotEncoder(handle_unknown="ignore"), categorical_features),
        ],
        remainder="passthrough",
    )

    return Pipeline([
        ("preprocessor", preprocessor),
        ("model", estimator),
    ])


def get_fitted_estimator(model):
    if isinstance(model, Pipeline):
        return get_fitted_estimator(model.named_steps["model"])

    if isinstance(model, CalibratedClassifierCV):
        calibrated_models = getattr(model, "calibrated_classifiers_", [])
        if calibrated_models:
            return calibrated_models[0].estimator

    return model


def get_transformed_feature_names(model, feature_columns: list[str]) -> list[str]:
    if isinstance(model, Pipeline):
        preprocessor = model.named_steps["preprocessor"]
        return list(preprocessor.get_feature_names_out())

    return list(feature_columns)


def aggregate_global_feature_importance(model, feature_columns: list[str]) -> list[dict]:
    estimator = get_fitted_estimator(model)
    raw_importances = getattr(estimator, "feature_importances_", None)
    if raw_importances is None:
        return []

    transformed_names = get_transformed_feature_names(model, feature_columns)
    grouped_importance: dict[str, float] = {}

    for name, importance in zip(transformed_names, raw_importances):
        raw_name = str(name)
        if "__" in raw_name:
            raw_name = raw_name.split("__", 1)[1]

        if "_" in raw_name and raw_name.rsplit("_", 1)[0] in feature_columns:
            candidate = raw_name.rsplit("_", 1)[0]
            if candidate in feature_columns:
                raw_name = candidate

        grouped_importance[raw_name] = grouped_importance.get(raw_name, 0.0) + float(importance)

    return [
        {"feature": feature, "importance": round(score, 4)}
        for feature, score in sorted(grouped_importance.items(), key=lambda item: item[1], reverse=True)
    ]


def build_reference_profile(X: pd.DataFrame, categorical_features: list[str], numeric_features: list[str]) -> dict:
    reference_profile: dict[str, object] = {}

    for column in numeric_features:
        if column in X.columns:
            reference_profile[column] = float(round(pd.to_numeric(X[column], errors="coerce").median(), 4))

    for column in categorical_features:
        if column in X.columns:
            mode_series = X[column].mode(dropna=True)
            reference_profile[column] = str(mode_series.iloc[0]) if not mode_series.empty else "unknown"

    return reference_profile


def build_example_profiles(X: pd.DataFrame, y: pd.Series) -> dict:
    joined = X.copy()
    joined["__target__"] = y.values

    positive_rows = joined[joined["__target__"] == 1].drop(columns=["__target__"])
    negative_rows = joined[joined["__target__"] == 0].drop(columns=["__target__"])

    return {
        "positive_example": positive_rows.head(1).to_dict(orient="records")[0] if not positive_rows.empty else None,
        "negative_example": negative_rows.head(1).to_dict(orient="records")[0] if not negative_rows.empty else None,
    }


def train(data_path: Path = DEFAULT_DATA_PATH):
    print(f"1. Loading JSON dataset from {data_path.name}...")
    records = load_json_records(data_path)
    dataset_kind = infer_dataset_kind(records)

    if dataset_kind == "historical_data":
        X, y, feature_columns, metadata = prepare_historical_dataframe(records)
    elif dataset_kind == "station_barangay_monthly":
        X, y, feature_columns, metadata = prepare_station_barangay_monthly_dataframe(records)
    elif dataset_kind == "merged_incident_dataset":
        X, y, feature_columns, metadata = prepare_merged_incident_dataframe(records)
    else:
        X, y, feature_columns, metadata = prepare_legacy_dataframe(records)

    print(f"2. Dataset kind: {metadata['dataset_kind']}")
    print(f"3. Prepared {len(X)} clean records with {len(feature_columns)} model features.")
    print(f"4. Class distribution: {y.value_counts().to_dict()}")

    stratify_target = y if y.nunique() > 1 and y.value_counts().min() >= 2 else None
    X_train, X_test, y_train, y_test = train_test_split(
        X,
        y,
        test_size=0.25,
        random_state=42,
        stratify=stratify_target,
    )

    print("5. Training probability-aware event model...")
    model = build_model(y_train, metadata["categorical_features"])
    model.fit(X_train, y_train)

    y_pred = model.predict(X_test)
    y_prob = model.predict_proba(X_test)[:, 1] if len(set(y)) > 1 else [0.0] * len(y_test)
    cv_scores = cross_val_score(
        build_model(y_train, metadata["categorical_features"]),
        X,
        y,
        cv=min(5, max(2, len(X) // 20)),
        scoring="roc_auc",
    ) if y.nunique() > 1 and y.value_counts().min() >= 2 else []

    global_feature_importance = aggregate_global_feature_importance(model, feature_columns)
    reference_profile = build_reference_profile(X, metadata["categorical_features"], metadata["numeric_features"])
    example_profiles = build_example_profiles(X, y)
    regression_models: dict[str, object] = {}
    regression_metrics: dict[str, dict] = {}

    regression_targets = metadata.get("regression_targets", {})
    if regression_targets:
        for output_name, target_column in regression_targets.items():
            if target_column not in pd.DataFrame(records).columns:
                continue

            target_values = pd.to_numeric(pd.DataFrame(records)[target_column], errors="coerce")
            valid_mask = ~target_values.isna()
            if valid_mask.sum() < 8:
                continue

            X_reg = X.loc[valid_mask].reset_index(drop=True)
            y_reg = target_values.loc[valid_mask].reset_index(drop=True)
            X_reg_train, X_reg_test, y_reg_train, y_reg_test = train_test_split(
                X_reg,
                y_reg,
                test_size=0.25,
                random_state=42,
            )

            regression_model = build_regression_model(metadata["categorical_features"])
            regression_model.fit(X_reg_train, y_reg_train)
            y_reg_pred = regression_model.predict(X_reg_test)

            regression_models[output_name] = regression_model
            regression_metrics[output_name] = {
                "target_column": target_column,
                "train_rows": int(len(X_reg_train)),
                "test_rows": int(len(X_reg_test)),
                "mae": float(round(mean_absolute_error(y_reg_test, y_reg_pred), 4)),
                "rmse": float(round(math.sqrt(mean_squared_error(y_reg_test, y_reg_pred)), 4)),
                "r2_score": float(round(r2_score(y_reg_test, y_reg_pred), 4)) if len(y_reg_test) > 1 else None,
            }

    metrics = {
        "train_rows": int(len(X_train)),
        "test_rows": int(len(X_test)),
        "accuracy": float(round(accuracy_score(y_test, y_pred), 4)),
        "roc_auc": float(round(roc_auc_score(y_test, y_prob), 4)) if len(set(y_test)) > 1 else None,
        "precision": float(round(precision_score(y_test, y_pred, zero_division=0), 4)),
        "recall": float(round(recall_score(y_test, y_pred, zero_division=0), 4)),
        "f1_score": float(round(f1_score(y_test, y_pred, zero_division=0), 4)),
        "brier_score": float(round(brier_score_loss(y_test, y_prob), 4)) if len(set(y_test)) > 1 else None,
        "cross_val_roc_auc_mean": float(round(sum(cv_scores) / len(cv_scores), 4)) if len(cv_scores) else None,
        "cross_val_roc_auc_scores": [float(round(score, 4)) for score in cv_scores] if len(cv_scores) else [],
        "classification_report": classification_report(y_test, y_pred, output_dict=True, zero_division=0),
        "regression_metrics": regression_metrics,
    }

    model_bundle = {
        "model": model,
        "regression_models": regression_models,
        "feature_columns": feature_columns,
        "dataset_kind": metadata["dataset_kind"],
        "target_column": metadata["target_column"],
        "raw_feature_columns": metadata["raw_feature_columns"],
        "categorical_features": metadata["categorical_features"],
        "numeric_features": metadata["numeric_features"],
        "global_feature_importance": global_feature_importance,
        "reference_profile": reference_profile,
        "example_profiles": example_profiles,
        "regression_targets": regression_targets,
    }
    joblib.dump(model_bundle, MODEL_PATH)

    metadata_payload = {
        "dataset_path": str(data_path.name),
        "model_path": str(MODEL_PATH.name),
        "dataset_kind": metadata["dataset_kind"],
        "target_column": metadata["target_column"],
        "target_description": metadata["target_description"],
        "records_used": int(len(X)),
        "feature_columns": feature_columns,
        "global_feature_importance": global_feature_importance[:10],
        "reference_profile": reference_profile,
        "metrics": metrics,
        "trained_at": pd.Timestamp.utcnow().isoformat(),
        "notes": [
            "This is a JSON-first baseline designed for later API integration.",
            "Perfect or near-perfect demo metrics usually mean the dataset is still synthetic or too small.",
            "Deep learning is not recommended until the project has substantially more historical data.",
            "Use the prediction output drivers and ai_context fields when prompting a language model for recommendations.",
            "Regression outputs are intended for forecasted incident counts and estimated damage totals.",
        ],
    }
    METADATA_PATH.write_text(json.dumps(metadata_payload, indent=2), encoding="utf-8")

    print("6. Model saved to", MODEL_PATH.name)
    print("7. Metadata saved to", METADATA_PATH.name)
    print(json.dumps(metrics, indent=2))


if __name__ == "__main__":
    requested_path = Path(sys.argv[1]) if len(sys.argv) > 1 else DEFAULT_DATA_PATH
    train(requested_path)
