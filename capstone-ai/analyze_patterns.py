import json
import os
from pathlib import Path

import pandas as pd


BASE_DIR = Path(__file__).resolve().parent
DATA_PATH = BASE_DIR / "fire_data.json"
SUMMARY_PATH = BASE_DIR / "pattern_summary.json"
CHARTS_DIR = BASE_DIR / "charts"


def load_dataframe() -> pd.DataFrame:
    payload = json.loads(DATA_PATH.read_text(encoding="utf-8"))
    records = payload["records"] if isinstance(payload, dict) else payload
    return pd.DataFrame(records)


def build_summary(df: pd.DataFrame) -> dict:
    month_risk = (
        df.groupby("month")["fire_occurred"]
        .agg(["count", "sum", "mean"])
        .reset_index()
        .rename(columns={"count": "records", "sum": "fires", "mean": "probability"})
    )

    density_risk = (
        df.groupby("density")["fire_occurred"]
        .agg(["count", "sum", "mean"])
        .reset_index()
        .rename(columns={"count": "records", "sum": "fires", "mean": "probability"})
    )

    temp_bands = pd.cut(
        df["avg_temp"],
        bins=[0, 29, 32, 35, 50],
        labels=["cool", "warm", "hot", "extreme"],
        include_lowest=True,
    )
    temp_risk = (
        df.assign(temp_band=temp_bands)
        .groupby("temp_band", observed=False)["fire_occurred"]
        .agg(["count", "sum", "mean"])
        .reset_index()
        .rename(columns={"count": "records", "sum": "fires", "mean": "probability"})
    )

    top_risk_rows = df.sort_values(
        by=["barangay_risk_score", "past_7_day_incidents", "avg_temp"],
        ascending=False,
    ).head(5)

    return {
        "dataset_rows": int(len(df)),
        "overall_fire_probability": round(float(df["fire_occurred"].mean()), 4),
        "monthly_pattern": month_risk.to_dict(orient="records"),
        "density_pattern": density_risk.to_dict(orient="records"),
        "temperature_pattern": temp_risk.to_dict(orient="records"),
        "top_risk_samples": top_risk_rows.to_dict(orient="records"),
    }


def try_generate_charts(df: pd.DataFrame) -> list[str]:
    generated = []

    try:
        mpl_cache_dir = BASE_DIR / ".matplotlib"
        mpl_cache_dir.mkdir(exist_ok=True)
        os.environ.setdefault("MPLCONFIGDIR", str(mpl_cache_dir))
        import matplotlib.pyplot as plt
    except ImportError:
        return generated

    CHARTS_DIR.mkdir(exist_ok=True)

    monthly = df.groupby("month")["fire_occurred"].mean()
    plt.figure(figsize=(8, 4))
    monthly.plot(kind="bar", color="#ea580c")
    plt.title("Monthly Fire Probability Pattern")
    plt.xlabel("Month")
    plt.ylabel("Probability")
    plt.tight_layout()
    monthly_path = CHARTS_DIR / "monthly_fire_probability.png"
    plt.savefig(monthly_path, dpi=160)
    plt.close()
    generated.append(str(monthly_path.name))

    plt.figure(figsize=(6, 4))
    plt.scatter(
        df["avg_temp"],
        df["barangay_risk_score"],
        c=df["fire_occurred"],
        cmap="coolwarm",
        edgecolors="black",
        alpha=0.8,
    )
    plt.title("Temperature vs Barangay Risk Score")
    plt.xlabel("Average Temperature")
    plt.ylabel("Barangay Risk Score")
    plt.tight_layout()
    scatter_path = CHARTS_DIR / "temp_vs_risk_score.png"
    plt.savefig(scatter_path, dpi=160)
    plt.close()
    generated.append(str(scatter_path.name))

    return generated


def main():
    df = load_dataframe()
    summary = build_summary(df)
    summary["generated_charts"] = try_generate_charts(df)
    SUMMARY_PATH.write_text(json.dumps(summary, indent=2), encoding="utf-8")
    print(json.dumps(summary, indent=2))


if __name__ == "__main__":
    main()
