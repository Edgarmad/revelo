import { execFileSync } from 'node:child_process';
import { existsSync, mkdirSync, readFileSync, writeFileSync } from 'node:fs';
import { dirname, resolve } from 'node:path';

const root = process.cwd();
const inputPath = process.argv[2] ? resolve(process.argv[2]) : 'C:/Users/edmad/Downloads/inventario.json';
const outputPath = resolve(root, 'wordpress/migration/inventario-canonico.json');
const reportPath = resolve(root, 'wordpress/migration/inventario-canonico-report.json');
const liveUrl = 'https://cms.milaprohome.com/wp-json/wp/v2/products?per_page=100&_fields=id,slug,title,product_category,main_image_url,gallery_urls,product_details';

const categoryByInventory = new Map([
  ['ALUMINIO', { slug: 'aluminio', name: 'Aluminio', order: 2 }],
  ['PLASTICO', { slug: 'plastico', name: 'Plastico', order: 4 }],
  ['PLÁSTICO', { slug: 'plastico', name: 'Plastico', order: 4 }],
  ['RATTAN', { slug: 'ratan', name: 'Ratan', order: 3 }],
  ['RATAN', { slug: 'ratan', name: 'Ratan', order: 3 }],
  ['PLANTAS', { slug: 'plantas', name: 'Plantas', order: 1 }],
  ['SOMBRILLAS', { slug: 'sombrillas', name: 'Sombrillas', order: 5 }],
]);

const hexByColor = new Map([
  ['amarillo', '#d6a722'],
  ['azul', '#315f7d'],
  ['azul oceano', '#315f7d'],
  ['beige', '#d8c7ad'],
  ['blanca', '#f4f1ea'],
  ['blanco', '#f4f1ea'],
  ['cafe', '#7a533b'],
  ['caf e', '#7a533b'],
  ['chocolate', '#5b372c'],
  ['ginger', '#b56f3a'],
  ['greige', '#b8afa2'],
  ['gris', '#8b8b84'],
  ['gris con blanco', '#8b8b84'],
  ['gris oscuro', '#4f5454'],
  ['khaki', '#b8aa86'],
  ['khaki rojo', '#b8aa86'],
  ['mostaza', '#c59a2f'],
  ['naranja', '#c76f35'],
  ['negro', '#222222'],
  ['rojo', '#8d1f2d'],
  ['verde', '#6f7f55'],
  ['verde militar', '#5d6744'],
  ['verde olivo', '#6f7350'],
]);

