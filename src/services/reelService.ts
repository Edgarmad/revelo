import type { Reel } from '@/types/reel';
import { decodeHtml, getPaginatedFromWordPress, stripHtml, wpBoolean, wpImageUrl } from './wordpressClient';

const localReels: Reel[] = [
  { id: 'reel-01', url: 'https://www.instagram.com/milaprohome.mid/reel/DbE5EoXtMei/', thumbnail: '/instagram-reels/reel-01.jpg', title: 'Reel 1', platform: 'instagram', displayOrder: 1, visible: true },
  { id: 'reel-02', url: 'https://www.instagram.com/milaprohome.mid/reel/Da6HAhoJtuT/', thumbnail: '/instagram-reels/reel-02.jpg', title: 'Reel 2', platform: 'instagram', displayOrder: 2, visible: true },
  { id: 'reel-03', url: 'https://www.instagram.com/milaprohome.mid/reel/DaLX89wR8Gn/', thumbnail: '/instagram-reels/reel-03.jpg', title: 'Reel 3', platform: 'instagram', displayOrder: 3, visible: true },
  { id: 'reel-04', url: 'https://www.instagram.com/milaprohome.mid/reel/DTl0X5QD12n/', thumbnail: '/instagram-reels/reel-04.jpg', title: 'Reel 4', platform: 'instagram', displayOrder: 4, visible: true },
  { id: 'reel-05', url: 'https://www.instagram.com/milaprohome.mid/reel/DUJOXy7kdGP/', thumbnail: '/instagram-reels/reel-05.jpg', title: 'Reel 5', platform: 'instagram', displayOrder: 5, visible: true },
  { id: 'reel-06', url: 'https://www.instagram.com/milaprohome.mid/reel/DRU30uZgfVq/', thumbnail: '/instagram-reels/reel-06.jpg', title: 'Reel 6', platform: 'instagram', displayOrder: 6, visible: true },
  { id: 'reel-07', url: 'https://www.instagram.com/milaprohome.mid/reel/DPouY0NklzU/', thumbnail: '/instagram-reels/reel-07.jpg', title: 'Reel 7', platform: 'instagram', displayOrder: 7, visible: true },
  { id: 'reel-08', url: 'https://www.instagram.com/milaprohome.mid/reel/DPUBKIkjWwX/', thumbnail: '/instagram-reels/reel-08.jpg', title: 'Reel 8', platform: 'instagram', displayOrder: 8, visible: true },
  { id: 'reel-09', url: 'https://www.instagram.com/milaprohome.mid/reel/DOWnTiViefZ/', thumbnail: '/instagram-reels/reel-09.jpg', title: 'Reel 9', platform: 'instagram', displayOrder: 9, visible: true },
];

interface WpReel {
  id: number;
  title: { rendered: string };
  cover_image_url?: string;
  reel_details?: {
    video_url?: string;
    cover_image?: unknown;
    platform?: Reel['platform'];
    display_order?: number | string;
    is_visible?: boolean | number | string;
  };
  acf?: {
    video_url?: string;
    cover_image?: unknown;
    platform?: Reel['platform'];
    display_order?: number | string;
    is_visible?: boolean | number | string;
  };
}

export async function getVisibleReels(): Promise<Reel[]> {
  const reels = await getPaginatedFromWordPress<WpReel>('reels', { orderby: 'date', order: 'asc' });
  if (!reels?.length) return localReels;

  const normalized = reels.map(normalizeReel).filter((reel) => reel.visible && reel.url && reel.thumbnail);
  return normalized.length ? normalized.sort((a, b) => a.displayOrder - b.displayOrder) : localReels;
}

function normalizeReel(reel: WpReel): Reel {
  const acf = reel.reel_details ?? reel.acf ?? {};
  return {
    id: `reel-${reel.id}`,
    title: decodeHtml(stripHtml(reel.title.rendered)),
    url: acf.video_url ?? '',
    thumbnail: reel.cover_image_url || wpImageUrl(acf.cover_image) || '',
    platform: acf.platform ?? 'instagram',
    displayOrder: Number(acf.display_order ?? 999),
    visible: wpBoolean(acf.is_visible, true),
  };
}
