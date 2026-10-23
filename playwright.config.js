import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
    globalSetup: process.env.PLAYWRIGHT_BASE_URL ? undefined : './scripts/e2e-server.mjs',
    testDir: './tests/E2E',
    // The local PHP development server handles requests serially.
    workers: 2,
    expect: { timeout: 15_000 },
    timeout: 30_000,
    retries: process.env.CI ? 2 : 0,
    reporter: process.env.CI ? [['html', { open: 'never' }], ['line']] : 'list',
    use: {
        baseURL: process.env.PLAYWRIGHT_BASE_URL || 'http://127.0.0.1:8010',
        trace: 'on-first-retry',
        screenshot: 'only-on-failure',
    },
    projects: [
        { name: 'chromium-desktop', use: { ...devices['Desktop Chrome'] } },
        { name: 'chromium-tablet', use: { ...devices['Galaxy Tab S4'] } },
        { name: 'chromium-mobile', use: { ...devices['Pixel 7'] } },
    ],
});
