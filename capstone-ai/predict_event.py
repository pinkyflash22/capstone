import json
import math
import sys
from pathlib import Path

import joblib
import pandas as pd


BASE_DIR = Path(__file__).resolve().parent
MODEL_PATH = BASE_DIR / "fire_model.pkl"
MONTH_ORDER = {
    "January": 1, "February": 2, "March": 3, "April": 4,
    "May": 5, "June": 6, "July": 7, "August": 8,
    "September": 9, "October": 10, "November": 11, "December": 12,
}


RISK_LABEL_THRESHOLDS = {
    "legacy_fire_data": (0.7, 0.4),
    "historical_data": (0.65, 0.35),
    "station_barangay_monthly": (0.65, 0.35),
    "merged_incident_dataset": (0.65, 0.35),
}


def prepare_historical_request_frame(payload: dict, feature_columns: list[str]) -> pd.DataFrame:
    df = pd.DataFrame([payload]).copy()

    for column in ["station_id", "year", "average_temperature", "near_water"]:
        df[column] = pd.to_numeric(df.get(column), errors="coerce")

    month_name = str(df.at[0, "month"])
    month_number = MONTH_ORDER.get(month_name)
    if month_number is None:
        raise ValueError("month must be a full month name like 'January' or 'April'.")

    season = str(df.at[0, "season"]).lower()
    density = str(df.at[0, "density"]).lower()

    df["month_sin"] = math.sin(2 * math.pi * month_number / 12.0)
    df["month_cos"] = math.cos(2 * math.pi * month_number / 12.0)
    df["water_access_flag"] = df["near_water"].astype(int)
    df["is_dry_season"] = 1 if season == "dry" else 0
    df["high_density_flag"] = 1 if density == "high" else 0

    return df[feature_columns]


def prepare_station_barangay_request_frame(payload: dict, feature_columns: list[str]) -> pd.DataFrame:
    df = pd.DataFrame([payload]).copy()

    for column in [
        "station_id",
        "barangay_id",
        "year",
        "incident_count",
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
        "ai_checklist_total",
        "ai_checklist_confirmed_count",
        "ai_checklist_active_count",
        "ai_checklist_completion_ratio",
        "ai_checklist_item_total",
        "ai_checklist_item_followed_ratio",
        "linked_mitigation_total",
        "linked_mitigation_completed_count",
        "linked_mitigation_blocked_count",
        "linked_mitigation_open_count",
        "mitigation_completion_ratio",
        "avg_mitigation_completion_days",
        "mitigation_readiness_signal",
    ]:
        df[column] = pd.to_numeric(df.get(column), errors="coerce")

    month_name = str(df.at[0, "month"])
    month_number = MONTH_ORDER.get(month_name)
    if month_number is None:
        raise ValueError("month must be a full month name like 'January' or 'April'.")

    season = str(df.at[0, "season"]).lower()
    density = str(df.at[0, "density"]).lower()
    weather_condition = str(df.at[0, "weather_condition"] if "weather_condition" in df.columns else "unknown").strip().lower() or "unknown"

    df["month_sin"] = math.sin(2 * math.pi * month_number / 12.0)
    df["month_cos"] = math.cos(2 * math.pi * month_number / 12.0)
    df["water_access_flag"] = df["near_water"].astype(int)
    df["is_dry_season"] = 1 if season == "dry" else 0
    df["high_density_flag"] = 1 if density == "high" else 0
    df["temperature_signal"] = df["average_temperature"].fillna(0)
    df["humidity_signal"] = df["average_humidity"].fillna(0)
    df["wind_signal"] = df["average_wind_speed"].fillna(0)
    for column in [
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
        "ai_checklist_total",
        "ai_checklist_confirmed_count",
        "ai_checklist_active_count",
        "ai_checklist_completion_ratio",
        "ai_checklist_item_total",
        "ai_checklist_item_followed_ratio",
        "linked_mitigation_total",
        "linked_mitigation_completed_count",
        "linked_mitigation_blocked_count",
        "linked_mitigation_open_count",
        "mitigation_completion_ratio",
        "avg_mitigation_completion_days",
        "mitigation_readiness_signal",
    ]:
        df[column] = df[column].fillna(0)
    df["barangay_pressure_signal"] = (
        df["incident_count"].fillna(0) * 1.2
        + df["average_temperature"] * 0.35
        + df["high_density_flag"] * 2.5
        - df["water_access_flag"] * 0.75
        + df["average_wind_speed"].fillna(0) * 0.15
        - df["average_humidity"].fillna(0) * 0.03
        + df["risk_area_pressure_signal"] * 0.9
        + df["high_assessment_count"] * 1.1
        + df["blocked_hydrant_count"] * 0.8
        - df["mitigation_readiness_signal"] * 0.6
        - df["mitigation_completion_ratio"] * 1.5
        - df["ai_checklist_item_followed_ratio"] * 1.0
    )
    df["weather_condition"] = weather_condition

    return df[feature_columns]


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


