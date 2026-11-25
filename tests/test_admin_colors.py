import hashlib
import importlib.util
import json
import re
import unittest
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
spec = importlib.util.spec_from_file_location('colors', ROOT / 'scripts/build-admin-colors.py')
colors = importlib.util.module_from_spec(spec)
spec.loader.exec_module(colors)

class AdminColorsTest(unittest.TestCase):
    def setUp(self):
        self.source = (colors.SOURCE / 'modern.css').read_bytes()
        self.manifest = json.loads((colors.SOURCE / 'source.json').read_text())
        self.css = colors.build(self.source, self.manifest)

    def declarations(self, selector):
        for selectors, declarations in re.findall(r'([^{}]+)\{([^{}]*)\}', self.css):
            if selector in [s.strip() for s in selectors.split(',')]:
                yield declarations

    def test_menu_uses_saved_palette(self):
        self.assertTrue(any('background: var(--ebsSecondary)' in d for d in self.declarations('#adminmenu')))
        self.assertTrue(any('background: var(--ebsSubMenu)' in d for d in self.declarations('#adminmenu .wp-submenu')))
        self.assertTrue(any('background: var(--ebsTertiary)' in d for d in self.declarations('#adminmenu li.current a.menu-top')))

    def test_separate_button_and_highlight_text(self):
        self.assertTrue(any('color: var(--ebsPrimaryButtonText)' in d for d in self.declarations('.wp-core-ui .button-primary')))
        self.assertTrue(any('color: var(--ebsHighlightedText)' in d for d in self.declarations('#adminmenu li.current a.menu-top')))
        for selector in ('#adminmenu li.current > a.menu-top',
                         '#adminmenu li.wp-has-current-submenu > a.wp-has-current-submenu',
                         '#adminmenu li.menu-top:hover > a.menu-top',
                         '#adminmenu li.opensub > a.menu-top',
                         '#adminmenu li > a.menu-top:focus',
                         '#adminmenu li.current > a.menu-top div.wp-menu-image:before',
                         '#adminmenu li.wp-has-current-submenu > a.wp-has-current-submenu div.wp-menu-image:before',
                         '#adminmenu li.menu-top:hover > a.menu-top div.wp-menu-image:before',
                         '#adminmenu li > a.menu-top:focus div.wp-menu-image:before'):
            self.assertTrue(any('color: var(--ebsCurrentMenuText)' in d for d in self.declarations(selector)), selector)
        self.assertTrue(any('color: var(--ebsHighlightedText)' in d for d in self.declarations('#adminmenu .wp-submenu li.current a')))

    def test_rgb_channels_and_core_aliases(self):
        self.assertIn('rgba(var(--ebsPrimaryRGB), 0.04)', self.css)
        self.assertNotIn('rgba(var(--ebsPrimary),', self.css)
        self.assertIn('--wp-admin-theme-color: var(--ebsPrimary)', self.css)
        self.assertIn('--wp-components-color-accent: var(--ebsPrimary)', self.css)
        self.assertIn('body.admin-color-personalizedcolorscheme .editor-editor-interface', self.css)

    def test_no_fixed_chromatic_colors(self):
        # Neutral structural grays may remain; accents must follow the palette.
        literals = set(re.findall(r'#[0-9a-fA-F]{3,8}\b|(?:rgb|hsl)\([\d.% ,]+\)', self.css))
        self.assertEqual(literals, {'#dddddd', '#fbfbfc'})
        checks = {
            '.nav-menus-php .item-edit:focus:before': '1px var(--ebsLinks)',
            '.wp-pointer .wp-pointer-content h3': 'border-color: var(--ebsLinksHover)',
            '#wpadminbar:not(.mobile) .ab-top-menu > #wp-admin-bar-recovery-mode:hover > .ab-item': 'background-color: var(--ebsLinksHover)',
            '.wp-core-ui .button-primary:active': 'background: var(--ebsPrimaryDarker20)',
        }
        for selector, expected in checks.items():
            self.assertTrue(any(expected in d for d in self.declarations(selector)), selector)

    def test_upstream_change_requires_review(self):
        with self.assertRaisesRegex(ValueError, 'WordPress CSS changed'):
            colors.build(self.source + b'\n', self.manifest)
        source = self.source + b'\na { color: #123456; }'
        self.manifest['sha256'] = hashlib.sha256(source).hexdigest()
        with self.assertRaisesRegex(ValueError, 'Unreviewed colors'):
            colors.build(source, self.manifest)

    def test_no_runtime_generation(self):
        php = (ROOT / 'easyBackendStyle.php').read_text()
        self.assertNotIn('file_put_contents', php)
        self.assertNotIn('generateColorsCss', php)
        self.assertNotIn('colors/blue/colors.css', php)

    def test_list_row_colors_apply_to_plugin_scheme_and_settings_preview(self):
        css = (ROOT / 'resources/admin-lists.css').read_text()
        for scope in ('body.admin-color-personalizedcolorscheme', 'body.ebs-color-preview', 'body.admin-color-fresh'):
            self.assertIn(scope, css)
        for parity, color in [('odd', 'ebsListOdd'), ('even', 'ebsListEven')]:
            self.assertIn(f'.wp-list-table:not(.plugins) > tbody > tr:nth-child({parity})', css)
            self.assertIn(f'background-color: var(--{color})', css)
        self.assertNotIn('.wp-list-table.plugins', css)

if __name__ == '__main__':
    unittest.main()
