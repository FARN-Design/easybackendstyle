import { expect, type Page } from '@playwright/test';

export async function checkPluginUpdateColors(page: Page) {
  await page.evaluate(() => {
    const table = document.createElement('table');
    table.className = 'wp-list-table widefat plugins';
    table.id = 'ebs-row-test';
    table.innerHTML = `<tbody>
      <tr class="active" data-plugin="first"><th class="check-column"></th><td>First</td></tr>
      <tr class="active plugin-update-tr" data-plugin="first"><td class="plugin-update colspanchange"><div class="update-message notice notice-warning" style="background-color: rgb(255, 244, 204)">Update</div></td></tr>
      <tr class="active" data-plugin="second"><th class="check-column"></th><td>Second</td></tr>
      <tr class="plugin-update-tr"><td class="plugin-update colspanchange"><div class="update-message notice notice-error">Incompatible</div></td></tr>
      <tr class="active plugin-update-tr" data-plugin="second"><td class="plugin-update colspanchange"><div class="update-message notice notice-warning">Update</div></td></tr>
      <tr class="inactive" data-plugin="third"><th class="check-column"></th><td>Third</td></tr>
      <tr class="plugin-update-tr" data-plugin="third"><td class="plugin-update colspanchange"><div class="update-message notice notice-warning">Update</div></td></tr>
      <tr class="plugin-deleted-tr inactive" data-plugin="deleted"><td>Deleted</td></tr>
      <tr class="inactive" data-plugin="fourth"><th class="check-column"></th><td>Fourth</td></tr>
      <tr class="plugin-update-tr" data-plugin="fourth"><td class="plugin-update colspanchange"><div class="update-message notice notice-warning">Update</div></td></tr>
    </tbody>`;
    document.querySelector('#wpbody-content')!.append(table);
  });
  for (const [name, expected] of [['first', 'rgb(237, 244, 250)'], ['second', 'rgb(255, 255, 255)'], ['third', 'rgb(237, 244, 250)'], ['fourth', 'rgb(255, 255, 255)']]) {
    const row = page.locator(`#ebs-row-test tr[data-plugin="${name}"]:not(.plugin-update-tr)`);
    await expect(row.locator('th')).toHaveCSS('background-color', expected);
    await expect(row.locator('td')).toHaveCSS('background-color', expected);
    const update = page.locator(`#ebs-row-test tr.plugin-update-tr[data-plugin="${name}"]`);
    await expect(update).toHaveCSS('background-color', expected);
    await expect(update.locator('td.plugin-update')).toHaveCSS('background-color', expected);
  }
  await expect(page.locator('#ebs-row-test tr.plugin-update-tr:not([data-plugin]) td.plugin-update')).toHaveCSS('background-color', 'rgb(255, 255, 255)');
  await expect(page.locator('#ebs-row-test .active th.check-column').first()).toHaveCSS('border-left-width', '4px');
  await expect(page.locator('#ebs-row-test tr.plugin-update-tr').first().locator('.update-message')).toHaveCSS('background-color', 'rgb(255, 244, 204)');
}
