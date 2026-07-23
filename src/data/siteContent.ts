import type { BlogCard, CustomerReview, NavItem } from '@/types/site';

export const navItems: NavItem[] = [
  { label: 'Muebles De Exterior', href: '/categories/exterior/' },
  { label: '🔥 Productos', href: '/products/' },
  { label: 'Reviews', href: '/#reviews' },
  { label: 'About Us', href: '/about/' },
  { label: 'Contacto', href: '/contact/' },
];

export const customerReviews: CustomerReview[] = [
  {
    name: 'Mariana G.',
    product: 'Sala exterior en rattan',
    quote: 'La sala transformo nuestra terraza. Se siente resistente, comoda y lista para reuniones familiares sin preocuparnos por el clima.',
    image: 'https://images.unsplash.com/photo-1600566753190-17f0baa2a6c3?auto=format&fit=crop&w=900&q=80',
    rating: 5,
  },
  {
    name: 'Carlos M.',
    product: 'Comedor exterior de aluminio',
    quote: 'Llegaron a tiempo y el comedor se ve elegante sin sentirse delicado. Lo usamos diario y limpiarlo toma muy poco tiempo.',
    image: 'https://images.unsplash.com/photo-1600210492486-724fe5c67fb0?auto=format&fit=crop&w=900&q=80',
    rating: 5,
  },
  {
    name: 'Lucia P.',
    product: 'Pergola para jardin',
    quote: 'La pergola cambio por completo el area de la alberca. Ahora tenemos sombra agradable y el espacio se siente mucho mas terminado.',
    image: 'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?auto=format&fit=crop&w=900&q=80',
    rating: 5,
  },
  {
    name: 'Andres R.',
    product: 'Sombrilla lateral',
    quote: 'La sombrilla es firme, cubre muy bien y nos ayudo a usar la terraza durante las horas de mas sol. Excelente compra.',
    image: 'https://images.unsplash.com/photo-1615874694520-474822394e73?auto=format&fit=crop&w=900&q=80',
    rating: 5,
  },
  {
    name: 'Sofia L.',
    product: 'Set lounge para balcon',
    quote: 'El set quedo perfecto para un balcon pequeno. La calidad se siente muy buena y el color combina con plantas y accesorios.',
    image: 'https://images.unsplash.com/photo-1616486338812-3dadae4b4ace?auto=format&fit=crop&w=900&q=80',
    rating: 4,
  },
  {
    name: 'Diego H.',
    product: 'Mesa auxiliar exterior',
    quote: 'La mesa es practica, estable y facil de mover. Me hubiera gustado una medida adicional, pero la calidad esta excelente.',
    image: 'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?auto=format&fit=crop&w=900&q=80',
    rating: 4,
  },
  {
    name: 'Valeria T.',
    product: 'Proyecto terraza restaurante',
    quote: 'Nos ayudaron a elegir piezas resistentes para uso comercial. La terraza se ve mas ordenada y los clientes la disfrutan mucho.',
    image: 'https://images.unsplash.com/photo-1616627561950-9f746e330187?auto=format&fit=crop&w=900&q=80',
    rating: 5,
  },
];

export const blogCards: BlogCard[] = [
  {
    title: 'Como elegir muebles de exterior para una terraza activa',
    slug: 'como-elegir-muebles-exterior-terraza-activa',
    category: 'Guia de exterior',
    image: 'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?auto=format&fit=crop&w=800&q=80',
    excerpt: 'Ideas para configurar zonas de descanso, conversacion y juego sin perder orden visual.',
    content: [
      'Una terraza activa necesita piezas que acompanen distintos momentos del dia: descanso, conversacion, comida y juego. Antes de elegir muebles, define cuantas personas usaran el espacio con frecuencia y que actividades quieres priorizar.',
      'Para mantener orden visual, agrupa por zonas. Una sala modular puede marcar el area social, una mesa auxiliar resuelve apoyo diario y una sombrilla o pergola ayuda a controlar el sol sin recargar el ambiente.',
      'Elige materiales resistentes al exterior y telas faciles de limpiar. En espacios familiares o de uso constante, conviene priorizar estructuras firmes, cojines removibles y colores neutros que puedan renovarse con accesorios.',
    ],
  },
  {
    title: 'Materiales resistentes para sol, humedad y uso diario',
    slug: 'materiales-resistentes-sol-humedad-uso-diario',
    category: 'Milapro Home',
    image: 'https://images.unsplash.com/photo-1615874694520-474822394e73?auto=format&fit=crop&w=800&q=80',
    excerpt: 'Rattan sintetico, aluminio y plastico de facil mantenimiento para espacios exteriores.',
    content: [
      'Los muebles de exterior estan expuestos a sol, humedad, polvo y movimiento constante. Por eso, la seleccion del material determina tanto la durabilidad como la facilidad de mantenimiento.',
      'El rattan sintetico ofrece una apariencia calida con buena resistencia para terrazas y jardines. El aluminio aporta ligereza y estabilidad frente a la corrosion, mientras que los plasticos de calidad son practicos para areas de alto uso.',
      'La clave esta en combinar estructura, tejido y cuidado. Limpiezas periodicas, proteccion en temporadas de lluvia intensa y una ubicacion bien ventilada ayudan a conservar mejor cada pieza.',
    ],
  },
  {
    title: 'Ideas para proyectos a la medida',
    slug: 'ideas-para-proyectos-a-la-medida',
    category: 'Proyectos',
    image: 'https://images.unsplash.com/photo-1616627561950-9f746e330187?auto=format&fit=crop&w=800&q=80',
    excerpt: 'Soluciones para arquitectos, hoteles, restaurantes y hogares que buscan diseno funcional.',
    content: [
      'Los proyectos a la medida permiten que cada pieza responda al uso real del espacio. En hoteles, restaurantes y hogares amplios, el mobiliario debe equilibrar estetica, circulacion, resistencia y mantenimiento.',
      'Antes de producir o seleccionar piezas, conviene revisar medidas, recorridos, exposicion solar y cantidad de usuarios. Esta informacion ayuda a definir formatos, modulos, alturas y materiales adecuados.',
      'Una propuesta funcional tambien considera continuidad visual. Repetir tonos, texturas o lineas de diseno crea ambientes coherentes sin perder flexibilidad para futuras ampliaciones.',
    ],
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
