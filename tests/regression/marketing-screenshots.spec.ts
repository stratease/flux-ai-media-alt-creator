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

async function login(page: any) {
  await page.goto(`${BASE_URL}/wp-login.php`, { waitUntil: 'domcontentloaded' });
  await page.locator('#user_login').fill(USERNAME!);
  await page.locator('#user_pass').fill(PASSWORD!);
  await page.locator('#wp-submit').click();
  await expect(page).toHaveURL(/wp-admin/);
}

async function cleanPluginState(page: any) {
  await page.goto(`${BASE_URL}/wp-admin/plugins.php?plugin_status=active`, { waitUntil: 'domcontentloaded' });
  await page.waitForTimeout(1000);

  const rows = page.locator('tr.active[data-slug], tr.active[id^="plugin-"]');
  const count = await rows.count();
  for (let i = 0; i < count; i++) {
    const row = rows.nth(i);
    const text = ((await row.innerText()) || '').toLowerCase();
    const keep = text.includes('flux ai alt text') || text.includes('flux ai media alt creator') || text.includes('flux-ai-media-alt-creator');
    if (keep) continue;

    const deactivate = row.getByRole('link', { name: /^Deactivate$/i }).first();
    if (await deactivate.count()) {
      await deactivate.click();
      await page.waitForLoadState('domcontentloaded');
      await page.waitForTimeout(350);
    }
  }
}

test.describe('flux-ai-alt marketing screenshot pack', () => {
  test('capture clean, plugin-page-ready screenshots', async ({ page }, testInfo) => {
    if (!USERNAME || !PASSWORD) {
      throw new Error('Missing WP_LOCAL_USERNAME / WP_LOCAL_PASSWORD env vars');
    }

    await login(page);
    await cleanPluginState(page);

    await page.goto(`${BASE_URL}/wp-admin/admin.php?page=flux-ai-media-alt-creator`, { waitUntil: 'domcontentloaded' });
    await expect(page.getByRole('heading', { level: 1, name: /Flux AI Alt Text & Accessibility Audit/i })).toBeVisible();

    await page.screenshot({ path: shotPath(testInfo, '01-overview-full.png'), fullPage: true });

    const tabs = [
      { name: /Overview/i, file: '02-overview-tab.png' },
      { name: /Media/i, file: '03-media-tab.png' },
      { name: /Compliance/i, file: '04-compliance-tab.png' },
      { name: /Settings/i, file: '05-settings-tab.png' },
    ];

    for (const tab of tabs) {
      const t = page.getByRole('tab', { name: tab.name }).first();
      if (await t.count()) {
        await t.click();
        await page.waitForTimeout(900);
        await page.screenshot({ path: shotPath(testInfo, tab.file), fullPage: true });
      }
    }

    const licenseLink = page.getByRole('link', { name: /^License$/i }).first();
    if (await licenseLink.count()) {
      await licenseLink.click();
      await page.waitForLoadState('domcontentloaded');
      await page.waitForTimeout(800);
      await page.screenshot({ path: shotPath(testInfo, '06-license-page.png'), fullPage: true });
    }

    const logsLink = page.getByRole('link', { name: /^Logs$/i }).first();
    if (await logsLink.count()) {
      await logsLink.click();
      await page.waitForLoadState('domcontentloaded');
      await page.waitForTimeout(800);
      await page.screenshot({ path: shotPath(testInfo, '07-logs-page.png'), fullPage: true });
    }

    if (SHOT_DIR) {
      console.log(`SCREENSHOT_DIR=${SHOT_DIR}`);
    }
  });
});
