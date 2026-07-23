# Project Implementation Brief — Visual MVP

## Project Goal

Build the visual MVP of a modern, responsive product catalog website.

The current phase is focused exclusively on:

* Visual design
* Responsive layouts
* Reusable UI components
* Navigation between pages
* Product presentation
* Static mock data

Do not implement a backend, WordPress integration, authentication, database, checkout, or real API calls during this phase.

The architecture must still be prepared so that functionality can be added in the next phase and WordPress Headless can be connected in a future phase without rebuilding the frontend.

---

# Technology Stack

Use the following stack:

* Astro
* TypeScript
* Tailwind CSS
* React only for interactive UI components when necessary
* Lucide Icons
* Local static data using TypeScript files
* Astro static output

Do not use:

* Next.js
* Node.js server runtime
* Express
* Laravel
* WordPress
* WooCommerce
* Database connections
* External CMS APIs
* Authentication
* Global state libraries unless strictly necessary

The application must be deployable as static files on HostGator shared hosting.

The production build must generate a `dist` directory that can be uploaded directly to `public_html`.

---

# Development Phases

The project must be structured around three separate phases.

## Phase 1 — Visual MVP

This is the only phase to implement now.

Implement:

* Complete visual interface
* Responsive layouts
* Static navigation
* Mock products and categories
* Reusable components
* Product listing
* Product detail pages
* Category pages
* Visual filters
* Search interface
* Product galleries
* Color selection interface
* Contact actions
* Loading, empty and error visual states

The filters, search, color selectors and buttons may have local frontend behavior, but they must not depend on a backend.

## Phase 2 — Functional Frontend

Do not implement this phase now.

The future functional phase may include:

* Real product filtering
* Search logic
* Contact form submission
* Product inquiry forms
* Analytics
* Persistent favorites
* Dynamic content loading
* API integration
* Form validation
* Deployment automation

The current structure must make this phase easy to implement.

## Phase 3 — WordPress Headless

Do not implement this phase now.

The future WordPress phase will include:

* WordPress installed separately
* Custom Post Type for products
* Custom taxonomies
* Advanced Custom Fields
* WordPress REST API
* Product and category data loaded from WordPress
* Automatic rebuild or dynamic content strategy

All product data access must therefore be isolated from UI components.

---

# Architecture Requirements

Use a clear separation between:

* UI components
* Page layouts
* Product data
* Product services
* Types
* Utilities
* Static assets

Use the following general structure:

```text
src/
├── components/
│   ├── common/
│   ├── layout/
│   ├── navigation/
│   ├── product/
│   ├── category/
│   ├── filters/
│   └── sections/
├── layouts/
│   └── MainLayout.astro
├── pages/
│   ├── index.astro
│   ├── products/
│   │   ├── index.astro
│   │   └── [slug].astro
│   ├── categories/
│   │   └── [slug].astro
│   ├── about.astro
│   ├── contact.astro
│   └── 404.astro
├── data/
│   ├── products.ts
│   ├── categories.ts
│   └── siteContent.ts
├── services/
│   ├── productService.ts
│   └── categoryService.ts
├── types/
│   ├── product.ts
│   ├── category.ts
│   └── site.ts
├── utils/
├── styles/
└── assets/
```

The exact structure can be adjusted when justified, but maintain the same separation of responsibilities.

---

# Data Abstraction

Do not import mock product data directly inside visual components.

Components must receive information through props.

Pages must obtain data through service functions.

Example flow:

```text
Mock TypeScript data
        ↓
Product service
        ↓
Astro page
        ↓
Visual component props
```

The future flow will be:

```text
WordPress REST API
        ↓
Product service
        ↓
Astro page
        ↓
Visual component props
```

This means the WordPress integration should later require replacing or extending the service layer, not rewriting the components.

---

# Product Data Model

Create a typed product model that supports the future catalog requirements.

Each product should support:

* `id`
* `slug`
* `name`
* `shortDescription`
* `description`
* `mainImage`
* `gallery`
* `category`
* `categories`
* `collection`
* `brand`
* `sku`
* `colors`
* `materials`
* `dimensions`
* `featured`
* `available`
* `displayOrder`
* `tags`
* `specifications`

Each color option should support:

* `id`
* `name`
* `hex`
* `image`
* `gallery`
* `available`

Not every mock product needs to use every property, but the type should be prepared for them.

Use realistic mock products rather than generic values such as “Product 1” or “Lorem Ipsum”.

---

# Required Pages

## Home Page

Include:

