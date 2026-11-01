#!/usr/bin/env python3
import importlib.util
from pathlib import Path
import shutil
import tempfile
import unittest
import zipfile

ROOT = Path(__file__).resolve().parents[2]
spec = importlib.util.spec_from_file_location('release', ROOT / 'scripts/release.py')
release = importlib.util.module_from_spec(spec)
spec.loader.exec_module(release)

class ReleaseTests(unittest.TestCase):
    def test_declared_compatibility(self):
        self.assertEqual(release.metadata()['minimum_php'], '8.2')
        with self.assertRaises(AssertionError):
            release.metadata(wordpress='99.0', stable=True)

    def test_package_allowlist_version_and_matching_exports(self):
        with tempfile.TemporaryDirectory() as temp:
            root = Path(temp) / 'source'
            root.mkdir()
            for name in ['easyBackendStyle.php','README.txt','LICENCE.md','index.php','composer.json','blueprint.json','.wordpress-org','src','resources','docs/generated']:
                source = ROOT / name
                target = root / name
                target.parent.mkdir(exist_ok=True, parents=True)
                if source.is_dir(): shutil.copytree(source, target)
                else: shutil.copyfile(source, target)
            (root / 'vendor').mkdir()
            (root / 'vendor/autoload.php').write_text('<?php // fixture')
            (root / 'private-secret.txt').write_text('must not ship')
            output = Path(temp) / 'output'
            package = release.build('v3.2.6-rc.1', output, root)
            self.assertEqual(release.header((package/'easyBackendStyle.php').read_text(),'Version'),'3.2.6-rc.1')
            self.assertEqual(release.header((package/'README.txt').read_text(),'Stable tag'),'3.2.6-rc.1')
            self.assertIn('3.2.5', (root/'easyBackendStyle.php').read_text())
            with zipfile.ZipFile(output/'easybackendstyle.zip') as archive:
                names = archive.namelist()
                self.assertIn('easybackendstyle/vendor/autoload.php', names)
                self.assertFalse(any('/tests/' in p or '/docs/' in p or '/.wordpress-org/' in p or 'private-secret' in p for p in names))
            for locale in ['de_DE','de_DE_formal']:
                self.assertEqual((output/f'translations/plugin-{locale}.po').read_bytes(), (root/f'docs/generated/plugin-{locale}.po').read_bytes())
            with self.assertRaises(ValueError): release.build('../bad', Path(temp)/'unsafe', root)
            (root/'vendor/autoload.php').unlink()
            (root/'vendor/autoload.php').symlink_to(root/'easyBackendStyle.php')
            with self.assertRaises(ValueError): release.build('3.2.6', Path(temp)/'symlink', root)

    def test_required_workflow_gates(self):
        workflow = (ROOT/'.github/workflows/release-deploy.yml').read_text()
        self.assertIn('needs: [verify, package, compatibility]', workflow)
        self.assertIn("if: '!github.event.release.prerelease'", workflow)
        self.assertNotIn('git push', workflow)
        self.assertNotIn('types: [published, released, edited]', workflow)
        self.assertIn('plugin-deploy.yml@', workflow)
        self.assertIn("release_targets: '[\"wordpress-org\"]'", workflow)
        self.assertFalse((ROOT/'.github/workflows/marketplace.yml').exists())

if __name__ == '__main__': unittest.main()
