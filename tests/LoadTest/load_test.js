/**
 * PutraKop Live Chat — k6 Load Test
 * ===================================
 *
 * End-to-end load test covering the complete customer chat lifecycle:
 *   1. Health check (unauthenticated)
 *   2. Customer login
 *   3. Start a conversation
 *   4. Send messages (simulating a real chat)
 *   5. Poll for AI / agent responses
 *   6. Close the conversation
 *
 * Run:
 *   k6 run tests/LoadTest/load_test.js
 *
 * Override scenario:
 *   K6_SCENARIO=stress k6 run tests/LoadTest/load_test.js
 *
 * Override base URL:
 *   K6_BASE_URL=https://staging.putrakop.com k6 run tests/LoadTest/load_test.js
 */

import http from 'k6/http';
import { check, group, sleep, fail } from 'k6';
import { Counter, Rate, Trend } from 'k6/metrics';
import { randomItem } from 'https://jslib.k6.io/k6-utils/1.4.0/index.js';

import {
    API_BASE,
    USERS,
    TEST_DATA,
    THRESHOLDS,
    getActiveScenario,
    getCustomerForVU,
    getRandomMessage,
} from './config.js';

// ─── Custom Metrics ───────────────────────────────────────────────

const loginSuccessRate = new Rate('login_success_rate');
const chatStartSuccessRate = new Rate('chat_start_success_rate');
const messageSendSuccessRate = new Rate('message_send_success_rate');
const chatCloseSuccessRate = new Rate('chat_close_success_rate');

const chatLifecycleDuration = new Trend('chat_lifecycle_duration', true);
const aiResponseTime = new Trend('ai_response_time', true);

const totalMessagesSent = new Counter('total_messages_sent');
const totalConversationsCreated = new Counter('total_conversations_created');

// ─── Options ──────────────────────────────────────────────────────

export const options = {
    scenarios: getActiveScenario(),
    thresholds: THRESHOLDS,

    // TLS settings for production tests
    tlsVersion: 'tls1.2',

    // Disable response body compression for accurate timing
    compression: 'none',

    // Batch settings
    batch: 6,
    batchPerHost: 6,
};

// ─── Setup (runs once before all VUs) ─────────────────────────────

export function setup() {
    console.log('──────────────────────────────────────────────');
    console.log(`PutraKop Live Chat Load Test`);
    console.log(`Target: ${API_BASE}`);
    console.log(`Scenario: ${__ENV.K6_SCENARIO || 'load'}`);
    console.log(`VUs: ${options.scenarios[Object.keys(options.scenarios)[0]].maxVUs || 'default'}`);
    console.log('──────────────────────────────────────────────');

    // Verify the target is reachable
    const healthRes = http.get(`${API_BASE}/health`, {
        tags: { endpoint: 'health' },
    });

    if (healthRes.status !== 200) {
        fail(`Health check failed with status ${healthRes.status}. Is the server running?`);
    }

    console.log('Health check passed. Starting load test...');

    return { startTime: Date.now() };
}

// ─── Main Test Flow (runs per VU iteration) ───────────────────────

