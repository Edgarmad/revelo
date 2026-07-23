export interface NavItem {
  label: string;
  href: string;
}

export interface CustomerReview {
  name: string;
  product: string;
  quote: string;
  image: string;
  rating: 4 | 5;
}

export interface BlogCard {
  title: string;
  slug: string;
  category: string;
  image: string;
  excerpt: string;
  content: string[];
}
