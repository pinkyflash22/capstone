# Capstone AI Baseline

This folder now uses JSON instead of CSV so the dataset shape is closer to a future API payload.
The training baseline now supports both the `historical_data` table shape and the merged incident export built from your investigation tables.

## Files

- `fire_data.json`: older toy dataset kept for reference
- `historical_data_demo.json`: demo 5-year JSON version of the `historical_data` table
- `generate_historical_data_json.py`: regenerates the `historical_data` demo JSON
- `generate_station_barangay_monthly_json.py`: generates a demo station + barangay + month dataset
- `sample_station_barangay_request.json`: sample payload for barangay hotspot forecasting
- `sample_merged_request.json`: sample request that matches the merged incident export shape
- `build_ai_prompt_payload.py`: converts a prediction into an AI-ready recommendation payload
- `train_model.py`: trains the probability model and saves metadata
- `predict_event.py`: predicts from a JSON payload
- `analyze_patterns.py`: creates JSON summaries and optional charts
- `fire_model.pkl`: saved model bundle
- `fire_model_metadata.json`: saved metrics and feature metadata after training
- `pattern_summary.json`: generated pattern summary after running analysis

## historical_data dataset shape

Each record should include:

```json
{
  "station_id": 1,
  "month": "April",
  "year": 2025,
  "incident_count": 19,
  "risk_level": "high",
  "average_temperature": 35.4,
  "season": "dry",
  "near_water": 1,
  "density": "high",
  "materials": "mixed",
  "peak_hours": "evening",
  "accesibility": "moderate access",
  "type_incident": "electrical fire",
  "occupancy": "commercial",
  "created_at": "2026-05-07T00:00:00+00:00",
  "updated_at": "2026-05-07T00:00:00+00:00"
}
```

The historical-data model currently derives a binary target called `high_risk_event` from:
- `risk_level == "high"`
- or `incident_count >= 18`

## Merged incident dataset shape

The Laravel app can now export an ML-ready JSON dataset from:

- `incidents`
- `spot_investigation_report`
- `progress_investigation_report`
- `final_investigation_report`
- `historical_data`

Use the export command:

```powershell
php artisan ml:export-incident-dataset
```

Default output:

```text
capstone-ai/merged_incident_dataset.json
```

This export joins the monthly `historical_data` context onto each investigated incident using:

- `station_id`
- incident month
- incident year

The merged dataset also supports a nullable `weather_context` JSON column on `spot_investigation_report`.
Suggested shape:

```json
{
  "condition": "hot and dry",
  "temperature_c": 35.1,
  "humidity": 62,
  "wind_kph": 17,
  "rainfall_mm": 0
}
```

The merged-incident model currently derives a binary target called `high_impact_incident` from:
- casualties present
- or `final_total_damage >= 150000`
- or a meaningful `responsible_arson` value

## Station + barangay monthly dataset shape

For barangay-level prediction without removing station context, use one row per:

- `station_id`
- `barangay_id`
- `month`
- `year`

Example:

```json
{
  "station_id": 1,
  "barangay_id": 103,
  "barangay_name": "Barangay Mabini",
  "month": "April",
  "year": 2026,
  "incident_count": 8,
  "risk_level": "high",
  "average_temperature": 35.4,
  "season": "dry",
  "near_water": 0,
  "density": "high",
  "materials": "mixed",
  "peak_hours": "evening",
  "accesibility": "moderate access",
  "type_incident": "electrical fire",
  "occupancy": "commercial"
}
```

This is the recommended shape for:

- barangay hotspot forecasting
- station-to-barangay risk ranking
- future city/province rollups without removing `station_id`

The model currently derives a target called `high_risk_barangay_month` from:
- `risk_level == "high"`
- or `incident_count >= 8`

## Train

```powershell
python capstone-ai/train_model.py
```

To train from the merged incident export instead of the demo monthly file:

```powershell
python capstone-ai/train_model.py capstone-ai/merged_incident_dataset.json
```

To train for barangay-level monthly forecasting:

```powershell
python capstone-ai/generate_station_barangay_monthly_json.py
python capstone-ai/train_model.py capstone-ai/station_barangay_monthly_demo.json
```

