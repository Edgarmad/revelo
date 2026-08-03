import { copyFileSync, existsSync, mkdirSync, readdirSync, readFileSync, rmSync, writeFileSync } from 'node:fs';
import { basename, dirname, extname, join, relative, resolve } from 'node:path';

const root = process.cwd();
const sourceRoot = resolve(root, 'PRODUCTOS MILA WEB');
const publicRoot = resolve(root, 'public/productos-mila-web');
const manifestPath = resolve(root, 'wordpress/migration/productos-mila-web-products.json');
const blockedPath = resolve(root, 'wordpress/migration/productos-mila-web-blocked.json');

const imageExtensions = new Set(['.jpg', '.jpeg', '.png', '.webp', '.gif']);

const hexByColor = new Map([
  ['amarillo', '#d6a722'],
  ['azul oceano', '#315f7d'],
  ['azul turquesa', '#2aa6a1'],
  ['beige', '#d8c7ad'],
  ['blanca', '#f4f1ea'],
  ['blanco', '#f4f1ea'],
  ['cafe', '#7a533b'],
  ['chocolate', '#5b372c'],
  ['ginger', '#b56f3a'],
  ['greige', '#b8afa2'],
  ['greie', '#b8afa2'],
  ['gris', '#8b8b84'],
  ['gris oscuro', '#4f5454'],
  ['khaki', '#b8aa86'],
  ['marco gris asiento cafe', '#77736b'],
  ['mostaza', '#c59a2f'],
  ['negra', '#222222'],
  ['negro', '#222222'],
  ['olivo', '#6f7350'],
  ['rojo carmin', '#8d1f2d'],
  ['verde', '#6f7f55'],
  ['verde livo', '#6f7350'],
  ['verde militar', '#5d6744'],
  ['verde olivo', '#6f7350'],
]);

function extractArray(filePath, marker) {
  const source = readFileSync(resolve(root, filePath), 'utf8');
  const markerIndex = source.indexOf(marker);
  if (markerIndex === -1) throw new Error(`Marker not found: ${marker}`);

  const assignmentIndex = source.indexOf('=', markerIndex);
  if (assignmentIndex === -1) throw new Error(`Assignment not found: ${marker}`);

  const start = source.indexOf('[', assignmentIndex);
  let depth = 0;
  let inString = false;
  let quote = '';

  for (let index = start; index < source.length; index += 1) {
    const char = source[index];
    const prev = source[index - 1];

    if (inString) {
      if (char === quote && prev !== '\\') inString = false;
      continue;
    }

    if (char === '"' || char === "'") {
      inString = true;
      quote = char;
      continue;
    }

    if (char === '[') depth += 1;
    if (char === ']') depth -= 1;
    if (depth === 0) return source.slice(start, index + 1);
  }

  throw new Error(`Array not closed for marker: ${marker}`);
}

function listDirectories(path) {
  if (!existsSync(path)) return [];
  return readdirSync(path, { withFileTypes: true }).filter((entry) => entry.isDirectory()).map((entry) => entry.name).sort((a, b) => a.localeCompare(b));
}

function listImageFiles(path) {
  if (!existsSync(path)) return [];
  return readdirSync(path, { withFileTypes: true })
    .filter((entry) => entry.isFile() && imageExtensions.has(extname(entry.name).toLowerCase()))
    .map((entry) => join(path, entry.name))
    .sort((a, b) => basename(a).localeCompare(basename(b)));
}

function normalizeText(value) {
  return value
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/�/g, '')
    .replace(/_/g, ' ')
    .replace(/\s+/g, ' ')
    .trim();
}

function titleCase(value) {
  return normalizeText(value.toLowerCase()).replace(/\b\p{L}/gu, (letter) => letter.toUpperCase());
}

function slugify(value) {
  return normalizeText(value)
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '');
}

function copyImage(source, productSlug, role, index) {
  const extension = extname(source).toLowerCase() || '.png';
  const filename = `${role}-${String(index).padStart(2, '0')}${extension}`;
  const destination = resolve(publicRoot, productSlug, filename);
  mkdirSync(dirname(destination), { recursive: true });
  copyFileSync(source, destination);
  return `/productos-mila-web/${productSlug}/${filename}`;
}

function variantImageFiles(variantRoot) {
  const variants = [];
  for (const directory of listDirectories(variantRoot)) {
    const files = listImageFiles(join(variantRoot, directory));
    variants.push({ name: normalizeText(directory), files });
  }

  for (const file of listImageFiles(variantRoot)) {
    variants.push({ name: normalizeText(basename(file, extname(file))), files: [file] });
  }

  return variants;
}

function isAmbiguousVariant(name) {
  const normalized = normalizeText(name);
  return !normalized || /^IMG([\s_-]|\d)/i.test(normalized) || /[?]/.test(normalized) || /�/.test(name);
}

function analyzeProduct(categoryName, productName) {
  const productRoot = join(sourceRoot, categoryName, productName);
  const directories = listDirectories(productRoot).map((name) => ({ name, path: join(productRoot, name) }));
  const mainDirectories = directories.filter((directory) => /imagen\s*principal/i.test(directory.name));
  const realDirectories = directories.filter((directory) => /fondo\s*real|foto\s*real/i.test(directory.name));
  const measureDirectories = directories.filter((directory) => /^medida/i.test(directory.name));
  const variantDirectories = directories.filter((directory) => /variante|variantes|variedades/i.test(directory.name));

  const mainImages = mainDirectories.flatMap((directory) => listImageFiles(directory.path));
  const realImages = realDirectories.flatMap((directory) => listImageFiles(directory.path));
  const measureImages = measureDirectories.flatMap((directory) => listImageFiles(directory.path));
  const variants = variantDirectories.flatMap((directory) => variantImageFiles(directory.path));
  const variantsWithImages = variants.filter((variant) => variant.files.length > 0);
  const issues = [];

  if (mainImages.length === 0) issues.push('sin imagen principal');

  const ambiguousVariants = variantsWithImages.filter((variant) => isAmbiguousVariant(variant.name)).map((variant) => variant.name);
  if (ambiguousVariants.length > 0) issues.push(`variante ambigua: ${ambiguousVariants.join(', ')}`);

  return { categoryName, productName, mainImages, realImages, measureImages, variants: variantsWithImages, issues };
}

