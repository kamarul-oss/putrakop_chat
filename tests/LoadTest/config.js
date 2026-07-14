/**
 * PutraKop Live Chat — k6 Load Test Configuration
 * =================================================
 *
 * Central configuration for all k6 load test scenarios.
 * Adjust BASE_URL, credentials, and thresholds per environment.
 *
 * Usage:
 *   k6 run tests/LoadTest/config.js    # validates config compiles
 *   k6 run tests/LoadTest/load_test.js  # executes the actual test
 *
 * Environment variables (override defaults):
 *   K6_BASE_URL     – Application base URL
 *   K6_SCENARIO     – Scenario name: smoke | load | stress | spike
 *   K6_API_TOKEN    – Bearer token for authenticated requests
 */

// ─── Environment Configuration ────────────────────────────────────

export const ENV = {
    /**
     * Base URL of the PutraKop Live Chat API.
     * Adjust per environment (local, staging, production).
     */
    baseUrl: __ENV.K6_BASE_URL || 'http://localhost:8000',

    /**
     * API version prefix — all endpoints sit behind /api/v1.
     */
    apiPrefix: '/api/v1',

    /**
     * Active test scenario.
     * Can be overridden via K6_SCENARIO env var.
     */
    scenario: __ENV.K6_SCENARIO || 'load',
};

// ─── Computed API Base ────────────────────────────────────────────

export const API_BASE = `${ENV.baseUrl}${ENV.apiPrefix}`;

// ─── Test User Credentials ────────────────────────────────────────
//
// These are seed-data users created by the database seeder.
// Each role has its own set of credentials for role-specific tests.

export const USERS = {
    /**
     * Admin user — full system access.
     */
    admin: {
        email: 'admin@putrakop.test',
        password: 'password',
        deviceFingerprint: 'k6-admin-device-001',
        deviceName: 'k6-admin-test',
    },

    /**
     * Manager user — department oversight.
     */
    manager: {
        email: 'manager@putrakop.test',
        password: 'password',
        deviceFingerprint: 'k6-manager-device-001',
        deviceName: 'k6-manager-test',
    },

    /**
     * Agent user — handles customer conversations.
     */
    agent: {
        email: 'agent@putrakop.test',
        password: 'password',
        deviceFingerprint: 'k6-agent-device-001',
        deviceName: 'k6-agent-test',
    },

    /**
     * Customer users — simulate real customers chatting.
     * Multiple entries allow concurrent VUs to use unique identities.
     */
    customers: [
        {
            email: 'customer1@putrakop.test',
            password: 'password',
            deviceFingerprint: 'k6-customer-001',
            deviceName: 'k6-customer-1',
        },
        {
            email: 'customer2@putrakop.test',
            password: 'password',
            deviceFingerprint: 'k6-customer-002',
            deviceName: 'k6-customer-2',
        },
        {
            email: 'customer3@putrakop.test',
            password: 'password',
            deviceFingerprint: 'k6-customer-003',
            deviceName: 'k6-customer-3',
        },
        {
            email: 'customer4@putrakop.test',
            password: 'password',
            deviceFingerprint: 'k6-customer-004',
            deviceName: 'k6-customer-4',
        },
        {
            email: 'customer5@putrakop.test',
            password: 'password',
            deviceFingerprint: 'k6-customer-005',
            deviceName: 'k6-customer-5',
        },
    ],
};

// ─── Test Data ────────────────────────────────────────────────────

export const TEST_DATA = {
    /**
     * Department ID to use when starting conversations.
     * Must match an existing department seeded in the database.
     */
    departmentId: 1,

    /**
     * Message templates for chat simulation.
     * Randomly selected during test execution.
     */
    messages: [
        'Hello, I need help with my order.',
        'Can you check the status of my order?',
        'I have a question about pricing.',
        'How do I reset my password?',
        'I need to update my account information.',
        'Can you help me with a refund?',
        'What are your business hours?',
        'Do you have a mobile app?',
        'I want to cancel my subscription.',
        'How can I contact support?',
        'My payment was declined, what should I do?',
        'Can I change my delivery address?',
        'I received a damaged product.',
        'Where can I find my invoice?',
        'Do you offer bulk discounts?',
    ],

    /**
     * Conversation languages to test.
     */
    languages: ['en', 'bm'],

    /**
     * Rating values for satisfaction surveys.
     */
    ratings: [1, 2, 3, 4, 5],
};

// ─── Scenario Definitions ─────────────────────────────────────────
//
// Each scenario defines VU count, duration, and ramping strategy.
// Select the active scenario via K6_SCENARIO env var.

