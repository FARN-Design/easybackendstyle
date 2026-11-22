import { test, expect } from '@playwright/test';
import { checkPluginUpdateColors } from './list-color-checks';
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

test('plugin row colors and current menu text are independent', async ({ page }) => {
  test.setTimeout(90000);
  await page.goto('/wp-login.php');
  await page.locator('#loginform').evaluate((form: HTMLFormElement) => {
    (form.elements.namedItem('log') as HTMLInputElement).value = 'admin';
    (form.elements.namedItem('pwd') as HTMLInputElement).value = 'password';
  });
  await page.locator('#wp-submit').click();
  await page.waitForURL('**/wp-admin/**');
  await page.goto('/wp-admin/options-general.php?page=easyBackendStyle');
  await page.locator('.ebs_advanced_settings_toggle').click();
  for (const [name, value] of Object.entries({
    ebsTertiary: '#a5e5d0',
    ebsListOdd: '#edf4fa',
    ebsListEven: '#ffffff',
    ebsCurrentMenuText: '#123456',
    ebsHighlightedText: '#ffffff',
    ebsMenuHover: '#aa3355',
  })) {
    await page.locator('#' + name).evaluate((input, color) => {
      const { jQuery } = window as Window & {
        jQuery: (element: Element) => {
          wpColorPicker: (action: string, value: string) => void;
          val: (value: string) => void;
        };
      };
      const picker = jQuery(input);
      picker.wpColorPicker('color', color);
      picker.val(color);
    }, value);
  }
  await page.locator('#submit').evaluate((button: HTMLButtonElement) => button.click());
  await expect(page.getByText('Changes saved.', { exact: true })).toBeVisible();
  await page.goto('/wp-admin/plugins.php');
  await page.mouse.move(1300, 50);
  const menu = page.locator('#menu-plugins > a.wp-has-current-submenu');
  await expect(menu).toHaveCSS('color', 'rgb(18, 52, 86)');
  expect(await menu.locator('.wp-menu-image').evaluate(el => getComputedStyle(el, '::before').color)).toBe('rgb(18, 52, 86)');
  await expect(page.locator('#menu-plugins .wp-submenu li.current a')).toHaveCSS('color', 'rgb(255, 255, 255)');
  await menu.hover();
  await expect(menu).toHaveCSS('color', 'rgb(18, 52, 86)');
  expect(await menu.locator('.wp-menu-image').evaluate(el => getComputedStyle(el, '::before').color)).toBe('rgb(18, 52, 86)');
  await menu.focus();
  await expect(menu).toHaveCSS('color', 'rgb(18, 52, 86)');
  await page.evaluate(() => document.body.classList.add('folded'));
  await expect(menu).toHaveCSS('color', 'rgb(18, 52, 86)');
  expect(await menu.locator('.wp-menu-image').evaluate(el => getComputedStyle(el, '::before').color)).toBe('rgb(18, 52, 86)');
  await page.evaluate(() => document.body.classList.remove('folded'));
  const otherMenu = page.locator('#menu-media > a.menu-top');
  await otherMenu.hover();
  await expect(otherMenu).toHaveCSS('color', 'rgb(18, 52, 86)');
  await expect.poll(() => otherMenu.locator('.wp-menu-image').evaluate(el => getComputedStyle(el, '::before').color)).toBe('rgb(18, 52, 86)');
  await otherMenu.focus();
  await expect(otherMenu).toHaveCSS('color', 'rgb(18, 52, 86)');
  const subMenu = page.locator('#menu-media .wp-submenu a').last();
  await subMenu.hover();
  await expect(subMenu).toHaveCSS('color', 'rgb(170, 51, 85)');

  const activeRow = page.locator('.wp-list-table.plugins > tbody > tr.active[data-plugin]:not(.plugin-update-tr)').first();
  await expect(activeRow.locator('td').first()).toHaveCSS('background-color', await activeRow.evaluate(el => getComputedStyle(el).backgroundColor));
  await checkPluginUpdateColors(page);
  await page.goto('/wp-admin/options-general.php?page=easyBackendStyle');
  await expect(page.locator('#ebsCurrentMenuText')).toHaveValue('#123456');
  await expect(page.locator('#ebsHighlightedText')).toHaveValue('#ffffff');
  await expect(page.locator('#ebsListEven')).toHaveValue('#ffffff');
});