def prepare_merged_request_frame(payload: dict, feature_columns: list[str]) -> pd.DataFrame:
    df = pd.DataFrame([payload]).copy()

    weather_context = normalize_weather_context(payload.get("weather_context"))
    df["weather_condition"] = str(weather_context.get("condition") or weather_context.get("summary") or "unknown").lower()
    df["weather_temp_c"] = pd.to_numeric(weather_context.get("temperature_c", weather_context.get("temp_c")), errors="coerce")
    df["weather_humidity"] = pd.to_numeric(weather_context.get("humidity"), errors="coerce")
    df["weather_wind_kph"] = pd.to_numeric(weather_context.get("wind_kph"), errors="coerce")
    df["weather_rainfall_mm"] = pd.to_numeric(weather_context.get("rainfall_mm"), errors="coerce")

    for column in [
        "station_id",
        "incident_year",
        "marker_lat",
        "marker_lng",
        "spot_estimated_damage",
        "estimated_area",
        "historical_incident_count",
        "historical_average_temperature",
        "historical_near_water",
    ]:
        df[column] = pd.to_numeric(df.get(column), errors="coerce")

    month_name = str(df.at[0, "incident_month"])
    month_number = MONTH_ORDER.get(month_name)
    if month_number is None:
        raise ValueError("incident_month must be a full month name like 'January' or 'April'.")

    df["month_sin"] = math.sin(2 * math.pi * month_number / 12.0)
    df["month_cos"] = math.cos(2 * math.pi * month_number / 12.0)
    df["historical_high_risk_flag"] = 1 if str(df.at[0, "historical_risk_level"]).lower() == "high" else 0
    df["historical_water_access_flag"] = df["historical_near_water"].fillna(0).astype(int)
    df["damage_pressure_signal"] = (
        df["spot_estimated_damage"].fillna(0) * 0.55
        + df["historical_incident_count"].fillna(0) * 1200
        + df["weather_wind_kph"].fillna(0) * 450
    )

    for column in [
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
    ]:
        df[column] = str(df.at[0, column] if column in df.columns else "unknown").strip() or "unknown"

    return df[feature_columns]


def prepare_legacy_request_frame(payload: dict, feature_columns: list[str]) -> pd.DataFrame:
    df = pd.DataFrame([payload]).copy()

    for column in ["avg_temp", "density", "month", "humidity", "wind_kph", "rainfall_mm", "past_7_day_incidents", "past_30_day_incidents", "barangay_risk_score"]:
        df[column] = pd.to_numeric(df.get(column), errors="coerce")

    month = df.at[0, "month"]
    df["month_sin"] = math.sin(2 * math.pi * month / 12.0)
    df["month_cos"] = math.cos(2 * math.pi * month / 12.0)
    df["heat_index_proxy"] = (df["avg_temp"] * 0.65) + (df["humidity"] * 0.08) + (df["density"] * 0.4)
    df["dryness_signal"] = (df["avg_temp"] * 1.4) - (df["rainfall_mm"] * 0.9) - (df["humidity"] * 0.35)
    df["recent_activity_signal"] = df["past_7_day_incidents"] * 1.2 + df["past_30_day_incidents"] * 0.4

    return df[feature_columns]


