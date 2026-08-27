import { blogCards } from '@/data/siteContent';
import type { BlogCard } from '@/types/site';
import { decodeHtml, getFromWordPress, getPaginatedFromWordPress, stripHtml, wpBoolean } from './wordpressClient';

interface WpBlog {
  id: number;
  slug: string;
  title: { rendered: string };
  excerpt?: { rendered: string };
  blog_image_url?: string;
  blog_details?: {
    eyebrow?: string;
    intro_text?: string;
    body_text?: string;
    display_order?: number;
    is_visible?: boolean | number | string;
  };
}

export async function getAllBlogs(): Promise<BlogCard[]> {
  const posts = await getPaginatedFromWordPress<WpBlog>('blogs');
  if (!posts) return blogCards;

  return posts
    .filter((post) => wpBoolean(post.blog_details?.is_visible, true))
    .sort((a, b) => (a.blog_details?.display_order ?? 999) - (b.blog_details?.display_order ?? 999))
    .map(normalizeBlog);
}

export async function getBlogBySlug(slug: string): Promise<BlogCard | undefined> {
  const posts = await getFromWordPress<WpBlog[]>('blogs', { slug });
  if (!posts) return blogCards.find((blog) => blog.slug === slug);
  if (!posts[0] || !wpBoolean(posts[0].blog_details?.is_visible, true)) return undefined;
  return normalizeBlog(posts[0]);
}

export async function getBlogSlugs(): Promise<string[]> {
  const posts = await getPaginatedFromWordPress<WpBlog>('blogs', { _fields: 'slug,blog_details' });
  if (!posts) return blogCards.map((blog) => blog.slug);
  return posts.filter((post) => wpBoolean(post.blog_details?.is_visible, true)).map((post) => post.slug);
}

function normalizeBlog(post: WpBlog): BlogCard {
  const localFallback = blogCards.find((blog) => blog.slug === post.slug);
  const title = decodeHtml(stripHtml(post.title.rendered));
  const introText = post.blog_details?.intro_text || stripHtml(post.excerpt?.rendered ?? '');
  const bodyText = post.blog_details?.body_text ?? '';

  return {
    title,
    slug: post.slug,
    category: decodeHtml(post.blog_details?.eyebrow || 'Milapro Home'),
    image: post.blog_image_url || localFallback?.image || '/client-images/decoracion/decoracion-08.jpg',
    excerpt: decodeHtml(stripHtml(introText)),
    content: bodyText ? bodyText.split(/\n{2,}/).map((paragraph) => decodeHtml(stripHtml(paragraph))).filter(Boolean) : (localFallback?.content ?? []),
  };
}
