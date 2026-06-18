import json
from datetime import datetime, timezone
from pathlib import Path


BASE_DIR = Path(__file__).resolve().parent
OUTPUT_PATH = BASE_DIR / "historical_data_demo.json"

MONTHS = [
    "January",
    "February",
    "March",
    "April",
    "May",
    "June",
    "July",
    "August",
    "September",
    "October",
    "November",
    "December",
]

TEMPERATURE_BY_MONTH = {
    "January": 28.0,
    "February": 29.0,
    "March": 31.0,
    "April": 32.0,
    "May": 33.0,
    "June": 31.0,
    "July": 30.0,
    "August": 30.0,
    "September": 30.0,
    "October": 29.0,
    "November": 29.0,
    "December": 28.0,
}

STATION_REFERENCE = {
    "station_id": 1,
    "station_name": "Urbiztondo Fire Station",
    "station_code": "ST-001",
    "coverage": "Urbiztondo, Pangasinan",
}

MONTHLY_OVERRIDES = {
    "2023-January": {
        "incident_count": 1,
        "risk_level": "medium",
        "average_temperature": 30.0,
        "near_water": 1,
        "density": "high",
        "materials": "light materials",
        "peak_hours": "17:38",
        "accesibility": "easy access",
        "type_incident": "open flame from rubbish fire",
        "occupancy": "residential",
    },
    "2023-March": {
        "incident_count": 1,
        "risk_level": "medium",
        "average_temperature": 35.0,
        "near_water": 0,
        "density": "low",
        "materials": "substantial house",
        "peak_hours": "12:30",
        "accesibility": "difficult access",
        "type_incident": "open flame from rubbish fire",
        "occupancy": "residential",
    },
    "2023-July": {
        "incident_count": 1,
        "risk_level": "medium",
        "average_temperature": 32.0,
        "near_water": 0,
        "density": "low",
        "materials": "semi concrete residential house",
        "peak_hours": "15:30",
        "accesibility": "difficult access",
        "type_incident": "electrical ignition due to arcing",
        "occupancy": "residential",
    },
    "2023-August": {
        "incident_count": 1,
        "risk_level": "medium",
        "average_temperature": 28.0,
        "near_water": 0,
        "density": "low",
        "materials": "two storey residential house",
        "peak_hours": "07:40",
        "accesibility": "difficult access",
        "type_incident": "open flame from cooking (lpg/gas stove)",
        "occupancy": "residential",
    },
    "2024-January": {
        "incident_count": 1,
        "risk_level": "medium",
        "average_temperature": 28.0,
        "near_water": 0,
        "density": "low",
        "materials": "shanty house",
        "peak_hours": "19:24",
        "accesibility": "difficult access",
        "type_incident": "open flame from kerosene lamp",
        "occupancy": "residential",
    },
    "2025-January": {
        "incident_count": 1,
        "risk_level": "medium",
        "average_temperature": 28.0,
        "near_water": 1,
        "density": "medium",
        "materials": "motorcycle",
        "peak_hours": "none recorded",
        "accesibility": "easy access",
        "type_incident": "vehicular fire",
        "occupancy": "mixed-use",
    },
    "2025-February": {
        "incident_count": 1,
        "risk_level": "medium",
        "average_temperature": 29.0,
        "near_water": 1,
        "density": "medium",
        "materials": "school storage",
        "peak_hours": "none recorded",
        "accesibility": "easy access",
        "type_incident": "storage fire",
        "occupancy": "institutional",
    },
    "2025-May": {
        "incident_count": 1,
        "risk_level": "high",
        "average_temperature": 33.0,
        "near_water": 0,
        "density": "low",
        "materials": "farm storage",
        "peak_hours": "11:41",
        "accesibility": "moderate access",
        "type_incident": "electrical ignition overloading",
        "occupancy": "industrial",
    },
    "2025-December": {
        "incident_count": 1,
        "risk_level": "medium",
        "average_temperature": 28.0,
        "near_water": 0,
        "density": "low",
        "materials": "residential",
        "peak_hours": "none recorded",
        "accesibility": "difficult access",
        "type_incident": "residential fire",
        "occupancy": "residential",
    },
    "2026-January": {
        "incident_count": 1,
        "risk_level": "medium",
        "average_temperature": 28.0,
        "near_water": 0,
        "density": "low",
        "materials": "single and two family residential",
        "peak_hours": "05:38",
        "accesibility": "difficult access",
        "type_incident": "thrown cigarette butt",
        "occupancy": "residential",
    },
    "2026-February": {
        "incident_count": 1,
        "risk_level": "low",
        "average_temperature": 29.0,
        "near_water": 0,
        "density": "low",
        "materials": "storage",
        "peak_hours": "09:05",
        "accesibility": "moderate access",
        "type_incident": "unattended smoldering coconut leaves",
        "occupancy": "commercial",
    },
    "2026-March": {
        "incident_count": 1,
        "risk_level": "medium",
        "average_temperature": 31.0,
        "near_water": 0,
        "density": "medium",
        "materials": "single and two family residential",
        "peak_hours": "14:28",
        "accesibility": "moderate access",
        "type_incident": "electrical ignition due to pinch wire",
        "occupancy": "residential",
    },
}


def build_monthly_records() -> list[dict]:
    created_at = datetime.now(timezone.utc).isoformat()
    records = []

    for year in range(2023, 2027):
        for month in MONTHS:
            season = "dry" if month in ["December", "January", "February", "March", "April", "May"] else "wet"
            key = f"{year}-{month}"
            row = {
                "station_id": STATION_REFERENCE["station_id"],
                "month": month,
                "year": year,
                "incident_count": 0,
                "risk_level": "low",
                "average_temperature": TEMPERATURE_BY_MONTH[month],
                "season": season,
                "near_water": 0,
                "density": "medium",
                "materials": "mixed",
                "peak_hours": "none recorded",
                "accesibility": "moderate access",
                "type_incident": "no recorded incident",
                "occupancy": "mixed-use",
                "created_at": created_at,
                "updated_at": created_at,
            }

            if key in MONTHLY_OVERRIDES:
                row.update(MONTHLY_OVERRIDES[key])

            records.append(row)

    return records


def generate() -> dict:
    records = build_monthly_records()

    return {
        "table": "historical_data",
        "source": "user-provided Urbiztondo 2023-2026 records aligned with database/seeders/HistoricalDataSeeder.php",
        "schema_columns": [
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
            "created_at",
            "updated_at",
        ],
        "notes": [
            "This JSON mirrors the Urbiztondo monthly historical_data seed.",
            "Zero-incident months are intentionally included for prediction training.",
            "Each row represents one station-month summary, not an individual incident.",
        ],
        "year_range": {"start": 2023, "end": 2026},
        "station_reference": [STATION_REFERENCE],
        "records": records,
    }


if __name__ == "__main__":
    payload = generate()
    OUTPUT_PATH.write_text(json.dumps(payload, indent=2), encoding="utf-8")
    print(f"Generated {len(payload['records'])} records into {OUTPUT_PATH.name}")
