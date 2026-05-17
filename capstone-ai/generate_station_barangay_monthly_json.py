import json
import random
from datetime import datetime, timezone
from pathlib import Path


BASE_DIR = Path(__file__).resolve().parent
OUTPUT_PATH = BASE_DIR / "station_barangay_monthly_demo.json"

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

STATION_BARANGAY_MAP = [
    {
        "station_id": 1,
        "station_name": "Sample Station 1",
        "station_code": "ST-001",
        "barangays": [
            {"barangay_id": 101, "barangay_name": "Barangay Rizal", "density_bias": "high", "near_water": 0},
            {"barangay_id": 102, "barangay_name": "Barangay Bonifacio", "density_bias": "medium", "near_water": 1},
            {"barangay_id": 103, "barangay_name": "Barangay Mabini", "density_bias": "high", "near_water": 0},
            {"barangay_id": 104, "barangay_name": "Barangay San Vicente", "density_bias": "low", "near_water": 1},
        ],
    },
    {
        "station_id": 2,
        "station_name": "Central Station",
        "station_code": "ST-002",
        "barangays": [
            {"barangay_id": 201, "barangay_name": "Barangay Poblacion", "density_bias": "high", "near_water": 0},
            {"barangay_id": 202, "barangay_name": "Barangay Talang", "density_bias": "medium", "near_water": 0},
            {"barangay_id": 203, "barangay_name": "Barangay Mangin", "density_bias": "medium", "near_water": 1},
            {"barangay_id": 204, "barangay_name": "Barangay Calmay", "density_bias": "low", "near_water": 1},
        ],
    },
    {
        "station_id": 3,
        "station_name": "North Station",
        "station_code": "ST-003",
        "barangays": [
            {"barangay_id": 301, "barangay_name": "Barangay Malabago", "density_bias": "medium", "near_water": 1},
            {"barangay_id": 302, "barangay_name": "Barangay Nancalobasaan", "density_bias": "low", "near_water": 0},
            {"barangay_id": 303, "barangay_name": "Barangay Anonas", "density_bias": "high", "near_water": 0},
            {"barangay_id": 304, "barangay_name": "Barangay Bued", "density_bias": "medium", "near_water": 1},
        ],
    },
]

DENSITY_OPTIONS = ["low", "medium", "high"]
MATERIALS_OPTIONS = ["concrete", "mixed", "light materials"]
PEAK_HOURS_OPTIONS = ["afternoon", "evening", "night", "midday"]
ACCESS_OPTIONS = ["easy access", "moderate access", "difficult access"]
INCIDENT_TYPES = ["electrical fire", "cooking fire", "open flame", "short circuit", "gas leak"]
OCCUPANCY_OPTIONS = ["residential", "commercial", "industrial", "institutional", "mixed-use"]


def resolve_density(rng: random.Random, density_bias: str) -> str:
    if density_bias == "high":
        return rng.choices(DENSITY_OPTIONS, weights=[1, 2, 5], k=1)[0]
    if density_bias == "low":
        return rng.choices(DENSITY_OPTIONS, weights=[5, 2, 1], k=1)[0]
    return rng.choices(DENSITY_OPTIONS, weights=[2, 5, 2], k=1)[0]


def build_record(station: dict, barangay: dict, year: int, month: str, rng: random.Random, created_at: str) -> dict:
    season = "dry" if month in ["December", "January", "February", "March", "April", "May"] else "wet"
    density = resolve_density(rng, barangay["density_bias"])

    if season == "dry":
        average_temperature = round(rng.uniform(30, 37), 2)
        base_incidents = rng.randint(3, 8)
    else:
        average_temperature = round(rng.uniform(25, 31), 2)
        base_incidents = rng.randint(0, 4)

    density_bonus = {"low": 0, "medium": 1, "high": 3}[density]
    water_bonus = -1 if barangay["near_water"] else 1
    incident_count = max(0, base_incidents + density_bonus + water_bonus + rng.randint(0, 3))

    if incident_count >= 9 or (density == "high" and season == "dry"):
        risk_level = "high"
    elif incident_count >= 4:
        risk_level = "medium"
    else:
        risk_level = "low"

    return {
        "station_id": station["station_id"],
        "barangay_id": barangay["barangay_id"],
        "barangay_name": barangay["barangay_name"],
        "month": month,
        "year": year,
        "incident_count": incident_count,
        "risk_level": risk_level,
        "average_temperature": average_temperature,
        "season": season,
        "near_water": barangay["near_water"],
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
    rng = random.Random(84)
    created_at = datetime.now(timezone.utc).isoformat()
    records = []

    for station in STATION_BARANGAY_MAP:
        for barangay in station["barangays"]:
            for year in YEARS:
                for month in MONTHS:
                    records.append(build_record(station, barangay, year, month, rng, created_at))

    return {
        "table": "station_barangay_monthly_forecast",
        "source": "demo generator aligned to historical_data + barangay hotspot forecasting",
        "schema_columns": [
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
            "created_at",
            "updated_at",
        ],
        "notes": [
            "This demo shape keeps station_id for future province-level integration while adding barangay_id for hotspot forecasting.",
            "There is no direct barangay->station foreign key in the current schema, so this dataset uses an explicit mapping for ML readiness.",
            "Use one row per station + barangay + month + year for monthly barangay risk forecasting.",
        ],
        "year_range": {"start": YEARS[0], "end": YEARS[-1]},
        "station_barangay_reference": STATION_BARANGAY_MAP,
        "records": records,
    }


if __name__ == "__main__":
    payload = generate()
    OUTPUT_PATH.write_text(json.dumps(payload, indent=2), encoding="utf-8")
    print(f"Generated {len(payload['records'])} records into {OUTPUT_PATH.name}")
