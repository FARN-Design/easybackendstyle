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

    /** Save before admin-header.php selects the color stylesheet and body classes. */
    public static function processPage(): void {
        $plugin = $GLOBALS['ebsPlugin'];
        if (!$plugin->dbc->checkFields()) { self::saveFailure(); return; }
        self::handle($plugin->dbc, $plugin->dbc->defaults());
        if ($plugin instanceof \easyBackendStyle) $plugin->registerColorScheme();
    }

    public static function handle($database, array $defaults): void {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You are not allowed to change these settings.', 'easybackendstyle'));
        }
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') return;
        if (!isset($_POST['submit']) && !isset($_POST['resetDefaults'])) return;
        check_admin_referer('ebs_save_colors', 'ebs_nonce');
        if (isset($_POST['resetDefaults'])) {
            if (!$database->resetDefaults()) { self::saveFailure(); return; }
            InitialColors::saved();
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
        if (!$database->saveColors($values)) { self::saveFailure(); return; }
        InitialColors::saved();
        add_settings_error('ebs_colors', 'saved', __('Changes saved.', 'easybackendstyle'), 'success');
    }

    public static function saveFailure(): void {
        add_settings_error('ebs_colors', 'database', __('The colors could not be saved. Please try again. If the problem persists, contact your administrator.', 'easybackendstyle'));
    }

    public static function databaseWarning(): void {
        add_action('admin_notices', static function () {
            if (!current_user_can('manage_options')) return;
            echo '<div class="notice notice-warning"><p>' . esc_html__('Easy Backend-Style could not finish updating its color settings. The original settings have been retained. Please try again or contact your administrator.', 'easybackendstyle') . '</p></div>';
        });
    }

    public static function enqueueThemeImport(): void {
        wp_enqueue_script('ebs_themeImport', plugins_url('resources/theme-import.js', EBS_PLUGIN_PATH . 'easyBackendStyle.php'), ['jquery', 'wp-color-picker', 'wp-i18n'], filemtime(EBS_PLUGIN_PATH . 'resources/theme-import.js'), true);
        wp_set_script_translations('ebs_themeImport', 'easybackendstyle', EBS_PLUGIN_PATH . 'src/languages');
    }

    /** Read-only: import prepares the form; only the normal save handler persists it. */
    public static function importThemeColors(): void {
        if (!current_user_can('manage_options')) wp_send_json_error([], 403);
        check_ajax_referer('ebs_import_theme_colors');
        $base = InitialColors::detect();
        if (!$base) wp_send_json_error([], 422);
        $plugin = $GLOBALS['ebsPlugin'];
        if ($plugin instanceof \easyBackendStyle) $plugin->color_mapping();
        wp_send_json_success(InitialColors::generate($base, $plugin->dbc->defaults()));
    }

    public static function header(): void {
        ?>
        <div class="ebs-page-header">
            <div><div class="ebs-overtitle">Easy Backend-Style</div>
                <h1><?php esc_html_e('Settings', 'easybackendstyle'); ?></h1></div>
            <a class="ebs-documentation" href="<?php echo esc_url(__('https://www.farnlabs.com/easy-backend-style/documentation', 'easybackendstyle')); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Documentation', 'easybackendstyle'); ?> <span class="dashicons dashicons-external" aria-hidden="true"></span></a>
        </div>
        <?php if (InitialColors::detect()) : ?>
            <p class="ebs-theme-import"><button type="button" class="button button-secondary" id="ebs-import-theme-colors" data-nonce="<?php echo esc_attr(wp_create_nonce('ebs_import_theme_colors')); ?>"><?php esc_html_e('Import colors from theme', 'easybackendstyle'); ?></button></p>
            <p id="ebs-import-status" role="status" aria-live="polite"></p>
        <?php endif; ?>
        <?php if (!did_action('admin_notices')) settings_errors('ebs_colors');
    }
}
