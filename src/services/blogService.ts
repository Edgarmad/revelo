import { blogCards } from '@/data/siteContent';
import type { BlogCard } from '@/types/site';
import { decodeHtml, getFromWordPress, getPaginatedFromWordPress, stripHtml } from './wordpressClient';

interface WpPost {
  id: number;
  slug: string;
  date?: string;
  title: { rendered: string };
  excerpt: { rendered: string };
  content: { rendered: string };
  _embedded?: {
    'wp:featuredmedia'?: Array<{ source_url?: string; alt_text?: string }>;
    'wp:term'?: Array<Array<{ name: string }>>;
  };
}

export async function getAllBlogs(): Promise<BlogCard[]> {
  const posts = await getPaginatedFromWordPress<WpPost>('posts', { _embed: true, orderby: 'date', order: 'desc' });
  const publishedPosts = posts?.filter((post) => post.slug !== 'hello-world') ?? [];
  if (!publishedPosts.length) return blogCards;
  return publishedPosts.map(normalizePost);
}

export async function getBlogBySlug(slug: string): Promise<BlogCard | undefined> {
  const posts = await getFromWordPress<WpPost[]>('posts', { slug, _embed: true });
  if (posts?.[0]) return normalizePost(posts[0]);
  return blogCards.find((blog) => blog.slug === slug);
}

export async function getBlogSlugs(): Promise<string[]> {
  const posts = await getPaginatedFromWordPress<WpPost>('posts', { _fields: 'slug' });
  const slugs = posts?.map((post) => post.slug).filter((slug) => slug !== 'hello-world') ?? [];
  if (!slugs.length) return blogCards.map((blog) => blog.slug);
  return slugs;
}

function normalizePost(post: WpPost): BlogCard {
  const category = post._embedded?.['wp:term']?.[0]?.[0]?.name ?? 'Milapro Home';
  const featuredImage = post._embedded?.['wp:featuredmedia']?.[0]?.source_url ?? '/client-images/decoracion/decoracion-08.jpg';
  const contentText = stripHtml(post.content.rendered);

  return {
    title: decodeHtml(stripHtml(post.title.rendered)),
    slug: post.slug,
    category: decodeHtml(category),
    image: featuredImage,
    excerpt: decodeHtml(stripHtml(post.excerpt.rendered)),
    content: contentText ? [decodeHtml(contentText)] : [],
  };
}