* Header
* Hero section
* Main call to action
* Featured categories
* Featured products
* Brand or catalog introduction
* Benefits or differentiators
* Promotional visual section
* Contact call to action
* Footer

The home page should feel like a real commercial catalog, not a developer demo.

## Products Page

Include:

* Page title
* Introductory text
* Search field
* Category filter
* Color filter
* Availability filter
* Sort control
* Product count
* Responsive product grid
* Empty-state design
* Mobile filter interface

Filtering can work with local data during this phase.

## Product Detail Page

Include:

* Breadcrumbs
* Product gallery
* Main product image
* Thumbnails
* Product name
* Short description
* Category
* Color options
* Availability
* Specifications
* Description
* Related products
* Contact or request-information action

Changing a color may update the selected visual image locally when mock data supports it.

Do not implement shopping cart or checkout.

## Category Page

Include:

* Category title
* Category description
* Category image or visual header
* Product grid
* Filters
* Related categories

## About Page

Include:

* Brand story
* Mission or positioning
* Supporting visual sections
* Values or differentiators
* Contact call to action

## Contact Page

Include a visual contact form with:

* Name
* Email
* Phone
* Subject
* Message
* Optional product reference

The form does not need to submit to a backend.

It may validate locally and show a clear demo success state without sending data.

## 404 Page

Create a custom, visually consistent 404 page.

---

# Required Components

Create reusable components for at least:

* Header
* Desktop navigation
* Mobile navigation
* Footer
* Container
* Section heading
* Primary button
* Secondary button
* Product card
* Product grid
* Product gallery
* Product color selector
* Product specifications
* Category card
* Search input
* Filter controls
* Breadcrumbs
* Badge
* Empty state
* Contact callout
* Newsletter or contact section
* Skeleton or loading placeholder

Avoid building one large page component with all markup inside it.

---

# Visual Direction

The design should be:

* Modern
* Premium
* Minimal
* Commercial
* Clean
* Image-focused
* Responsive
* Accessible
* Suitable for a product catalog

Use:

* Generous spacing
* Clear hierarchy
* Strong typography
* Consistent borders and radii
* Subtle hover states
* Restrained shadows
* Large product photography
* Clean product cards
* Consistent call-to-action styles

Avoid:

* Excessive gradients
* Excessive animations
* Glassmorphism everywhere
* Very small text
* Overloaded cards
* Dashboard-like styling
* Generic template appearance
* Unnecessary carousels
* Excessive use of JavaScript

Define design tokens using CSS variables or Tailwind theme configuration for:

* Primary color
* Secondary color
* Accent color
* Background colors
* Text colors
* Border colors
* Spacing
* Border radius
* Container sizes

If no brand identity is provided, create a neutral premium visual system that can be changed easily later.

---

# Responsive Requirements

Support at least:

* Mobile: 320px and above
* Tablet
* Laptop
* Desktop
* Large desktop

Requirements:

* No horizontal overflow
* Touch-friendly controls
* Responsive typography
* Mobile navigation
* Mobile-friendly filters
* Product grids that adapt correctly
* Product galleries usable on touch devices
* Appropriate image aspect ratios
* Buttons with adequate touch size

The mobile version must be intentionally designed, not merely a compressed desktop layout.

---

# Accessibility

Implement:

* Semantic HTML
* Correct heading hierarchy
* Descriptive image alt text
* Keyboard-accessible navigation
* Visible focus states
* Accessible labels for controls
* Sufficient color contrast
* Buttons for actions and links for navigation
* Properly associated form labels
* `aria-expanded` where appropriate
* Reduced-motion support for animations

---

# SEO Preparation

Even though this phase uses mock content, prepare:

* Unique page titles
* Meta descriptions
* Canonical-ready layout
* Open Graph metadata structure
* Semantic product headings
* Clean URLs using slugs
* Product and category pages generated statically

Do not implement complex schema markup unless it can be added cleanly without inventing unavailable business information.

---

# Performance

Prioritize static output and minimal JavaScript.

Requirements:

* Use Astro components by default
* Use React only for components that require client-side interaction
* Avoid hydrating entire pages
* Lazy-load non-critical images
* Set image dimensions to reduce layout shifts
* Avoid large third-party libraries
* Avoid unnecessary dependencies
* Optimize repeated components
* Keep the initial page load lightweight

Use Astro client directives only where necessary.

---

# Mock Content

Create enough mock content to make the interface feel complete.

Include at least:

