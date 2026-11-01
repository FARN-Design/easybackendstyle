<?php
// Isolated request checks: no WordPress database writes.
function __($s, $d = '') { return $s; }
function esc_html__($s, $d = '') { return $s; }
function current_user_can($cap) { return $GLOBALS['allowed']; }
function wp_die($message) { throw new RuntimeException($message); }
function check_admin_referer($action, $field) { if (($_POST[$field] ?? '') !== 'valid') throw new RuntimeException('Invalid nonce'); }
function sanitize_hex_color($s) { return preg_match('/^#(?:[a-f0-9]{3}|[a-f0-9]{6})$/i', $s) ? $s : null; }
function wp_unslash($s) { return stripslashes($s); }
function add_settings_error($setting, $code, $text, $type = 'error') { $GLOBALS['notice'] = [$code, $type]; }
require dirname(__DIR__) . '/src/AdminSettings.php';
$db = new class {
    public array $writes = [];
    public bool $reset = false;
    function saveValueInDB($value, $key) { $this->writes[$key] = $value; }
    function resetDefaults() { $this->reset = true; }
};
function check($condition, $message) { if (!$condition) throw new RuntimeException($message); }
$defaults = ['primary' => '#123456', 'secondary' => '#abcdef'];
$GLOBALS['allowed'] = true;
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = ['submit' => '', 'primary' => '#000', 'secondary' => '#fff'];
try { \Farn\EasyBackendStyle\AdminSettings::handle($db, $defaults); throw new LogicException('Missing nonce accepted'); } catch (RuntimeException $e) { check($e->getMessage() === 'Invalid nonce', $e->getMessage()); }
check(!$db->writes, 'Nonce failure wrote data');
$_POST['ebs_nonce'] = 'valid';
$_POST['secondary'] = ['#fff'];
\Farn\EasyBackendStyle\AdminSettings::handle($db, $defaults);
check(!$db->writes && $GLOBALS['notice'][0] === 'invalid', 'Invalid payload caused partial update');
$_POST['secondary'] = '#abc';
\Farn\EasyBackendStyle\AdminSettings::handle($db, $defaults);
check($db->writes === ['primary' => '#000', 'secondary' => '#abc'], 'Valid settings not saved');
$_POST = ['resetDefaults' => '', 'ebs_nonce' => 'valid'];
$GLOBALS['allowed'] = false;
try { \Farn\EasyBackendStyle\AdminSettings::handle($db, $defaults); throw new LogicException('Unauthorized reset accepted'); } catch (RuntimeException $e) { check(!$db->reset, 'Unauthorized reset'); }
$GLOBALS['allowed'] = true;
\Farn\EasyBackendStyle\AdminSettings::handle($db, $defaults);
check($db->reset, 'Authorized reset failed');
echo 'Settings request validation passed on PHP '.PHP_VERSION."\n";
