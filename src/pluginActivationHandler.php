<?php

namespace Farn\EasyBackendStyle;

class pluginActivationHandler
{
    static private pluginActivationHandler|null $instance = null;
    private string $prefix;
    private string $transient_name;

    // this = Instanz-Attribut == zugriff auf Attribute/Methoden einer konkreten Instanz
    // self::$instance... = statisches Attribut der Klasse == zugriff auf statische Attribute/Methoden der Klasse selbst, ohne dass eine Instanz existieren muss

    private function __construct(string $prefix)
    {
        $this->prefix = $prefix;
        $this->transient_name = $prefix . '_pah_error_on_activation';
    }

    public static function getInstance(string $prefix){
        if (self::$instance == null){
            self::$instance = new self($prefix);
        }
        return self::$instance;
    }

    /**
     * Creates admin notices to be handled after the activation
     *
     * @param string $type error, warning, success, info
     * @param string $message
     * @param string $severity soft, hard
     * @return void
     */
    public function createNotice(string $type, string $message, string $severity){
        $notice = [
            "type" => $type,
            "message" => $message,
            "severity" => $severity
        ];

        $error_activation_transient = get_transient($this->transient_name);
        if (!$error_activation_transient){
            set_transient($this->transient_name, [$notice]);
        } else {
            $error_activation_transient[] = $notice;
            set_transient($this->transient_name, $error_activation_transient);
        }
    }

    function handleNotices(){
        $allNotices = get_transient($this->transient_name);
        if(!$allNotices){
            return false;
        }
        $hasHardError = false;

        foreach ($allNotices as $notice){

            if($notice["severity"] == "hard"){
                add_action('admin_notices', function() use ($notice){
                    wp_admin_notice($notice["message"], ["type"=>$notice["type"]]);
                });

                $hasHardError = true;
            } else {
                add_action('admin_notices', function() use ($notice){
                    wp_admin_notice($notice["message"], ["type"=>$notice["type"]]);
                });
            }
        }
        if($hasHardError){
            deactivate_plugins(plugin_basename(__DIR__));
        }
        // TODO: Kann man das hier noch verbessern?
        add_filter("wp_admin_notice_markup", function($markup, $message, $args){
            if(str_contains($markup, "Plugin activated.")){
                return "";
            }
            return $markup;
        },10,3 );
        delete_transient($this->transient_name);
    }
}