const aliasByKey = new Map(Object.entries({
  'aluminio|sala onega': 'aluminio-onega',
  'aluminio|sala esquinera onega': 'aluminio-onega-esquinero',
  'aluminio|sala caspio': 'aluminio-caspio',
  'aluminio|sala esquinera caspio': 'aluminio-caspio-esquinero',
  'aluminio|silla caspio': 'aluminio-silla-caspio',
  'aluminio|mesa de comedor volga': 'aluminio-mesa-volga-1-6',
  'aluminio|camastro sena': 'aluminio-camastro-sena',
  'aluminio|sillones caspio': 'aluminio-sillones-casio',
  'aluminio|sala malaui': 'aluminio-malaui',
  'aluminio|silla malaui': 'aluminio-silla-malaui',
  'aluminio|camastros victoria': 'outlet-camastro-victoria',
  'aluminio|mesa de comedor elba': 'aluminio-mesa-elba',
  'aluminio|mesa de comedor erie': 'aluminio-mesa-erie',
  'aluminio|sala turkana': 'outlet-turkana',
  'aluminio|sala ladoga': 'outlet-ladoga',
  'aluminio|sala liena': 'outlet-liena',
  'aluminio|silla erie': 'aluminio-silla-erie',
  'aluminio|sala nyasa': 'outlet-nyasa',
  'aluminio|sala ontario gris': 'aluminio-ontario-gris',
  'aluminio|sala ontario beige': 'aluminio-ontario-beige',
  'aluminio|sillon ontario gris': 'aluminio-sillon-ind-ontario-gris',
  'aluminio|sillon ontario beige': 'aluminio-sillon-ind-onrario-beige',
  'aluminio|sillon esquinero extra llona': 'aluminio-sillon-extra-llona',
  'aluminio|sillon llona': '',
  'aluminio|sala llona 4': 'aluminio-llona-4-ax',
  'aluminio|sala llona 5': 'aluminio-llona-5-ax',
  'aluminio|mesa auxiliar llona': 'aluminio-llona-mesa-auxiliar',
  'aluminio|sala esquinera llona': 'aluminio-llona-esquinero',
  'aluminio|sala esquinera bahia': 'aluminio-bahia-esquinera',
  'aluminio|mesa nilo 2 m': 'plastico-mesa-nilo-2m',
  'aluminio|mesa nilo 1 60': 'plastico-mesa-nilo-1-6m',
  'aluminio|mesa lucerna': 'plastico-mesa-licerna',
  'aluminio|mesa leman': 'plastico-mesa-leman',
  'plastico|banca narciso': 'plastico-banca-narciso',
  'plastico|mesa narciso': 'plastico-mesa-rectangular-narciso',
  'plastico|sillon narciso': 'plastico-sillones-narciso',
  'plastico|silla apoyabrazos': 'plastico-narciso-con-aopyabrazos',
  'plastico|silla alta narciso': 'plastico-silla-alta-narciso',
  'plastico|mesa auxiliar narciso': 'plastico-mesa-auxiliar-narciso',
  'plastico|silla narciso': 'plastico-silla-narciso',
  'plastico|mesa cala apoyo central': 'plastico-mesa-cala-s-central',
  'plastico|mesa cala': 'plastico-mesa-cala',
  'plastico|mesa cala comedor': 'plastico-mesa-comedor-cala',
  'plastico|mesa loto': 'plastico-mesa-loto',
  'plastico|mesa loto alta': '',
  'plastico|silla dalia': 'plastico-silla-dalia',
  'plastico|silla zinnia': 'plastico-silla-zinnia',
  'plastico|silla zinnia alta': 'plastico-silla-alta-zinnia',
  'plastico|silla calendula': 'plastico-silla-calendula',
  'plastico|silleta iris': 'plastico-silleta-iris',
  'plastico|silla peonia': 'plastico-silla-peonia',
  'plastico|silla gerbera': 'plastico-silla-gerbera',
  'plastico|silla gerbera apoya brazos': 'plastico-gerbera-con-apoyabrazos',
  'plastico|camastro raflessia': 'plastico-camastro-raflessia',
  'plastico|mesa aster': 'plastico-aster-mesa-con-almacenamiento',
  'plastico|mecedora iris': 'plastico-mecedora-iris',
  'plastico|mesa auxiliar iris': 'plastico-mesa-auxiliar-iris',
  'plastico|sala iris 4': '',
  'plastico|set iris 5': 'plastico-set-iris',
  'plastico|set craspedia 4': '',
  'plastico|set craspedia 5': 'plastico-set-craspedia',
  'ratan|sala malaga petit': 'ratan-malaga-petit',
  'ratan|sala malaga': 'ratan-malaga',
  'ratan|sala marbella': 'ratan-marbella',
  'ratan|sala madrid': 'ratan-madrid',
  'ratan|toledo': '',
  'ratan|bar ibiza': 'ratan-ibiza',
  'ratan|comedor granada': 'ratan-granada',
  'ratan|comedor barcelona 2 sillas': 'ratan-barcelona-2',
  'ratan|comedor barcelona 4 sillas': 'ratan-barcelona-4',
  'ratan|comedor barcelona mesa cuadrada 6 sillas': 'ratan-barcelona-6',
  'ratan|comedor barcelona mesa redonda 6 sillas': '',
  'ratan|sillones sevilla': 'ratan-sevilla',
  'ratan|sala bilbao': 'ratan-bilbao',
  'ratan|mecedora girona': 'outlet-mecedora-girona',
  'plantas|planta ave de paraiso': 'plantas-ave-paraiso',
  'plantas|palmera areca aa': '',
  'plantas|palmera areca hb': 'plantas-palma-areca-hb',
  'plantas|arbol de olivo ga': 'plantas-olivo-ga',
  'plantas|arbol de olivo fa': 'plantas-olivo-fa',
  'plantas|arbol de olivo eb': 'plantas-olivo-eb',
  'sombrillas|sombrilla circular con pilar central': 'sombrilla-circular-con-pilar-central',
  'sombrillas|sombrilla cuadrada con ondas': 'sombrilla-cuadrada-con-ondas',
  'sombrillas|sombrilla roma petit': 'sombrilla-roma-pettit',
  'sombrillas|sombrilla banana': 'sombrilla-banana',
  'sombrillas|sombrilla con iluminacion led 2 7m': 'sombrilla-con-iluminacion-led',
  'sombrillas|sombrilla con iluminacion led 3m': '',
  'sombrillas|sombrilla roma 4x4': 'sombrilla-roma-4-x-4',
  'sombrillas|base para sombrilla circular': 'sombrilla-base-circular',
  'sombrillas|base para sombrilla rectangular': 'sombrilla-base-rectangular',
  'sombrillas|base para sombrilla cuadrada': 'sombrilla-base-cuadrada',
}));

