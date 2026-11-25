import { test, expect } from '@playwright/test';
import { checkListColors } from './list-color-checks';

test('legacy WordPress keeps plugin list styling and independent menu colors', async ({ page }) => {
  test.skip(!process.env.FARN_TEST_WP_VERSION?.startsWith('6.'), 'Requires WordPress 6');
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
    currentMenuText: '#123456',
    menuText: '#ffffff',
    listOddColor: '#edf4fa',
    listEvenColor: '#e4f2dc',
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
  await page.locator('#submit').evaluate((button: HTMLInputElement) => button.click());
  await page.goto('/wp-admin/plugins.php');
  await page.mouse.move(1300, 50);
  const menu = page.locator('#menu-plugins > a.wp-has-current-submenu');
  await expect(menu).toHaveCSS('color', 'rgb(18, 52, 86)');
  expect(await menu.locator('.wp-menu-image').evaluate(el => getComputedStyle(el, '::before').color)).toBe('rgb(18, 52, 86)');
  await expect(page.locator('#menu-plugins .wp-submenu li.current a')).toHaveCSS('color', 'rgb(255, 255, 255)');
  const otherMenu = page.locator('#menu-media > a.menu-top');
  await otherMenu.hover();
  await expect(otherMenu).toHaveCSS('color', 'rgb(18, 52, 86)');
  await expect.poll(() => otherMenu.locator('.wp-menu-image').evaluate(el => getComputedStyle(el, '::before').color)).toBe('rgb(18, 52, 86)');
  await otherMenu.focus();
  await expect(otherMenu).toHaveCSS('color', 'rgb(18, 52, 86)');
  await checkListColors(page);
  await page.goto('/wp-admin/options-general.php?page=easyBackendStyle');
  await expect(page.locator('#currentMenuText')).toHaveValue('#123456');
  await expect(page.locator('#listEvenColor')).toHaveValue('#e4f2dc');
});
