import json
import sys
from pathlib import Path

from predict_event import predict


BASE_DIR = Path(__file__).resolve().parent


def load_payload(source: str) -> dict:
    input_path = Path(source)

    if input_path.exists():
        return json.loads(input_path.read_text(encoding="utf-8"))

    return json.loads(source)


def build_prompt_payload(model_input: dict, model_output: dict) -> dict:
    ai_context = model_output.get("ai_context", {})
    drivers = model_output.get("drivers", [])

    return {
        "instruction": (
            "Use this grounded prediction output to write human-readable fire-risk analysis, "
            "prioritized recommendations, and action plans. Do not invent data that contradicts the model context."
        ),
        "model_input": model_input,
        "model_prediction": {
            "target": model_output.get("target"),
            "prediction": model_output.get("prediction"),
            "probability": model_output.get("probability"),
            "probability_percent": model_output.get("probability_percent"),
            "risk_level": model_output.get("risk_level"),
            "confidence_band": model_output.get("confidence_band"),
            "watch_items": model_output.get("watch_items", []),
        },
        "prediction_drivers": drivers,
        "global_signals": model_output.get("global_feature_importance", []),
        "recommended_sections": [
            "1. One-sentence risk assessment",
            "2. Why the model reached this risk level",
            "3. Three immediate actions",
            "4. Three preventive recommendations",
            "5. One short operational plan for the next period",
        ],
        "prompt_focus": ai_context.get("recommended_prompt_focus", []),
        "system_hint": ai_context.get("system_hint"),
    }


if __name__ == "__main__":
    raw_input = sys.argv[1] if len(sys.argv) > 1 else sys.stdin.read()
    payload = load_payload(raw_input)
    prediction = predict(payload)
    prompt_payload = build_prompt_payload(payload, prediction)
    print(json.dumps(prompt_payload, indent=2))
