import { test, expect } from '@playwright/test';
import fs from 'node:fs';
import path from 'node:path';

const BASE_URL = process.env.BASE_URL || 'http://localhost';
const USERNAME = process.env.WP_LOCAL_USERNAME;
const PASSWORD = process.env.WP_LOCAL_PASSWORD;
const SHOT_DIR = process.env.E2E_SHOT_DIR;

function shotPath(testInfo: any, name: string) {
  if (SHOT_DIR) {
    fs.mkdirSync(SHOT_DIR, { recursive: true });
    return path.join(SHOT_DIR, name);
  }
  const p = testInfo.outputPath('screenshots', name);
  fs.mkdirSync(path.dirname(p), { recursive: true });
  return p;
}

test.describe('flux-ai-alt admin tabs/content @regression', () => {
  test('loads plugin admin pages and renders expected content', async ({ page }, testInfo) => {
    test.skip(!USERNAME || !PASSWORD, 'Missing WP_LOCAL_USERNAME / WP_LOCAL_PASSWORD env vars');

    await page.goto(`${BASE_URL}/wp-login.php`);
    await page.locator('#user_login').fill(USERNAME!);
    await page.locator('#user_pass').fill(PASSWORD!);
    await page.locator('#wp-submit').click();
    await expect(page).toHaveURL(/wp-admin/);

    // Main plugin page
    await page.goto(`${BASE_URL}/wp-admin/admin.php?page=flux-ai-media-alt-creator`);
    await expect(page).toHaveURL(/flux-ai-media-alt-creator/);
    await expect(page.getByText(/AI Media Alt Creator|Compliance|Accessibility|Development Mode Active/i).first()).toBeVisible();
    await page.screenshot({ path: shotPath(testInfo, '04-plugin-main-page.png'), fullPage: true });

    // License tab/page via submenu link
    await page.getByRole('link', { name: /^License$/i }).click();
    await page.waitForLoadState('domcontentloaded');
    await expect(page.getByText(/License|Flux Suite|Activate|Key/i).first()).toBeVisible();
    await page.screenshot({ path: shotPath(testInfo, '05-plugin-license-page.png'), fullPage: true });

    // Logs tab/page via submenu link
    await page.getByRole('link', { name: /^Logs$/i }).click();
    await page.waitForLoadState('domcontentloaded');
    await expect(page.getByText(/Logs|Log|entries|No logs|Activity/i).first()).toBeVisible();
    await page.screenshot({ path: shotPath(testInfo, '06-plugin-logs-page.png'), fullPage: true });
  });
});
