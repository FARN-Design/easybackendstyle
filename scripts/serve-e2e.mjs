import { readFileSync, writeFileSync, mkdtempSync, rmSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join, resolve } from 'node:path';
import { spawn } from 'node:child_process';
const root = resolve(import.meta.dirname, '..');
const php = process.env.FARN_TEST_PHP_VERSION || '8.5';
if (!['8.2', '8.3', '8.4', '8.5'].includes(php)) throw new Error('Unsupported test PHP');
const blueprint = JSON.parse(readFileSync(join(root, 'blueprint.json'), 'utf8'));
blueprint.preferredVersions.php = php;
if (process.env.FARN_TEST_WP_VERSION) blueprint.preferredVersions.wp = process.env.FARN_TEST_WP_VERSION;
blueprint.steps = blueprint.steps.filter(step => step.step !== 'login');
blueprint.steps.unshift({step:'defineWpConfigConsts', consts:{DISABLE_WP_CRON:true}});
blueprint.steps.push({step:'runPHP', code:`<?php if (PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION !== '${php}') throw new RuntimeException('Wrong browser PHP');`});
const directory = mkdtempSync(join(tmpdir(), 'ebs-browser-'));
writeFileSync(join(directory, 'blueprint.json'), JSON.stringify(blueprint));
const child = spawn(process.execPath, [join(root,'node_modules/@wp-playground/cli/cli.js'), 'server', '--debug', '--php='+php, '--wp='+blueprint.preferredVersions.wp,
  '--blueprint='+join(directory,'blueprint.json'), '--port=8081',
  '--mount='+root+':/wordpress/wp-content/plugins/easybackendstyle'], {stdio:'inherit'});
for (const signal of ['SIGINT','SIGTERM']) process.on(signal, () => child.kill(signal));
child.on('exit', code => {rmSync(directory,{recursive:true,force:true});process.exit(code ?? 1);});
