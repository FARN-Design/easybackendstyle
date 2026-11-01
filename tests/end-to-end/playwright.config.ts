import { defineConfig, devices } from '@playwright/test';
export default defineConfig({
  testDir: './', testMatch: '*.spec.ts', workers: 1, forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 1 : 0,
  webServer: { command: 'node scripts/serve-e2e.mjs', cwd: new URL('../..', import.meta.url).pathname,
    wait: { stdout: /WordPress is running on/ }, reuseExistingServer: false, timeout: 120000 },
  use: { baseURL: 'http://127.0.0.1:8081', trace: 'retain-on-failure' },
  projects: [{ name: 'chromium', use: { ...devices['Desktop Chrome'] } }],
});
