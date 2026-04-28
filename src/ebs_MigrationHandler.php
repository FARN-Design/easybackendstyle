<?php

namespace Farn\EasyBackendStyle;
use Farn\EasyBackendStyle\deprecated\ebs_DatabaseConnector as oldDataBaseConnector;
use Farn\EasyBackendStyle\ebs_DatabaseConnector;
use Farn\EasyBackendStyle\pluginActivationHandler;
use wpdb;

class ebs_MigrationHandler
{
    private wpdb $wpdb;
    private oldDataBaseConnector $odbc;
    private ebs_DatabaseConnector $dbc;
    private bool $is_migration_needed = false;
    private bool $first_step_successful = false;
    private bool $second_step_successful = false;
    private bool $third_step_successful = false;
    private string $old_table_name;
    private string $new_table_name;
    private string $charset_collate;

    function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->odbc = new oldDataBaseConnector();
        $this->dbc = new ebs_DatabaseConnector();
        $this->old_table_name = $this->odbc->tableName;
        $this->new_table_name = $this->dbc->tableName;
        $this->charset_collate = $wpdb->get_charset_collate();

        add_action('admin_notices', [$this, 'userFeedback']);
    }

    public function migration()
    {
        $this->migrationNeeded();

        if($this->is_migration_needed){
            $this->startMigration();
        }
    }
    /**
     * This function checks whether a migration process is necessary.
     */
    private function migrationNeeded() : bool
    {
        if($this->wpdb->get_var("show tables like '" . $this->new_table_name . "'") == $this->new_table_name){
            $this->is_migration_needed = false;
        } elseif($this->wpdb->get_var("show tables like '" . $this->old_table_name . "'") == $this->old_table_name){
            $this->is_migration_needed = true;
        } else {
            $this->is_migration_needed = false;
        }
        return $this->is_migration_needed;
    }

    private function startMigration() : void
    {
        // manages the various steps of the migration process
            $this->createNewTable();
            $this->migrateValues();
            $this->validateMigrationsSteps();
    }

    private function migrateValues() : void
    {
        $map = [
            "primaryColor" => "ebsPrimary",
            "menuText" => "ebsPrimaryText",
            "secondaryColor" => "ebsSecondary",
            "subMenu" => "ebsSubMenu",
            "menuText" => "ebsSubMenuText",
            "notification" => "ebsNotification",
            "icon" => "ebsIcon",
            "background" => "ebsBackground",
            "links" => "ebsLinks",
            "linkHover" => "ebsLinksHover",
            "disabledButton" => "ebsDisabledButtonBorder",
            "disabledButtonText" => "ebsDisabledButtonText",
            "highlight" => "ebsTertiary",
            "highlightText" => "ebsHighlightedText",
            "buttonHover" => "ebsPrimaryDarker10",
        ];

        foreach ($map as $old_key => $new_key) {
            $temp_value = $this->odbc->getValueFromDB($old_key);
            $this->dbc->saveValueInDB($temp_value[0][0], $new_key);
        }
        if(empty($this->wpdb->last_error)){
            $this->second_step_successful = true;
        }
    }

    private function createNewTable() : void
    {
        $this->dbc->setup_Database();
        if(empty($this->wpdb->last_error)){
            $this->first_step_successful = true;
        }
    }

    private function deleteOldTable() : void
    {
        // Query smth like: DROP TABLE IF EXISTS $oldTableName - wpdb::delete löscht nur reihen, keine tables
        $this->wpdb->query("DROP TABLE IF EXISTS $this->old_table_name;");
    }

    private function validateMigrationsSteps() : void
    {
        if($this->first_step_successful && $this->second_step_successful){
            $this->deleteOldTable();
            update_option('ebs_plugin_notice_success', true);
            update_option('ebs_plugin_notice_warning', false);
        } else {
            update_option('ebs_plugin_notice_success', false);
            update_option('ebs_plugin_notice_warning', true);
        }
        update_option('ebs_plugin_notice_info', true);
    }

    public function userFeedback(): void
    {
        if (isset($_GET['dismiss_notice'])) {
            $which = $_GET['dismiss_notice'];
            if($which === 'info') delete_option('ebs_plugin_notice_info');
            if($which === 'success') delete_option('ebs_plugin_notice_success');
            if($which === 'warning') delete_option('ebs_plugin_notice_warning');
        }

        $screen = get_current_screen();
        $current_url = $_SERVER['REQUEST_URI'];
        $symbol = str_contains($current_url, '?') ? '&' : '?';

        // info notice - only visible on settings_page
        if (get_option("ebs_plugin_notice_info") && $screen->id === "settings_page_easyBackendStyle") {
            wp_admin_notice(
                'What\'s new in this update:
                    A third primary color option has been added
                    Various color values have been redistributed and reorganized
                    New color settings have been introduced
                    Please note: Your existing color profiles have been migrated where possible.
                    Newly added color settings have been pre-filled with default values and
                    may require manual adjustment to match your design. 
                    <a href="' . admin_url('admin.php?page=easyBackendStyle&dismiss_notice=info') . '" class="button">Verstanden</a>',
                ["type" => "info", "dismissible" => false]
            );
        }
        // success & warning notices - visible throughout the admin menu
        if (get_option("ebs_plugin_notice_success")) {
            wp_admin_notice(
                'Update Successful:
                    Plugin Easy Backend-Style has received an update with new features and improvements.
                    The plugin has been updated successfully.
                    Your existing color profiles have been migrated. <a href="' . $current_url . $symbol . 'dismiss_notice=success" class="button">Verstanden</a>',
                ['type' => 'success', 'dismissible' => false]
            );
        }
        if (get_option("ebs_plugin_notice_warning")) {
            wp_admin_notice(
                'Update Warning:
                    Plugin Easy Backend-Style has received an update with new features and improvements.
                    Something went wrong during the update process.
                    Your settings may not have been migrated correctly.
                    Please check your Plugin-Settingspage. <a href="' . $current_url . $symbol . '&dismiss_notice=warning" class="button">Verstanden</a>',
                ['type' => 'warning', 'dismissible' => false]
            );
        }
    }
}
