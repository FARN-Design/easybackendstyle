<?php
namespace Farn\EasyBackendStyle;

/** Shared settings safeguards and page header for both supported WordPress branches. */
final class AdminSettings {
    public static function loadTranslations(): void {
        global $wp_textdomain_registry;
        load_plugin_textdomain('easybackendstyle', false, dirname(plugin_basename(EBS_PLUGIN_PATH . '/easyBackendStyle.php')) . '/src/languages');
        $locale = determine_locale();
        $file = EBS_PLUGIN_PATH . '/src/languages/easybackendstyle-' . $locale . '.mo';
        if (!is_readable($file)) return;
        // Preserve WordPress.org and site-specific translations; fill missing entries.
        get_translations_for_domain('easybackendstyle');
        $preferred = isset($wp_textdomain_registry) ? $wp_textdomain_registry->get('easybackendstyle', $locale) : false;
        load_textdomain('easybackendstyle', $file, $locale);
        if ($preferred) $wp_textdomain_registry->set('easybackendstyle', $locale, $preferred);
    }

    public static function handle($database, array $defaults): void {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You are not allowed to change these settings.', 'easybackendstyle'));
        }
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') return;
        if (!isset($_POST['submit']) && !isset($_POST['resetDefaults'])) return;
        check_admin_referer('ebs_save_colors', 'ebs_nonce');
        if (isset($_POST['resetDefaults'])) {
            $database->resetDefaults();
            add_settings_error('ebs_colors', 'reset', __('Default colors restored.', 'easybackendstyle'), 'success');
            return;
        }
        $values = [];
        foreach ($defaults as $key => $default) {
            $raw = $_POST[$key] ?? null;
            $color = is_string($raw) ? sanitize_hex_color(wp_unslash($raw)) : null;
            if (!$color) {
                add_settings_error('ebs_colors', 'invalid', __('Please enter a valid hex color in every color field. No changes were saved.', 'easybackendstyle'));
                return;
            }
            $values[$key] = $color;
        }
        foreach ($values as $key => $color) $database->saveValueInDB($color, $key);
        add_settings_error('ebs_colors', 'saved', __('Changes saved.', 'easybackendstyle'), 'success');
    }

    public static function header(): void {
        ?>
        <div class="ebs-page-header">
            <div><div class="ebs-overtitle">Easy Backend-Style</div>
                <h1><?php esc_html_e('Settings', 'easybackendstyle'); ?></h1></div>
            <a class="ebs-documentation" href="<?php echo esc_url(__('https://www.farnlabs.com/easy-backend-style/documentation', 'easybackendstyle')); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Documentation', 'easybackendstyle'); ?> <span class="dashicons dashicons-external" aria-hidden="true"></span></a>
        </div>
        <?php settings_errors('ebs_colors');
    }
}