def get_risk_level(probability: float, dataset_kind: str) -> str:
    high_threshold, moderate_threshold = RISK_LABEL_THRESHOLDS.get(dataset_kind, (0.65, 0.35))
    if probability >= high_threshold:
        return "high"
    if probability >= moderate_threshold:
        return "moderate"
    return "low"


def get_confidence_band(probability: float) -> str:
    distance_from_midpoint = abs(probability - 0.5)
    if distance_from_midpoint >= 0.3:
        return "high"
    if distance_from_midpoint >= 0.15:
        return "medium"
    return "low"


def build_local_drivers(model, request_frame: pd.DataFrame, reference_profile: dict, feature_columns: list[str]) -> list[dict]:
    base_probability = float(model.predict_proba(request_frame)[0][1])
    drivers = []

    for feature in feature_columns:
        if feature not in request_frame.columns or feature not in reference_profile:
            continue

        comparison_frame = request_frame.copy()
        try:
            comparison_frame.at[comparison_frame.index[0], feature] = reference_profile[feature]
        except (TypeError, ValueError):
            comparison_frame[feature] = comparison_frame[feature].astype(object)
            comparison_frame.at[comparison_frame.index[0], feature] = reference_profile[feature]
        comparison_probability = float(model.predict_proba(comparison_frame)[0][1])
        contribution = base_probability - comparison_probability
        current_value = request_frame.at[request_frame.index[0], feature]
        reference_value = reference_profile[feature]

        if abs(contribution) < 0.015:
            continue

        direction = "increases" if contribution > 0 else "reduces"
        drivers.append({
            "feature": feature,
            "current_value": current_value.item() if hasattr(current_value, "item") else current_value,
            "reference_value": reference_value,
            "impact_score": round(abs(contribution), 4),
            "direction": direction,
            "summary": f"{feature}={current_value} {direction} predicted risk compared with the model baseline {reference_value}.",
        })

    return sorted(drivers, key=lambda item: item["impact_score"], reverse=True)


def build_watch_items(drivers: list[dict]) -> list[str]:
    return [
        driver["feature"].replace("_", " ")
        for driver in drivers[:4]
    ]


def build_ai_context(dataset_kind: str, target: str, probability: float, risk_level: str, confidence_band: str, drivers: list[dict], global_feature_importance: list[dict]) -> dict:
    top_driver_summaries = [driver["summary"] for driver in drivers[:3]]
    global_signals = [item["feature"] for item in global_feature_importance[:5]]

    return {
        "dataset_kind": dataset_kind,
        "target": target,
        "probability_percent": round(probability * 100, 2),
        "risk_level": risk_level,
        "confidence_band": confidence_band,
        "top_local_drivers": top_driver_summaries,
        "top_global_signals": global_signals,
        "recommended_prompt_focus": [
            "Explain why the predicted risk level is justified.",
            "Turn the top local drivers into concrete prevention actions.",
            "Prioritize staffing, inspections, awareness, and equipment actions based on the current risk profile.",
        ],
        "system_hint": "Use the model drivers as evidence, but keep recommendations operational and human-readable.",
    }


def apply_reference_defaults(request_frame: pd.DataFrame, feature_columns: list[str], reference_profile: dict) -> pd.DataFrame:
    normalized = request_frame.copy()

    for column in feature_columns:
        if column not in normalized.columns:
            normalized[column] = reference_profile.get(column, 0)

        fallback = reference_profile.get(column, 0)
        normalized[column] = normalized[column].where(~normalized[column].isna(), fallback)

        if normalized[column].dtype == object:
            normalized[column] = normalized[column].replace("", fallback)

    return normalized[feature_columns]


