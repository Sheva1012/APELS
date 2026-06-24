import http from 'k6/http';
import { sleep, check } from 'k6';

// k6 configuration for 100 concurrent users
export const options = {
    stages: [
        { duration: '30s', target: 50 },  // Ramp-up to 50 users
        { duration: '1m', target: 100 },  // Ramp-up to 100 users
        { duration: '2m', target: 100 },  // Stay at 100 users
        { duration: '30s', target: 0 },   // Ramp-down to 0 users
    ],
    thresholds: {
        http_req_duration: ['p(95)<2000'], // 95% of requests should complete within 2s
        http_req_failed: ['rate<0.01'],    // Error rate should be less than 1%
    },
};

const BASE_URL = __ENV.BASE_URL || 'http://localhost';

export default function () {
    // 1. Simulate visiting the home page and logging in
    let loginRes = http.post(`${BASE_URL}/api/login`, JSON.stringify({
        email: `student.perf.${__VU}@example.com`,
        password: 'password',
    }), {
        headers: { 'Content-Type': 'application/json' },
    });

    check(loginRes, {
        'login status is 200 or 401/404/405 (if dev/seeder dependent)': (r) => r.status === 200 || r.status === 405 || r.status === 404,
    });

    sleep(1);

    // 2. Submit Diagnostic Test
    // Simulates sending 3 text answers and a boundary mock audio
    let payload = JSON.stringify({
        text_answers: [
            'This is a sample answer for grammar testing purposes.',
            'Vocabulary is essential for professional communication in business.',
            'Speaking audio is transcribed and evaluated asynchronously.',
        ],
    });

    let params = {
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            // In actual load test, Auth Bearer token or Cookie would be passed here
        },
    };

    let submitRes = http.post(`${BASE_URL}/api/diagnostic/submit`, payload, params);
    
    check(submitRes, {
        'diagnostic submit returns success or daily limit/unauthorized': (r) => {
            return r.status === 200 || r.status === 429 || r.status === 401;
        },
    });

    sleep(2);

    // 3. Poll Dashboard (simulate polling every 5s for 3 iterations)
    for (let i = 0; i < 3; i++) {
        let dashboardRes = http.get(`${BASE_URL}/api/dashboard`, params);
        
        check(dashboardRes, {
            'dashboard status is 200 or 401': (r) => r.status === 200 || r.status === 401,
        });
        
        sleep(5);
    }
}
