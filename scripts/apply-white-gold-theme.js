const fs = require('fs');
const path = require('path');
const root = process.cwd();

const targetFiles = [
  'about.html','ai-seo-specialist-sri-lanka.html','best-seo-specialist-sri-lanka.html',
  'checkout.html','contact.html','product-details.html','products-upload.html',
  'products.html','seo-company-in-sri-lanka.html','seo-consultant-in-sri-lanka.html',
  'seo-consultant-sri-lanka.html','seo-expert-sri-lanka.html','seo-packages-sri-lanka.html',
  'seo-price-in-sri-lanka.html','seo-services-sri-lanka.html','seo-sri-lanka.html',
  'scripts/generate-sri-lanka-query-pages.js'
];

const replacements = [
  [
    '.site-header{position:sticky;top:0;z-index:100;background:#0d1225;box-shadow:0 2px 32px rgba(0,0,0,.55);border-bottom:1px solid rgba(255,255,255,.06);}',
    '.site-header{position:sticky;top:0;z-index:100;background:#ffffff;box-shadow:0 2px 20px rgba(0,0,0,.08);border-bottom:2px solid #d4a017;}'
  ],
  [
    '.site-nav-links a{color:rgba(255,255,255,.8);text-decoration:none;font-size:.88rem;font-weight:600;letter-spacing:.01em;transition:color .18s;}',
    '.site-nav-links a{color:#374151;text-decoration:none;font-size:.88rem;font-weight:600;letter-spacing:.01em;transition:color .18s;}'
  ],
  [
    '.site-nav-links a:hover,.site-nav-links a.active{color:#d4a017;}',
    '.site-nav-links a:hover,.site-nav-links a.active{color:#b8881a;}'
  ],
  [
    '.site-menu-btn{display:none;background:transparent;border:0;color:#fff;font-size:1.9rem;cursor:pointer;padding:6px;}',
    '.site-menu-btn{display:none;background:transparent;border:0;color:#111827;font-size:1.9rem;cursor:pointer;padding:6px;}'
  ],
  [
    'background:#0d1225;border-radius:12px;border:1px solid rgba(255,255,255,.08);flex-direction:column',
    'background:#ffffff;border-radius:12px;border:1px solid rgba(212,160,23,.3);box-shadow:0 8px 24px rgba(0,0,0,.1);flex-direction:column'
  ],
  [
    '.site-footer .ft-bottom{background:#0d1225;padding:22px 0;display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;color:rgba(255,255,255,.5);font-size:.82rem;}',
    '.site-footer .ft-bottom{background:#d4a017;padding:22px 0;display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;color:rgba(255,255,255,.9);font-size:.82rem;}'
  ],
  [
    '.site-footer .ft-bottom a{color:rgba(255,255,255,.5);text-decoration:none;transition:color .15s;}',
    '.site-footer .ft-bottom a{color:rgba(255,255,255,.9);text-decoration:none;transition:color .15s;}'
  ],
  [
    '.site-footer .ft-bottom a:hover{color:#d4a017;}',
    '.site-footer .ft-bottom a:hover{color:#ffffff;}'
  ]
];

let updated = 0;
for (const rel of targetFiles) {
  const fp = path.join(root, rel);
  if (!fs.existsSync(fp)) { console.log(`SKIP (not found): ${rel}`); continue; }
  let content = fs.readFileSync(fp, 'utf8');
  let changed = false;
  for (const [old, neo] of replacements) {
    if (content.includes(old)) { content = content.split(old).join(neo); changed = true; }
  }
  if (changed) {
    fs.writeFileSync(fp, content, 'utf8');
    console.log(`Updated: ${rel}`);
    updated++;
  } else {
    console.log(`No changes: ${rel}`);
  }
}
console.log(`\nDone. ${updated} files updated.`);
