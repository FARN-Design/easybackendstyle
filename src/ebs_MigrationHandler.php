<?php
namespace Farn\EasyBackendStyle;

/** Recoverable upgrade; never drops the original table or replaces existing modern colors. */
class ebs_MigrationHandler {
    public const VERSION = '2';
    public const VERSION_OPTION = 'ebs_schema_version';
    private const PENDING = 'ebs_migration_pending';
    private const BACKUP = 'ebs_legacy_colors_backup';
    private const LOCK = 'ebs_migration_lock';

    public function migration(): bool {
        if (get_option(self::VERSION_OPTION) === self::VERSION) return true;
        // A unique option serializes concurrent upgrades; an interrupted request can retry.
        $lock = get_option(self::LOCK);
        if ($lock && (int) $lock < time() - 300) {
            global $wpdb;
            // Compare-and-delete: never remove a lock acquired by a competing request.
            $wpdb->delete($wpdb->options, ['option_name' => self::LOCK, 'option_value' => (string) $lock], ['%s', '%s']);
            wp_cache_delete(self::LOCK, 'options');
        }
        if (!add_option(self::LOCK, time(), '', false)) return false;
        try {
            return $this->upgrade();
        } finally {
            delete_option(self::LOCK);
        }
    }

    private function upgrade(): bool {
        $old = new deprecated\ebs_DatabaseConnector();
        $new = new ebs_DatabaseConnector();
        $pending = get_option(self::PENDING, false);
        if (!$new->tableExists() && $old->tableExists() && !$pending) {
            // Persist the exact old values before creating anything. Retain unmapped keys too.
            $snapshot = $old->allColors();
            global $wpdb;
            if ($wpdb->last_error) return false;
            if (get_option(self::BACKUP, null) === null && !add_option(self::BACKUP, $snapshot, '', false)) return false;
            if (!add_option(self::PENDING, '1', '', false)) return false;
            $pending = true;
        }
        if (!$new->setup_Database()) return false;
        if ($pending) {
            $snapshot = get_option(self::BACKUP, null);
            if (!is_array($snapshot)) return false;
            // Destination keys are unique: menuText intentionally feeds two fields.
            $map = [
                'ebsPrimary' => 'primaryColor', 'ebsSecondary' => 'secondaryColor',
                'ebsPrimaryText' => 'menuText', 'ebsSubMenuText' => 'menuText',
                'ebsSubMenu' => 'subMenu', 'ebsNotification' => 'notification',
                'ebsIcon' => 'icon', 'ebsBackground' => 'background',
                'ebsLinks' => 'links', 'ebsLinksHover' => 'linkHover',
                'ebsDisabledButtonText' => 'disabledButtonText', 'ebsTertiary' => 'highlight',
                'ebsHighlightedText' => 'highlightText', 'ebsPrimaryDarker10' => 'buttonHover',
                'ebsPrimaryButtonText' => 'buttonText',
            ];
            $values = [];
            foreach ($map as $destination => $source) {
                $value = $snapshot[$source] ?? $old->defaults()[$source];
                if (!is_string($value) || !sanitize_hex_color($value)) return false;
                $values[$destination] = $value;
            }
            if (!$new->saveColors($values)) return false;
        }
        // A failed marker write must not turn a partial upgrade into a completed one.
        update_option(self::VERSION_OPTION, self::VERSION, false);
        if (get_option(self::VERSION_OPTION) !== self::VERSION) return false;
        delete_option(self::PENDING);
        // Remove obsolete announcements only after successful verification.
        foreach (['is_migration_needed', 'ebs_plugin_notice_info', 'ebs_plugin_notice_success', 'ebs_plugin_notice_warning', '_transient_ebs_pah_error_on_activation', '_transient_timeout_ebs_pah_error_on_activation'] as $name) delete_option($name);
        return true;
    }
}