function relativeSource(path) {
  return relative(sourceRoot, path).replace(/\\/g, '/');
}

const inventory = JSON.parse(extractArray('src/data/products.ts', 'const inventory'));
const inventoryBySlug = new Map(inventory.map((product) => [product.slug, product]));
const inventoryAliasBySlug = new Map([
  ['plantas-ave-paraiso', 'plantas-ave-de-paraiso'],
  ['plantas-palma-areca', 'plantas-palma-areca-hb'],
  ['plastico-mecedora-datura', 'plastico-mecedra-datura'],
]);
const migrable = [];
const blocked = [];

if (!existsSync(sourceRoot)) throw new Error(`Source folder not found: ${sourceRoot}`);

if (existsSync(publicRoot)) rmSync(publicRoot, { recursive: true, force: true });

for (const categoryName of listDirectories(sourceRoot)) {
  for (const productName of listDirectories(join(sourceRoot, categoryName))) {
    const analysis = analyzeProduct(categoryName, productName);
    if (analysis.issues.length > 0) {
      blocked.push({
        category: categoryName,
        product: normalizeText(productName),
        mainImages: analysis.mainImages.length,
        realImages: analysis.realImages.length,
        measureImages: analysis.measureImages.length,
        variants: analysis.variants.map((variant) => variant.name),
        issues: analysis.issues,
      });
      continue;
    }

    const categorySlug = slugify(categoryName);
    const productSlug = `${categorySlug}-${slugify(productName)}`;
    const existing = inventoryBySlug.get(productSlug) ?? inventoryBySlug.get(inventoryAliasBySlug.get(productSlug));
    const mainImage = copyImage(analysis.mainImages[0], productSlug, 'main', 1);
    const gallery = [];
    let galleryIndex = 1;

    for (const image of analysis.mainImages.slice(1)) gallery.push(copyImage(image, productSlug, 'gallery-main', galleryIndex++));
    for (const image of analysis.realImages) gallery.push(copyImage(image, productSlug, 'gallery-real', galleryIndex++));
    for (const image of analysis.measureImages) gallery.push(copyImage(image, productSlug, 'gallery-measures', galleryIndex++));

    const colors = analysis.variants.map((variant, index) => {
      const normalizedName = normalizeText(variant.name);
      const id = slugify(normalizedName);
      return {
        id,
        name: titleCase(normalizedName),
        hex: hexByColor.get(id.replace(/-/g, ' ')) ?? '#cccccc',
        image: copyImage(variant.files[0], productSlug, `variant-${id || 'color'}`, index + 1),
        available: true,
      };
    });

    migrable.push({
      slug: productSlug,
      name: titleCase(productName),
      shortDescription: `${titleCase(productName)} disponible en ${titleCase(categoryName)}.`,
      description: `${titleCase(productName)} forma parte del inventario actual de ${titleCase(categoryName).toLowerCase()} para espacios exteriores.`,
      sourceImage: mainImage,
      gallery: [mainImage, ...gallery],
      category: categorySlug,
      categories: existing?.compareAtPrice ? [categorySlug, 'descuentos'] : [categorySlug],
      collection: titleCase(categoryName),
      brand: 'Milapro Home',
      sku: productSlug.toUpperCase(),
      colors,
      materials: [],
      dimensions: analysis.measureImages.length > 0 ? 'Ver imagen de medidas en galeria' : 'Consultar disponibilidad',
      featured: existing?.featured ?? false,
      available: existing?.available ?? true,
      displayOrder: 1000 + migrable.length,
      specifications: [{ label: 'Categoria', value: titleCase(categoryName) }],
      price: existing?.price ?? 0,
      compareAtPrice: existing?.compareAtPrice,
      sourceDirectory: relativeSource(join(sourceRoot, categoryName, productName)),
      sourceFiles: {
        mainImage: relativeSource(analysis.mainImages[0]),
        gallery: [...analysis.mainImages.slice(1), ...analysis.realImages, ...analysis.measureImages].map(relativeSource),
        variants: analysis.variants.map((variant) => ({ name: variant.name, image: relativeSource(variant.files[0]) })),
      },
    });
  }
}

mkdirSync(dirname(manifestPath), { recursive: true });
writeFileSync(manifestPath, `${JSON.stringify({ sourceRoot: 'PRODUCTOS MILA WEB', generatedAt: new Date().toISOString(), criteria: 'Imagen principal requerida. Imagenes principales adicionales, fondo/foto real y medidas van a galeria si existen. Variantes sin imagen se omiten; sin variantes implica version default.', products: migrable }, null, 2)}\n`);
writeFileSync(blockedPath, `${JSON.stringify({ sourceRoot: 'PRODUCTOS MILA WEB', generatedAt: new Date().toISOString(), blocked }, null, 2)}\n`);

console.log(`Productos migrables: ${migrable.length}`);
console.log(`Productos bloqueados: ${blocked.length}`);
console.log(`Imagenes copiadas a ${publicRoot}`);
console.log(`Manifest escrito en ${manifestPath}`);
