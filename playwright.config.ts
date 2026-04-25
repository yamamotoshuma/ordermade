import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
    testDir: './tests/e2e',
    timeout: 60_000,
    workers: 1,
    fullyParallel: false,
    reporter: 'list',
    use: {
        baseURL: process.env.PLAYWRIGHT_BASE_URL ?? 'http://127.0.0.1:8080',
        locale: 'ja-JP',
        timezoneId: 'Asia/Tokyo',
        trace: 'on-first-retry',
        screenshot: 'only-on-failure',
        video: 'retain-on-failure',
    },
    projects: [
        {
            name: 'desktop-chromium',
            use: {
                ...devices['Desktop Chrome'],
                viewport: { width: 1440, height: 1800 },
            },
        },
        {
            name: 'mobile-chromium',
            use: {
                ...devices['Pixel 7'],
            },
        },
    ],
});
