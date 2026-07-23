import type { BlogCard, NavItem, Testimonial } from '@/types/site';

export const navItems: NavItem[] = [
  { label: 'Modular Products', href: '/products/' },
  { label: 'Reclining Couch', href: '/categories/reclinables/' },
  { label: 'July Comfort Event', href: '/categories/exterior/' },
  { label: 'Reviews', href: '/#reviews' },
  { label: 'About Us', href: '/about/' },
  { label: 'Free Swatches', href: '/contact/' },
];

export const testimonials: Testimonial[] = [
  {
    name: 'Troy N.',
    product: 'Rubik V',
    quote: 'El sofa se ve mejor en persona y fue sencillo reorganizarlo para reuniones familiares. La tela se siente resistente y facil de cuidar.',
    image: 'https://images.unsplash.com/photo-1618220179428-22790b461013?auto=format&fit=crop&w=900&q=80',
    rating: 5,
  },
];

export const blogCards: BlogCard[] = [
  {
    title: 'Como elegir un sofa modular para una sala activa',
    category: 'Reclining Couch',
    image: 'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?auto=format&fit=crop&w=800&q=80',
    excerpt: 'Ideas para configurar zonas de descanso, conversacion y juego sin perder orden visual.',
  },
  {
    title: 'Texturas claras que si funcionan con familia y mascotas',
    category: 'Milapro Home',
    image: 'https://images.unsplash.com/photo-1615874694520-474822394e73?auto=format&fit=crop&w=800&q=80',
    excerpt: 'Tapizados performance, tonos calidos y mantenimiento sencillo para el dia a dia.',
  },
  {
    title: 'La guia rapida para reclinables modernos',
    category: 'Reclining Couch',
    image: 'https://images.unsplash.com/photo-1616627561950-9f746e330187?auto=format&fit=crop&w=800&q=80',
    excerpt: 'Comodidad profunda sin que la sala se sienta pesada o anticuada.',
  },
];

export const socialGallery = [
  'https://images.unsplash.com/photo-1600566753190-17f0baa2a6c3?auto=format&fit=crop&w=500&q=80',
  'https://images.unsplash.com/photo-1616486338812-3dadae4b4ace?auto=format&fit=crop&w=500&q=80',
  'https://images.unsplash.com/photo-1618220179428-22790b461013?auto=format&fit=crop&w=500&q=80',
  'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?auto=format&fit=crop&w=500&q=80',
  'https://images.unsplash.com/photo-1600210492486-724fe5c67fb0?auto=format&fit=crop&w=500&q=80',
  'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?auto=format&fit=crop&w=500&q=80',
];
