import { categories } from '@/data/categories';
import type { Category } from '@/types/category';
import { decodeHtml, getPaginatedFromWordPress, stripHtml, wpBoolean, wpImageUrl } from './wordpressClient';

const byOrder = (a: Category, b: Category) => a.displayOrder - b.displayOrder;

interface WpCategoryTerm {
  id: number;
  slug: string;
  name: string;
  description?: string;
  category_image_url?: string;
  category_details?: {
    eyebrow?: string;
    display_order?: number | string;
    featured?: boolean | number | string;
    category_image?: number;
  };
  acf?: {
    category_image?: unknown;
    eyebrow?: string;
    display_order?: number | string;
    featured?: boolean | number | string;
  };
}

export async function getAllCategories(): Promise<Category[]> {
  const terms = await getPaginatedFromWordPress<WpCategoryTerm>('product_category', { hide_empty: false });
  if (!terms?.length) return [...categories].sort(byOrder);

  return terms.map(normalizeCategory).sort(byOrder);
}

export async function getFeaturedCategories(limit = 4): Promise<Category[]> {
  return (await getAllCategories()).filter((category) => category.featured).slice(0, limit);
}

export async function getCategoryBySlug(slug: string): Promise<Category | undefined> {
  return (await getAllCategories()).find((category) => category.slug === slug);
}

export async function getCategorySlugs(): Promise<string[]> {
  return (await getAllCategories()).map((category) => category.slug);
}

function normalizeCategory(term: WpCategoryTerm): Category {
  const local = categories.find((category) => category.slug === term.slug);
  const acf = term.acf ?? {};
  const details = term.category_details ?? {};

  return {
    id: `cat-${term.id}`,
    slug: term.slug,
    name: decodeHtml(term.name),
    eyebrow: details.eyebrow ? decodeHtml(details.eyebrow) : acf.eyebrow ? decodeHtml(acf.eyebrow) : local?.eyebrow ?? 'Catálogo',
    description: decodeHtml(stripHtml(term.description ?? '')) || local?.description || '',
    image: term.category_image_url || wpImageUrl(acf.category_image) || local?.image || '/client-images/decoracion/decoracion-01.jpg',
    featured: wpBoolean(details.featured ?? acf.featured, local?.featured ?? true),
    displayOrder: Number(details.display_order ?? acf.display_order ?? local?.displayOrder ?? 999),
  };
}
