# Copilot Instructions for konvix_ecommerce

## Project Overview

-   Symfony 7.3 e-commerce application with user roles, secure checkout, Stripe/PayPal integration, and modern UX.
-   Main components: Product, User, Review entities; CartService; controllers for product, profile, admin, and seller workflows.
-   Templates use Twig and Bootstrap 5 for responsive UI.

## Architecture & Data Flow

-   Entities: `src/Entity/` (Product, User, Review) with Doctrine ORM, migrations in `migrations/`.
-   Controllers: `src/Controller/` (ProductController, ProfileController, Admin, Seller, etc.)
-   Services: `src/Service/` (CartService, ReviewRepository)
-   Templates: `templates/` (Twig views, Bootstrap layout)
-   Static assets: `public/` (images, uploads, .htaccess for rewrites)
-   Assets managed via Webpack Encore (see `webpack.config.js`, `assets/`)

## Developer Workflow

-   Start dev server: `symfony server:start` (or use XAMPP/Apache for Windows)
-   Install dependencies: `composer install` (PHP), `npm install` (JS/CSS)
-   Build assets: `npm run dev` or `npm run build`
-   Database migrations: `php bin/console doctrine:migrations:migrate`
-   Clear cache: `php bin/console cache:clear`

## Project Conventions

-   Use Bootstrap 5 and Bootstrap Icons for UI (see `base.html.twig`)
-   All forms use Symfony Form component, custom rendering in Twig (avoid double rendering fields)
-   Flash messages for user feedback (success, error)
-   Product page: three-column Bootstrap grid, header separated, responsive layout
-   Reviews: custom radio for rating, avoid duplicate field rendering
-   .htaccess: only enable necessary rewrite rules and file access for uploads

## Integration Points

-   Stripe/PayPal: payment integration in checkout (see ProductController, templates)
-   Webpack Encore: JS/CSS bundling, entry points in `assets/`, config in `webpack.config.js`
-   Doctrine ORM: entity relations, migrations

## Key Files & Directories

-   `src/Entity/` — Doctrine entities
-   `src/Controller/` — Business logic
-   `src/Service/` — Custom services
-   `templates/` — Twig templates
-   `assets/` — JS/CSS source
-   `public/` — Static files, uploads, .htaccess
-   `webpack.config.js` — Asset build config
-   `composer.json` — PHP dependencies
-   `package.json` — JS dependencies

## Example Patterns

-   Product detail: `templates/product/show.html.twig` — Bootstrap grid, reviews, add to cart
-   Review form: custom radio rendering, single field per input
-   CartService: session-based cart management
-   Admin controllers: CRUD via EasyAdmin or custom logic

---

If any section is unclear or missing, please specify what needs to be improved or added for your workflow.
