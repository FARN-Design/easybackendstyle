import { expect, type Page } from '@playwright/test';

export async function checkListColors(page: Page) {
  await page.evaluate(() => {
    const table = document.createElement('table');
    table.className = 'wp-list-table widefat plugins';
    table.id = 'ebs-plugin-row-test';
    table.innerHTML = `<tbody>
      <tr class="active" data-plugin="first"><th class="check-column"></th><td>First</td></tr>
      <tr class="active plugin-update-tr" data-plugin="first"><td class="plugin-update colspanchange"><div class="update-message notice notice-warning">Update</div></td></tr>
      <tr class="inactive" data-plugin="second"><th class="check-column"></th><td>Second</td></tr>
      <tr class="plugin-update-tr" data-plugin="second"><td class="plugin-update colspanchange"><div class="update-message notice notice-warning">Update</div></td></tr>
    </tbody>`;
    const other = document.createElement('table');
    other.className = 'wp-list-table widefat striped';
    other.id = 'ebs-other-row-test';
    other.innerHTML = '<tbody><tr><td>First</td></tr><tr><td>Second</td></tr></tbody>';
    document.querySelector('#wpbody-content')!.append(table, other);
  });

  const stylesheet = page.locator('link[href*="admin-lists.css"]');
  await expect(stylesheet).toHaveCount(1);
  const pluginStyles = () => page.locator('#ebs-plugin-row-test tr, #ebs-plugin-row-test th, #ebs-plugin-row-test td, #ebs-plugin-row-test .update-message')
    .evaluateAll(nodes => nodes.map(node => {
      const style = getComputedStyle(node);
      return [style.backgroundColor, style.borderLeftColor, style.borderLeftWidth];
    }));
  await stylesheet.evaluate((link: HTMLLinkElement) => { link.disabled = true; });
  const coreStyles = await pluginStyles();
  await stylesheet.evaluate((link: HTMLLinkElement) => { link.disabled = false; });
  expect(await pluginStyles()).toEqual(coreStyles);
  await expect(page.locator('#ebs-plugin-row-test .active th.check-column').first()).toHaveCSS('border-left-width', '4px');

  const rows = page.locator('#ebs-other-row-test tr');
  await expect(rows.nth(0)).toHaveCSS('background-color', 'rgb(237, 244, 250)');
  await expect(rows.nth(1)).toHaveCSS('background-color', 'rgb(228, 242, 220)');
  await page.evaluate(() => {
    document.documentElement.style.setProperty('--ebsListOdd', '#ff00ff');
    document.documentElement.style.setProperty('--ebsListEven', '#00ff00');
  });
  await expect(rows.nth(0)).toHaveCSS('background-color', 'rgb(255, 0, 255)');
  await expect(rows.nth(1)).toHaveCSS('background-color', 'rgb(0, 255, 0)');
  expect(await pluginStyles()).toEqual(coreStyles);
}
