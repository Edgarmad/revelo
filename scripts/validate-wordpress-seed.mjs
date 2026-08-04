import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';

const seedPath = resolve(process.cwd(), process.argv[2] || 'wordpress/migration/seed.json');
const required = {
  categories: ['slug', 'name'],
  products: ['slug', 'name', 'shortDescription', 'description', 'sku', 'price'],
  reels: ['slug', 'title'],
  blogs: ['slug', 'title', 'excerpt', 'content'],
};

function fail(message) {
  console.error(message);
  process.exitCode = 1;
}

let seed;
try {
  seed = JSON.parse(readFileSync(seedPath, 'utf8'));
} catch (error) {
  fail(`Invalid JSON: ${error.message}`);
  process.exit();
}

if (!seed || typeof seed !== 'object' || Array.isArray(seed)) {
  fail('Seed must be a JSON object.');
  process.exit();
}

for (const [section, fields] of Object.entries(required)) {
  if (!Array.isArray(seed[section])) {
    fail(`Missing or invalid section: ${section}.`);
    continue;
  }

  const slugs = new Set();
  seed[section].forEach((item, index) => {
    if (!item || typeof item !== 'object' || Array.isArray(item)) {
      fail(`${section}[${index}] must be an object.`);
      return;
    }

    fields.forEach((field) => {
      if (item[field] === undefined || item[field] === null || item[field] === '') {
        fail(`${section}[${index}] missing required field: ${field}.`);
      }
    });

    if (item.slug) {
      if (slugs.has(item.slug)) fail(`${section}[${index}] duplicate slug: ${item.slug}.`);
      slugs.add(item.slug);
    }
  });
}

if (!process.exitCode) {
  console.log(`Seed valid: ${seed.categories.length} categories, ${seed.products.length} products, ${seed.reels.length} reels, ${seed.blogs.length} blogs.`);
}