function normalizeText(value = '') {
  return String(value)
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/ñ/g, 'n')
    .replace(/Ñ/g, 'N')
    .replace(/[^a-zA-Z0-9]+/g, ' ')
    .replace(/\s+/g, ' ')
    .trim()
    .toLowerCase();
}

function titleCase(value = '') {
  return normalizeText(value).replace(/\b[a-z]/g, (letter) => letter.toUpperCase());
}

function slugify(value = '') {
  return normalizeText(value).replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
}

function formatPrice(price) {
  return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(Number(price || 0));
}

function basePrice(product) {
  if (typeof product.precio === 'number') return product.precio;
  const prices = product.variantes?.map((variant) => variant.precio).filter((price) => typeof price === 'number') ?? [];
  return prices.length ? Math.min(...prices) : null;
}

function totalQuantity(product) {
  return (product.variantes ?? []).reduce((sum, variant) => sum + (typeof variant.cantidad === 'number' ? variant.cantidad : 0), 0);
}

function variants(product) {
  return (product.variantes ?? []).map((variant) => ({
    name: variant.nombre,
    quantity: variant.cantidad,
    price: typeof variant.precio === 'number' ? variant.precio : product.precio,
    note: variant.nota ?? '',
  }));
}

function colors(product, liveProduct) {
  const liveColors = new Map((liveProduct?.product_details?.colors ?? []).map((color) => [slugify(color.name || color.id), color]));
  return (product.variantes ?? [])
    .filter((variant) => variant.nombre)
    .map((variant) => {
      const name = titleCase(variant.nombre);
      const id = slugify(variant.nombre);
      const liveColor = liveColors.get(id);
      return {
        id,
        name,
        hex: liveColor?.hex || hexByColor.get(normalizeText(variant.nombre)) || '#cccccc',
        image: liveColor?.image || '',
        available: typeof variant.cantidad === 'number' ? variant.cantidad > 0 : true,
        quantity: variant.cantidad,
        price: typeof variant.precio === 'number' ? variant.precio : product.precio,
        note: variant.nota ?? '',
      };
    });
}

function fetchLiveProducts() {
  const raw = execFileSync('curl.exe', ['-s', liveUrl], { encoding: 'utf8', maxBuffer: 1024 * 1024 * 10 });
  return JSON.parse(raw);
}

if (!existsSync(inputPath)) throw new Error(`Inventario no encontrado: ${inputPath}`);

const inventory = JSON.parse(readFileSync(inputPath, 'utf8'));
const liveProducts = fetchLiveProducts();
const liveBySlug = new Map(liveProducts.map((product) => [product.slug, product]));
const usedLiveSlugs = new Set();
const products = [];
const unmatched = [];
let displayOrder = 1;

