# Revelo Catalog MVP

Visual MVP de catalogo comercial inspirado en el PDF de referencia de Linsy Home. El proyecto usa Astro, TypeScript, Tailwind CSS, datos mock locales y salida estatica para HostGator.

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

## Paginas Implementadas

- `/` home comercial con hero, beneficios, categorias, productos, social proof, blogs, reviews y CTA.
- `/products/` catalogo con busqueda y filtros locales visuales.
- `/products/[slug]/` detalle estatico por producto con galeria, colores, especificaciones y relacionados.
- `/categories/[slug]/` categorias estaticas con productos y categorias relacionadas.
- `/about/` historia y valores de marca.
- `/contact/` formulario visual con validacion local, sin envio real.
- `/404.html` pagina de error personalizada.

## Datos y Futuro WordPress

Los componentes no importan datos mock directamente. El flujo actual es:

```text
src/data -> src/services -> src/pages -> components via props
```

En la fase WordPress Headless se reemplazaria o extenderia `src/services/productService.ts` y `src/services/categoryService.ts` para consumir la REST API de WordPress, manteniendo los componentes visuales.

## Despliegue HostGator

1. Ejecutar `npm run build`.
2. Subir el contenido de `dist/` a `public_html`.
3. No se requiere proceso Node.js en produccion.
4. El archivo `.htaccess` generado desde `public/.htaccess` define `404.html` como pagina de error.

## Diferido Intencionalmente

- Backend, base de datos y autenticacion.
- WordPress, WooCommerce o llamadas a APIs externas.
- Carrito, checkout, pagos y ordenes.
- Envio real del formulario de contacto.
- Persistencia de favoritos o analitica.
- Filtrado avanzado conectado a inventario real.

## Validacion

- `npm run build` genera `dist/` con salida estatica.
- `npx tsc --noEmit` valida TypeScript sin errores.
