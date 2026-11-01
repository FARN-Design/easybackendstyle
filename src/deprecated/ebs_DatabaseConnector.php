<?php
namespace Farn\EasyBackendStyle\deprecated;

/** WordPress 6 compatibility: keep its original keys and stored values. */
class ebs_DatabaseConnector extends \Farn\EasyBackendStyle\ebs_DatabaseConnector {
    public array $defaultsMap = [
        'primaryColor' => '#2271b1',
        'secondaryColor' => '#1d2327',
        'menuText' => '#f0f0f1',
        'baseMenu' => '#1d2327',
        'subMenu' => '#2c3338',
        'highlight' => '#2271b1',
        'highlightText' => '#f0f0f1',
        'notification' => '#d63638',
        'notificationText' => '#f0f0f1',
        'background' => '#f0f0f1',
        'links' => '#2271b1',
        'buttons' => '#2271b1',
        'buttonText' => '#f0f0f1',
        'formInputs' => '#3582c4',
        'linkHover' => '#195785',
        'buttonHover' => '#195785',
        'disabledButton' => '#969696',
        'disabledButtonText' => '#000000',
        'icon' => '#c9c9c9'];

    public function __construct() {
        parent::__construct();
        $this->tableName = $this->wpdb->prefix . 'easyBackendStyle';
    }
    public function defaults(): array { return $this->defaultsMap; }
}
