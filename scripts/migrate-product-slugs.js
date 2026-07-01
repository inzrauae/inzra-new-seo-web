/**
 * migrate-product-slugs.js
 *
 * One-time migration script:
 *   1. Generates a URL-friendly slug from each product's title
 *   2. Adds the slug field to products-data.json
 *   3. Renames products/{itemNumber}.html → products/{slug}.html
 *   4. Updates canonical, og:url, and JSON-LD urls inside each HTML file
 *   5. Rewrites sitemap.xml product entries to use slug URLs
 *
 * Run once from the workspace root:
 *   node scripts/migrate-product-slugs.js
 */

const fs = require('fs');
const path = require('path');

const root = process.cwd();
const dataPath = path.join(root, 'products-data.json');
const productsDir = path.join(root, 'products');
const sitemapPath = path.join(root, 'sitemap.xml');
const siteOrigin = 'https://inzra.com';

function slugify(title) {
  return title
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '')
    .replace(/-+/g, '-')
    .substring(0, 72)
    .replace(/-$/, '');
}

// Build unique slugs across all products
const rawData = fs.readFileSync(dataPath, 'utf8').replace(/^\uFEFF/, '');
const data = JSON.parse(rawData);

const usedSlugs = {};
for (const product of data) {
  const base = slugify(String(product.title || product.itemNumber));
  let slug = base;
  let count = 2;
  while (usedSlugs[slug]) {
    slug = `${base}-${count}`;
    count++;
  }
  usedSlugs[slug] = true;
  product.slug = slug;
}

// Rename HTML files and update internal URLs
let renamedCount = 0;
let skippedCount = 0;
const sitemapReplacements = [];

for (const product of data) {
  const item = String(product.itemNumber || '').trim();
  const slug = product.slug;
  if (!item || !slug) continue;

  const oldPath = path.join(productsDir, `${item}.html`);
  const newPath = path.join(productsDir, `${slug}.html`);
  const oldUrl = `${siteOrigin}/products/${encodeURIComponent(item)}.html`;
  const newUrl = `${siteOrigin}/products/${slug}.html`;

  if (!fs.existsSync(oldPath)) {
    skippedCount++;
    continue;
  }

  let html = fs.readFileSync(oldPath, 'utf8');

  // Update all occurrences of the old numeric URL to the new slug URL
  const escapedOld = oldUrl.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  html = html.replace(new RegExp(escapedOld, 'g'), newUrl);

  // Also catch unencoded variant (e.g. 145830639800.html)
  const plainOld = `${siteOrigin}/products/${item}.html`;
  if (plainOld !== oldUrl) {
    const escapedPlain = plainOld.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    html = html.replace(new RegExp(escapedPlain, 'g'), newUrl);
  }

  fs.writeFileSync(newPath, html, 'utf8');
  fs.unlinkSync(oldPath);
  renamedCount++;

  sitemapReplacements.push({ oldUrl, newUrl, plainOld });
}

// Write updated products-data.json
fs.writeFileSync(dataPath, JSON.stringify(data, null, 4) + '\n', 'utf8');
console.log(`Added slugs to products-data.json`);
console.log(`Renamed ${renamedCount} product HTML files`);
if (skippedCount > 0) console.log(`Skipped ${skippedCount} (file not found)`);

// Update sitemap.xml
if (fs.existsSync(sitemapPath)) {
  let sitemap = fs.readFileSync(sitemapPath, 'utf8');
  for (const { oldUrl, newUrl, plainOld } of sitemapReplacements) {
    sitemap = sitemap.split(oldUrl).join(newUrl);
    sitemap = sitemap.split(plainOld).join(newUrl);
  }
  fs.writeFileSync(sitemapPath, sitemap, 'utf8');
  console.log(`Updated sitemap.xml`);
}

console.log('Migration complete.');
