import { defineConfig, devices } from "@playwright/test";

export default defineConfig({
  testDir: "./e2e",
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  workers: process.env.CI ? 1 : undefined,
  reporter: process.env.CI
    ? [["github"], ["list"], ["html", { open: "never", outputFolder: "playwright-report" }]]
    : "list",
  use: {
    baseURL: "http://localhost:5000",
    trace: "on-first-retry",
  },
  projects: [
    {
      name: "chromium",
      use: { ...devices["Desktop Chrome"] },
    },
  ],
  webServer: {
    command: "npm run preview",
    url: "http://localhost:5000",
    reuseExistingServer: !process.env.CI,
    timeout: 120 * 1000,
    env: {
      VITE_INTERNAL_ENCRYPTION_KEY:
        process.env.VITE_INTERNAL_ENCRYPTION_KEY || "12345678901234567890123456789012",
    },
  },
});
