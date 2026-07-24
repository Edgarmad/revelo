import type { BlogCard, CustomerReview, NavItem } from '@/types/site';

export const navItems: NavItem[] = [
  { label: 'Muebles de exterior', href: '/categories/exterior/' },
  { label: '🔥 Productos', href: '/products/' },
  { label: 'Opiniones', href: '/#reviews' },
  { label: 'Acerca de', href: '/about/' },
  { label: 'Contacto', href: '/contact/' },
];

export const customerReviews: CustomerReview[] = [
  {
    name: 'Mariana G.',
    product: 'Sala exterior en ratán',
    quote: 'La sala transformó nuestra terraza. Se siente resistente, cómoda y lista para reuniones familiares sin preocuparnos por el clima.',
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
    name: 'Lucía P.',
    product: 'Pérgola para jardín',
    quote: 'La pérgola cambió por completo el área de la alberca. Ahora tenemos sombra agradable y el espacio se siente mucho más terminado.',
    image: 'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?auto=format&fit=crop&w=900&q=80',
    rating: 5,
  },
  {
    name: 'Andrés R.',
    product: 'Sombrilla lateral',
    quote: 'La sombrilla es firme, cubre muy bien y nos ayudó a usar la terraza durante las horas de más sol. Excelente compra.',
    image: 'https://images.unsplash.com/photo-1615874694520-474822394e73?auto=format&fit=crop&w=900&q=80',
    rating: 5,
  },
  {
    name: 'Sofía L.',
    product: 'Set lounge para balcón',
    quote: 'El set quedó perfecto para un balcón pequeño. La calidad se siente muy buena y el color combina con plantas y accesorios.',
    image: 'https://images.unsplash.com/photo-1616486338812-3dadae4b4ace?auto=format&fit=crop&w=900&q=80',
    rating: 4,
  },
  {
    name: 'Diego H.',
    product: 'Mesa auxiliar exterior',
    quote: 'La mesa es práctica, estable y fácil de mover. Me hubiera gustado una medida adicional, pero la calidad está excelente.',
    image: 'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?auto=format&fit=crop&w=900&q=80',
    rating: 4,
  },
  {
    name: 'Valeria T.',
    product: 'Proyecto terraza restaurante',
    quote: 'Nos ayudaron a elegir piezas resistentes para uso comercial. La terraza se ve más ordenada y los clientes la disfrutan mucho.',
    image: 'https://images.unsplash.com/photo-1616627561950-9f746e330187?auto=format&fit=crop&w=900&q=80',
    rating: 5,
  },
];

export const blogCards: BlogCard[] = [
  {
    title: 'Cómo elegir muebles de exterior para una terraza activa',
    slug: 'como-elegir-muebles-exterior-terraza-activa',
    category: 'Guía de exterior',
    image: 'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?auto=format&fit=crop&w=800&q=80',
    excerpt: 'Ideas para configurar zonas de descanso, conversación y juego sin perder orden visual.',
    content: [
      'Una terraza activa necesita piezas que acompañen distintos momentos del día: descanso, conversación, comida y juego. Antes de elegir muebles, define cuántas personas usarán el espacio con frecuencia y qué actividades quieres priorizar.',
      'Para mantener orden visual, agrupa por zonas. Una sala modular puede marcar el área social, una mesa auxiliar resuelve apoyo diario y una sombrilla o pérgola ayuda a controlar el sol sin recargar el ambiente.',
      'Elige materiales resistentes al exterior y telas fáciles de limpiar. En espacios familiares o de uso constante, conviene priorizar estructuras firmes, cojines removibles y colores neutros que puedan renovarse con accesorios.',
    ],
  },
  {
    title: 'Materiales resistentes para sol, humedad y uso diario',
    slug: 'materiales-resistentes-sol-humedad-uso-diario',
    category: 'Milapro Home',
    image: 'https://images.unsplash.com/photo-1615874694520-474822394e73?auto=format&fit=crop&w=800&q=80',
    excerpt: 'Ratán sintético, aluminio y plástico de fácil mantenimiento para espacios exteriores.',
    content: [
      'Los muebles de exterior están expuestos a sol, humedad, polvo y movimiento constante. Por eso, la selección del material determina tanto la durabilidad como la facilidad de mantenimiento.',
      'El ratán sintético ofrece una apariencia cálida con buena resistencia para terrazas y jardines. El aluminio aporta ligereza y estabilidad frente a la corrosión, mientras que los plásticos de calidad son prácticos para áreas de alto uso.',
      'La clave está en combinar estructura, tejido y cuidado. Limpiezas periódicas, protección en temporadas de lluvia intensa y una ubicación bien ventilada ayudan a conservar mejor cada pieza.',
    ],
  },
  {
    title: 'Ideas para proyectos a la medida',
    slug: 'ideas-para-proyectos-a-la-medida',
    category: 'Proyectos',
    image: 'https://images.unsplash.com/photo-1616627561950-9f746e330187?auto=format&fit=crop&w=800&q=80',
    excerpt: 'Soluciones para arquitectos, hoteles, restaurantes y hogares que buscan diseño funcional.',
    content: [
      'Los proyectos a la medida permiten que cada pieza responda al uso real del espacio. En hoteles, restaurantes y hogares amplios, el mobiliario debe equilibrar estética, circulación, resistencia y mantenimiento.',
      'Antes de producir o seleccionar piezas, conviene revisar medidas, recorridos, exposición solar y cantidad de usuarios. Esta información ayuda a definir formatos, módulos, alturas y materiales adecuados.',
      'Una propuesta funcional también considera continuidad visual. Repetir tonos, texturas o líneas de diseño crea ambientes coherentes sin perder flexibilidad para futuras ampliaciones.',
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
