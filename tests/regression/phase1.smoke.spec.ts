import { test, expect } from '@playwright/test';
import fs from 'node:fs';
import path from 'node:path';

function loadEnvFile(filePath: string) {
  if (!fs.existsSync(filePath)) return;
  const lines = fs.readFileSync(filePath, 'utf8').split(/\r?\n/);
  for (const raw of lines) {
    const line = raw.trim();
    if (!line || line.startsWith('#')) continue;
    const eq = line.indexOf('=');
    if (eq <= 0) continue;
    const key = line.slice(0, eq).trim();
    let value = line.slice(eq + 1).trim();
    if ((value.startsWith('"') && value.endsWith('"')) || (value.startsWith("'") && value.endsWith("'"))) {
      value = value.slice(1, -1);
    }
    if (!(key in process.env)) process.env[key] = value;
  }
}

loadEnvFile(path.resolve(process.cwd(), '.env.e2e'));
loadEnvFile(path.resolve(process.cwd(), '.env'));

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

test.describe('flux-ai-alt phase-1 @smoke', () => {
  test('wp admin login and plugin page reachable', async ({ page }, testInfo) => {
    if (!USERNAME || !PASSWORD) {
      throw new Error('Missing WP_LOCAL_USERNAME / WP_LOCAL_PASSWORD env vars. Export them before running smoke tests.');
    }

    await page.goto(`${BASE_URL}/wp-login.php`);
    await page.screenshot({ path: shotPath(testInfo, '01-login-page.png'), fullPage: true });

    await page.locator('#user_login').fill(USERNAME!);
    await page.locator('#user_pass').fill(PASSWORD!);
    await page.locator('#wp-submit').click();

    await expect(page).toHaveURL(/wp-admin/);
    await page.screenshot({ path: shotPath(testInfo, '02-wp-admin-dashboard.png'), fullPage: true });

    await page.goto(`${BASE_URL}/wp-admin/admin.php?page=flux-ai-media-alt-creator`);
    await expect(page).toHaveURL(/flux-ai-media-alt-creator/);

    await expect(
      page.getByRole('heading', { level: 1, name: 'Flux AI Alt Text & Accessibility Audit' })
    ).toBeVisible();

    await expect(page.getByRole('tab', { name: 'Overview' })).toBeVisible();
    await expect(page.getByRole('tab', { name: 'Media' })).toBeVisible();
    await expect(page.getByRole('tab', { name: 'Compliance' })).toBeVisible();
    await expect(page.getByRole('tab', { name: 'Settings' })).toBeVisible();

    await page.screenshot({ path: shotPath(testInfo, '03-plugin-page.png'), fullPage: true });
  });
});
