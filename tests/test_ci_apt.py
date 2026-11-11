"""Test persistent CI sources, including APT calls made by other tools."""
import os
from pathlib import Path
import shutil
import subprocess
import tempfile
import unittest

ROOT = Path(__file__).resolve().parents[1]
SCRIPT = ROOT / 'scripts/configure-ci-apt.sh'


class AptSetupTests(unittest.TestCase):
    def test_persistent_sources_and_failure_paths(self):
        for scenario in ['normal', 'timeout', 'all-fail']:
            with self.subTest(scenario=scenario), tempfile.TemporaryDirectory() as tmp:
                root = Path(tmp)
                bin_dir = root / 'bin'
                bin_dir.mkdir()
                (root / 'etc/apt/apt.conf.d').mkdir(parents=True)
                (root / 'etc/os-release').write_text('ID=ubuntu\nVERSION_CODENAME=noble\n')
                for name in ['bash', 'cat', 'chmod', 'mkdir', 'mktemp', 'rm', 'install']:
                    (bin_dir / name).symlink_to(shutil.which(name))

                def executable(name, source):
                    path = bin_dir / name
                    path.write_text(source)
                    path.chmod(0o755)

                executable('sudo', '#!/bin/sh\nexec "$@"\n')
                executable('dpkg', '#!/bin/sh\necho amd64\n')
                executable('timeout', '''#!/bin/bash
set -eu
[[ "$1" == --kill-after=5s && "$2" == 90s ]]
shift 2
exec "$@"
''')
                executable('apt-get', '''#!/bin/bash
set -eu
config=$(cat "$TEST_ROOT/etc/apt/apt.conf.d/99-farn-ci")
sources=$(cat "$TEST_ROOT/etc/apt/farn-ci.list")
[[ "$config" == *'Dir::Etc::sourceparts "-"'* ]]
[[ "$config" == *lists-farn-ci* ]]
[[ "$sources" == *'main restricted universe multiverse'* ]]
[[ "$sources" == *signed-by=/usr/share/keyrings/ubuntu-archive-keyring.gpg* ]]
[[ "$sources" != *azure* && "$sources" != *security.ubuntu.com* ]]
echo "$*" >> "$TEST_ROOT/calls"
if [[ "$1" == update ]]; then
  [[ "$TEST_SCENARIO" != all-fail ]] || exit 124
  if [[ "$TEST_SCENARIO" == timeout && "$sources" == *mirrors.edge.kernel.org* ]]; then exit 124; fi
fi
''')
                source = SCRIPT.read_text().replace('/etc/os-release', str(root / 'etc/os-release'))
                source = source.replace('/etc/apt/', str(root / 'etc/apt') + '/')
                source = source.replace('/var/lib/apt/', str(root / 'var/lib/apt') + '/')
                script = root / 'run.sh'
                script.write_text(source)
                env = {**os.environ, 'PATH': str(bin_dir), 'TEST_ROOT': tmp, 'TEST_SCENARIO': scenario}
                result = subprocess.run([str(bin_dir / 'bash'), str(script)], env=env, capture_output=True, text=True, timeout=10)
                self.assertEqual(result.returncode, 1 if scenario == 'all-fail' else 0, result.stdout + result.stderr)
                if scenario != 'all-fail':
                    # No source flags: simulate Playwright/SVN invoking sudo apt-get later.
                    for packages in [['xfonts-utils', 'fonts-ipafont-gothic'], ['subversion']]:
                        subprocess.run(['sudo', 'apt-get', 'install', '-y', *packages], env=env, check=True)

    def test_shared_workflow_embeds_same_configuration(self):
        workflow = (ROOT / '.github/workflows/farn-plugin-tests.yml').read_text()
        embedded = ''.join('          ' + line + '\n' for line in SCRIPT.read_text().splitlines()[1:])
        self.assertIn(embedded, workflow)
        self.assertLess(workflow.index(embedded), workflow.index('playwright install --with-deps'))
        release = (ROOT / '.github/workflows/release-deploy.yml').read_text()
        self.assertLess(release.index('bash scripts/configure-ci-apt.sh'), release.index('playwright install --with-deps'))


if __name__ == '__main__':
    unittest.main()
