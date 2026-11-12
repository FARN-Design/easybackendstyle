jQuery(function ($) {
    const button = document.getElementById('ebs-import-theme-colors');
    if (!button) return;
    const { __ } = wp.i18n;
    button.addEventListener('click', async function () {
        const status = document.getElementById('ebs-import-status');
        button.disabled = true;
        status.textContent = '';
        try {
            const response = await fetch(ajaxurl, {
                method: 'POST', credentials: 'same-origin',
                body: new URLSearchParams({ action: 'ebs_import_theme_colors', _ajax_nonce: button.dataset.nonce })
            });
            const result = await response.json();
            if (!response.ok || !result.success) throw new Error('Import unavailable');
            // Main pickers may recalculate dependent values; apply advanced fields last.
            const keys = Object.keys(result.data).sort((a, b) =>
                Number(!document.getElementById(a)?.classList.contains('ebs_mainColorPicker')) -
                Number(!document.getElementById(b)?.classList.contains('ebs_mainColorPicker')));
            for (const key of keys) {
                const input = document.getElementById(key);
                if (!input) continue;
                $(input).wpColorPicker('color', result.data[key]);
                $(input).val(result.data[key]);
                $('.wrapper_' + key).find('.hex_code').text(result.data[key]);
            }
            document.getElementById('colorPickForm').dispatchEvent(new Event('ebs:colors-imported'));
            status.textContent = __('Theme colors imported. Save changes to apply them.', 'easybackendstyle');
        } catch {
            status.textContent = __('No suitable theme colors could be imported. Reload the page and try again.', 'easybackendstyle');
        } finally {
            button.disabled = false;
        }
    });
});
