<?php
namespace Farn\EasyBackendStyle;

/** One-time initialization, shared by the WordPress 6 and 7 implementations. */
final class InitialColors {
    public const OPTION = 'ebs_initial_colors';

    public static function prepare(): void {
        if (get_option(self::OPTION, false) !== false) return;
        $modern = new ebs_DatabaseConnector();
        $legacy = new deprecated\ebs_DatabaseConnector();
        // Existing installations retain their colors, including installations before this marker.
        add_option(self::OPTION, ($modern->tableExists() || $legacy->tableExists()) ? 'ready' : 'pending', '', false);
    }

    public static function enabled(): bool {
        return get_option(self::OPTION, 'ready') === 'ready';
    }

    public static function initialize($database): void {
        // Only untouched, unconfirmed defaults may change during an upgrade.
        if (get_option(self::OPTION) === 'waiting') {
            $colors = $database->allColors();
            if (($colors['ebsPrimary'] ?? '') === '#096484' && ($colors['ebsSecondary'] ?? '') === '#52accc'
                && ($colors['ebsTertiary'] ?? '') === '#096484') {
                $database->resetDefaults();
            }
            return;
        }
        if (get_option(self::OPTION) !== 'pending') return;
        $base = self::detect();
        if (!$base) {
            update_option(self::OPTION, 'waiting', false);
            return;
        }
        if ($database->saveColors(self::generate($base, $database->defaults()))) {
            self::saved();
        }
    }

    public static function saved(): void {
        update_option(self::OPTION, 'ready', false);
        $plugin = $GLOBALS['ebsPlugin'] ?? null;
        if ($plugin instanceof \easyBackendStyle) {
            $plugin->changeAdminColorScheme(get_current_user_id());
        } else {
            update_user_option(get_current_user_id(), 'admin_color', 'fresh', true);
        }
    }

    public static function detect(): array {
        // Include Global Styles saved by the Site Editor, not just theme files.
        $settings = \WP_Theme_JSON_Resolver::get_merged_data()->get_settings();
        $palettes = $settings['color']['palette'] ?? [];
        // Ignore WordPress core's default palette. User presets override theme presets.
        $palette = array_merge($palettes['theme'] ?? [], $palettes['custom'] ?? []);
        $colors = [];
        foreach ($palette as $entry) {
            if (is_array($entry) && in_array($entry['slug'] ?? '', ['primary', 'secondary', 'tertiary'], true)) {
                $colors[$entry['slug']] = $entry['color'] ?? '';
            }
        }
        // Read only local theme stylesheets; child declarations override parent declarations.
        $css = '';
        foreach (array_unique([get_template_directory(), get_stylesheet_directory()]) as $directory) {
            $file = $directory . '/style.css';
            if (is_readable($file)) $css .= "\n" . file_get_contents($file);
        }
        $css = preg_replace('~/\*.*?\*/~s', '', $css);
        preg_match_all('/(--[a-z0-9_-]+)\s*:\s*([^;{}]+)[;}] /ix', $css, $matches, PREG_SET_ORDER);
        $variables = [];
        foreach ($matches as $match) $variables[$match[1]] = trim($match[2]);
        foreach (['primary', 'secondary', 'tertiary'] as $role) {
            if (!isset($colors[$role])) {
                foreach (['--wp--preset--color--'.$role, '--color-'.$role, '--'.$role.'-color', '--'.$role] as $name) {
                    if (isset($variables[$name])) { $colors[$role] = $variables[$name]; break; }
                }
            }
            $value = $colors[$role] ?? '';
            for ($i = 0; $i < 10 && preg_match('/^var\((--[a-z0-9_-]+)\)$/i', $value, $match); $i++) {
                $value = $variables[$match[1]] ?? '';
            }
            $colors[$role] = self::hex($value);
        }
        if (!$colors['primary'] || !$colors['secondary'] || $colors['primary'] === $colors['secondary']) return [];
        while (self::luminance($colors['primary']) > self::luminance('#00bc6f')) {
            $colors['primary'] = self::shift($colors['primary'], -1);
        }
        if ($colors['primary'] === $colors['secondary']) return [];
        $colors['tertiary'] = $colors['tertiary'] ?: self::shift($colors['secondary'], 10);
        return $colors;
    }

    private static function hex($value): string {
        if (!is_string($value) || !sanitize_hex_color($value)) return '';
        $value = strtolower($value);
        return strlen($value) === 4 ? '#' . $value[1].$value[1].$value[2].$value[2].$value[3].$value[3] : $value;
    }

    public static function luminance(string $hex): float {
        $rgb = array_map(static function ($channel) {
            $v = hexdec($channel) / 255;
            return $v <= 0.04045 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4;
        }, str_split(substr($hex, 1), 2));
        return $rgb[0] * 0.2126 + $rgb[1] * 0.7152 + $rgb[2] * 0.0722;
    }

    /** HSL lightness percentage points, matching the settings color generator. */
    public static function shift(string $hex, float $amount): string {
        $rgb = array_map(static fn($c) => hexdec($c) / 255, str_split(substr($hex, 1), 2));
        $max = max($rgb); $min = min($rgb); $light = ($max + $min) / 2;
        $next = max(0, min(1, $light + $amount / 100));
        $scale = ($light <= 0 || $light >= 1) ? 0 : (1 - abs(2 * $next - 1)) / (1 - abs(2 * $light - 1));
        return '#' . implode('', array_map(static fn($c) => sprintf('%02x', (int) round(255 * ($next + ($c - $light) * $scale))), $rgb));
    }

    private static function text(string $hex): string {
        $rgb = array_map('hexdec', str_split(substr($hex, 1), 2));
        return (max($rgb) + min($rgb)) / 510 <= 0.65 ? '#ffffff' : '#000000';
    }

    public static function generate(array $base, array $defaults): array {
        ['primary' => $p, 'secondary' => $s, 'tertiary' => $t] = $base;
        $text = self::text($s); $button = self::text($p); $highlight = self::text($t);
        $submenu = self::shift($s, $text === '#ffffff' ? 10 : -10);
        $hover = self::shift($p, -20);
        $modern = ['ebsPrimary'=>$p, 'ebsSecondary'=>$s, 'ebsTertiary'=>$t,
            'ebsLinks'=>$p, 'ebsLinksHover'=>$hover, 'ebsPrimaryDarker10'=>self::shift($p, -10),
            'ebsPrimaryDarker20'=>$hover, 'ebsPrimaryText'=>$text, 'ebsIcon'=>$text,
            'ebsSubMenu'=>$submenu, 'ebsSubMenuText'=>$text, 'ebsSecondaryLighter'=>$submenu,
            'ebsNotification'=>$t, 'ebsHighlightedText'=>$highlight, 'ebsPrimaryButtonText'=>$button];
        $legacy = ['primaryColor'=>$p, 'secondaryColor'=>$s, 'baseMenu'=>$s, 'menuText'=>$text,
            'subMenu'=>$submenu, 'highlight'=>$t, 'highlightText'=>$highlight, 'links'=>$p,
            'buttons'=>$p, 'buttonText'=>$button, 'formInputs'=>$p, 'linkHover'=>$hover,
            'buttonHover'=>$hover, 'icon'=>$text];
        return array_replace($defaults, array_intersect_key($modern + $legacy, $defaults));
    }
}
