import json
import random
from datetime import datetime, timezone
from pathlib import Path


BASE_DIR = Path(__file__).resolve().parent
OUTPUT_PATH = BASE_DIR / "historical_data_demo.json"

YEARS = [2021, 2022, 2023, 2024, 2025]
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

# Demo station references so the JSON is API-friendly and still understandable.
STATIONS = [
    {"station_id": 1, "station_name": "Sample Station 1", "station_code": "ST-001", "coverage": "San Carlos core"},
    {"station_id": 2, "station_name": "Central Station", "station_code": "ST-002", "coverage": "Dagupan urban belt"},
    {"station_id": 3, "station_name": "North Station", "station_code": "ST-003", "coverage": "Mangaldan and nearby barangays"},
]

DENSITY_OPTIONS = ["low", "medium", "high"]
MATERIALS_OPTIONS = ["concrete", "mixed", "light materials"]
PEAK_HOURS_OPTIONS = ["afternoon", "evening", "night", "midday"]
ACCESS_OPTIONS = ["easy access", "moderate access", "difficult access"]
INCIDENT_TYPES = ["electrical fire", "cooking fire", "open flame", "short circuit", "gas leak"]
OCCUPANCY_OPTIONS = ["residential", "commercial", "industrial", "institutional", "mixed-use"]


def build_record(station: dict, year: int, month: str, rng: random.Random, created_at: str) -> dict:
    season = "dry" if month in ["December", "January", "February", "March", "April", "May"] else "wet"

    if season == "dry":
        average_temperature = round(rng.uniform(30, 36.99), 2)
        incident_count = rng.randint(10, 25)
    else:
        average_temperature = round(rng.uniform(25, 30.99), 2)
        incident_count = rng.randint(3, 12)

    density = rng.choice(DENSITY_OPTIONS)

    if incident_count > 18 or density == "high":
        risk_level = "high"
    elif incident_count > 8:
        risk_level = "medium"
    else:
        risk_level = "low"

    return {
        "station_id": station["station_id"],
        "month": month,
        "year": year,
        "incident_count": incident_count,
        "risk_level": risk_level,
        "average_temperature": average_temperature,
        "season": season,
        "near_water": rng.choice([0, 1]),
        "density": density,
        "materials": rng.choice(MATERIALS_OPTIONS),
        "peak_hours": rng.choice(PEAK_HOURS_OPTIONS),
        "accesibility": rng.choice(ACCESS_OPTIONS),
        "type_incident": rng.choice(INCIDENT_TYPES),
        "occupancy": rng.choice(OCCUPANCY_OPTIONS),
        "created_at": created_at,
        "updated_at": created_at,
    }


def generate() -> dict:
    rng = random.Random(42)
    created_at = datetime.now(timezone.utc).isoformat()

    records = []
    for station in STATIONS:
        for year in YEARS:
            for month in MONTHS:
                records.append(build_record(station, year, month, rng, created_at))

    return {
        "table": "historical_data",
        "source": "database/seeders/HistoricalDataSeeder.php",
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
            "This is a demo-only JSON representation of the historical_data table.",
            "It follows the same seasonal randomization pattern used by HistoricalDataSeeder.",
            "station_id values are demo references and may need remapping before direct database import.",
        ],
        "year_range": {"start": YEARS[0], "end": YEARS[-1]},
        "station_reference": STATIONS,
        "records": records,
    }


if __name__ == "__main__":
    payload = generate()
    OUTPUT_PATH.write_text(json.dumps(payload, indent=2), encoding="utf-8")
    print(f"Generated {len(payload['records'])} records into {OUTPUT_PATH.name}")
