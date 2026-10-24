# AI model comparison for forecast explanation and mitigation

Open http://127.0.0.1:8000/gis-demo/ai and sign in using your existing application account. The demo uses the configured `OPENROUTER_API_KEY`; no new provider keys or `.env` edits are needed. Running generation consumes the account's credits where applicable. Loading the page checks the public catalog but does not automatically generate answers.

## Scope: language assistance, not numerical incident forecasting

The listed models are compared for explanatory text, grounded interpretation of supplied factors and mitigation/checklist drafting. The demo does **not** train a forecasting model, produce validated incident-count predictions, infer fire probabilities, evaluate statistical forecast skill or modify production model configuration.

The input deliberately sets the numerical incident forecast to null. Each model is asked to acknowledge missing evidence and avoid inventing numeric predictions. If a model invents a forecast elsewhere in its text, the human reviewer must flag it; JSON-format checks do not detect all factual or instruction-following errors.

## Models and availability

All seven requested models are available in the demo's selectors, using explicit IDs:

| Requested model | OpenRouter ID | Catalog observation on October 18, 2026 |
| --- | --- | --- |
| DeepSeek-R1-Distill-Llama-70B | `deepseek/deepseek-r1-distill-llama-70b` | Not listed |
| GPT-4.1 Mini | `openai/gpt-4.1-mini` | Listed; live final answer received |
| Llama 3.3 70B Instruct | `meta-llama/llama-3.3-70b-instruct` | Listed; live final answer received |
| Qwen2.5-72B-Instruct | `qwen/qwen-2.5-72b-instruct` | Listed; not generated in this run |
| Phi-4 | `microsoft/phi-4` | Listed; not generated in this run |
| Mistral Large | `mistralai/mistral-large` | Listed; not generated in this run |
| Claude 3.5 Sonnet | `anthropic/claude-3.5-sonnet` | Not listed |

The public catalog is checked again on page load, availability refresh and before generation. Network failures mean availability unknown, not proof of retirement. A catalog listing does not guarantee an active serving endpoint or account access. An unlisted exact ID is reported; no alternate model is silently substituted. OpenRouter can choose serving providers for the same model, so actual returned model and serving-provider fields are retained when available. Catalog absence does not mean the model is unavailable through every other hosting platform.

## Shared inputs and task

Inputs are read from local saved documentation artifacts, without querying a database:

- The saved successful Open-Meteo forecast in `weather-sample-results.json`, including coordinates, dates, hourly-derived daily values and snapshot timestamp.
- Date, barangay, cause and notes from `capstone-ai/historical_incident_response_times.json`. Synthetic response-time values are not supplied to the models.
- Explicit limits: small/incomplete archive, placeholder days, sample station weather rather than exact incident-site measurements, and no numerical incident forecast.

The evidence is a **snapshot**, not automatically refreshed live weather. It may be outdated. Expand **View the exact shared evidence snapshot** before running. If you refresh the weather sample artifact, that changes future AI input; preserve previous artifacts for reproducibility.

Choose either forecast-briefing explanation or mitigation checklist drafting. The system/user messages and evidence are identical across selected models for the same task. A SHA-256 digest of the full messages is stored with each result, allowing input identity to be checked.

## Method and output

- Three distinct models per UI comparison, selected from the seven names above.
- Temperature 0.2 and a 900-token output cap are requested for each model. Unsupported settings may be ignored by a provider.
- Exactly one completion attempt per model; no automatic completion retries, web tools or cross-model fallback list.
- Prompt-only JSON instruction for all models, rather than enforcing structured output only for models that support it. The task requests a summary, up to three factors, exactly three checklist items, at least two limitations, null numerical forecast and fewer than 180 words.
- Raw final content is retained. Code fences, invalid JSON and malformed required fields cause the basic JSON-shape check to fail; answers are not silently repaired.
- Word count is reported separately, using PHP's English-oriented word counting. A JSON-shape pass is not a complete instruction-compliance or factuality assessment.
- Server request elapsed time excludes the catalog lookup and includes network plus generation through full HTTP response. The non-streaming requests do not measure time-to-first-token.
- Actual returned model, finish reason, usage and API-reported cost are retained when provided. Token counts use different native tokenizers and are not directly interchangeable across models.
- Hidden reasoning fields are neither shown nor saved. No chain-of-thought comparison is performed.

The page displays content side by side, metadata and response-time bars, and offers **Screenshot view**. Model output is rendered as text; it cannot execute HTML or modify operational records. Each request is authenticated, CSRF-protected and throttled. No generation is triggered automatically on page load.

## Observed sample

`php docs/gis-comparison/ai_sample.php` makes the same bounded live demonstration for the default DeepSeek/GPT/Llama selection. It saves the context, full prompt and normalized final results in `ai-sample-results.json`, excluding the key and hidden reasoning. It is a generation example, not an automated test suite.

The initial run used the forecast-explanation task:

| Model | Observed result | Full-response elapsed | Basic JSON-shape compliance |
| --- | --- | ---: | --- |
| DeepSeek-R1-Distill-Llama-70B | Exact model not listed; no completion requested | Not applicable | Not applicable |
| GPT-4.1 Mini | Final answer received | 4.176 s | Passed |
| Llama 3.3 70B Instruct | Final answer received inside a Markdown JSON fence | 5.072 s | Failed because the final content was not plain JSON |

These are **single-request observations**, not evidence of general speed, reliability, factual accuracy or forecasting superiority. A reasoning model can spend more of the same output budget on reasoning and return a shorter or empty final answer; this affects fairness and must be documented if observed.

## Panel evaluation

Review grounding in input facts, unsupported claims, use of uncertainty, usefulness/specificity of actions, readability, requested output compliance, latency and actual reported usage. Do not assign content-quality scores automatically from JSON validity. Prefer a panel rubric agreed before generation and multiple representative cases for a defensible model recommendation; these have not been run as part of this demo.

For three available models in a screenshot, explicitly select Qwen, Phi or Mistral in place of an unavailable selection before clicking Run. Keep the original unavailable model documented rather than relabeling another model as DeepSeek or Claude.

## Isolation and verification

The production OpenRouter settings, current forecast implementation and actual `.env` are untouched. Demo code lives in `AiComparisonService`, `AiDemoController`, its separate view/assets and the existing demo route file.

PHP and JavaScript syntax checks, route inspection, local sign-in protection and static asset delivery checks passed. The two successful sample completions and the unavailable-model result are saved. Signed-in visual verification remains pending. No migrations, seeders, automated tests or destructive database commands were run.

## Primary references

- OpenAI's official GPT-4.1 Mini documentation: https://developers.openai.com/api/docs/models/gpt-4.1-mini
- OpenRouter request/response schema: https://openrouter.ai/docs/api-reference/overview
- OpenRouter live catalog: https://openrouter.ai/api/v1/models
