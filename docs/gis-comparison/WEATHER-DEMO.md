# Forecast and historical weather comparison

Open http://127.0.0.1:8000/gis-demo/weather and sign in with your existing application account.

The separate demo compares **Open-Meteo**, **WeatherAPI** and **Visual Crossing**. It does not change the production weather services, historical weather records, fire-risk model, `.env` or incident data. No database queries are made by the comparison service; normal application authentication still applies.

## Selected providers

| Provider | Demo endpoints | Setup and coverage |
| --- | --- | --- |
| Open-Meteo | Forecast API and Historical Weather Archive API | No key required for this demonstration; archive products are reanalysis/model data |
| WeatherAPI | `forecast.json` and `history.json` | One key; historical date access and forecast length depend on account plan |
| Visual Crossing | Timeline API with dates and hourly records | One key; plan and account quotas apply to historical/forecast queries |

These alternatives were selected because both forecast and history can be requested through their respective provider accounts. Other listed providers were not integrated or benchmarked. An endpoint's existence does not guarantee that a free account can access every date or variable.

## Setup for the alternatives

Obtain keys from:

- WeatherAPI: https://www.weatherapi.com/signup.aspx
- Visual Crossing: https://www.visualcrossing.com/sign-up/

Add only the real values locally to `.env`; see `weather-demo.env.example`:

```dotenv
WEATHERAPI_KEY=your_key
VISUAL_CROSSING_API_KEY=your_key
```

Fallback names `WEATHER_API_KEY` and `VISUALCROSSING_API_KEY` are supported. Keys stay server-side. No browser key or separate basemap setup is required. If configuration is cached, refresh it using your normal development workflow and restart the server. Missing keys, access denials and quota errors appear explicitly in the provider card.

## How to use

1. Choose **Forecast** for today and the following two days, or **Historical** for one date.
2. Historical dates are limited to at least seven days before the current Philippine date to allow for archive lag. Default: February 26, 2026, matching a date in the historical incident seeder. This is weather for the sample station coordinates, not a measured incident-site observation.
3. Click **Fetch weather comparison**. All providers receive the same requested coordinates: 15.8222 N, 120.3297 E, the project's seeded sample station marker.
4. Inspect daily mean/min/max temperature, mean humidity, precipitation total, maximum hourly wind and hourly coverage counts. The four charts use aligned dates and units. One historical day appears as individual points.
5. Click **Screenshot view** to hide controls. Include dataset dates, provider status and the explanatory footer in your screenshot.

## Normalization and interpretation

All displayed daily values are calculated from **hourly records** rather than mixing each provider's daily aggregate definitions:

- Temperature: hourly mean, minimum and maximum, Celsius.
- Relative humidity: arithmetic mean of hourly values, percent.
- Precipitation: sum of hourly precipitation, millimeters. Precipitation is not necessarily rain-only.
- Wind: maximum of hourly wind-speed values, km/h. This is not a wind gust.

A variable requires 24 unique local hourly samples. Incomplete variables remain null and show a dash; missing precipitation is never converted to zero. Temperature extrema are extrema of hourly samples, not continuous daily instrument extremes. Counts for T / RH / P / W are included for review. Provider-resolved coordinates and time zones are displayed, since models/stations can resolve to different locations than the requested point.

Open-Meteo archive/reanalysis is not an archived forecast issued on the historical date and is not an independent station observation. Forecasts containing the current day may mix observed and forecast hours, especially for Timeline products. Different provider values are not proof that one is more accurate. Assess forecast skill separately using predictions captured at the same lead time and an independent reference.

## Live sample and verification

Run `php docs/gis-comparison/weather_sample.php` from the project root for a standalone demonstration through the same comparison service. It saves normalized data and status messages to `weather-sample-results.json`, excluding keys and authenticated URLs. It does not invoke a test suite, migrations or seeders.

The October 18, 2026 run successfully fetched:

- Open-Meteo forecast: October 18-20, three daily rows, with complete hourly coverage.
- Open-Meteo archive: February 26, 2026, one daily row, with complete hourly coverage.
- WeatherAPI and Visual Crossing: not requested upstream, because no keys were configured.

PHP/JavaScript syntax checks and route inspection passed. Local HTTP requests confirmed sign-in protection and HTTP 200 delivery for the new assets. Signed-in visual verification remains pending. No migrations, seeders, automated tests or destructive database commands were run.

## Documentation comparison

Evaluate historical-date accessibility, forecast horizon, hourly completeness, variable relevance, model/source transparency, account limits and integration effort. Keep actual measured availability separate from advertised capabilities. The demo makes no automatic provider ranking or fire-risk prediction. Missing-key cards are setup status, not evidence that a provider is unreliable.

## Primary references

- Open-Meteo forecast: https://open-meteo.com/en/docs
- Open-Meteo archive and reanalysis: https://open-meteo.com/en/docs/historical-weather-api
- WeatherAPI documentation: https://www.weatherapi.com/docs/
- WeatherAPI endpoint/plan overview: https://www.weatherapi.com/api.aspx
- Visual Crossing Timeline API: https://www.visualcrossing.com/resources/documentation/weather-api/timeline-weather-api/
- Visual Crossing variable definitions: https://www.visualcrossing.com/resources/documentation/weather-data/weather-data-documentation/
