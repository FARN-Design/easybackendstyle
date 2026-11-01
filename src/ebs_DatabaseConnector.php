<?php
namespace Farn\EasyBackendStyle;

/** Request-local color cache and checked, non-recursive database operations. */
class ebs_DatabaseConnector {
    protected \wpdb $wpdb;
    public string $tableName;
    public string $charset_collate;
    private ?array $colors = null;
    private ?bool $exists = null;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->tableName = $wpdb->prefix . 'ebs_easyBackendStyle';
        $this->charset_collate = $wpdb->get_charset_collate();
    }

    public function defaults(): array {
        return array_map(static fn($definition) => $definition[2], $GLOBALS['ebsColorMapping'] ?? []);
    }

    public function tableExists(): bool {
        if ($this->exists === null) {
            $this->exists = $this->wpdb->get_var($this->wpdb->prepare('SHOW TABLES LIKE %s', $this->wpdb->esc_like($this->tableName))) === $this->tableName;
        }
        return $this->exists;
    }

    public function setup_Database(): bool {
        if (!$this->tableExists()) {
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            dbDelta("CREATE TABLE {$this->tableName} (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                Variable varchar(128) NOT NULL UNIQUE,
                Value varchar(128) NOT NULL,
                UNIQUE KEY id (id)
            ) {$this->charset_collate};");
            $this->exists = null;
            if (!$this->tableExists()) return false;
        }
        return $this->checkFields();
    }

    /** Only called during authorized admin setup, activation or a settings request. */
    public function checkFields(): bool {
        if (!$this->tableExists()) return false;
        $colors = $this->allColors();
        if ($this->wpdb->last_error) return false;
        foreach ($this->defaults() as $key => $value) {
            if (!array_key_exists($key, $colors) && false === $this->wpdb->insert($this->tableName, ['Variable' => $key, 'Value' => $value], ['%s', '%s'])) {
                $this->colors = null;
                return false;
            }
        }
        $this->colors = null;
        $actual = $this->allColors();
        return !$this->wpdb->last_error && !array_diff_key($this->defaults(), $actual);
    }

    public function allColors(): array {
        if ($this->colors !== null) return $this->colors;
        $this->colors = [];
        if (!$this->tableExists()) return $this->colors;
        $rows = $this->wpdb->get_results("SELECT Variable, Value FROM {$this->tableName}", ARRAY_A);
        if (!is_array($rows) || $this->wpdb->last_error) return $this->colors;
        foreach ($rows as $row) $this->colors[$row['Variable']] = $row['Value'];
        return $this->colors;
    }

    public function getValueFromDB(string $key): array {
        $colors = $this->allColors();
        return isset($colors[$key]) ? [[$colors[$key]]] : [];
    }

    /** One statement prevents partial form saves when an individual SQL write fails. */
    public function saveColors(array $values): bool {
        if (!$values) return true;
        foreach ($values as $key => $value) {
            if (!is_string($value) || !sanitize_hex_color($value) || !array_key_exists($key, $this->defaults())) return false;
        }
        $before = $this->allColors();
        if ($this->wpdb->last_error || array_diff_key($values, $before)) return false;
        $cases = [];
        $arguments = [];
        foreach ($values as $key => $value) {
            $cases[] = 'WHEN %s THEN %s';
            $arguments[] = $key;
            $arguments[] = $value;
        }
        $placeholders = implode(', ', array_fill(0, count($values), '%s'));
        $arguments = array_merge($arguments, array_keys($values));
        $sql = "UPDATE {$this->tableName} SET Value = CASE Variable " . implode(' ', $cases) . " ELSE Value END WHERE Variable IN ($placeholders)";
        $result = $this->wpdb->query($this->wpdb->prepare($sql, $arguments));
        $this->colors = null;
        if ($result === false) return false;
        $actual = $this->allColors();
        if ($this->wpdb->last_error) return false;
        foreach ($values as $key => $value) if (($actual[$key] ?? null) !== $value) return false;
        return true;
    }

    public function saveValueInDB(string $value, string $key): bool {
        return $this->saveColors([$key => $value]);
    }

    public function resetDefaults(): bool {
        return $this->saveColors($this->defaults());
    }
}
