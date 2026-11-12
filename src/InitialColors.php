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
            $historical = [
                ['#096484', '#52accc', '#096484'],
                ['#2271b1', '#1d2327', '#2271b1'],
            ];
            $base = [$colors['ebsPrimary'] ?? '', $colors['ebsSecondary'] ?? '', $colors['ebsTertiary'] ?? ''];
            if (in_array($base, $historical, true)) {
                if ($database->checkFields()) $database->resetDefaults();
            }
            return;
        }
        if (get_option(self::OPTION) !== 'pending') return;
        update_option(self::OPTION, 'waiting', false);
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
        $theme = \WP_Theme_JSON_Resolver::get_merged_data();
        $settings = $theme->get_settings();
        $palettes = $settings['color']['palette'] ?? [];
        // Preserve theme order; saved custom entries override matching theme slugs.
        // Core defaults are never candidates for a theme-derived scheme.
        $presets = [];
        foreach (array_merge($palettes['theme'] ?? [], $palettes['custom'] ?? []) as $entry) {
            if (is_array($entry) && isset($entry['slug'], $entry['color'])) $presets[$entry['slug']] = $entry['color'];
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
        foreach ($presets as $slug => $value) $variables['--wp--preset--color--'.$slug] = $value;
        $resolve = static function ($value) use ($variables): string {
            if (!is_string($value)) return '';
            if (str_starts_with($value, 'var:preset|color|')) $value = 'var(--wp--preset--color--'.substr($value, 17).')';
            for ($i = 0; $i < 10 && preg_match('/^var\((--[a-z0-9_-]+)\)$/i', $value, $match); $i++) {
                $value = $variables[$match[1]] ?? '';
            }
            return self::hex($value);
        };
        $candidates = [];
        foreach ($presets as $value) {
            $color = $resolve($value);
            if ($color && !in_array($color, $candidates, true)) $candidates[] = $color;
        }
        // Classic themes without usable presets can expose named CSS colors.
        if (!$candidates) {
            foreach (['primary', 'secondary', 'tertiary'] as $role) {
                foreach (['--wp--preset--color--'.$role, '--color-'.$role, '--'.$role.'-color', '--'.$role] as $name) {
                    $color = $resolve($variables[$name] ?? '');
                    if ($color) { $candidates[] = $color; break; }
                }
            }
            $candidates = array_values(array_unique($candidates));
        }
        if (!$candidates) return [];
        // Prefer visible chromatic brand colors over ink, white and pale surfaces.
        $brand = array_values(array_filter($candidates, static function ($color) {
            [, $saturation, $lightness] = self::hsl($color);
            return $saturation >= 0.20 && $lightness >= 0.18 && $lightness <= 0.80
                && self::luminance($color) >= 0.025;
        }));
        // Pastel-only themes still have a brand hue: darken it rather than
        // choosing a preceding white surface and producing an all-gray scheme.
        $pastels = array_values(array_filter($candidates, static function ($color) {
            [, $saturation, $lightness] = self::hsl($color);
            return $saturation >= 0.20 && $lightness > 0.80 && $lightness < 0.98;
        }));
        $pool = $brand ?: ($pastels ?: $candidates);
        $dark = array_values(array_filter($pool, static fn($color) => self::luminance($color) <= 0.35));
        $primary = ($dark ?: $pool)[0];
        while (self::luminance($primary) > self::luminance('#00bc6f')) {
            $primary = self::shift($primary, -1);
        }
        // One hue anchors the interface. A menu is a darker tonal variation,
        // never the site's (usually light) page background or a second brand hue.
        $secondary = self::calmMenu(self::shift($primary, -15));
        [$primaryHue] = self::hsl($primary);
        foreach ($brand as $candidate) {
            [$candidateHue, $sat, $light] = self::hsl($candidate);
            $distance = abs($primaryHue - $candidateHue);
            if (min($distance, 360 - $distance) <= 25 && $sat <= 0.55
                && $light <= 0.20 && self::luminance($candidate) <= 0.06) {
                $secondary = $candidate;
                break;
            }
        }
        while (self::luminance($secondary) > 0.08) $secondary = self::shift($secondary, -1);
        if ($secondary === $primary) $primary = self::shift($primary, 15);
        $tertiary = $primary;
        $bestDistance = INF;
        [$hue] = self::hsl($primary);
        foreach ($brand as $candidate) {
            if ($candidate === $primary) continue;
            [$otherHue] = self::hsl($candidate);
            $distance = abs($hue - $otherHue);
            $distance = min($distance, 360 - $distance);
            // Prefer a tonal variation, then nearby hues; avoid unrelated
            // red/yellow accents in a violet scheme. Ties retain palette order.
            if ($distance <= 65 && $distance < $bestDistance
                && self::luminance($candidate) <= 0.45
                && self::luminance($candidate) > self::luminance($secondary) + 0.03) {
                $tertiary = $candidate;
                $bestDistance = $distance;
            }
        }
        return ['primary' => $primary, 'secondary' => $secondary, 'tertiary' => $tertiary];
    }

    /** Hue degrees, saturation and lightness in [0, 1]. */
    private static function hsl(string $hex): array {
        [$r, $g, $b] = array_map(static fn($c) => hexdec($c) / 255, str_split(substr($hex, 1), 2));
        $max = max($r, $g, $b); $min = min($r, $g, $b);
        $delta = $max - $min; $light = ($max + $min) / 2;
        if ($delta <= 0.000001) return [0.0, 0.0, $light];
        $hue = $max === $r ? fmod(($g - $b) / $delta, 6) : ($max === $g ? ($b - $r) / $delta + 2 : ($r - $g) / $delta + 4);
        return [fmod($hue * 60 + 360, 360), $delta / (1 - abs(2 * $light - 1)), $light];
    }

    /** Keep large menu surfaces dark and muted, including saturated pastels. */
    private static function calmMenu(string $hex): string {
        [, $saturation, $lightness] = self::hsl($hex);
        $hex = self::shift($hex, min(0, 0.20 - $lightness) * 100);
        [, $saturation, $lightness] = self::hsl($hex);
        $factor = $saturation > 0.55 ? 0.55 / $saturation : 1;
        $rgb = array_map(static fn($c) => hexdec($c) / 255, str_split(substr($hex, 1), 2));
        return '#' . implode('', array_map(static fn($c) => sprintf('%02x', (int) round(
            255 * ($lightness + ($c - $lightness) * $factor)
        )), $rgb));
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

    /** Text accents need 4.5:1 against the light admin canvas. Keep fill colors intact. */
    public static function readableLink(string $hex): string {
        while ((self::luminance('#f0f0f0') + 0.05) / (self::luminance($hex) + 0.05) < 4.5) {
            $hex = self::shift($hex, -1);
        }
        return $hex;
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
        $link = self::readableLink($p);
        $linkHover = self::readableLink($hover);
        $modern = ['ebsPrimary'=>$p, 'ebsSecondary'=>$s, 'ebsTertiary'=>$t,
            'ebsLinks'=>$link, 'ebsLinksHover'=>$linkHover, 'ebsPrimaryDarker10'=>self::shift($p, -10),
            'ebsPrimaryDarker20'=>$hover, 'ebsPrimaryText'=>$text, 'ebsIcon'=>$text,
            'ebsSubMenu'=>$submenu, 'ebsSubMenuText'=>$text, 'ebsSecondaryLighter'=>$submenu,
            'ebsMenuHover'=>self::shift($p, 10), 'ebsNotification'=>$t, 'ebsHighlightedText'=>$highlight, 'ebsPrimaryButtonText'=>$button];
        $legacy = ['primaryColor'=>$p, 'secondaryColor'=>$s, 'baseMenu'=>$s, 'menuText'=>$text,
            'subMenu'=>$submenu, 'highlight'=>$t, 'highlightText'=>$highlight, 'links'=>$link,
            'buttons'=>$p, 'buttonText'=>$button, 'formInputs'=>$p, 'linkHover'=>$linkHover,
            'buttonHover'=>$hover, 'icon'=>$text];
        return array_replace($defaults, array_intersect_key($modern + $legacy, $defaults));
    }
}
