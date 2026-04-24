<?php

namespace Farn\EasyBackendStyle;
require_once 'pluginActivationHandler.php';
require_once 'Enums.php';
use Farn\EasyBackendStyle\deprecated\ebs_DatabaseConnector as oldDataBaseConnector;
use Farn\EasyBackendStyle\ebs_DatabaseConnector;
use http\Message;
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
            $this->validateKeys();
            $this->userFeedback();
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

    private function validateKeys() : void
    {
        if($this->first_step_successful && $this->second_step_successful){
            $this->deleteOldTable();
            $this->third_step_successful = true;
        }
    }
    //TODO: userFeedback() - um die User über das Update/spezielle Erneuerungen zu informieren
    private function userFeedback()
    {
        $handler = pluginActivationHandler::getInstance("ebs_");
        if ($this->third_step_successful) {
            $handler->createNotice(
                Type::Success,
                "Plugin Easy Backend-Style has received an update with new features and improvements. 
                The plugin has been updated successfully. Your existing color profiles have been migrated.",
                Severity::Soft);
        } else {
            $handler->createNotice(
                Type::Warning,
                "Plugin Easy Backend-Style has received an update with new features and improvements. 
                            Something went wrong during the update process. Your settings may not have been migrated correctly. 
                            Please check your Plugin-Settingspage.",
                Severity::Soft);
        }
        $handler->createNotice(
            Type::Info,
            "What's new in this update: - A third primary color option has been added 
                                                 - Various color values have been redistributed and reorganized
                                                 - New color settings have been introduced
                        Please note: Your existing color profiles have been migrated where possible. 
                        Newly added color settings have been pre-filled with default values and may require manual adjustment to match your design.",
            Severity::Soft);

        $transient_name = $handler->getTransientName();
        add_action('admin_notices', function () use ($transient_name) {
            $notices = get_transient($transient_name);

            if (!$notices) return;

            foreach ($notices as $notice) {
                $screen = get_current_screen();
                if ($screen->id != "settings_page_easyBackendStyle" && $notice["type"] == Type::Info) {
                    continue;
                }
                wp_admin_notice($notice["message"], ["type" => $notice["type"]->to_string(), "dismissible" => true]);
            }
        });
    }
}
