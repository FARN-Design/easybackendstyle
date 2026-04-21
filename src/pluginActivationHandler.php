<?php

namespace Farn\EasyBackendStyle;

/**
 * Available Types for notices
 * Error, Warning, Success, Info
 */
enum Type {
    case Error;
    case Warning;
    case Success;
    case Info;

    function to_string():string
    {
        return match ($this) {
            Type::Error => "error",
            Type::Warning => "warning",
            Type::Info => "info",
            Type::Success => "success",
        };
    }
}

enum Severity {
    case Soft;
    case Hard;
}

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

    public static function getInstance(string $prefix) : self {
        if (self::$instance == null){
            self::$instance = new self($prefix);
        }
        return self::$instance;
    }

    /**
     * Creates admin notices to be handled after the activation
     *
     * @param Type $type
     * @param string $message
     * @param Severity $severity
     * @return void
     */

    public function createNotice( Type $type, string $message, Severity $severity){
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

            /** @var Type $notice_type */
            $notice_type = $notice["type"];
            $notice_type = $notice_type->to_string();

            if($notice["severity"] == Severity::Hard){
                add_action('admin_notices', function() use ($notice, $notice_type){
                    wp_admin_notice($notice["message"], ["type"=> $notice_type]);
                });
                $hasHardError = true;
            } else {
                add_action('admin_notices', function() use ($notice, $notice_type){
                    wp_admin_notice($notice["message"], ["type"=>$notice_type]);
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