def format_prediction_response(model, request_frame: pd.DataFrame, dataset_kind: str, target: str, reference_profile: dict, global_feature_importance: list[dict], feature_columns: list[str]) -> dict:
    probability = float(model.predict_proba(request_frame)[0][1])
    prediction = int(model.predict(request_frame)[0])
    risk_level = get_risk_level(probability, dataset_kind)
    confidence_band = get_confidence_band(probability)
    local_drivers = build_local_drivers(model, request_frame, reference_profile, feature_columns)
    ai_context = build_ai_context(dataset_kind, target, probability, risk_level, confidence_band, local_drivers, global_feature_importance)

    return {
        "prediction": prediction,
        "probability": round(probability, 4),
        "probability_percent": round(probability * 100, 2),
        "risk_level": risk_level,
        "confidence_band": confidence_band,
        "target": target,
        "drivers": local_drivers[:5],
        "global_feature_importance": global_feature_importance[:8],
        "watch_items": build_watch_items(local_drivers),
        "ai_context": ai_context,
    }


def append_regression_outputs(bundle: dict, request_frame: pd.DataFrame, response: dict) -> dict:
    regression_models = bundle.get("regression_models", {}) or {}

    for output_name, model in regression_models.items():
        try:
            prediction_value = float(model.predict(request_frame)[0])
        except Exception:
            continue

        if output_name == "predicted_incident_count":
            response[output_name] = max(0, int(round(prediction_value)))
        elif output_name == "predicted_estimated_damage":
            response[output_name] = round(max(0.0, prediction_value), 2)
        else:
            response[output_name] = round(prediction_value, 4)

    return response


def predict(payload: dict) -> dict:
    bundle = joblib.load(MODEL_PATH)
    model = bundle["model"]
    feature_columns = bundle["feature_columns"]
    dataset_kind = bundle.get("dataset_kind", "legacy_fire_data")
    reference_profile = bundle.get("reference_profile", {})
    global_feature_importance = bundle.get("global_feature_importance", [])

    if dataset_kind == "historical_data":
        request_frame = apply_reference_defaults(
            prepare_historical_request_frame(payload, feature_columns),
            feature_columns,
            reference_profile,
        )
        response = format_prediction_response(
            model,
            request_frame,
            dataset_kind,
            "high_risk_event",
            reference_profile,
            global_feature_importance,
            feature_columns,
        )
        return append_regression_outputs(bundle, request_frame, response)

    if dataset_kind == "station_barangay_monthly":
        request_frame = apply_reference_defaults(
            prepare_station_barangay_request_frame(payload, feature_columns),
            feature_columns,
            reference_profile,
        )
        response = format_prediction_response(
            model,
            request_frame,
            dataset_kind,
            "high_risk_barangay_month",
            reference_profile,
            global_feature_importance,
            feature_columns,
        )
        return append_regression_outputs(bundle, request_frame, response)

    if dataset_kind == "merged_incident_dataset":
        request_frame = apply_reference_defaults(
            prepare_merged_request_frame(payload, feature_columns),
            feature_columns,
            reference_profile,
        )
        response = format_prediction_response(
            model,
            request_frame,
            dataset_kind,
            "high_impact_incident",
            reference_profile,
            global_feature_importance,
            feature_columns,
        )
        return append_regression_outputs(bundle, request_frame, response)

    request_frame = apply_reference_defaults(
        prepare_legacy_request_frame(payload, feature_columns),
        feature_columns,
        reference_profile,
    )
    response = format_prediction_response(
        model,
        request_frame,
        dataset_kind,
        "fire_occurred",
        reference_profile,
        global_feature_importance,
        feature_columns,
    )
    return append_regression_outputs(bundle, request_frame, response)


if __name__ == "__main__":
    raw_input = sys.argv[1] if len(sys.argv) > 1 else sys.stdin.read()
    input_path = Path(raw_input)

    if input_path.exists():
        payload = json.loads(input_path.read_text(encoding="utf-8"))
    else:
        payload = json.loads(raw_input)

    print(json.dumps(predict(payload), indent=2))
