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

const BASE_URL = process.env.BASE_URL || 'http://localhost:8091';
const USERNAME = process.env.WP_LOCAL_USERNAME;
const PASSWORD = process.env.WP_LOCAL_PASSWORD;

test('woocommerce image filter includes product images and excludes non-product featured image', async ({ page }) => {
  test.skip(!USERNAME || !PASSWORD, 'Missing WP_LOCAL_USERNAME / WP_LOCAL_PASSWORD env vars');

  await page.goto(`${BASE_URL}/wp-login.php`);
  await page.locator('#user_login').fill(USERNAME!);
  await page.locator('#user_pass').fill(PASSWORD!);
  await page.locator('#wp-submit').click();
  await expect(page).toHaveURL(/wp-admin/);

  await page.goto(`${BASE_URL}/wp-admin/admin.php?page=flux-ai-media-alt-creator#/media`);

  // Set category to WooCommerce Images (robust MUI select handling + diagnostics)
  const isWooFlag = await page.evaluate(() => Boolean((window as any)?.fluxAIMediaAltCreatorAdmin?.isWooCommerceActive));
  expect(isWooFlag).toBeTruthy();

  const categoryInput = page.getByLabel('Category');
  await expect(categoryInput).toBeVisible();
  await categoryInput.click();

  const popupOptions = page.locator([
    '.MuiPopover-root:visible [role="menuitem"]',
    '.MuiMenu-root:visible [role="menuitem"]',
    '.MuiAutocomplete-popper:visible [role="option"]',
    '[role="presentation"]:visible [role="menuitem"]',
    '[role="listbox"]:visible [role="option"]',
  ].join(', '));

  await expect(popupOptions.first()).toBeVisible();

  const optionTexts = await popupOptions.allTextContents();
  test.info().annotations.push({ type: 'category-options', description: optionTexts.join(' | ') });

  const wooOption = popupOptions.filter({ hasText: 'WooCommerce Images' }).first();
  await expect(wooOption).toBeVisible();
  await wooOption.click();

  // Narrow down to deterministic fixture names.
  const searchInput = page.getByPlaceholder('Search media files...');
  await searchInput.fill('flux-e2e-');

  await expect(page.getByText('flux-e2e-woo-featured.png').first()).toBeVisible();
  await expect(page.getByText('flux-e2e-woo-gallery.png').first()).toBeVisible();
  await expect(page.getByText('flux-e2e-woo-variation.png').first()).toBeVisible();

  await expect(page.getByText('flux-e2e-nonwoo-featured.png')).toHaveCount(0);
});