export default function (data) {
    const vuIndex = __VU - 1;
    const customer = getCustomerForVU(vuIndex);
    let token = null;
    let conversationId = null;

    // ═══════════════════════════════════════════════════════════════
    // Phase 1: Health Check (unauthenticated)
    // ═══════════════════════════════════════════════════════════════

    group('1. Health Check', () => {
        const res = http.get(`${API_BASE}/health`, {
            tags: { endpoint: 'health' },
        });

        check(res, {
            'health: status is 200': (r) => r.status === 200,
            'health: status field is healthy': (r) => {
                const body = JSON.parse(r.body);
                return body.status === 'healthy';
            },
            'health: response time < 200ms': (r) => r.timings.duration < 200,
        }) || console.error(`Health check failed: status=${res.status}`);
    });

    sleep(0.5);

    // ═══════════════════════════════════════════════════════════════
    // Phase 2: Customer Login
    // ═══════════════════════════════════════════════════════════════

    group('2. Customer Login', () => {
        const payload = JSON.stringify({
            email: customer.email,
            password: customer.password,
            device_fingerprint: customer.deviceFingerprint,
            device_name: customer.deviceName,
        });

        const params = {
            headers: { 'Content-Type': 'application/json' },
            tags: { endpoint: 'login' },
        };

        const res = http.post(`${API_BASE}/auth/login`, payload, params);

        const loginOk = check(res, {
            'login: status is 200': (r) => r.status === 200,
            'login: returns token': (r) => {
                try {
                    const body = JSON.parse(r.body);
                    return body.token !== undefined && body.token !== null;
                } catch {
                    return false;
                }
            },
            'login: response time < 600ms': (r) => r.timings.duration < 600,
        });

        loginSuccessRate.add(loginOk);

        if (loginOk) {
            const body = JSON.parse(res.body);
            token = body.token;
        } else {
            console.error(`Login failed for ${customer.email}: status=${res.status}, body=${res.body}`);
            return; // Cannot proceed without auth
        }
    });

    sleep(0.5);

    // ═══════════════════════════════════════════════════════════════
    // Phase 3: Start Conversation
    // ═══════════════════════════════════════════════════════════════

    group('3. Start Conversation', () => {
        const payload = JSON.stringify({
            department_id: TEST_DATA.departmentId,
            language: randomItem(TEST_DATA.languages),
        });

        const params = {
            headers: {
                'Content-Type': 'application/json',
                Authorization: `Bearer ${token}`,
            },
            tags: { endpoint: 'chat_start' },
        };

        const res = http.post(`${API_BASE}/customer/conversations`, payload, params);

        const startOk = check(res, {
            'chat_start: status is 201': (r) => r.status === 201,
            'chat_start: returns conversation': (r) => {
                try {
                    const body = JSON.parse(r.body);
                    return body.data?.conversation?.id !== undefined;
                } catch {
                    return false;
                }
            },
            'chat_start: response time < 800ms': (r) => r.timings.duration < 800,
        });

        chatStartSuccessRate.add(startOk);

        if (startOk) {
            const body = JSON.parse(res.body);
            conversationId = body.data.conversation.id;
            totalConversationsCreated.add(1);
        } else {
            console.error(`Chat start failed: status=${res.status}, body=${res.body}`);
            return;
        }
    });

    sleep(1);

    // ═══════════════════════════════════════════════════════════════
    // Phase 4: Send Messages (multi-turn chat simulation)
    // ═══════════════════════════════════════════════════════════════

    const messageCount = Math.floor(Math.random() * 4) + 2; // 2-5 messages

    group('4. Send Messages', () => {
        for (let i = 0; i < messageCount; i++) {
            const message = getRandomMessage();

            const payload = JSON.stringify({
                content: message,
                message_type: 'text',
            });

            const params = {
                headers: {
                    'Content-Type': 'application/json',
                    Authorization: `Bearer ${token}`,
                },
                tags: { endpoint: 'chat_message' },
            };

            const res = http.post(
                `${API_BASE}/customer/conversations/${conversationId}/messages`,
                payload,
                params,
            );

            const msgOk = check(res, {
                'message_send: status is 201': (r) => r.status === 201,
                'message_send: returns message': (r) => {
                    try {
                        const body = JSON.parse(r.body);
                        return body.data?.message?.id !== undefined;
                    } catch {
                        return false;
                    }
                },
                'message_send: response time < 500ms': (r) => r.timings.duration < 500,
            });

            messageSendSuccessRate.add(msgOk);
            totalMessagesSent.add(msgOk ? 1 : 0);

            if (!msgOk) {
                console.error(`Message send failed: status=${res.status}, body=${res.body}`);
                break;
            }

            // Simulate typing delay and wait for AI/agent response
            sleep(Math.random() * 2 + 1); // 1-3 seconds between messages
        }
    });

    // ═══════════════════════════════════════════════════════════════
    // Phase 5: Poll for AI / Agent Response
    // ═══════════════════════════════════════════════════════════════

    group('5. Poll for Response', () => {
        const pollParams = {
            headers: {
                Authorization: `Bearer ${token}`,
            },
            tags: { endpoint: 'chat_history' },
        };

        let foundResponse = false;
        const maxPolls = 10;
        const pollStart = Date.now();

        for (let i = 0; i < maxPolls; i++) {
            const res = http.get(
                `${API_BASE}/customer/conversations/${conversationId}/messages?limit=10`,
                pollParams,
            );

            check(res, {
                'poll: status is 200': (r) => r.status === 200,
                'poll: response time < 400ms': (r) => r.timings.duration < 400,
            });

            if (res.status === 200) {
                try {
                    const body = JSON.parse(res.body);
                    const messages = body.data?.messages || [];

                    // Check if there's a response from agent or AI (not from customer)
                    const agentMessages = messages.filter(
                        (m) => m.sender_type === 'agent' || m.sender_type === 'ai',
                    );

                    if (agentMessages.length > 0) {
                        foundResponse = true;
                        const elapsed = Date.now() - pollStart;
                        aiResponseTime.add(elapsed);
                        break;
                    }
                } catch {
                    // JSON parse error — continue polling
                }
            }

            sleep(2); // Wait 2 seconds between polls
        }

        if (!foundResponse) {
            console.warn(`No AI/agent response received after ${maxPolls} polls for conversation ${conversationId}`);
        }
    });

    sleep(1);

    // ═══════════════════════════════════════════════════════════════
    // Phase 6: Close Conversation
    // ═══════════════════════════════════════════════════════════════

    group('6. Close Conversation', () => {
        const params = {
            headers: {
                Authorization: `Bearer ${token}`,
            },
            tags: { endpoint: 'chat_close' },
        };

        const res = http.patch(
            `${API_BASE}/customer/conversations/${conversationId}/close`,
            null,
            params,
        );

        const closeOk = check(res, {
            'close: status is 200': (r) => r.status === 200,
            'close: conversation is closed': (r) => {
                try {
                    const body = JSON.parse(r.body);
                    return body.data?.conversation?.status === 'closed';
                } catch {
                    return false;
                }
            },
            'close: response time < 500ms': (r) => r.timings.duration < 500,
        });

        chatCloseSuccessRate.add(closeOk);

        if (!closeOk) {
            console.error(`Chat close failed: status=${res.status}, body=${res.body}`);
        }
    });

    // ═══════════════════════════════════════════════════════════════
    // Phase 7: Optional Rating
    // ═══════════════════════════════════════════════════════════════

    group('7. Submit Rating', () => {
        const rating = randomItem(TEST_DATA.ratings);

        const payload = JSON.stringify({
            conversation_id: conversationId,
            rating: rating,
            comment: rating < 3 ? 'Could be improved.' : 'Great service!',
        });

        const params = {
            headers: {
                'Content-Type': 'application/json',
                Authorization: `Bearer ${token}`,
            },
            tags: { endpoint: 'rating' },
        };

        const res = http.post(`${API_BASE}/customer/ratings`, payload, params);

        check(res, {
            'rating: status is 201': (r) => r.status === 201,
            'rating: response time < 300ms': (r) => r.timings.duration < 300,
        });
    });

    // Record total lifecycle duration
    chatLifecycleDuration.add(Date.now() - data.startTime);

    // Brief pause between full iterations
    sleep(Math.random() * 3 + 1); // 1-4 seconds
}

