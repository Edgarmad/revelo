import { categories } from '@/data/categories';
import type { Category } from '@/types/category';

const byOrder = (a: Category, b: Category) => a.displayOrder - b.displayOrder;

export function getAllCategories(): Category[] {
  return [...categories].sort(byOrder);
}

export function getFeaturedCategories(limit = 4): Category[] {
  return getAllCategories().filter((category) => category.featured).slice(0, limit);
}

export function getCategoryBySlug(slug: string): Category | undefined {
  return categories.find((category) => category.slug === slug);
}

export function getCategorySlugs(): string[] {
  return categories.map((category) => category.slug);
}