export const SCENARIOS = {
    /**
     * Smoke test — minimal load to verify endpoints work.
     * 1 VU for 30 seconds.
     */
    smoke: {
        smoke: {
            executor: 'constant-vus',
            vus: 1,
            duration: '30s',
            gracefulStop: '5s',
            tags: { scenario: 'smoke' },
        },
    },

    /**
     * Normal load — expected production traffic.
     * Ramps to 20 VUs over 1 minute, holds for 5 minutes, ramps down.
     */
    load: {
        load: {
            executor: 'ramping-vus',
            startVUs: 0,
            stages: [
                { duration: '1m', target: 10 },   // ramp up
                { duration: '2m', target: 20 },   // sustain peak
                { duration: '5m', target: 20 },   // hold peak
                { duration: '1m', target: 10 },   // ramp down
                { duration: '30s', target: 0 },   // cool down
            ],
            gracefulRampDown: '30s',
            tags: { scenario: 'load' },
        },
    },

    /**
     * Stress test — find the breaking point.
     * Ramps to 50 VUs over 3 minutes, holds, ramps down.
     */
    stress: {
        stress: {
            executor: 'ramping-vus',
            startVUs: 0,
            stages: [
                { duration: '1m', target: 15 },
                { duration: '2m', target: 30 },
                { duration: '3m', target: 50 },   // peak stress
                { duration: '2m', target: 30 },
                { duration: '1m', target: 0 },
            ],
            gracefulRampDown: '30s',
            tags: { scenario: 'stress' },
        },
    },

    /**
     * Spike test — sudden burst of traffic.
     * Jumps from 0 to 40 VUs instantly, holds, drops back.
     */
    spike: {
        spike: {
            executor: 'ramping-vus',
            startVUs: 0,
            stages: [
                { duration: '10s', target: 0 },    // baseline
                { duration: '5s', target: 40 },    // sudden spike
                { duration: '2m', target: 40 },    // sustain spike
                { duration: '5s', target: 0 },     // drop off
                { duration: '1m', target: 0 },     // recovery
            ],
            gracefulRampDown: '30s',
            tags: { scenario: 'spike' },
        },
    },
};

// ─── Thresholds ───────────────────────────────────────────────────
//
// Performance budgets — k6 will exit with a non-zero code if any
// threshold is breached. Tune these per scenario/environment.

export const THRESHOLDS = {
    // Global thresholds
    http_req_duration: ['p(95)<500', 'p(99)<1000'],
    http_req_failed: ['rate<0.1'],
    http_reqs: ['rate>10'],

    // Per-endpoint thresholds (tag-based)
    'http_req_duration{endpoint:health}': ['p(95)<200'],
    'http_req_duration{endpoint:login}': ['p(95)<600'],
    'http_req_duration{endpoint:chat_start}': ['p(95)<800'],
    'http_req_duration{endpoint:chat_message}': ['p(95)<500'],
    'http_req_duration{endpoint:chat_history}': ['p(95)<400'],
    'http_req_duration{endpoint:chat_close}': ['p(95)<500'],

    // Error rate per endpoint
    'http_req_failed{endpoint:health}': ['rate<0.01'],
    'http_req_failed{endpoint:login}': ['rate<0.05'],
    'http_req_failed{endpoint:chat_start}': ['rate<0.05'],
    'http_req_failed{endpoint:chat_message}': ['rate<0.05'],
};

// ─── Exported Helper ──────────────────────────────────────────────

/**
 * Get the active scenario configuration based on K6_SCENARIO env var.
 *
 * @returns {object} k6 scenarios config object
 */
export function getActiveScenario() {
    const scenario = ENV.scenario;
    if (!SCENARIOS[scenario]) {
        throw new Error(
            `Unknown scenario "${scenario}". Available: ${Object.keys(SCENARIOS).join(', ')}`
        );
    }
    return SCENARIOS[scenario];
}

/**
 * Get a customer user for the given VU index.
 * Rotates through the customer pool to spread load across identities.
 *
 * @param {number} vuIndex - Current VU iteration index
 * @returns {object} Customer user credentials
 */
export function getCustomerForVU(vuIndex) {
    const customers = USERS.customers;
    return customers[vuIndex % customers.length];
}

/**
 * Get a random message from the test data pool.
 *
 * @returns {string} A random message string
 */
export function getRandomMessage() {
    const messages = TEST_DATA.messages;
    return messages[Math.floor(Math.random() * messages.length)];
}
