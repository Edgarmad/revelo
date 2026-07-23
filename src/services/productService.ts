import { products } from '@/data/products';
import type { Product } from '@/types/product';

const byOrder = (a: Product, b: Product) => a.displayOrder - b.displayOrder;

export function getAllProducts(): Product[] {
  return [...products].sort(byOrder);
}

export function getFeaturedProducts(limit = 4): Product[] {
  return getAllProducts().filter((product) => product.featured).slice(0, limit);
}

export function getProductBySlug(slug: string): Product | undefined {
  return products.find((product) => product.slug === slug);
}

export function getProductsByCategory(categorySlug: string): Product[] {
  return getAllProducts().filter((product) => product.categories.includes(categorySlug));
}

export function getRelatedProducts(product: Product, limit = 4): Product[] {
  return getAllProducts()
    .filter((candidate) => candidate.id !== product.id && candidate.categories.some((category) => product.categories.includes(category)))
    .slice(0, limit);
}

export function getProductSlugs(): string[] {
  return products.map((product) => product.slug);
}
