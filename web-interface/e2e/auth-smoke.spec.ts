import { test, expect } from '@playwright/test'

test.describe('Auth Module Smoke Tests', () => {
  test('landing page loads with title', async ({ page }) => {
    await page.goto('/')
    await expect(page).toHaveTitle(/SBSI/)
  })

  test('login page presents credentials form', async ({ page }) => {
    await page.goto('/login')

    const emailInput = page.locator('input[type="email"], input[name="email"], input#email')
    const passwordInput = page.locator('input[type="password"], input[name="password"], input#password')

    await expect(emailInput.first()).toBeVisible()
    await expect(passwordInput.first()).toBeVisible()
  })

  test('unauthenticated access to protected /admin redirects to login', async ({ page }) => {
    await page.goto('/admin')
    await expect(page).toHaveURL(/\/login/)
  })
})
