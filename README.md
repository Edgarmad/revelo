# Revelo Catalog MVP

Visual MVP de catálogo comercial inspirado en el PDF de referencia de Milapro Home. El proyecto usa Astro, TypeScript, Tailwind CSS, datos mock locales y salida estática para HostGator.

## Comandos

```bash
npm install
npm run dev
npm run build
npm run preview
```

## Estructura

```text
src/
  components/
    common/
    navigation/
    product/
    category/
    filters/
    sections/
  data/
  layouts/
  pages/
  services/
  styles/
  types/
```

## Páginas Implementadas

- `/` home comercial con hero, beneficios, categorías, productos, social proof, blogs, reviews y CTA.
- `/products/` catálogo con búsqueda y filtros locales visuales.
- `/products/[slug]/` detalle estático por producto con galería, colores, especificaciones y relacionados.
- `/categories/[slug]/` categorías estáticas con productos y categorías relacionadas.
- `/about/` historia y valores de marca.
- `/contact/` formulario visual con validación local, sin envío real.
- `/404.html` página de error personalizada.

## Datos y Futuro WordPress

Los componentes no importan datos mock directamente. El flujo actual es:

```text
src/data -> src/services -> src/pages -> components via props
```

En la fase WordPress Headless se reemplazaría o extendería `src/services/productService.ts` y `src/services/categoryService.ts` para consumir la REST API de WordPress, manteniendo los componentes visuales.

## Despliegue HostGator

1. Ejecutar `npm run build`.
2. Subir el contenido de `dist/` a `public_html`.
3. No se requiere proceso Node.js en producción.
4. El archivo `.htaccess` generado desde `public/.htaccess` define `404.html` como página de error.

## Diferido Intencionalmente

- Backend, base de datos y autenticación.
- WordPress, WooCommerce o llamadas a APIs externas.
- Carrito, checkout, pagos y órdenes.
- Envío real del formulario de contacto.
- Persistencia de favoritos o analítica.
- Filtrado avanzado conectado a inventario real.

## Validación

- `npm run build` genera `dist/` con salida estática.
- `npx tsc --noEmit` valida TypeScript sin errores.
