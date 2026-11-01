#!/usr/bin/env python3
"""Build one verified WordPress.org payload; never edit the source checkout."""
import argparse
import hashlib
import json
from pathlib import Path
import re
import shutil
import subprocess
import zipfile

ROOT = Path(__file__).resolve().parents[1]
SLUG = 'easybackendstyle'
MAIN = 'easyBackendStyle.php'


def header(text, name):
    values = re.findall(r'^' + re.escape(name) + r':\s*(.+)$', text, re.M)
    if len(values) != 1:
        raise ValueError('Expected one ' + name)
    return values[0].strip()


def metadata(root=ROOT, wordpress=None, stable=False):
    main = (root / MAIN).read_text()
    readme = (root / 'README.txt').read_text()
    blueprint = json.loads((root / 'blueprint.json').read_text())
    public = json.loads((root / '.wordpress-org/blueprints/blueprint.json').read_text())
    assert blueprint['preferredVersions'] == public['preferredVersions'], 'Preview runtimes differ'
    assert blueprint['landingPage'] == public['landingPage'], 'Preview landing pages differ'
    minimum = header(main, 'Requires PHP')
    assert minimum == header(readme, 'Requires PHP') == '8.2'
    assert header(main, 'Requires at least') == header(readme, 'Requires at least')
    assert json.loads((root / 'composer.json').read_text())['require']['php'] == '>=' + minimum
    assert blueprint['preferredVersions']['php'] == '8.5'
    assert blueprint['preferredVersions']['wp'] == 'latest'
    assert any(s.get('pluginPath') == SLUG + '/' + MAIN for s in blueprint['steps']), 'Wrong preview plugin filename'
    if wordpress:
        assert re.fullmatch(r'\d+\.\d+(?:\.\d+)?', wordpress), 'Stable WordPress version required'
    if stable:
        assert wordpress, 'Resolve latest WordPress before releasing'
        tested = header(readme, 'Tested up to')
        # WordPress.org normally declares a major.minor, not a maintenance patch.
        assert tested == wordpress or tested == '.'.join(wordpress.split('.')[:2]), f'Tested up to {tested} does not cover WordPress {wordpress}; test and update deliberately'
    return {'minimum_php': minimum, 'preview_php': '8.5', 'wordpress': wordpress}


def build(version, output, root=ROOT):
    version = version.removeprefix('v')
    if not re.fullmatch(r'\d+\.\d+\.\d+(?:-[A-Za-z0-9.-]+)?', version):
        raise ValueError('Expected semantic release version')
    metadata(root)
    output = output.resolve()
    if output.exists():
        raise ValueError('Output must be a fresh directory')
    output.mkdir(parents=True)
    payload = output / SLUG
    payload.mkdir()
    # An allowlist prevents build tools, private checkout state and test fixtures
    # from leaking into the WordPress.org ZIP. Runtime dependency licenses stay.
    for name in [MAIN, 'README.txt', 'LICENCE.md', 'index.php', 'src', 'resources', 'vendor']:
        source = root / name
        if not source.exists():
            raise ValueError('Missing runtime path: ' + name)
        entries = source.rglob('*') if source.is_dir() else [source]
        for file in entries:
            if file.is_symlink():
                raise ValueError('Symlink in runtime package: ' + str(file))
            if not file.is_file(): continue
            rel = file.relative_to(root)
            if file.name in ('.DS_Store', '.git') or '__pycache__' in rel.parts: continue
            target = payload / rel
            target.parent.mkdir(parents=True, exist_ok=True)
            shutil.copyfile(file, target)
    for name, field in [(MAIN, 'Version'), ('README.txt', 'Stable tag')]:
        file = payload / name
        text, count = re.subn(r'^' + field + r':[^\r\n]*', field + ': ' + version, file.read_text(), flags=re.M)
        assert count == 1
        file.write_text(text)
    assert (payload / 'vendor/autoload.php').is_file()
    imports = output / 'translations'
    imports.mkdir()
    for locale in ('de_DE', 'de_DE_formal'):
        shutil.copyfile(root / f'docs/generated/plugin-{locale}.po', imports / f'plugin-{locale}.po')
    with zipfile.ZipFile(output / (SLUG + '.zip'), 'w', zipfile.ZIP_DEFLATED) as archive:
        for file in sorted(payload.rglob('*')):
            if file.is_file(): archive.write(file, SLUG + '/' + file.relative_to(payload).as_posix())
    with zipfile.ZipFile(output / (SLUG + '.zip')) as archive:
        assert archive.testzip() is None
        (output / 'package-manifest.txt').write_text('\n'.join(archive.namelist()) + '\n')
    files = [output / (SLUG + '.zip'), *sorted(imports.glob('*.po'))]
    (output / 'SHA256SUMS').write_text(''.join(hashlib.sha256(p.read_bytes()).hexdigest() + '  ' + p.relative_to(output).as_posix() + '\n' for p in files))
    return payload


def check_package(package):
    for file in package.rglob('*.php'):
        result = subprocess.run(['php', '-l', str(file)], text=True, capture_output=True)
        if result.returncode: raise RuntimeError(result.stdout + result.stderr)
    subprocess.run(['php', '-r', 'require $argv[1];', str(package / 'vendor/autoload.php')], check=True)
    assert header((package / MAIN).read_text(), 'Version') == header((package / 'README.txt').read_text(), 'Stable tag')
    for locale in ('de_DE', 'de_DE_formal'):
        for extension in ('po', 'mo', 'l10n.php'):
            assert (package / f'src/languages/easybackendstyle-{locale}.{extension}').is_file()
        assert len(list((package / 'src/languages').glob(f'easybackendstyle-{locale}-*.json'))) == 2


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--version')
    parser.add_argument('--output', type=Path, default=ROOT / 'output')
    parser.add_argument('--package', type=Path)
    parser.add_argument('--wordpress')
    parser.add_argument('--stable', action='store_true')
    args = parser.parse_args()
    print(json.dumps(metadata(wordpress=args.wordpress, stable=args.stable)))
    if args.version: build(args.version, args.output)
    if args.package: check_package(args.package.resolve())
