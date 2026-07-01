const fs = require('fs');
const path = require('path');

const root = process.cwd();
const dataPath = path.join(root, 'products-data.json');
const productsDir = path.join(root, 'products');
const siteOrigin = 'https://inzra.com';
const siteLogoUrl = `${siteOrigin}/logos/inzra-logo.png`;
const organizationId = `${siteOrigin}/#organization`;

function escapeHtml(text) {
  return String(text)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;');
}

function escapeAttr(text) {
  return escapeHtml(text).replace(/"/g, '&quot;');
}

function escapeForJson(text) {
  return String(text).replace(/\\/g, '\\\\').replace(/"/g, '\\"');
}

function singularPlural(count, singular, plural) {
  return count === 1 ? singular : plural;
}

function detectType(title, category) {
  const t = `${title} ${category || ''}`.toLowerCase();
  if (/(backlink|backlinks|dofollow|nofollow|guest post|link building|web 2\.0|entity stacking|contextual)/.test(t)) return 'backlinks';
  if (/(website|web design|wordpress|shopify|landing page|blog pages|ecommerce|store)/.test(t)) return 'website';
  if (/(email|emails|lead|leads|b2b|b2c|consumer list|database)/.test(t)) return 'leads';
  if (/(citation|citations|gmb|google business|directory submission|directory submissions|maps seo|local seo)/.test(t)) return 'local';
  if (/(youtube|channel|watch hours|subscribers)/.test(t)) return 'social';
  return 'digital';
}

function makeDescription(product) {
  const title = String(product.title || 'Digital Growth Service').trim();
  const kind = detectType(title, product.category);
  const sold = Number(product.sold || 0);
  const rating = Number(product.rating || 0);
  const reviews = Number(product.reviews || 0);
  const quantity = Number(product.quantity || 0);
  const watchers = Number(product.watchers || 0);

  const first = `\"${title}\" is delivered as a practical done-for-you service focused on measurable results.`;

  let second;
  if (kind === 'backlinks') {
    second = 'Built to strengthen authority with relevant link placements that support rankings, indexing, and long-term search visibility.';
  } else if (kind === 'website') {
    second = 'Designed for a clean, conversion-focused web presence so visitors understand your offer quickly and take action with confidence.';
  } else if (kind === 'leads') {
    second = 'Prepared for targeted outreach and campaign execution with organized data that helps teams launch faster and follow up consistently.';
  } else if (kind === 'local') {
    second = 'Structured to improve local discoverability with trustworthy citation signals and consistent business profile support.';
  } else if (kind === 'social') {
    second = 'Built to increase channel momentum with steady visibility signals that help improve reach, trust, and audience growth.';
  } else {
    second = 'Prepared as a professional digital service with clear delivery scope, reliable communication, and practical business value.';
  }

  const trustParts = [];
  if (sold > 0) {
    trustParts.push(`${sold} ${singularPlural(sold, 'order', 'orders')} completed`);
  }
  if (rating > 0 && reviews > 0) {
    trustParts.push(`${rating.toFixed(1)} rating from ${reviews} ${singularPlural(reviews, 'review', 'reviews')}`);
  }

  let third = trustParts.length > 0 ? `Track record: ${trustParts.join(', ')}.` : 'Track record: new listing with active support from Inzra.';

  if (quantity <= 0) {
    third += ' Currently sold out; contact us to confirm the next available slot.';
  } else if (quantity <= 3) {
    third += ` Only ${quantity} ${singularPlural(quantity, 'slot', 'slots')} currently available.`;
  } else {
    third += ` ${quantity} ${singularPlural(quantity, 'slots', 'slots')} currently open for purchase.`;
  }

  if (watchers > 0) {
    third += ` ${watchers} ${singularPlural(watchers, 'buyer is', 'buyers are')} watching this offer.`;
  }

  const demoSuffix = ' Demo metrics shown for client presentation only.';
  return `${first} ${second} ${third}${demoSuffix}`;
}

function makeKeywords(product, kind) {
  const title = String(product.title || '').toLowerCase();
  const base = ['inzra', 'digital services', 'seo services'];

  if (kind === 'backlinks') base.push('backlinks', 'link building', 'seo backlinks');
  if (kind === 'website') base.push('website design', 'business website', 'web development');
  if (kind === 'leads') base.push('email leads', 'marketing list', 'b2b leads');
  if (kind === 'local') base.push('local seo', 'citations', 'map rankings');
  if (kind === 'social') base.push('social growth', 'channel growth', 'audience growth');
  if (kind === 'digital') base.push('online business', 'digital marketing', 'growth service');

  if (title.includes('german')) base.push('german backlinks');
  if (title.includes('uk')) base.push('uk backlinks');
  if (title.includes('spanish') || title.includes('mexico')) base.push('spanish seo');
  if (title.includes('uae')) base.push('uae marketing');

  return [...new Set(base)].join(', ');
}

function buildCustomerReviews(product, kind) {
  const names = ['A. Perera', 'M. Silva', 'R. Khan'];
  const days = ['2026-05-08', '2026-05-14', '2026-05-21'];

  let opener = 'Clear communication and delivery matched the listing details.';
  if (kind === 'backlinks') opener = 'Link report was easy to review and the placements were niche-relevant.';
  if (kind === 'website') opener = 'Design looked modern, loaded fast, and matched our business goals.';
  if (kind === 'leads') opener = 'List format was clean and ready for campaign upload.';
  if (kind === 'local') opener = 'Local SEO work improved map consistency and profile trust.';
  if (kind === 'social') opener = 'Delivery helped improve reach momentum and audience confidence.';

  const sold = Number(product.sold || 0);
  const watchers = Number(product.watchers || 0);

  const snippets = [
    `${opener} Good value for the price and quick turnaround.`,
    `Professional service with transparent updates. ${sold > 0 ? `Already ${sold} completed orders gave me confidence.` : 'I would purchase again.'}`,
    `Support was responsive and practical. ${watchers > 0 ? `${watchers} active watchers also make sense after using it.` : 'Overall experience was smooth.'}`
  ];

  const ratingA = 5;
  const ratingB = 5;
  const ratingC = 4;
  const ratingText = Number(product.rating || 4.9).toFixed(1);

  const reviews = [
    { name: names[0], rating: ratingA, date: days[0], text: snippets[0] },
    { name: names[1], rating: ratingB, date: days[1], text: snippets[1] },
    { name: names[2], rating: ratingC, date: days[2], text: snippets[2] }
  ];

  const sectionCards = reviews.map((r) => {
    return `          <article class="review-card">\n            <div class="review-head"><strong>${escapeHtml(r.name)}</strong><span>${r.rating}/5</span></div>\n            <p>${escapeHtml(r.text)}</p>\n            <small>${r.date}</small>\n          </article>`;
  }).join('\n');

  const section = [
    '        <!-- CUSTOMER_REVIEWS_START -->',
    '        <section class="reviews" aria-label="Customer reviews">',
    '          <h2>Customer Reviews</h2>',
    `          <p class="reviews-summary">Average rating: <strong>${ratingText}/5</strong> from ${Number(product.reviews || 0)} verified reviews.</p>`,
    '          <div class="review-grid">',
    sectionCards,
    '          </div>',
    '        </section>',
    '        <!-- CUSTOMER_REVIEWS_END -->'
  ].join('\n');

  return { reviews, section };
}

function buildMetaBlock(product, description, keywords, reviews) {
  const item = String(product.itemNumber || '').trim();
  const title = String(product.title || 'Inzra Service').trim();
  const category = String(product.category || 'Digital Services').trim();
  const currency = String(product.currency || 'USD').trim();
  const price = Number(product.price || 0).toFixed(2);
  const rating = Number(product.rating || 4.9).toFixed(1);
  const reviewCount = Number(product.reviews || 0);
  const pageUrl = `${siteOrigin}/products/${encodeURIComponent(item)}.html`;
  const availability = Number(product.quantity || 0) > 0
    ? 'https://schema.org/InStock'
    : 'https://schema.org/OutOfStock';

  const reviewJson = reviews.map((r) => {
    return `      {\n        "@type": "Review",\n        "author": { "@type": "Person", "name": "${escapeForJson(r.name)}" },\n        "reviewRating": { "@type": "Rating", "ratingValue": ${r.rating}, "bestRating": 5 },\n        "reviewBody": "${escapeForJson(r.text)}",\n        "datePublished": "${r.date}"\n      }`;
  }).join(',\n');

  return [
    '  <!-- PRODUCT_META_START -->',
    `  <meta name="author" content="Inzra">`,
    `  <meta name="keywords" content="${escapeAttr(keywords)}">`,
    `  <meta property="og:type" content="product">`,
    `  <meta property="og:site_name" content="Inzra">`,
    `  <meta property="og:title" content="${escapeAttr(title)} | Inzra">`,
    `  <meta property="og:description" content="${escapeAttr(description)}">`,
    `  <meta property="og:url" content="${escapeAttr(pageUrl)}">`,
    `  <meta name="twitter:card" content="summary_large_image">`,
    `  <meta name="twitter:title" content="${escapeAttr(title)} | Inzra">`,
    `  <meta name="twitter:description" content="${escapeAttr(description)}">`,
    `  <link rel="canonical" href="${escapeAttr(pageUrl)}">`,
    '  <script type="application/ld+json">',
    '  {',
    '    "@context": "https://schema.org",',
    '    "@type": "Product",',
    `    "@id": "${escapeForJson(pageUrl)}#product",`,
    `    "url": "${escapeForJson(pageUrl)}",`,
    `    "mainEntityOfPage": "${escapeForJson(pageUrl)}",`,
    `    "name": "${escapeForJson(title)}",`,
    `    "description": "${escapeForJson(description)}",`,
    `    "category": "${escapeForJson(category)}",`,
    `    "inLanguage": "en",`,
    `    "image": "${escapeForJson(siteLogoUrl)}",`,
    `    "sku": "${escapeForJson(item)}",`,
    `    "brand": { "@type": "Organization", "@id": "${escapeForJson(organizationId)}", "name": "Inzra" },`,
    `    "isPartOf": { "@type": "WebSite", "@id": "${escapeForJson(siteOrigin)}/#website", "url": "${escapeForJson(siteOrigin)}/", "name": "Inzra" },`,
    '    "aggregateRating": {',
    '      "@type": "AggregateRating",',
    `      "ratingValue": ${rating},`,
    `      "reviewCount": ${reviewCount}`,
    '    },',
    '    "offers": {',
    '      "@type": "Offer",',
    `      "priceCurrency": "${escapeForJson(currency)}",`,
    `      "price": "${price}",`,
    `      "availability": "${availability}",`,
      `      "url": "${escapeForJson(pageUrl)}",`,
      `      "seller": { "@type": "Organization", "@id": "${escapeForJson(organizationId)}", "name": "Inzra" }`,
    '    },',
    '    "review": [',
    reviewJson,
    '    ]',
    '  }',
    '  </script>',
    '  <!-- PRODUCT_META_END -->'
  ].join('\n');
}

function injectReviewStyles(html) {
  const hasReviewStyles = html.includes('.reviews {');

  const reviewCss = [
    '    .reviews { border:1px solid var(--line); border-radius:12px; padding:12px; background:#fff; }',
    '    .reviews h2 { margin:0; font-size:1.02rem; }',
    '    .reviews-summary { margin:6px 0 10px; color:var(--muted); font-size:.86rem; }',
    '    .review-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:10px; }',
    '    .review-card { border:1px solid var(--line); border-radius:10px; padding:10px; background:#fcfcfb; }',
    '    .review-head { display:flex; justify-content:space-between; gap:8px; margin-bottom:6px; font-size:.84rem; }',
    '    .review-card p { margin:0; color:#334155; font-size:.84rem; line-height:1.55; }',
    '    .review-card small { display:block; margin-top:7px; color:var(--muted); font-size:.76rem; }'
  ].join('\n');

  if (!hasReviewStyles) {
    html = html.replace(
      /\s*\.note \{[^\n]*\}\s*/,
      (m) => `${m}${reviewCss}\n`
    );
  }

  html = html.replace(
    '@media (max-width:680px){ .stats{grid-template-columns:1fr;} .topbar{border-radius:16px;} }',
    '@media (max-width:680px){ .stats{grid-template-columns:1fr;} .review-grid{grid-template-columns:1fr;} .topbar{border-radius:16px;} }'
  );

  if (!html.includes('@media (max-width:680px){ .stats{grid-template-columns:1fr;} .review-grid{grid-template-columns:1fr;} .topbar{border-radius:16px;} }')) {
    html = html.replace(
      /@media \(max-width:680px\)\{([^}]*)\}/,
      (full, inner) => {
        if (inner.includes('.review-grid')) return full;
        return `@media (max-width:680px){${inner} .review-grid{grid-template-columns:1fr;} }`;
      }
    );
  }

  return html;
}

const rawData = fs.readFileSync(dataPath, 'utf8').replace(/^\uFEFF/, '');
const data = JSON.parse(rawData);
let updatedJsonCount = 0;
let updatedHtmlCount = 0;

for (const product of data) {
  const item = String(product.itemNumber || '').trim();
  if (!item) continue;

  const kind = detectType(product.title, product.category);
  const description = makeDescription(product);
  const keywords = makeKeywords(product, kind);
  const { reviews, section } = buildCustomerReviews(product, kind);
  const metaBlock = buildMetaBlock(product, description, keywords, reviews);

  product.description = description;
  updatedJsonCount += 1;

  const htmlPath = path.join(productsDir, `${item}.html`);
  if (!fs.existsSync(htmlPath)) continue;

  let html = fs.readFileSync(htmlPath, 'utf8');
  const metaLine = `<meta name="description" content="${escapeAttr(description)}">`;
  const descLine = `<p class="desc">${escapeHtml(description)}</p>`;

  html = html.replace(/\s*<!-- PRODUCT_META_START -->[\s\S]*?<!-- PRODUCT_META_END -->\s*/g, '\n');
  html = html.replace(/\s*<!-- CUSTOMER_REVIEWS_START -->[\s\S]*?<!-- CUSTOMER_REVIEWS_END -->\s*/g, '\n');

  html = html.replace(/<meta name="description" content="[^"]*">/, metaLine);
  html = html.replace(/(<meta name="robots" content="index, follow">\r?\n)/, `$1${metaBlock}\n`);
  html = html.replace(/<p class="desc">[\s\S]*?<\/p>/, descLine);
  html = injectReviewStyles(html);
  html = html.replace(/(\s*<p class="note">[\s\S]*?<\/p>)/, `\n${section}\n$1`);

  fs.writeFileSync(htmlPath, html, 'utf8');
  updatedHtmlCount += 1;
}

fs.writeFileSync(dataPath, JSON.stringify(data, null, 4) + '\n', 'utf8');
console.log(`Updated JSON descriptions: ${updatedJsonCount}`);
console.log(`Updated product HTML files: ${updatedHtmlCount}`);
