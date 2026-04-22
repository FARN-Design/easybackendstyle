<?php

namespace Farn\EasyBackendStyle;
use Farn\EasyBackendStyle\deprecated\ebs_DatabaseConnector as oldDataBaseConnector;
use Farn\EasyBackendStyle\ebs_DatabaseConnector;
use wpdb;

class ebs_MigrationHandler
{
    private wpdb $wpdb;
    private oldDataBaseConnector $odbc;
    private ebs_DatabaseConnector $dbc;
    private bool $is_migration_needed = false;
    private string $old_table_name;
    private string $new_table_name;
    private string $charset_collate;

    function __construct(wpdb $wpdb, ebs_DatabaseConnector $dbc, oldDataBaseConnector $odbc)
    {
        $this->wpdb = $wpdb;
        $this->odbc = $odbc;
        $this->dbc = $dbc;
        $this->old_table_name = $odbc->tableName;
        $this->new_table_name = $dbc->tableName;
        $this->charset_collate = $wpdb->get_charset_collate();
    }

    /**
     * This function checks whether a migration process is necessary.
     */
    function migrationNeeded() : bool
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

    function startMigration(){
        // manages the various steps of the migration process
        if($this->is_migration_needed){
            $this->createNewTable();
            $this->migrateValues();
            $this->deleteOldTable();
        }
    }

    function migrateValues()
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
            $this->dbc->saveValueInDB($temp_value, $new_key);
        }

    }

    function createNewTable()
    {
        $this->dbc->setup_Database();

    }

    function deleteOldTable(){
        // Query smth like: DROP TABLE IF EXISTS $oldTableName - wpdb::delete löscht nur reihen, keine tables
        $this->wpdb->query("DROP TABLE IF EXISTS $this->old_table_name;");
    }

    //TODO: validateKeys() (zur Validierung bevor die alte Tabelle gelöscht wird)

}
