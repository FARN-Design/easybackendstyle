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
function update_option($key, $value, $autoload = false) { $GLOBALS[$key] = $value; }
function get_current_user_id() { return 1; }
function update_user_option($id, $key, $value, $global = false) { $GLOBALS[$key] = $value; }
require dirname(__DIR__) . '/src/InitialColors.php';
require dirname(__DIR__) . '/src/AdminSettings.php';
$db = new class {
    public array $writes = [];
    public bool $reset = false;
    public bool $fail = false;
    function saveColors($values) { if ($this->fail) return false; $this->writes = $values; return true; }
    function resetDefaults() { if ($this->fail) return false; $this->reset = true; return true; }
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
check($GLOBALS['ebs_initial_colors'] === 'ready' && $GLOBALS['admin_color'] === 'fresh', 'Saving must enable colors');
check($db->writes === ['primary' => '#000', 'secondary' => '#abc'], 'Valid settings not saved');
$_POST = ['resetDefaults' => '', 'ebs_nonce' => 'valid'];
$GLOBALS['allowed'] = false;
try { \Farn\EasyBackendStyle\AdminSettings::handle($db, $defaults); throw new LogicException('Unauthorized reset accepted'); } catch (RuntimeException $e) { check(!$db->reset, 'Unauthorized reset'); }
$GLOBALS['allowed'] = true;
\Farn\EasyBackendStyle\AdminSettings::handle($db, $defaults);
check($db->reset, 'Authorized reset failed');
$_POST = ['submit'=>'', 'ebs_nonce'=>'valid', 'primary'=>'#123', 'secondary'=>'#456'];
$db->fail = true;
\Farn\EasyBackendStyle\AdminSettings::handle($db, $defaults);
check($GLOBALS['notice'] === ['database', 'error'], 'Write failure reported success');
$_POST = ['resetDefaults'=>'', 'ebs_nonce'=>'valid'];
\Farn\EasyBackendStyle\AdminSettings::handle($db, $defaults);
check($GLOBALS['notice'] === ['database', 'error'], 'Reset failure reported success');
echo 'Settings request validation passed on PHP '.PHP_VERSION."\n";
