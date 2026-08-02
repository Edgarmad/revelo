import { existsSync, readFileSync, writeFileSync, mkdirSync } from 'node:fs';
import { dirname, resolve } from 'node:path';

const root = process.cwd();
const outputPath = resolve(root, 'wordpress/migration/seed.json');

const formatPrice = (price) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(price);

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

function evaluateArray(arraySource) {
  return Function(`"use strict"; return (${arraySource});`)();
}

function toWebp(image) {
  const webp = image.replace(/\.(png|jpe?g)$/i, '.webp');
  return existsSync(resolve(root, 'public', webp.replace(/^\//, ''))) ? webp : image;
}

const inventory = JSON.parse(extractArray('src/data/products.ts', 'const inventory'));
const blogCards = evaluateArray(extractArray('src/data/siteContent.ts', 'export const blogCards'));

const categories = [
  { slug: 'plantas', name: 'Plantas', eyebrow: 'Decoración exterior', description: 'Acentos verdes para completar ambientes frescos, naturales y funcionales.', sourceImage: 'style:plantas.png', featured: true, displayOrder: 1 },
  { slug: 'aluminio', name: 'Aluminio', eyebrow: 'Alta durabilidad', description: 'Muebles ligeros y resistentes para terrazas, jardines y espacios exteriores de uso diario.', sourceImage: 'style:aluminio.png', featured: true, displayOrder: 2 },
  { slug: 'ratan', name: 'Ratán', eyebrow: 'Muebles para exterior', description: 'Salas, sillones y comedores tejidos para terrazas, jardines y áreas sociales al aire libre.', sourceImage: 'style:ratan.png', featured: true, displayOrder: 3 },
  { slug: 'plastico', name: 'Plástico', eyebrow: 'Muebles para exterior', description: 'Piezas prácticas, fáciles de limpiar y resistentes para áreas exteriores de alto uso.', sourceImage: 'style:plastico.png', featured: true, displayOrder: 4 },
  { slug: 'outlet', name: 'Outlet', eyebrow: 'Oportunidades especiales', description: 'Piezas seleccionadas con precios especiales para renovar espacios exteriores.', sourceImage: 'style:outlet.png', featured: true, displayOrder: 5 },
  { slug: 'descuentos', name: 'Descuentos', eyebrow: 'Precios especiales', description: 'Selección de productos con descuento para renovar terrazas, jardines y espacios exteriores.', sourceImage: 'style:outlet.png', featured: true, displayOrder: 6 },
];

const products = inventory.map((product, index) => {
  const hasDiscount = product.compareAtPrice !== undefined;
  const discountPercentage = hasDiscount ? Math.round((1 - product.price / product.compareAtPrice) * 100) : undefined;
  const mainImage = toWebp(product.image);

  return {
    slug: product.slug,
    name: product.name,
    shortDescription: `${product.name} disponible en ${product.collection}.`,
    description: `${product.name} forma parte del inventario actual de ${product.collection.toLowerCase()} para espacios exteriores.`,
    sourceImage: mainImage,
    gallery: [mainImage],
    category: product.category,
    categories: hasDiscount ? [product.category, 'descuentos'] : [product.category],
    collection: product.collection,
    brand: 'Milapro Home',
    sku: product.slug.toUpperCase(),
    colors: product.colors,
    materials: [],
    dimensions: 'Consultar disponibilidad',
    featured: product.featured,
    available: product.available,
    displayOrder: index + 1,
    specifications: [{ label: 'Categoria', value: product.collection }],
    price: product.price,
    formattedPrice: formatPrice(product.price),
    compareAtPrice: product.compareAtPrice,
    formattedCompareAtPrice: hasDiscount ? formatPrice(product.compareAtPrice) : undefined,
    badge: discountPercentage ? `${discountPercentage}% OFF` : undefined,
  };
});

const reels = [
  { slug: 'reel-01', title: 'Reel 1', videoUrl: 'https://www.instagram.com/milaprohome.mid/reel/DbE5EoXtMei/', sourceImage: '/instagram-reels/reel-01.jpg', platform: 'instagram', displayOrder: 1, visible: true },
  { slug: 'reel-02', title: 'Reel 2', videoUrl: 'https://www.instagram.com/milaprohome.mid/reel/Da6HAhoJtuT/', sourceImage: '/instagram-reels/reel-02.jpg', platform: 'instagram', displayOrder: 2, visible: true },
  { slug: 'reel-03', title: 'Reel 3', videoUrl: 'https://www.instagram.com/milaprohome.mid/reel/DaLX89wR8Gn/', sourceImage: '/instagram-reels/reel-03.jpg', platform: 'instagram', displayOrder: 3, visible: true },
  { slug: 'reel-04', title: 'Reel 4', videoUrl: 'https://www.instagram.com/milaprohome.mid/reel/DTl0X5QD12n/', sourceImage: '/instagram-reels/reel-04.jpg', platform: 'instagram', displayOrder: 4, visible: true },
  { slug: 'reel-05', title: 'Reel 5', videoUrl: 'https://www.instagram.com/milaprohome.mid/reel/DUJOXy7kdGP/', sourceImage: '/instagram-reels/reel-05.jpg', platform: 'instagram', displayOrder: 5, visible: true },
  { slug: 'reel-06', title: 'Reel 6', videoUrl: 'https://www.instagram.com/milaprohome.mid/reel/DRU30uZgfVq/', sourceImage: '/instagram-reels/reel-06.jpg', platform: 'instagram', displayOrder: 6, visible: true },
  { slug: 'reel-07', title: 'Reel 7', videoUrl: 'https://www.instagram.com/milaprohome.mid/reel/DPouY0NklzU/', sourceImage: '/instagram-reels/reel-07.jpg', platform: 'instagram', displayOrder: 7, visible: true },
  { slug: 'reel-08', title: 'Reel 8', videoUrl: 'https://www.instagram.com/milaprohome.mid/reel/DPUBKIkjWwX/', sourceImage: '/instagram-reels/reel-08.jpg', platform: 'instagram', displayOrder: 8, visible: true },
  { slug: 'reel-09', title: 'Reel 9', videoUrl: 'https://www.instagram.com/milaprohome.mid/reel/DOWnTiViefZ/', sourceImage: '/instagram-reels/reel-09.jpg', platform: 'instagram', displayOrder: 9, visible: true },
];

const blogs = blogCards.map((blog) => ({
  slug: blog.slug,
  title: blog.title,
  category: blog.category,
  excerpt: blog.excerpt,
  sourceImage: blog.image,
  content: blog.content.map((paragraph) => `<p>${paragraph}</p>`).join('\n'),
}));

mkdirSync(dirname(outputPath), { recursive: true });
writeFileSync(outputPath, `${JSON.stringify({ categories, products, reels, blogs }, null, 2)}\n`);

console.log(`WordPress seed written to ${outputPath}`);
console.log(`${categories.length} categories, ${products.length} products, ${reels.length} reels, ${blogs.length} blogs`);