Or use Laravel Artisan:

```powershell
php artisan ai:train-barangay-model
```

If you already have a custom dataset JSON:

```powershell
php artisan ai:train-barangay-model --dataset=capstone-ai/your_dataset.json
```

## Predict from JSON

```powershell
python capstone-ai/predict_event.py "{\"station_id\":1,\"month\":\"April\",\"year\":2026,\"average_temperature\":35.4,\"season\":\"dry\",\"near_water\":1,\"density\":\"high\",\"materials\":\"mixed\",\"peak_hours\":\"evening\",\"accesibility\":\"moderate access\",\"type_incident\":\"electrical fire\",\"occupancy\":\"commercial\"}"
```

You can also pass a JSON file, which is usually easier in PowerShell:

```powershell
python capstone-ai/predict_event.py capstone-ai/sample_request.json
```

For the merged incident model, use the sample merged payload:

```powershell
python capstone-ai/predict_event.py capstone-ai/sample_merged_request.json
```

For the barangay monthly model:

```powershell
python capstone-ai/predict_event.py capstone-ai/sample_station_barangay_request.json
```

Or use Laravel Artisan:

```powershell
php artisan ai:predict-barangay-risk
```

To predict from another JSON file:

```powershell
php artisan ai:predict-barangay-risk --file=capstone-ai/your_request.json
```

To pass inline JSON directly:

```powershell
php artisan ai:predict-barangay-risk "{\"station_id\":1,\"barangay_id\":103,\"barangay_name\":\"Barangay Mabini\",\"month\":\"April\",\"year\":2026,\"average_temperature\":35.4,\"season\":\"dry\",\"near_water\":0,\"density\":\"high\",\"materials\":\"mixed\",\"peak_hours\":\"evening\",\"accesibility\":\"moderate access\",\"type_incident\":\"electrical fire\",\"occupancy\":\"commercial\"}"
```

The prediction response now includes:

- `probability` and `probability_percent`
- `confidence_band`
- `drivers` for the strongest local reasons behind the result
- `global_feature_importance`
- `watch_items`
- `ai_context` for downstream recommendation prompts

## Build AI-ready prompt payload

This is the easiest bridge when you want your language model to generate grounded suggestions, recommendations, and plans from the prediction result:

```powershell
python capstone-ai/build_ai_prompt_payload.py capstone-ai/sample_request.json
```

Or for the merged incident model:

```powershell
python capstone-ai/build_ai_prompt_payload.py capstone-ai/sample_merged_request.json
```

From Artisan, you can get the AI-ready version with:

```powershell
php artisan ai:predict-barangay-risk --prompt-payload
```

The output bundles:

- the original model input
- the structured prediction
- the top local drivers
- the strongest global model signals
- guidance for what the AI should focus on

## Analyze patterns

```powershell
python capstone-ai/analyze_patterns.py
```

If `matplotlib` is installed, the script also saves charts into `capstone-ai/charts/`.

## Generate historical_data JSON

```powershell
python capstone-ai/generate_historical_data_json.py
```

This creates a demo `historical_data` JSON file for 2021-2025 using the same logic as `HistoricalDataSeeder`.

## Generate station + barangay monthly JSON

```powershell
python capstone-ai/generate_station_barangay_monthly_json.py
```

This creates a demo monthly forecasting dataset that keeps `station_id` and adds `barangay_id` explicitly, which is the safer approach because the current Laravel schema does not store a direct `barangay -> station_id` foreign key.

## Notes

- This is a better baseline than the old 3-feature CSV demo.
- It is still not a deep learning system.
- Deep learning only makes sense after you collect much more historical data.
- The current `historical_data` baseline predicts the probability of a high-risk month/event, not an exact future incident count.
- The merged incident baseline is best treated as a temporary severity-pattern model until you receive the official BFP historical dataset.
- The current recommendation quality will improve when your AI prompt uses the model `drivers`, `watch_items`, and `ai_context` instead of only raw counts.
- For panelist-facing barangay forecasting, the most honest framing is `station + barangay monthly risk forecast`, not exact incident-by-incident prediction.
