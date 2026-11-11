import { test, expect } from '@playwright/test';
test('administrator can save a color and reload it without losing the theme', async ({ page }) => {
  test.setTimeout(90000);
  await page.goto('/wp-login.php');
  await page.locator('#user_login').fill('admin');
  await page.locator('#user_pass').fill('password');
  await page.locator('#wp-submit').click();
  await page.waitForURL('**/wp-admin/**');
  await page.goto('/wp-admin/options-general.php?page=easyBackendStyle');
  await expect(page.locator('#colorPickForm')).toBeVisible();
  await expect(page.locator('.ebs-documentation')).toHaveAttribute('href', 'https://www.farnlabs.com/easy-backend-style/documentation');
  await expect(page.locator('.ebs-footer')).toHaveCount(0);
  const primary = page.locator('.wrapper_ebsPrimary');
  await primary.locator('button.wp-color-result').click();
  await primary.locator('#ebsPrimary').fill('#33258a');
  await primary.locator('#ebsPrimary').press('Tab');
  // The open WordPress picker overlays the save button at desktop width.
  await primary.locator('button.wp-color-result').click();
  await expect(primary.locator('.iris-picker')).toBeHidden();
  await page.locator('#submit').click();
  await expect(page.getByText('Changes saved.', { exact: true })).toBeVisible();
  await expect(page.locator('#ebsPrimary')).toHaveValue('#33258a');
  await page.getByRole('heading', { name: 'Settings', exact: true }).hover();
  await expect(page.locator('#submit')).toHaveCSS('background-color', 'rgb(51, 37, 138)');
  await expect(page.locator('body')).toHaveClass(/admin-color-personalizedcolorscheme/);
  await page.reload();
  await expect(page.locator('#ebsPrimary')).toHaveValue('#33258a');
  // Moving away from the button distinguishes its base color from :hover.
  await page.getByRole('heading', { name: 'Settings', exact: true }).hover();
  await expect(page.locator('#submit')).toHaveCSS('background-color', 'rgb(51, 37, 138)');
  await page.locator('.ebs_advanced_settings_toggle').click();
  await expect(page.locator('.ebs_advanced_settings_toggle')).toHaveAttribute('aria-expanded', 'true');
  await expect(page.locator('#ebsLinks')).toBeAttached();
});

test('public visitors receive no backend color assets', async ({ page }) => {
  await page.goto('/');
  await expect(page.locator('link[href*="ebsAdminbarFrontend"], link[href*="ebsMainCSS"], #ebs-root-variables')).toHaveCount(0);
});
