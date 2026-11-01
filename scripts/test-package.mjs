import { spawn } from 'node:child_process';
import { resolve, join } from 'node:path';
const root = resolve(import.meta.dirname, '..');
const archive = resolve(process.argv[2] || 'output/easybackendstyle.zip');
// Each scenario gets an independent process and SQLite database. This also
// isolates Playground's HTTP proxy and PHP worker lifecycle between scenarios.
for (const scenario of ['fresh', 'existing', 'preview']) {
  await new Promise((done, fail) => {
    const child = spawn(process.execPath, [join(root,'scripts/test-package-case.mjs'),archive,scenario], {stdio:'inherit'});
    const timeout = setTimeout(() => child.kill('SIGKILL'),240000);
    child.on('error', error => {clearTimeout(timeout); fail(error);});
    child.on('close', code => {clearTimeout(timeout); if (code === 0) done(); else fail(new Error(`Package ${scenario} failed (${code})`));});
  });
}

await new Promise((done, fail) => {
  const child = spawn(process.execPath, [join(root,'scripts/test-hardening.mjs'),archive], {stdio:'inherit'});
  const timeout = setTimeout(() => child.kill('SIGKILL'),240000);
  child.on('error', error => {clearTimeout(timeout); fail(error);});
  child.on('close', code => {clearTimeout(timeout); code === 0 ? done() : fail(new Error(`Hardening checks failed (${code})`));});
});

// Keep the active WordPress 6 compatibility branch covered as well.
await new Promise((done, fail) => {
  const child = spawn(process.execPath, [join(root,'scripts/test-hardening.mjs'),archive,'--legacy'], {stdio:'inherit',env:{...process.env,FARN_TEST_WP_VERSION:'6.9'}});
  const timeout = setTimeout(() => child.kill('SIGKILL'),240000);
  child.on('error', error => {clearTimeout(timeout); fail(error);});
  child.on('close', code => {clearTimeout(timeout); code === 0 ? done() : fail(new Error(`Legacy checks failed (${code})`));});
});
