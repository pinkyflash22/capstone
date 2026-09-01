const baseUrl = (process.env.BASE_URL || 'http://127.0.0.1:8000').replace(/\/$/, '');
const total = Number(process.env.LOAD_REQUESTS || 400);
const concurrency = Number(process.env.LOAD_CONCURRENCY || 20);
const maxErrorRate = Number(process.env.MAX_ERROR_RATE || 0.01);
const maxP95Ms = Number(process.env.MAX_P95_MS || 750);
const durations = [];
let cursor = 0;
let failures = 0;

async function worker() {
  while (true) {
    const index = cursor++;
    if (index >= total) return;
    const path = index % 2 === 0 ? '/health/ready' : '/';
    const started = performance.now();
    try {
      const response = await fetch(`${baseUrl}${path}`, { redirect: 'manual' });
      if (response.status < 200 || response.status >= 400) failures++;
      if (path === '/health/ready') {
        const payload = await response.json();
        if (payload.status !== 'ready') failures++;
      } else {
        await response.arrayBuffer();
      }
    } catch {
      failures++;
    } finally {
      durations.push(performance.now() - started);
    }
  }
}

await Promise.all(Array.from({ length: concurrency }, () => worker()));
durations.sort((a, b) => a - b);
const percentile = (value) => durations[Math.min(durations.length - 1, Math.ceil(durations.length * value) - 1)];
const errorRate = failures / total;
const summary = {
  requests: total,
  concurrency,
  failures,
  error_rate: errorRate,
  p50_ms: Math.round(percentile(0.5)),
  p95_ms: Math.round(percentile(0.95)),
  p99_ms: Math.round(percentile(0.99)),
};

console.log(JSON.stringify(summary, null, 2));
if (errorRate >= maxErrorRate || percentile(0.95) >= maxP95Ms) process.exit(1);
