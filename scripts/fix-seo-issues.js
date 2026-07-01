/**
 * fix-seo-issues.js
 * Adds BreadcrumbList schema + meta author to all 9 Sri Lanka query pages.
 * Also normalises the sitemap with consistent lastmod/changefreq.
 * Run: node scripts/fix-seo-issues.js
 */
const fs = require('fs');
const path = require('path');
const root = process.cwd();

// ── 1. ADD BreadcrumbList + meta author to 9 query pages ─────────────────────
const queryPages = [
  { file: 'seo-consultant-sri-lanka.html', label: 'SEO Consultant Sri Lanka' },
  { file: 'seo-expert-sri-lanka.html',       label: 'SEO Expert Sri Lanka' },
  { file: 'seo-company-in-sri-lanka.html',   label: 'SEO Company in Sri Lanka' },
  { file: 'seo-price-in-sri-lanka.html',     label: 'SEO Price in Sri Lanka' },
  { file: 'seo-consultant-in-sri-lanka.html',label: 'SEO Consultant in Sri Lanka' },
  { file: 'ai-seo-specialist-sri-lanka.html',label: 'AI SEO Specialist Sri Lanka' },
  { file: 'best-seo-specialist-sri-lanka.html', label: 'Best SEO Specialist Sri Lanka' },
  { file: 'seo-services-sri-lanka.html',     label: 'SEO Services Sri Lanka' },
  { file: 'seo-packages-sri-lanka.html',     label: 'SEO Packages Sri Lanka' },
];

for (const { file, label } of queryPages) {
  const fp = path.join(root, file);
  if (!fs.existsSync(fp)) { console.log(`SKIP: ${file}`); continue; }
  let html = fs.readFileSync(fp, 'utf8');
  const url = `https://inzra.com/${file}`;
  let changed = false;

  // Add meta author after twitter:image if not already present
  if (!html.includes('meta name="author"') && !html.includes("meta name='author'")) {
    html = html.replace(
      /<meta name="twitter:image" content="[^"]*">/,
      m => `${m}\n  <meta name="author" content="Buddhika S Weerasekara">`
    );
    changed = true;
  }

  // Add BreadcrumbList before </head> if not already present
  if (!html.includes('"BreadcrumbList"')) {
    const breadcrumb = `  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "BreadcrumbList",
    "itemListElement": [
      { "@type": "ListItem", "position": 1, "name": "Home", "item": "https://inzra.com/" },
      { "@type": "ListItem", "position": 2, "name": "SEO Sri Lanka", "item": "https://inzra.com/seo-sri-lanka.html" },
      { "@type": "ListItem", "position": 3, "name": "${label}", "item": "${url}" }
    ]
  }
  </script>
</head>`;
    html = html.replace('</head>', breadcrumb);
    changed = true;
  }

  // Update dateModified in WebPage schema if present
  if (html.includes('"WebPage"') && !html.includes('"dateModified"')) {
    html = html.replace(
      /"author":\s*\{\s*"@type":\s*"Person"/,
      '"dateModified": "2026-07-01",\n    "author": { "@type": "Person"'
    );
    changed = true;
  }

  if (changed) {
    fs.writeFileSync(fp, html, 'utf8');
    console.log(`Updated: ${file}`);
  } else {
    console.log(`No changes needed: ${file}`);
  }
}

// ── 2. NORMALISE sitemap.xml ──────────────────────────────────────────────────
const sitemapPath = path.join(root, 'sitemap.xml');
let sitemap = fs.readFileSync(sitemapPath, 'utf8');

// Remove the existing content and rebuild a clean version
// First collect all <loc> URLs present
const locMatches = [...sitemap.matchAll(/<loc>(https?:\/\/[^<]+)<\/loc>/g)];
const seen = new Set();
const urls = [];
for (const m of locMatches) {
  const u = m[1].trim();
  if (!seen.has(u)) { seen.add(u); urls.push(u); }
}

// Priority assignment
function getPriority(url) {
  if (url === 'https://inzra.com/') return '1.0';
  if (url.includes('seo-consultant-sri-lanka') || url.includes('seo-expert-sri-lanka') ||
      url.includes('seo-services-sri-lanka') || url.includes('seo-company-in-sri-lanka') ||
      url.includes('seo-packages-sri-lanka') || url.includes('seo-price-in-sri-lanka') ||
      url.includes('seo-consultant-in-sri-lanka') || url.includes('ai-seo-specialist') ||
      url.includes('best-seo-specialist')) return '0.8';
  if (url.includes('seo-sri-lanka')) return '0.9';
  if (url.includes('products.html') || url.includes('about.html') || url.includes('contact.html')) return '0.7';
  if (url.includes('/products/')) return '0.5';
  return '0.6';
}

function getChangefreq(url) {
  if (url === 'https://inzra.com/') return 'weekly';
  if (url.includes('/products/')) return 'monthly';
  return 'monthly';
}

const today = '2026-07-01';
const entries = urls.map(u =>
  `  <url>\n    <loc>${u}</loc>\n    <lastmod>${today}</lastmod>\n    <changefreq>${getChangefreq(u)}</changefreq>\n    <priority>${getPriority(u)}</priority>\n  </url>`
).join('\n');

const newSitemap = `<?xml version="1.0" encoding="UTF-8"?>\n<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n${entries}\n</urlset>\n`;
fs.writeFileSync(sitemapPath, newSitemap, 'utf8');
console.log(`\nSitemap normalised: ${urls.length} URLs.`);
console.log('Done.');
