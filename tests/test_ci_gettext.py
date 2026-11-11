"""Exercise mirror failures without installing packages or requiring sudo."""
import os
from pathlib import Path
import subprocess
import shutil
import tempfile
import unittest

SCRIPT = Path(__file__).resolve().parents[1] / 'scripts/install-ci-gettext.sh'


class GettextInstallerTests(unittest.TestCase):
    def test_mirror_paths(self):
        for scenario, expected, count in [
            ('installed', 0, 0), ('normal', 0, 3), ('dns-timeout', 0, 4),
            ('download-fails', 0, 5), ('all-fail', 1, 2), ('install-fails', 100, 3),
        ]:
            with self.subTest(scenario=scenario), tempfile.TemporaryDirectory() as tmp:
                root = Path(tmp)
                bin_dir = root / 'bin'
                bin_dir.mkdir()

                def executable(name, source):
                    path = bin_dir / name
                    path.write_text(source)
                    path.chmod(0o755)

                executable('sudo', '#!/bin/sh\nexec "$@"\n')
                executable('dpkg', '#!/bin/sh\necho amd64\n')
                executable('timeout', '''#!/bin/bash
set -eu
[[ "$1" == --kill-after=5s ]]
[[ "$2" == 60s || "$2" == 45s ]]
shift 2
export BOUNDED=yes
exec "$@"
''')
                executable('apt-get', '''#!/bin/bash
set -eu
echo "$*" >> "$TEST_ROOT/calls"
[[ "$*" == *Acquire::Retries=0* && "$*" == *Dir::Etc::sourceparts=-* ]]
for arg in "$@"; do
  case "$arg" in
    Dir::Etc::sourcelist=*)
      sources=$(cat "${arg#*=}")
      [[ "$sources" == *signed-by=/usr/share/keyrings/ubuntu-archive-keyring.gpg* ]]
      [[ "$sources" != *azure* && "$sources" != *security.ubuntu.com* ]]
      ;;
  esac
done
if [[ "$*" == *--no-download* ]]; then
  [[ -z "${BOUNDED:-}" ]]
  [[ "$TEST_SCENARIO" != install-fails ]] || exit 100
  for tool in msgmerge msgcmp msgfmt msgattrib; do
    echo '#!/bin/sh' > "$TEST_ROOT/bin/$tool"
    chmod +x "$TEST_ROOT/bin/$tool"
  done
  exit 0
fi
[[ "${BOUNDED:-}" == yes ]]
[[ "$TEST_SCENARIO" != all-fail ]] || exit 124
if [[ "$sources" == *mirrors.edge.kernel.org* ]]; then
  [[ "$TEST_SCENARIO" != dns-timeout ]] || exit 124
  if [[ "$TEST_SCENARIO" == download-fails && "$*" == *--download-only* ]]; then exit 100; fi
fi
''')
                if scenario == 'installed':
                    for tool in ['msgmerge', 'msgcmp', 'msgfmt', 'msgattrib']:
                        executable(tool, '#!/bin/sh\nexit 0\n')
                (root / 'os-release').write_text('ID=ubuntu\nVERSION_CODENAME=noble\n')
                # Hermetic PATH prevents host gettext installations changing the scenario.
                for name in ['bash', 'cat', 'chmod', 'mkdir', 'mktemp', 'rm']:
                    (bin_dir / name).symlink_to(shutil.which(name))
                script = root / 'run.sh'
                script.write_text(SCRIPT.read_text().replace('/etc/os-release', str(root / 'os-release')))
                env = {**os.environ, 'PATH': str(bin_dir), 'TEST_ROOT': tmp, 'TEST_SCENARIO': scenario}
                result = subprocess.run([str(bin_dir / 'bash'), str(script)], env=env, text=True, capture_output=True, timeout=10)
                self.assertEqual(result.returncode, expected, result.stdout + result.stderr)
                calls = (root / 'calls').read_text() if (root / 'calls').exists() else ''
                self.assertEqual(len(calls.splitlines()), count, calls)
                if scenario in ['dns-timeout', 'download-fails']:
                    self.assertIn('lists-2', calls)


if __name__ == '__main__':
    unittest.main()
