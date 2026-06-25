import { test, expect } from '@playwright/test';

// Use 127.0.0.1 for maximum local server compatibility
const BASE_URL = 'http://127.0.0.1:8000';
const TEST_EMAIL = 'test@test.com';
const TEST_PASSWORD = 'password';

test.use({
  baseURL: BASE_URL,
  actionTimeout: 10000,
  navigationTimeout: 15000,
});

test.describe('SME Platform E2E Tests', () => {
    test('Public Pages: Home and Explore', async ({ page }) => {
        await page.goto('/');
        await expect(page).toHaveTitle(/Welcome to SME E-books/);
        await expect(page.getByText('SME E-Book Platform')).toBeVisible();

        await page.goto('/explore');
        await expect(page.getByRole('heading', { name: 'E-Book Library' })).toBeVisible();
    });

    test('User Flow: Login, Dashboard, and Spreadsheet', async ({ page }) => {
        // 1. Login
        await page.goto('/login');
        
        await page.locator('input[name="email"]').fill(TEST_EMAIL);
        await page.locator('input[name="password"]').fill(TEST_PASSWORD);
        await page.getByRole('button', { name: 'Log in' }).click();

        // 2. Dashboard Verification
        // Use a regex for waitForURL to be more flexible
        await page.waitForURL(/\/dashboard$/, { timeout: 15000 });
        
        await expect(page.getByText(/Total E-books/i)).toBeVisible();
        await expect(page.getByText(/Total Tables/i)).toBeVisible();
        
        // 3. Navigate to Tables
        await page.getByRole('link', { name: 'Tables' }).click();
        await page.waitForURL(/\/dashboard\/spreadsheets$/, { timeout: 15000 });

        // 4. Verify Jspreadsheet loads in Editor
        const editLink = page.locator('a[href*="/edit"]').first();
        if (await editLink.isVisible()) {
            await editLink.click();
        } else {
            await page.getByRole('button', { name: 'Create New Table' }).click();
            await page.getByLabel('Table Title').fill('Playwright Auto Test');
            await page.getByRole('button', { name: 'Create & Open Editor' }).click();
        }

        // Wait for Jspreadsheet to initialize
        await page.waitForSelector('#spreadsheet-editor', { timeout: 15000 });
        await expect(page.locator('#spreadsheet-editor')).toBeVisible();

        // 5. Logout
        await page.locator('button[aria-haspopup="menu"]').last().click();
        await page.getByRole('menuitem', { name: 'Log out' }).click();
        
        await page.waitForURL(BASE_URL + '/');
    });
});
