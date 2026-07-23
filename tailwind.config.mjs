/** @type {import('tailwindcss').Config} */
export default {
  content: ['./src/**/*.{astro,html,js,jsx,md,mdx,svelte,ts,tsx,vue}'],
  theme: {
    extend: {
      colors: {
        ink: 'rgb(var(--color-ink) / <alpha-value>)',
        muted: 'rgb(var(--color-muted) / <alpha-value>)',
        paper: 'rgb(var(--color-paper) / <alpha-value>)',
        sand: 'rgb(var(--color-sand) / <alpha-value>)',
        aqua: 'rgb(var(--color-aqua) / <alpha-value>)',
        cocoa: 'rgb(var(--color-cocoa) / <alpha-value>)',
      },
      borderRadius: {
        soft: '1.4rem',
        pill: '999px',
      },
      fontFamily: {
        sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
        display: ['Inter Tight', 'Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
      },
      maxWidth: {
        site: '1180px',
      },
    },
  },
  plugins: [],
};
