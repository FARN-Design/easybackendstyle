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
        }
    }
    //TODO: userFeedback() - um die User über das Update/spezielle Erneuerungen zu informieren
    private function userFeedback()
    {

    }


}