// ─── Teardown (runs once after all VUs finish) ────────────────────

export function teardown(data) {
    const elapsed = ((Date.now() - data.startTime) / 1000).toFixed(1);
    console.log('──────────────────────────────────────────────');
    console.log(`Load test completed in ${elapsed}s`);
    console.log(`Conversations created: ${totalConversationsCreated.values()}`);
    console.log(`Messages sent: ${totalMessagesSent.values()}`);
    console.log('──────────────────────────────────────────────');
}

// ─── Handle summary ───────────────────────────────────────────────

/**
 * Custom summary handler — outputs a concise report to stdout
 * and writes a JSON summary to disk for CI/CD consumption.
 *
 * @param {object} data - k6 summary data
 */
export function handleSummary(data) {
    const summaryPath = `tests/LoadTest/summary-${Date.now()}.json`;

    const consoleOut = {};
    consoleOut[summaryPath] = JSON.stringify(data, null, 2);

    // Also log key metrics to stdout
    console.log('\n══════════════════════════════════════════════');
    console.log('  PutraKop Load Test — Summary');
    console.log('══════════════════════════════════════════════');

    const metrics = data.metrics;

    if (metrics.http_req_duration) {
        const dur = metrics.http_req_duration.values;
        console.log(`  HTTP Duration:  p95=${dur['p(95)']?.toFixed(1)}ms  p99=${dur['p(99)']?.toFixed(1)}ms  max=${dur.max?.toFixed(1)}ms`);
    }

    if (metrics.http_req_failed) {
        console.log(`  Error Rate:     ${(metrics.http_req_failed.values.rate * 100).toFixed(2)}%`);
    }

    if (metrics.http_reqs) {
        console.log(`  Throughput:     ${metrics.http_reqs.values.rate?.toFixed(1)} req/s`);
    }

    if (metrics.chat_lifecycle_duration) {
        const lc = metrics.chat_lifecycle_duration.values;
        console.log(`  Chat Lifecycle: p95=${lc['p(95)']?.toFixed(0)}ms  avg=${lc.avg?.toFixed(0)}ms`);
    }

    if (metrics.ai_response_time) {
        const ai = metrics.ai_response_time.values;
        console.log(`  AI Response:    p95=${ai['p(95)']?.toFixed(0)}ms  avg=${ai.avg?.toFixed(0)}ms`);
    }

    if (metrics.login_success_rate) {
        console.log(`  Login Success:  ${(metrics.login_success_rate.values.rate * 100).toFixed(1)}%`);
    }

    if (metrics.message_send_success_rate) {
        console.log(`  Message Success: ${(metrics.message_send_success_rate.values.rate * 100).toFixed(1)}%`);
    }

    console.log('══════════════════════════════════════════════\n');

    return consoleOut;
}
