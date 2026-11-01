#!/usr/bin/env python3
"""Extract and compile WordPress translations; never run on customer websites."""
import argparse
import json
from pathlib import Path
import shutil
import subprocess
import tempfile

parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--wp-cli', default='wp', help='WP-CLI executable or wp-cli.phar')
parser.add_argument('--php', default='php', help='PHP executable used for a PHAR')
parser.add_argument('--refresh-pot', action='store_true', help='Extract current sources and merge PO files without fuzzy guesses; translate missing entries before compiling')
parser.add_argument('--check-source', action='store_true', help='Verify that both PO catalogs cover a fresh source extraction')
args = parser.parse_args()
root = Path(__file__).resolve().parents[1]
languages = root / 'src/languages'
cli = [args.php, '-d', 'error_reporting=22527', args.wp_cli] if args.wp_cli.endswith('.phar') else [args.wp_cli]
locales = ['de_DE', 'de_DE_formal']
exclude = 'vendor,node_modules,tests,output,docs,build'


def run(*arguments):
    subprocess.run(list(arguments), cwd=root, check=True)


with tempfile.TemporaryDirectory(prefix='easybackendstyle-i18n-') as directory:
    temporary = Path(directory)
    if args.refresh_pot or args.check_source:
        pot = temporary / 'easybackendstyle.pot'
        run(*cli, 'i18n', 'make-pot', '.', str(pot), '--domain=easybackendstyle', '--exclude=' + exclude)
        if args.refresh_pot:
            shutil.copyfile(pot, languages / pot.name)
            for locale in locales:
                po = languages / f'easybackendstyle-{locale}.po'
                run('msgmerge', '--update', '--backup=none', '--no-fuzzy-matching', str(po), str(pot))
        else:
            for locale in locales:
                # Reject missing, fuzzy or untranslated entries, including newly added strings.
                run('msgcmp', str(languages / f'easybackendstyle-{locale}.po'), str(pot))
                # Reject stale entries in the committed template as well.
            run('msgcmp', '--use-untranslated', str(languages / 'easybackendstyle.pot'), str(pot))
            run('msgcmp', '--use-untranslated', str(pot), str(languages / 'easybackendstyle.pot'))

    for locale in locales:
        source = languages / f'easybackendstyle-{locale}.po'
        run('msgfmt', '--check', '-o', str(temporary / 'check.mo'), str(source))
        run('msgcmp', str(source), str(languages / 'easybackendstyle.pot'))
        shutil.copyfile(source, temporary / source.name)
    # Map original JSX references to the script actually enqueued by WordPress.
    mapping = {path.relative_to(root).as_posix(): path.relative_to(root).as_posix()
               for path in root.rglob('*.js')
               if not any(part in path.parts for part in ('vendor', 'node_modules'))}
    for command, options in [('make-mo', []), ('make-php', []), ('make-json', ['--no-purge', '--use-map=' + json.dumps(mapping)])]:
        run(*cli, 'i18n', command, str(temporary), *options)
    # Replace generated files only after all compilation steps have succeeded.
    for locale in locales:
        for obsolete in languages.glob(f'easybackendstyle-{locale}-*.json'):
            obsolete.unlink()
    for generated in temporary.iterdir():
        if generated.name.startswith('easybackendstyle-') and generated.suffix in ('.mo', '.php', '.json'):
            shutil.copyfile(generated, languages / generated.name)

# Keep WordPress.org import files separate from runtime and readme catalogs.
# This plugin has one edition: all validated UI strings belong in the export.
exports = root / 'docs/generated'
exports.mkdir(parents=True, exist_ok=True)
for locale in locales:
    run('msgattrib', '--no-obsolete', '--no-fuzzy',
        str(languages / f'easybackendstyle-{locale}.po'),
        '-o', str(exports / f'plugin-{locale}.po'))

print('German informal and formal runtime catalogs and WordPress.org UI imports generated.')
