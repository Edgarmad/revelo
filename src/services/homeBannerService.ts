import { decodeHtml, getFromWordPress } from './wordpressClient';

export interface HomeBanner {
  eyebrow: string;
  title: string;
  text: string;
  image: string;
}

export interface HomeBanners {
  hero_banner: HomeBanner;
  popup_banner: HomeBanner;
  carousel_banner: HomeBanner;
}

interface WpHomeBanner {
  eyebrow?: string;
  title?: string;
  text?: string;
  image_url?: string;
}

type WpHomeBanners = Partial<Record<keyof HomeBanners, WpHomeBanner>>;

const fallbackHomeBanners: HomeBanners = {
  hero_banner: {
    eyebrow: 'Oferta de verano',
    title: 'Hasta 50% de descuento',
    text: '',
    image: '',
  },
  popup_banner: {
    eyebrow: 'Oferta por tiempo limitado',
    title: 'Obtén hasta 50% OFF',
    text: 'Déjanos tu correo y recibe promociones, novedades y asesoría para renovar tus espacios exteriores.',
    image: '',
  },
  carousel_banner: {
    eyebrow: 'Oferta de verano',
    title: 'Hasta 50% de descuento',
    text: '',
    image: '',
  },
};

export async function getHomeBanners(): Promise<HomeBanners> {
  const wpBanners = await getFromWordPress<WpHomeBanners>('milapro/v1/home-banners');
  if (!wpBanners) return fallbackHomeBanners;

  return {
    hero_banner: normalizeBanner(wpBanners.hero_banner, fallbackHomeBanners.hero_banner),
    popup_banner: normalizeBanner(wpBanners.popup_banner, fallbackHomeBanners.popup_banner),
    carousel_banner: normalizeBanner(wpBanners.carousel_banner, fallbackHomeBanners.carousel_banner),
  };
}

function normalizeBanner(banner: WpHomeBanner | undefined, fallback: HomeBanner): HomeBanner {
  return {
    eyebrow: decodeHtml(banner?.eyebrow || fallback.eyebrow),
    title: decodeHtml(banner?.title || fallback.title),
    text: decodeHtml(banner?.text || fallback.text),
    image: banner?.image_url || fallback.image,
  };
}
