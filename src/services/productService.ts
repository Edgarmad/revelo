import { products } from '@/data/products';
import type { Product } from '@/types/product';
import type { ProductColor, ProductSpecification } from '@/types/product';
import { getAllCategories } from './categoryService';
import { decodeHtml, getFromWordPress, getPaginatedFromWordPress, stripHtml, wpBoolean, wpImageUrl } from './wordpressClient';

const byOrder = (a: Product, b: Product) => a.displayOrder - b.displayOrder;
const formatPrice = (price: number) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(price);
const fallbackImage = '/client-images/decoracion/decoracion-01.jpg';
const categoryPlaceholders: Record<string, string> = {
  aluminio: '/product-images/camastros-home.png',
  descuentos: '/client-images/decoracion/decoracion-01.jpg',
  outlet: '/client-images/decoracion/decoracion-01.jpg',
  plantas: '/product-images/plantas-ave-de-paraiso.webp',
  plastico: '/product-images/plastico-silla-narciso.webp',
  ratan: '/product-images/ratan-madrid.webp',
};
const localProductBySlug = new Map(products.map((product) => [normalizeSlug(product.slug), product]));

interface WpProductAcf {
  price?: number | string;
  compare_at_price?: number | string;
  sku?: string;
  available?: boolean | number | string;
  featured?: boolean | number | string;
  sale?: boolean | number | string;
  display_order?: number | string;
  collection?: string;
  brand?: string;
  dimensions?: string;
  keywords?: string;
  gallery?: unknown[];
  gallery_images?: Array<{ image?: unknown }>;
  colors?: Array<{ id?: string; name?: string; hex?: string; image?: unknown; available?: boolean | number | string }>;
  specifications?: ProductSpecification[];
}

interface WpProduct {
  id: number;
  slug: string;
  title: { rendered: string };
  excerpt?: { rendered: string };
  content?: { rendered: string };
  featured_media?: number;
  product_category?: number[];
  gallery_urls?: string[];
  main_image_url?: string;
  product_details?: WpProductAcf;
  acf?: WpProductAcf;
  _embedded?: {
    'wp:featuredmedia'?: Array<{ source_url?: string }>;
  };
}

export async function getAllProducts(): Promise<Product[]> {
  const wpProducts = await getPaginatedFromWordPress<WpProduct>('products', { _embed: true, orderby: 'date', order: 'asc' });
  if (!wpProducts?.length) return [...products].sort(byOrder);

  const categories = await getAllCategories();
  const categoryById = new Map(categories.map((category) => [Number(category.id.replace('cat-', '')), category]));
  return wpProducts.map((product) => normalizeProduct(product, categoryById, findLocalProduct(product.slug))).sort(byOrder);
}

export async function getFeaturedProducts(limit = 4): Promise<Product[]> {
  return (await getAllProducts()).filter((product) => product.featured).slice(0, limit);
}

export async function getDiscountedProducts(): Promise<Product[]> {
  return (await getAllProducts()).filter((product) => product.sale);
}

export async function getProductBySlug(slug: string): Promise<Product | undefined> {
  const wpProducts = await getFromWordPress<WpProduct[]>('products', { slug, _embed: true });
  if (wpProducts?.[0]) {
    const categories = await getAllCategories();
    const categoryById = new Map(categories.map((category) => [Number(category.id.replace('cat-', '')), category]));
    return normalizeProduct(wpProducts[0], categoryById, findLocalProduct(slug));
  }

  return products.find((product) => product.slug === slug);
}

export async function getProductsByCategory(categorySlug: string): Promise<Product[]> {
  return (await getAllProducts()).filter((product) => product.categories.includes(categorySlug));
}

export async function getRelatedProducts(product: Product, limit = 4): Promise<Product[]> {
  return (await getAllProducts())
    .filter((candidate) => candidate.id !== product.id && candidate.categories.some((category) => product.categories.includes(category)))
    .slice(0, limit);
}

export async function getProductSlugs(): Promise<string[]> {
  const wpProducts = await getPaginatedFromWordPress<WpProduct>('products', { _fields: 'slug' });
  if (!wpProducts?.length) return products.map((product) => product.slug);
  return wpProducts.map((product) => product.slug);
}