for (const category of inventory.categorias ?? []) {
  const categoryInfo = categoryByInventory.get(String(category.nombre_categoria || '').toUpperCase());
  if (!categoryInfo) throw new Error(`Categoria sin mapeo: ${category.nombre_categoria}`);

  for (const product of category.productos ?? []) {
    if (product.agregar_a_sitio_web === false) continue;
    const key = `${categoryInfo.slug}|${normalizeText(product.modelo)}`;
    const oldSlug = aliasByKey.get(key);
    const targetSlug = `${categoryInfo.slug}-${slugify(product.modelo)}`;
    const liveProduct = (oldSlug ? liveBySlug.get(oldSlug) : undefined) ?? liveBySlug.get(targetSlug);
    if (oldSlug) usedLiveSlugs.add(oldSlug);
    usedLiveSlugs.add(targetSlug);
    if (oldSlug === undefined) unmatched.push({ key, modelo: product.modelo, category: category.nombre_categoria });

    const price = basePrice(product);
    const compareAtPrice = '';
    const galleryUrls = liveProduct?.gallery_urls?.length ? liveProduct.gallery_urls : [];
    const sourceImage = liveProduct?.main_image_url || galleryUrls[0] || '';
    const name = titleCase(product.modelo);

    products.push({
      oldSlug: oldSlug || '',
      slug: targetSlug,
      name,
      shortDescription: `${name} disponible en ${categoryInfo.name}.`,
      description: `${name} forma parte del inventario actual de ${categoryInfo.name.toLowerCase()} para espacios exteriores.`,
      sourceImage,
      gallery: galleryUrls,
      category: categoryInfo.slug,
      categories: [categoryInfo.slug],
      collection: categoryInfo.name,
      brand: 'Milapro Home',
      sku: targetSlug.toUpperCase(),
      colors: colors(product, liveProduct),
      variants: variants(product),
      inventoryQuantity: totalQuantity(product),
      notes: product.notas ?? '',
      materials: [],
      dimensions: liveProduct?.product_details?.dimensions || 'Consultar disponibilidad',
      featured: Boolean(liveProduct?.product_details?.featured),
      available: totalQuantity(product) > 0,
      displayOrder: displayOrder++,
      specifications: [
        { label: 'Categoria', value: categoryInfo.name },
        { label: 'Inventario total', value: String(totalQuantity(product)) },
      ],
      price,
      formattedPrice: price === null ? '' : formatPrice(price),
      compareAtPrice,
      formattedCompareAtPrice: compareAtPrice ? formatPrice(compareAtPrice) : undefined,
    });
  }
}

const retireSlugs = liveProducts.map((product) => product.slug).filter((slug) => !usedLiveSlugs.has(slug));
const creates = products.filter((product) => !(product.oldSlug && liveBySlug.has(product.oldSlug)) && !liveBySlug.has(product.slug)).map((product) => product.slug);
const updates = products.filter((product) => (product.oldSlug && liveBySlug.has(product.oldSlug)) || liveBySlug.has(product.slug)).map((product) => ({ oldSlug: product.oldSlug, slug: product.slug }));

const categories = Array.from(new Map(Array.from(categoryByInventory.values()).map((category) => [category.slug, category])).values())
  .filter((category) => products.some((product) => product.category === category.slug))
  .map((category) => ({
    slug: category.slug,
    name: category.name,
    description: `Productos de ${category.name} disponibles en Milapro Home.`,
    featured: true,
    displayOrder: category.order,
  }));

const payload = { generatedAt: new Date().toISOString(), source: inputPath, categories, products, retireSlugs };
const report = {
  source: inputPath,
  liveProducts: liveProducts.length,
  inventoryProducts: (inventory.categorias ?? []).flatMap((category) => category.productos ?? []).length,
  webProducts: products.length,
  creates: creates.length,
  updates: updates.length,
  retire: retireSlugs.length,
  createSlugs: creates,
  updateSlugs: updates,
  retireSlugs,
  unmatched,
  categoryCounts: products.reduce((counts, product) => ({ ...counts, [product.category]: (counts[product.category] ?? 0) + 1 }), {}),
};

mkdirSync(dirname(outputPath), { recursive: true });
writeFileSync(outputPath, `${JSON.stringify(payload, null, 2)}\n`);
writeFileSync(reportPath, `${JSON.stringify(report, null, 2)}\n`);

console.log(`Inventario canonico escrito en ${outputPath}`);
console.log(`Reporte escrito en ${reportPath}`);
console.log(`${products.length} productos web: ${updates.length} actualizaciones, ${creates.length} altas, ${retireSlugs.length} retiros.`);
