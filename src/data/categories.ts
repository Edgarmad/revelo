import type { Category } from '@/types/category';
import aluminioImage from '@/styles/images/aluminio.png';
import outletImage from '@/styles/images/outlet.png';
import plantasImage from '@/styles/images/plantas.png';
import plasticoImage from '@/styles/images/plastico.png';
import ratanImage from '@/styles/images/ratan.png';

export const categories: Category[] = [
  {
    id: 'cat-plants',
    slug: 'plantas',
    name: 'Plantas',
    eyebrow: 'Decoración exterior',
    description: 'Acentos verdes para completar ambientes frescos, naturales y funcionales.',
    image: plantasImage,
    featured: true,
    displayOrder: 1,
  },
  {
    id: 'cat-aluminum',
    slug: 'aluminio',
    name: 'Aluminio',
    eyebrow: 'Alta durabilidad',
    description: 'Muebles ligeros y resistentes para terrazas, jardines y espacios exteriores de uso diario.',
    image: aluminioImage,
    featured: true,
    displayOrder: 2,
  },
  {
    id: 'cat-rattan',
    slug: 'ratan',
    name: 'Ratán',
    eyebrow: 'Muebles para exterior',
    description: 'Salas, sillones y comedores tejidos para terrazas, jardines y áreas sociales al aire libre.',
    image: ratanImage,
    featured: true,
    displayOrder: 3,
  },
  {
    id: 'cat-plastic',
    slug: 'plastico',
    name: 'Plástico',
    eyebrow: 'Muebles para exterior',
    description: 'Piezas prácticas, fáciles de limpiar y resistentes para áreas exteriores de alto uso.',
    image: plasticoImage,
    featured: true,
    displayOrder: 4,
  },
  {
    id: 'cat-outlet',
    slug: 'outlet',
    name: 'Outlet',
    eyebrow: 'Oportunidades especiales',
    description: 'Piezas seleccionadas con precios especiales para renovar espacios exteriores.',
    image: outletImage,
    featured: true,
    displayOrder: 5,
  },
];
