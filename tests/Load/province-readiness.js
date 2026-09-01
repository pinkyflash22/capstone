import http from 'k6/http';
import { check, group, sleep } from 'k6';

const baseUrl = (__ENV.BASE_URL || 'http://127.0.0.1:8000').replace(/\/$/, '');

export const options = {
  scenarios: {
    public_traffic: {
      executor: 'ramping-vus',
      startVUs: 0,
      stages: [
        { duration: '30s', target: 25 },
        { duration: '2m', target: 75 },
        { duration: '30s', target: 0 },
      ],
      gracefulRampDown: '15s',
    },
  },
  thresholds: {
    http_req_failed: ['rate<0.01'],
    http_req_duration: ['p(95)<750', 'p(99)<1500'],
    checks: ['rate>0.99'],
  },
};

export default function () {
  group('readiness', () => {
    const response = http.get(`${baseUrl}/health/ready`, { tags: { endpoint: 'readiness' } });
    check(response, {
      'readiness is 200': (result) => result.status === 200,
      'instance reports ready': (result) => result.json('status') === 'ready',
    });
  });

  group('public landing', () => {
    const response = http.get(baseUrl, { tags: { endpoint: 'landing' } });
    check(response, {
      'landing succeeds': (result) => result.status >= 200 && result.status < 400,
    });
  });

  if (__ENV.STATION_SESSION_COOKIE) {
    group('station-scoped live locations', () => {
      const response = http.get(`${baseUrl}/api/firefighter/locations`, {
        cookies: { laravel_session: __ENV.STATION_SESSION_COOKIE },
        tags: { endpoint: 'station_locations' },
      });
      check(response, { 'station feed succeeds': (result) => result.status === 200 });
    });
  }

  sleep(1);
}
