const fs = require('fs');
const path = require('path');

const root = process.cwd();
const siteOrigin = 'https://inzra.com';
const productsDir = path.join(root, 'products');

function existingFiles(dir, extension) {
  if (!fs.existsSync(dir)) {
    return [];
  }

  return fs.readdirSync(dir)
    .filter((name) => name.endsWith(extension))
    .sort();
}

function addReplacement(replacements, from, to) {
  if (!from || from === to) {
    return;
  }

  replacements.push([from, to]);
}

const replacements = [];
const rootHtmlFiles = existingFiles(root, '.html');
const productHtmlFiles = existingFiles(productsDir, '.html');

for (const file of rootHtmlFiles) {
  if (file === 'index.html') {
    addReplacement(replacements, `${siteOrigin}/index.html`, `${siteOrigin}/`);
    addReplacement(replacements, '../index.html', '../');
    addReplacement(replacements, './index.html', './');
    addReplacement(replacements, 'index.html', '/');
    continue;
  }

  const route = file.slice(0, -5);
  addReplacement(replacements, `${siteOrigin}/${file}`, `${siteOrigin}/${route}`);
  addReplacement(replacements, `../${file}`, `../${route}`);
  addReplacement(replacements, `./${file}`, `./${route}`);
  addReplacement(replacements, file, route);
}

for (const file of productHtmlFiles) {
  const route = `products/${file.slice(0, -5)}`;
  addReplacement(replacements, `${siteOrigin}/products/${file}`, `${siteOrigin}/${route}`);
  addReplacement(replacements, '../products/' + file, '../' + route);
  addReplacement(replacements, './products/' + file, './' + route);
  addReplacement(replacements, 'products/' + file, route);
}

replacements.sort((left, right) => right[0].length - left[0].length);

const targets = [
  ...rootHtmlFiles.map((file) => path.join(root, file)),
  ...productHtmlFiles.map((file) => path.join(productsDir, file)),
  path.join(root, 'sitemap.xml'),
  path.join(root, 'llms.txt')
].filter((filePath) => fs.existsSync(filePath));

let changedFiles = 0;

for (const filePath of targets) {
  const original = fs.readFileSync(filePath, 'utf8');
  let updated = original;

  for (const [from, to] of replacements) {
    if (updated.includes(from)) {
      updated = updated.split(from).join(to);
    }
  }

  if (updated !== original) {
    fs.writeFileSync(filePath, updated, 'utf8');
    changedFiles += 1;
    console.log(`Updated ${path.relative(root, filePath)}`);
  }
}

console.log(`Extensionless URL update complete: ${changedFiles} files changed.`);