* 10 to 12 products
* 4 to 6 categories
* Multiple product colors
* Featured and non-featured products
* Available and unavailable products
* Several products with galleries
* Related products
* Realistic descriptions and specifications

Store mock content centrally.

Do not duplicate product information across page files.

---

# Images

Use placeholder or royalty-free product imagery suitable for development.

Keep image references centralized in the product data.

The layout should not depend on one exact image size.

Use consistent aspect-ratio containers and `object-cover` or `object-contain` intentionally depending on the image type.

Do not embed important content inside images.

---

# Code Quality

Use:

* Strict TypeScript
* Clear naming
* Small reusable components
* Typed props
* Centralized constants
* Consistent formatting
* Minimal comments focused on non-obvious decisions

Avoid:

* `any`
* Duplicated markup
* Hardcoded product arrays inside components
* Large monolithic components
* Premature abstraction
* Premature backend logic
* Unused dependencies
* Dead code

---

# Environment and Commands

The project should support:

```bash
npm install
npm run dev
npm run build
npm run preview
```

The final production build must work as a static website from the `dist` directory.

Configure Astro for static output.

Do not require environment variables for the visual MVP.

---

# HostGator Deployment Preparation

The project will eventually be deployed to HostGator shared hosting.

Prepare the project so that:

* `npm run build` creates the complete static website
* The contents of `dist` can be uploaded to `public_html`
* No Node.js process is required in production
* No server-side runtime is required
* Internal routes work after static deployment
* Asset paths work from the domain root
* A basic `.htaccess` file can be included if needed

Do not configure WordPress during this phase.

---

# Scope Boundaries

Do not implement:

* WordPress integration
* WordPress REST API calls
* CMS authentication
* Database
* User accounts
* Shopping cart
* Checkout
* Payment gateway
* Order management
* Admin dashboard
* Real email delivery
* Real contact-form submission
* External search services
* Inventory synchronization
* Server-side Node.js
* Server-side rendering

Use clearly isolated placeholders where these future features will connect.

---

# Expected Deliverables

At the end of this phase, provide:

1. Complete Astro project.
2. Responsive visual implementation.
3. All required pages.
4. Reusable component library.
5. Typed mock product and category data.
6. Service layer consuming local data.
7. Static production build configuration.
8. HostGator-compatible `dist` output.
9. README containing:

   * Installation instructions
   * Development commands
   * Build instructions
   * HostGator deployment steps
   * Project structure
   * Explanation of the future WordPress integration point
10. A list of intentionally deferred functionality.

---

# Implementation Order

Follow this order:

1. Initialize Astro with TypeScript.
2. Configure Tailwind CSS.
3. Define design tokens and global styles.
4. Define TypeScript models.
5. Create mock data.
6. Create service layer.
7. Create main layout.
8. Create shared UI components.
9. Implement header and navigation.
10. Implement footer.
11. Implement product and category components.
12. Implement home page.
13. Implement product listing.
14. Implement product detail pages.
15. Implement category pages.
16. Implement about and contact pages.
17. Implement responsive behavior.
18. Add accessibility improvements.
19. Add SEO metadata.
20. Validate production build.
21. Create deployment documentation.

---

# Acceptance Criteria

The visual MVP is complete when:

* All defined pages are implemented.
* The website works on mobile, tablet and desktop.
* Products and categories use centralized mock data.
* Product pages are generated from slugs.
* Components do not depend directly on the mock-data files.
* The interface looks like a real commercial catalog.
* Navigation works without broken links.
* Filters and search have at least local demo behavior.
* The contact form has local validation.
* The production build completes without errors.
* The generated `dist` folder works as a static website.
* No backend or WordPress dependency exists.
* The service layer can later be replaced with WordPress API calls.
* TypeScript passes without errors.
* There are no major accessibility or responsive-layout issues.

---

# Codex Execution Instructions

Before writing code:

1. Inspect the existing repository.
2. Reuse the current project structure when appropriate.
3. Do not delete existing working configuration without justification.
4. Identify whether Astro and Tailwind are already installed.
5. Create a concise implementation plan based on the repository state.

During implementation:

* Work incrementally.
* Keep the application buildable.
* Validate routes and components.
* Run the TypeScript and production build checks.
* Fix errors instead of suppressing them.
* Do not introduce future-phase functionality.
* Do not add dependencies unless they provide clear value.

When finished:

* Run the production build.
* Review the main routes.
* Report the files created and modified.
* Report commands executed.
* Report validation results.
* Clearly list features deferred to Phase 2 and Phase 3.