function normalizeProduct(product: WpProduct, categoryById: Map<number, { slug: string; name: string }>, localProduct?: Product): Product {
  const acf = product.product_details ?? product.acf ?? {};
  const title = decodeHtml(stripHtml(product.title.rendered));
  const termCategories = (product.product_category ?? []).map((id) => categoryById.get(id)).filter(Boolean) as Array<{ slug: string; name: string }>;
  const primaryCategory = termCategories[0];
  const priceRaw = Number(acf.price ?? 0);
  const compareAtPriceRaw = acf.compare_at_price ? Number(acf.compare_at_price) : undefined;
  const hasDiscount = compareAtPriceRaw !== undefined && compareAtPriceRaw > priceRaw;
  const categories = termCategories.map((category) => category.slug);
  if (hasDiscount && !categories.includes('descuentos')) categories.push('descuentos');
  const placeholder = getCategoryPlaceholder(categories, primaryCategory?.slug);
  const wpMainImage = uniqueImages([
    product.main_image_url,
    product._embedded?.['wp:featuredmedia']?.[0]?.source_url,
  ])[0];
  const image = wpMainImage ?? firstValidImage([localProduct?.mainImage, placeholder, fallbackImage]);
  const repeaterGallery = uniqueImages((acf.gallery_images ?? []).map((item) => wpImageUrl(item.image)));
  const localGallery = uniqueImages(localProduct?.gallery ?? []);
  const wpGallery = firstImageList([uniqueImages(product.gallery_urls ?? []), repeaterGallery], []);
  const gallery = wpMainImage
    ? uniqueImages([image, ...wpGallery])
    : firstImageList([localGallery, uniqueImages([localProduct?.mainImage]), uniqueImages([image])]);

  warnIfUsingLocalProductImages(product.slug, { hasWpMainImage: Boolean(wpMainImage), hasWpGallery: wpGallery.length > 0 });

  return {
    id: `product-${product.id}`,
    slug: product.slug,
    name: title,
    shortDescription: decodeHtml(stripHtml(product.excerpt?.rendered ?? '')) || `${title} disponible en ${acf.collection || primaryCategory?.name || 'Milapro Home'}.`,
    description: decodeHtml(stripHtml(product.content?.rendered ?? '')) || `${title} forma parte del inventario actual de Milapro Home.`,
    mainImage: image,
    gallery,
    category: primaryCategory?.slug ?? 'productos',
    categories: categories.length ? categories : ['productos'],
    collection: decodeHtml(acf.collection || primaryCategory?.name || 'Productos'),
    brand: decodeHtml(acf.brand || 'Milapro Home'),
    sku: acf.sku || product.slug.toUpperCase(),
    colors: normalizeColors(acf.colors, localProduct?.colors),
    materials: [],
    dimensions: decodeHtml(acf.dimensions || 'Consultar disponibilidad'),
    featured: wpBoolean(acf.featured),
    available: wpBoolean(acf.available, true),
    sale: wpBoolean(acf.sale, false),
    displayOrder: Number(acf.display_order ?? 999),
    tags: buildProductTags({ title, collection: acf.collection || primaryCategory?.name || 'Productos', categories, keywords: acf.keywords }),
    specifications: acf.specifications?.length ? acf.specifications : [{ label: 'Categoria', value: primaryCategory?.name ?? 'Productos' }],
    price: formatPrice(priceRaw),
    compareAtPrice: hasDiscount ? formatPrice(compareAtPriceRaw) : undefined,
    badge: hasDiscount ? `${Math.round((1 - priceRaw / compareAtPriceRaw) * 100)}% OFF` : undefined,
  };
}

function buildProductTags({ title, collection, categories, keywords }: { title: string; collection: string; categories: string[]; keywords?: string }): string[] {
  const categoryAliases: Record<string, string[]> = {
    plantas: ['planta', 'plantas', 'plant', 'plants', 'decoracion', 'decoración'],
    ratan: ['ratan', 'ratán', 'rattan', 'sala', 'sofa', 'sofá', 'sillon', 'sillón', 'living room', 'couch', 'outdoor sofa'],
    aluminio: ['aluminio', 'aluminum', 'sala', 'mesa', 'mesas', 'comedor', 'dining room', 'dining table', 'table', 'chair', 'silla'],
    plastico: ['plastico', 'plástico', 'plastic', 'mesa', 'mesas', 'comedor', 'dining room', 'dining table', 'table', 'chair', 'silla'],
  };

  const wpKeywords = keywords?.split(/[,\n]+/).map((keyword) => keyword.trim()).filter(Boolean) ?? [];
  return Array.from(new Set([title, collection, ...categories, ...categories.flatMap((category) => categoryAliases[category] ?? []), ...wpKeywords]));
}

function normalizeColors(colors: WpProductAcf['colors'] = [], localColors: ProductColor[] = []): ProductColor[] {
  return (colors ?? []).map((color) => ({
    id: color.id || color.name?.toLowerCase().replace(/\s+/g, '-') || 'color',
    name: color.name || 'Color',
    hex: color.hex || '#cccccc',
    image: wpImageUrl(color.image) || findLocalColor(color, localColors)?.image,
    available: wpBoolean(color.available, true),
  }));
}

function findLocalProduct(slug: string): Product | undefined {
  return localProductBySlug.get(normalizeSlug(slug));
}

function findLocalColor(color: { id?: string; name?: string }, localColors: ProductColor[]): ProductColor | undefined {
  const id = normalizeSlug(color.id ?? '');
  const name = normalizeSlug(color.name ?? '');
  return localColors.find((localColor) => {
    if (id && normalizeSlug(localColor.id) === id) return true;
    if (name && normalizeSlug(localColor.name) === name) return true;
    return false;
  });
}

function normalizeSlug(value = ''): string {
  return value
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '');
}

function firstValidImage(images: Array<string | undefined>): string {
  return uniqueImages(images)[0] ?? fallbackImage;
}

function uniqueImages(images: Array<string | undefined>): string[] {
  return Array.from(new Set(images.map((image) => image?.trim()).filter(Boolean) as string[]));
}

function firstImageList(lists: string[][], fallback: string[] = [fallbackImage]): string[] {
  return lists.find((list) => list.length > 0) ?? fallback;
}

function warnIfUsingLocalProductImages(slug: string, { hasWpMainImage, hasWpGallery }: { hasWpMainImage: boolean; hasWpGallery: boolean }): void {
  if (hasWpMainImage && hasWpGallery) return;
  const missing = [!hasWpMainImage ? 'main image' : '', !hasWpGallery ? 'gallery' : ''].filter(Boolean).join(' and ');
  console.warn(`[wordpress] product ${slug} is missing WP ${missing}; using local image fallback.`);
}

function getCategoryPlaceholder(categories: string[], primaryCategory?: string): string {
  const category = [...categories, primaryCategory]
    .filter((item): item is string => Boolean(item))
    .sort((category) => (category === 'descuentos' || category === 'outlet' ? 1 : -1))
    .find((item) => categoryPlaceholders[item]);
  return category ? categoryPlaceholders[category] : fallbackImage;
}
