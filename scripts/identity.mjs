import { readFileSync } from 'node:fs';

export function identityFor(brand) {
  if (brand !== undefined && (typeof brand !== 'string' || !/^[a-z][a-z0-9]*(?:-[a-z0-9]+)*$/.test(brand) || brand.length > 48)) {
    throw new Error('--brand must contain 1–48 lowercase letters, digits or single hyphens, starting with a letter (example: bonniestar-art).');
  }
  const base = brand ?? 'starter';
  const words = base.split('-').map((word) => word[0].toUpperCase() + word.slice(1));
  return {
    brand,
    theme: `${base}-theme`,
    plugin: `${base}-plugin`,
    prefix: `${base.replaceAll('-', '_')}_theme_`,
    namespace: `${words.join('_')}_Plugin`,
    themeName: `${words.join(' ')} Theme`,
    pluginName: `${words.join(' ')} Plugin`,
    themePackage: `${words.join('')}Theme`,
    pluginPackage: `${words.join('')}Plugin`,
    title: brand === undefined ? 'WordPress Starter' : words.join(' '),
  };
}

const manifest = JSON.parse(readFileSync(new URL('../package.json', import.meta.url), 'utf8'));
export const identity = identityFor(manifest.starterIdentity?.brand);

// Transform presentation, PHP, configuration and documentation, never JS source.
export function identityTransform(next) {
  const fields = ['theme', 'plugin', 'prefix', 'namespace', 'themeName', 'pluginName', 'themePackage', 'pluginPackage', 'title'];
  const replacements = new Map(fields.map((field) => [identity[field], next[field]]));
  const pattern = new RegExp([...replacements.keys()].sort((a, b) => b.length - a.length)
    .map((value) => value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')).join('|'), 'g');
  return (source) => source.replace(pattern, (match) => replacements.get(match));
}
