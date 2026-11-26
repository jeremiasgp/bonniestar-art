import { spawnSync } from 'node:child_process';
import { createHash } from 'node:crypto';
import { closeSync, mkdirSync, mkdtempSync, openSync, readFileSync, writeFileSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { gzipSync } from 'node:zlib';
import { context } from './dev.mjs';
import { identity } from './identity.mjs';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const tool = `/var/www/html/wp-content/plugins/${identity.plugin}/tools/`;

// Output files are written directly: media archives never pass through text encoding.
function docker(args, { capture = false, input, env = {}, output } = {}) {
  const descriptor = output ? openSync(output, 'wx', 0o600) : undefined;
  let result;
  try {
    result = spawnSync('docker', args, {
      cwd: root, shell: false, windowsHide: true, encoding: 'utf8',
      maxBuffer: 32 * 1024 * 1024,
      env: { ...process.env, ...env }, input,
      stdio: [input === undefined ? 'ignore' : 'pipe', descriptor ?? (capture ? 'pipe' : 'inherit'), 'pipe'],
    });
  } finally { if (descriptor !== undefined) closeSync(descriptor); }
  if (result.error || result.status !== 0) {
    // Do not echo a command or environment: they can contain private configuration.
    throw new Error(result.error?.message || result.stderr?.trim() || 'Docker release command failed. Start the local site with npm run start.');
  }
  return result;
}

function directory(label) {
  const parent = resolve(root, '.cache/releases');
  mkdirSync(parent, { recursive: true, mode: 0o700 });
  return mkdtempSync(resolve(parent, `${label}-${new Date().toISOString().replaceAll(':', '-')}-`));
}

function inventory(ctx) {
  return JSON.parse(ctx.wp(['eval-file', `${tool}release-inventory.php`, '--quiet'], { capture: true }).stdout);
}

function archive(ctx, target, paths, exclude = []) {
  ctx.compose(['exec', '-T', 'wordpress', 'tar', ...exclude.map(pattern => `--exclude=${pattern}`),
    '-czf', '-', '-C', '/var/www/html', ...paths], { output: target });
}

function backup(ctx) {
  const target = directory('before-webp');
  const database = resolve(target, 'database-local.sql');
  ctx.wp(['db', 'export', '-', '--add-drop-table'], { output: database });
  if (!readFileSync(database).includes(Buffer.from('CREATE TABLE'))) throw new Error('The backup database export is incomplete; images were not changed.');
  archive(ctx, resolve(target, 'uploads-originals.tar.gz'), ['wp-content/uploads']);
  writeFileSync(resolve(target, 'inventory.json'), JSON.stringify(inventory(ctx), null, 2), { mode: 0o600 });
  console.log(`Original database and uploads backed up: ${target}`);
  return target;
}

function mediaArchives(ctx, destination) {
  const listing = ctx.compose(['exec', '-T', 'wordpress', 'find', '/var/www/html/wp-content/uploads', '-type', 'f', '-printf', '%s\\t%P\\0'], {capture: true}).stdout;
  const groups = [[]];
  let bytes = 0;
  for (const entry of listing.split('\0').filter(Boolean).sort()) {
    const separator = entry.indexOf('\t');
    const size = Number(entry.slice(0, separator));
    const name = entry.slice(separator + 1);
    if (!Number.isSafeInteger(size) || size < 0 || name.startsWith('/') || name.split('/').includes('..')) throw new Error('Invalid upload inventory.');
    if (name.endsWith('.log') || name.endsWith('.DS_Store')) continue;
    if (bytes + size > 100 * 1024 * 1024 && groups.at(-1).length) { groups.push([]); bytes = 0; }
    groups.at(-1).push(`wp-content/uploads/${name}`);
    bytes += size;
  }
  return groups.filter(group => group.length).map((group, index) => {
    const name = `media-${String(index + 1).padStart(2, '0')}.tar.gz`;
    ctx.compose(['exec', '-T', 'wordpress', 'tar', '-czf', '-', '-C', '/var/www/html', '--null', '--verbatim-files-from', '-T', '-'],
      {output: resolve(destination, name), input: Buffer.from(`${group.join('\0')}\0`)});
    return name;
  });
}

function codePaths(info) {
  if (info.theme !== identity.theme) throw new Error('Unexpected active theme; review the release inventory.');
  const plugins = info.plugins.map(({file}) => {
    if (!/^[a-z0-9_-]+\/[a-z0-9_.-]+\.php$/.test(file)) throw new Error('Review unexpected active plugin path.');
    return `wp-content/plugins/${file.split('/')[0]}`;
  });
  return [`wp-content/themes/${identity.theme}`, ...plugins];
}

const [command, ...options] = process.argv.slice(2);
try {
  const ctx = context(resolve(root, '.env'), docker);
  if (['audit', 'optimize'].includes(command)) {
    if (options.length) throw new Error('Unexpected media command arguments.');
    const destination = command === 'optimize' ? backup(ctx) : undefined;
    const report = JSON.parse(ctx.wp(['eval-file', `${tool}optimize-images.php`, ...(destination ? ['apply'] : []), '--quiet'], { capture: true }).stdout);
    if (destination) writeFileSync(resolve(destination, 'webp-report.json'), JSON.stringify(report, null, 2), { mode: 0o600 });
    console.log(JSON.stringify({ ...report, files: `${report.files.length} image versions; full report kept beside backup` }, null, 2));
  } else if (['prepare', 'code'].includes(command)) {
    if (options.length !== 2 || options[0] !== '--url') throw new Error('Use --url https://bonniestar.art');
    const targetUrl = new URL(options[1]);
    if (targetUrl.protocol !== 'https:' || targetUrl.username || targetUrl.password || targetUrl.search || targetUrl.hash || targetUrl.pathname !== '/' || targetUrl.port) {
      throw new Error('Use an HTTPS origin without credentials, path or port.');
    }
    const destination = directory(command === 'prepare' ? 'hostinger' : 'code');
    const before = inventory(ctx);
    if (before.home !== ctx.values.WP_URL || before.siteurl !== ctx.values.WP_URL) throw new Error('Local URLs do not match .env; review before export.');
    const paths = codePaths(before);
    archive(ctx, resolve(destination, 'site-files.tar.gz'), paths, ['*/tests', '*/tools', '*/README.md', '*/debug.log', '*/.DS_Store']);
    const media = command === 'prepare' ? mediaArchives(ctx, destination) : [];
    if (command === 'prepare') {
      const sqlPath = resolve(destination, 'database-production.sql');
      // --export transforms serialized data in the dump, leaving the local database intact.
      ctx.wp(['search-replace', before.home, targetUrl.origin, '--all-tables-with-prefix', '--skip-columns=guid', '--precise', '--export', '--no-report'], { output: sqlPath });
      const sql = readFileSync(sqlPath);
      if (!sql.includes(Buffer.from(targetUrl.origin)) || !sql.includes(Buffer.from('CREATE TABLE'))) throw new Error('The database export is incomplete.');
      writeFileSync(`${sqlPath}.gz`, gzipSync(sql), { mode: 0o600, flag: 'wx' });
      // Only GUIDs may retain local identifiers; runtime URLs must be production URLs.
      if (JSON.stringify(inventory(ctx)) !== JSON.stringify(before)) throw new Error('Local inventory changed during export; prepare a new snapshot.');
    }
    const files = command === 'prepare' ? ['site-files.tar.gz', ...media, 'database-production.sql', 'database-production.sql.gz'] : ['site-files.tar.gz'];
    const manifest = { createdAt: new Date().toISOString(), target: targetUrl.origin, kind: command, ...before,
      sha256: Object.fromEntries(files.map(file => [file, createHash('sha256').update(readFileSync(resolve(destination, file))).digest('hex')])) };
    writeFileSync(resolve(destination, 'manifest.json'), JSON.stringify(manifest, null, 2), { mode: 0o600, flag: 'wx' });
    writeFileSync(resolve(destination, 'READ-ME.txt'), 'PRIVATE RELEASE: database contains user accounts and password hashes.\nUpload site-files.tar.gz and ALL media-*.tar.gz to File Manager; extract EACH in the site root. Remove archives after extraction.\nImport database-production.sql.gz through phpMyAdmin, never upload SQL to public_html.\nSee docs/PUBLICACION.md in the repository. No production changes have been made.\n', { flag: 'wx', mode: 0o600 });
    console.log(`Prepared locally: ${destination}\nTarget: ${targetUrl.origin}\nNothing uploaded. Local URLs unchanged.`);
  } else throw new Error('Commands: audit | optimize | prepare --url https://bonniestar.art | code --url https://bonniestar.art');
} catch (error) {
  console.error(`ERROR: ${error.message}`);
  process.exitCode = 1;
}
