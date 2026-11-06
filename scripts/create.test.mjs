import assert from 'node:assert/strict';
import { spawnSync } from 'node:child_process';
import { existsSync, mkdirSync, mkdtempSync, readFileSync, readdirSync, realpathSync, rmSync, symlinkSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { basename, dirname, resolve } from 'node:path';
import { fileURLToPath, pathToFileURL } from 'node:url';
import test from 'node:test';
import { createProject, main } from './create.mjs';
import { loadEnvironment } from './environment.mjs';
import { identity, identityFor } from './identity.mjs';

function fixture(t) {
  const parent = realpathSync(tmpdir());
  const directory = mkdtempSync(resolve(parent, 'wp-generator-test-'));
  t.after(() => {
    const target = realpathSync(directory);
    assert.equal(dirname(target), parent);
    assert.ok(basename(target).startsWith('wp-generator-test-'));
    rmSync(target, { recursive: true, force: true });
  });
  return directory;
}

test('brand generates consistent components, tooling and instructions usable as another template', (t) => {
  const directory = fixture(t);
  const target = resolve(directory, 'branded');
  const brand = 'bonniestar-art';
  const next = identityFor(brand);
  createProject(target, { brand, skipSetup: true, run: () => {} });
  const content = (path) => readFileSync(resolve(target, path), 'utf8');
  assert.match(content(`wp-content/themes/${next.theme}/style.css`), /Theme Name: Bonniestar Art Theme/);
  assert.match(content(`wp-content/themes/${next.theme}/functions.php`), /bonniestar_art_theme_enqueue_styles/);
  assert.match(content(`wp-content/plugins/${next.plugin}/${next.plugin}.php`), /namespace Bonniestar_Art_Plugin;/);
  assert.equal(loadEnvironment(resolve(target, '.env')).WP_TITLE, 'Bonniestar Art');
  assert.equal(JSON.parse(content('package.json')).starterIdentity.brand, brand);
  for (const path of ['compose.yaml', 'phpcs.xml.dist', '.gitignore', 'AGENTS.md', 'README.md', '.agents/skills/wp-theme-scaffold/SKILL.md', '.agents/skills/wp-plugin-scaffold/SKILL.md']) {
    assert.ok(content(path).includes(path.includes('theme-scaffold') ? next.theme : path.includes('plugin-scaffold') ? next.plugin : next.theme), path);
  }
  if (identity.theme !== next.theme) {
    assert.ok(!existsSync(resolve(target, `wp-content/themes/${identity.theme}`)));
    assert.ok(!content('compose.yaml').includes(identity.theme));
  }
  for (const args of [['scripts/dev.mjs', 'verify-static'], ['--test', 'scripts/workflow.test.mjs']]) {
    const result = spawnSync(process.execPath, args, { cwd: target, encoding: 'utf8', shell: false });
    assert.equal(result.status, 0, result.stdout + result.stderr);
  }
  // Generated projects can generate another identity without rewriting JS source.
  const clone = resolve(directory, 'another');
  // Eval has no argv[1], so importing the generated module cannot trigger its CLI.
  // These are JavaScript string literals, executed with shell:false.
  const result = spawnSync(process.execPath, ['--input-type=module', '-e',
    `import {createProject} from ${JSON.stringify(pathToFileURL(resolve(target, 'scripts/create.mjs')).href)}; createProject(${JSON.stringify(clone)}, {brand:"another-site", skipSetup:true, run:()=>{}});`], { encoding: 'utf8', shell: false });
  assert.equal(result.status, 0, result.stdout + result.stderr);
  assert.ok(existsSync(resolve(clone, 'wp-content/plugins/another-site-plugin/another-site-plugin.php')));
  const verified = spawnSync(process.execPath, ['scripts/dev.mjs', 'verify-static'], { cwd: clone, encoding: 'utf8', shell: false });
  assert.equal(verified.status, 0, verified.stdout + verified.stderr);
});

test('invalid brands and branded destination collisions fail before copying', (t) => {
  const directory = fixture(t);
  const target = resolve(directory, 'new');
  for (const brand of ['', '../other', 'Uppercase', 'two--words', 'with_space', '123-name', 'a'.repeat(49)]) {
    assert.throws(() => createProject(target, { brand }), /brand/);
  }
  assert.throws(() => main([target, '--brand']), /option/);
  assert.throws(() => main([target, '--brand=one', '--brand=two']), /option/);
  assert.ok(!existsSync(target));
  const existing = resolve(directory, 'wp-content/themes/bonniestar-art-theme');
  mkdirSync(existing, { recursive: true });
  writeFileSync(resolve(existing, 'style.css'), 'keep');
  assert.throws(() => createProject(directory, { brand: 'bonniestar-art' }), /conflicts[\s\S]*bonniestar-art-theme/);
  assert.ok(!existsSync(resolve(directory, 'package.json')));
  assert.equal(readFileSync(resolve(existing, 'style.css'), 'utf8'), 'keep');
});

test('generated projects contain reusable source and independent environments, without local artifacts', (t) => {
  const parent = fixture(t);
  const calls = [];
  const first = createProject(resolve(parent, 'site with spaces'), { port: '8091', run: (args) => calls.push(args) });
  const second = createProject(resolve(parent, 'second'), { skipSetup: true, run: () => {} });
  for (const path of ['.agents/skills/wp-project-init/SKILL.md', '.env.example', 'composer.lock', 'scripts/create.test.mjs', `wp-content/themes/${identity.theme}/theme.json`]) {
    assert.ok(existsSync(resolve(first, path)), path);
  }
  for (const path of ['.git', 'vendor', '.cache', '.env.verify', 'CONTEXT.md', 'VERIFICATION.md']) {
    assert.ok(!existsSync(resolve(first, path)), path);
  }
  const a = loadEnvironment(resolve(first, '.env'));
  const b = loadEnvironment(resolve(second, '.env'));
  assert.equal(a.WP_URL, 'http://localhost:8091');
  assert.equal(a.HTTP_PORT, '8091');
  assert.notEqual(a.COMPOSE_PROJECT_NAME, b.COMPOSE_PROJECT_NAME);
  assert.notEqual(a.DB_PASSWORD, b.DB_PASSWORD);
  assert.notEqual(a.WP_ADMIN_PASSWORD, b.WP_ADMIN_PASSWORD);
  assert.deepEqual(calls.map((args) => args.at(-1)), ['verify-static', 'scripts/create.test.mjs', 'setup', 'verify']);
});

test('existing repositories and empty directories are supported without changing Git or unrelated files', (t) => {
  const directory = fixture(t);
  mkdirSync(resolve(directory, '.git'));
  writeFileSync(resolve(directory, '.git/config'), '[core]\n\tbare = false\n');
  const config = readFileSync(resolve(directory, '.git/config'), 'utf8');
  writeFileSync(resolve(directory, 'keep.txt'), 'keep');
  createProject(directory, { skipSetup: true, run: () => {} });
  assert.equal(readFileSync(resolve(directory, '.git/config'), 'utf8'), config);
  assert.equal(readFileSync(resolve(directory, 'keep.txt'), 'utf8'), 'keep');
  assert.ok(existsSync(resolve(directory, 'package.json')));
  const empty = fixture(t);
  createProject(empty, { skipSetup: true, run: () => {} });
  assert.ok(existsSync(resolve(empty, '.env')));
});

test('conflicts are reported before any files or credentials are created', (t) => {
  const directory = fixture(t);
  writeFileSync(resolve(directory, 'README.md'), 'my repository');
  writeFileSync(resolve(directory, 'package.json'), '{}');
  const before = readdirSync(directory);
  assert.throws(() => createProject(directory), /README.md[\s\S]*package.json/);
  assert.deepEqual(readdirSync(directory), before);
  assert.equal(readFileSync(resolve(directory, 'README.md'), 'utf8'), 'my repository');
  const envOnly = fixture(t);
  writeFileSync(resolve(envOnly, '.env'), 'private existing config');
  assert.throws(() => createProject(envOnly), /conflicts[\s\S]*\.env/);
  assert.deepEqual(readdirSync(envOnly), ['.env']);
  assert.equal(readFileSync(resolve(envOnly, '.env'), 'utf8'), 'private existing config');
});

test('blocked directories and destination links are rejected without following links', (t) => {
  const directory = fixture(t);
  writeFileSync(resolve(directory, 'scripts'), 'existing file');
  writeFileSync(resolve(directory, 'wp-content'), 'existing content');
  assert.throws(() => createProject(directory), /conflicts[\s\S]*scripts/);
  assert.deepEqual(readdirSync(directory), ['scripts', 'wp-content']);
  const linked = fixture(t);
  const external = fixture(t);
  symlinkSync(external, resolve(linked, 'scripts'), process.platform === 'win32' ? 'junction' : 'dir');
  assert.throws(() => createProject(linked), /conflicts[\s\S]*scripts/);
  assert.throws(() => createProject(resolve(linked, 'scripts')), /symbolic link/);
  assert.deepEqual(readdirSync(external), []);
});

test('invalid options and destinations inside the source are rejected without modifying files', (t) => {
  const directory = fixture(t);
  const marker = resolve(directory, 'keep.txt');
  writeFileSync(marker, 'keep');
  assert.equal(readFileSync(marker, 'utf8'), 'keep');
  const target = resolve(directory, 'new');
  assert.throws(() => createProject(target, { port: '80' }), /port/);
  assert.throws(() => main([target, '--unknown']), /option/);
  assert.throws(() => main([target, '--port']), /option/);
  assert.ok(!existsSync(target));
  const root = fileURLToPath(new URL('../', import.meta.url));
  assert.throws(() => createProject(resolve(root, 'generated-project-test')), /outside/);
});

test('skip setup avoids Docker; a failed setup preserves files and explains recovery', (t) => {
  const directory = fixture(t);
  const calls = [];
  createProject(resolve(directory, 'offline'), { skipSetup: true, run: (args) => calls.push(args) });
  assert.equal(calls.length, 2);
  const failed = resolve(directory, 'failed');
  assert.throws(() => createProject(failed, { run: (args) => {
    if (args.includes('setup')) throw new Error('Docker unavailable');
  } }), /retained.*[\s\S]*npm run setup/);
  assert.ok(existsSync(resolve(failed, '.env')));
  assert.ok(existsSync(resolve(failed, 'compose.yaml')));
});
