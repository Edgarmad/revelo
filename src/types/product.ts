export interface ProductColor {
  id: string;
  name: string;
  hex: string;
  image?: string;
  gallery?: string[];
  available: boolean;
}

export interface ProductSpecification {
  label: string;
  value: string;
}

export interface Product {
  id: string;
  slug: string;
  name: string;
  shortDescription: string;
  description: string;
  mainImage: string;
  gallery: string[];
  category: string;
  categories: string[];
  collection: string;
  brand: string;
  sku: string;
  colors: ProductColor[];
  materials: string[];
  dimensions: string;
  featured: boolean;
  available: boolean;
  displayOrder: number;
  tags: string[];
  specifications: ProductSpecification[];
  price: string;
  compareAtPrice?: string;
  badge?: string;
}
