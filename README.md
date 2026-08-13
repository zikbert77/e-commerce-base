# E-Commerce Base

Symfony 7.3 e-commerce application with PostgreSQL database. The project implements a multilingual product catalog system with user authentication, email verification, hierarchical category structure, and multi-store (multi-tenant) support resolved by domain.

## Table of Contents

- [System Requirements](#system-requirements)
- [Installation](#installation)
- [Architecture](#architecture)
- [Multi-Store Resolution](#multi-store-resolution)
- [Theme System](#theme-system)
- [Monetary Values](#monetary-values)
- [Entity Structure](#entity-structure)
- [Common Commands](#common-commands)
- [Development Guidelines](#development-guidelines)

## System Requirements

- PHP 8.2+
- PostgreSQL 16+
- Symfony CLI (optional, recommended)
- Composer

## Installation

1. Clone the repository
2. Install dependencies:
   ```bash
   composer install
   ```
3. Configure environment variables in `.env.local`:
   ```
   DATABASE_URL="postgresql://user:password@127.0.0.1:5432/database_name"
   ```
4. Create database and run migrations:
   ```bash
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate
   ```
5. Start development server:
   ```bash
   symfony server:start
   ```

## Architecture

### Multilingual Content Pattern

The application uses a **separate Info entities pattern** for internationalization:

- **Core entities** (`Category`, `Product`, `Order`, etc.): Store language-independent data (ID, status, relationships, timestamps)
- **Info entities** (`CategoryInfo`, `ProductInfo`): Store locale-specific content (title, slug, description)
- Each info entity has a `locale` field (6 chars) and references its parent entity
- One-to-many relationship: one core entity → many info entities (one per locale)

**Example**: A `Product` has multiple `ProductInfo` records, each with translations for different locales.

### Timestamps

All entities implement `TimestampableInterface` using `andanteproject/timestampable-bundle`:
- Automatically manages `created_at` and `updated_at` fields
- Applied via `TimestampableTrait`

### Database

- **Platform**: PostgreSQL 16
- **Naming Strategy**: `underscore_number_aware` (converts `firstName` → `first_name`)
- **Connection**: Configured via `DATABASE_URL` environment variable
- **Migrations**: Located in `migrations/` directory

### Caching

- **`store.cache` pool**: APCu-backed, tag-aware, 300s default lifetime (`config/packages/cache.yaml`) — used to cache domain-to-store resolution
- APCu is installed and enabled in the Docker PHP image

## Multi-Store Resolution

The application can serve multiple stores (tenants) from a single codebase, resolved per-request from the HTTP host:

- **`Store`**: Core tenant entity (title, status)
- **`StoreDomain`**: Maps a domain string to a `Store`
- On each request, `StoreResolverSubscriber` resolves the current `Store` from the request host (via `StoreResolver`, cached in the `store.cache` APCu pool) and stores it in `StoreContext`. Unmatched hosts result in a 404.
- Once resolved, Doctrine's `store_filter` SQL filter is enabled, automatically scoping queries for any entity implementing `App\Entity\Interface\StoreScopedInterface` to `store_id = :storeId`.
- To make an entity store-scoped: implement `StoreScopedInterface` and add a `store` relation.

## Theme System

Each store renders through a **theme**: a directory of Twig templates at `templates/themes/{code}/`, where `{code}` is the store's `Template.code`. Resolution happens in a custom Twig loader, not in controllers:

- **`ThemeAwareLoader`** (`src/Twig/Loader/ThemeAwareLoader.php`) is registered as a `twig.loader` service with the `theme` namespace pointed at `templates/themes`. Any template name starting with `@theme/` gets rewritten to `@theme/{current store's template code}/...` (via `StoreContext`) before being looked up. Other template names (like the app-level `base.html.twig`) fall through to Symfony's default loader.
- **`StoreGlobalsExtension`** (`src/Twig/Extension/StoreGlobalsExtension.php`) is a Twig extension (auto-registered — no config needed, `autoconfigure: true` picks it up) that injects the current `StoreDTO` as a Twig **global** named `store`, available in every template automatically. Controllers no longer need to pass `'store' => ...` on every `render()` call.

Controllers and theme templates just use the `@theme/` prefix — no code interpolation, no hardcoded theme name, and no manual `store` variable:

```php
// Controller — store is a Twig global, not passed here
return $this->render('@theme/home.html.twig');

// ...unless the action has its own data for the template
return $this->render('@theme/product.html.twig', [
    'slug' => $slug,
]);
```
```twig
{# Inside a theme's own templates — `store` just works, no {% set %} needed #}
{% extends '@theme/base.html.twig' %}
{% include '@theme/partials/_header.html.twig' %}
<h1>{{ store.title }}</h1>
```

### Directory structure

```
templates/themes/{code}/
├── base.html.twig       # root layout — extends the app-level templates/base.html.twig
├── home.html.twig       # one template per controller action (see table below)
├── catalog.html.twig
├── product.html.twig
├── checkout.html.twig
├── search.html.twig
├── category.html.twig
├── about.html.twig
├── contacts.html.twig
└── partials/            # shared chrome, included only — never rendered directly by a controller
    ├── _topbar.html.twig
    ├── _header.html.twig
    ├── _footer.html.twig
    └── _cart_modal.html.twig
```

`templates/themes/coffee/` is the reference implementation to copy when starting a new theme.

### Required files & naming

- **`base.html.twig`** is required for every theme — it extends the app shell (`templates/base.html.twig`), fills the `stylesheets` block with the theme's design tokens/CSS, and defines a `content` block that page templates override. It also nests a `page_title` block inside the app shell's `title` block, so pages can set the `<title>` without re-declaring `title` itself.
- Every other top-level `*.html.twig` file must be **named exactly** what its controller renders (case-sensitive) — there's no fuzzy lookup, a mismatch is a 500 at render time.
- Partial/include-only files live under `partials/` and are prefixed with `_` (the file is never a controller's render target, only ever `{% include %}`d).
- Because resolution goes through the `@theme/` namespace, a theme's own templates reference each other with `@theme/...` too (`{% extends '@theme/base.html.twig' %}`) instead of hardcoding the theme's own code — copying a theme to a new code needs no path changes inside it.

### Routing table

| Route | Path | Controller | Renders | Status |
|---|---|---|---|---|
| `app_home` | `/` | `MainController::index()` | `@theme/home.html.twig` | built (coffee) |
| `app_catalog` | `/catalog` | `CatalogController::catalog()` | `@theme/catalog.html.twig` | built (coffee) |
| `app_search` | `/catalog/search` | `CatalogController::search()` | `@theme/search.html.twig` | missing template |
| `app_category` | `/catalog/category/{slug}` | `CatalogController::category()` | `@theme/category.html.twig` | missing template |
| `app_product_view` | `/product/{slug}` | `ProductController::view()` | `@theme/product.html.twig` | built (coffee) |
| `app_order_checkout` | `/order/checkout` | `OrderController::checkout()` | `@theme/checkout.html.twig` | built (coffee) |
| `app_page_about` | `/about` | `PageController::about()` | `@theme/about.html.twig` | missing template |
| `app_page_contacts` | `/contacts` | `PageController::contacts()` | `@theme/contacts.html.twig` | missing template |

"Missing template" routes already work end-to-end (route + controller call `render()` correctly) but 500 today because no theme ships that file yet.

Login/register/logout/email-verification pages are **not** theme-scoped — they render from `templates/security/` and `templates/registration/` regardless of the active store's template, since they're shared across every store. The `/api/cart` endpoints return JSON and have no template.

### Adding a new theme

1. Create `templates/themes/{code}/` with `base.html.twig` plus a page template for every route you want that store to serve, all referencing each other via `@theme/...`.
2. Insert a `Template` row with that `code`, a `title`, and `default_config`.
3. Point the target `Store.template` at it — or leave it `null` to fall back to `TemplateRepository::getDefault()` (the `code = 'default'` row).

No controller or config changes are needed — `ThemeAwareLoader` picks up the new code automatically.

## Monetary Values

**IMPORTANT**: All monetary amounts in the system are stored as **integers representing cents** (or the smallest currency unit).

### Implementation

- All price and amount fields use `#[ORM\Column]` with type `int`
- Values are stored in cents (e.g., $19.99 is stored as 1999)
- This approach:
  - Avoids floating-point precision issues
  - Ensures accurate monetary calculations
  - Maintains consistency across all financial operations

### Affected Entities

#### Order Entity (`src/Entity/Order.php`)
All amount fields stored as integers (cents):
- `subtotalAmount`: Order subtotal before discounts/taxes
- `discountAmount`: Total discount applied
- `shippingCostAmount`: Shipping cost
- `taxAmount`: Tax amount
- `totalAmount`: Final order total
- `paidAmount`: Amount paid by customer

#### OrderItem Entity (`src/Entity/OrderItem.php`)
All amount fields stored as integers (cents):
- `price`: Unit price of the product
- `subtotalAmount`: Line item subtotal (price × qty)
- `discountAmount`: Discount applied to this item
- `taxAmount`: Tax for this line item
- `totalAmount`: Final line item total

### Usage Examples

```php
// Setting a price of $19.99
$orderItem->setPrice(1999);

// Setting a shipping cost of $5.50
$order->setShippingCostAmount(550);

// Calculating total in dollars for display
$totalInDollars = $order->getTotalAmount() / 100;
```

### Display Formatting

When displaying amounts to users, always convert from cents to the standard currency format:

```php
// In Twig templates
{{ (order.totalAmount / 100)|number_format(2, '.', ',') }}

// Or create a Twig filter for currency formatting
{{ order.totalAmount|money_format }}
```

## Entity Structure

### Core Entities

#### Category (Hierarchical)
```
Category
├── id: int
├── parent: Category (nullable, self-referencing)
├── status: int
├── childCategories: Collection<Category>
├── categoryInfos: Collection<CategoryInfo>
├── created_at: DateTimeImmutable
└── updated_at: DateTimeImmutable
```

#### Product
```
Product
├── id: int
├── category: Category (required)
├── status: int
├── creator: User (nullable)
├── productInfos: Collection<ProductInfo>
├── created_at: DateTimeImmutable
└── updated_at: DateTimeImmutable
```

#### Order
```
Order
├── id: int
├── uid: string (unique, 30 chars)
├── relatedUser: User (nullable)
├── status: OrderStatus (enum)
├── subtotalAmount: int (cents)
├── discountAmount: int (cents)
├── shippingCostAmount: int (cents)
├── taxAmount: int (cents)
├── totalAmount: int (cents)
├── paidAmount: int (cents)
├── customerFirstName: string (120 chars, nullable)
├── customerLastName: string (120 chars, nullable)
├── customerEmail: string (120 chars, nullable)
├── customerPhone: string (60 chars, nullable)
├── shippingAddress: string (255 chars)
├── paidAt: DateTimeImmutable (nullable)
├── shippedAt: DateTimeImmutable (nullable)
├── completedAt: DateTimeImmutable (nullable)
├── canceledAt: DateTimeImmutable (nullable)
├── orderItems: Collection<OrderItem>
├── created_at: DateTimeImmutable
└── updated_at: DateTimeImmutable
```

#### OrderItem
```
OrderItem
├── id: int
├── relatedOrder: Order (required)
├── product: Product (required)
├── productTitle: string (255 chars)
├── qty: int (SMALLINT)
├── price: int (cents)
├── subtotalAmount: int (cents)
├── discountAmount: int (cents)
├── taxAmount: int (cents)
├── totalAmount: int (cents)
├── created_at: DateTimeImmutable
└── updated_at: DateTimeImmutable
```

#### Cart & CartItem
```
CartItem
├── id: int
├── cart: Cart (required)
├── product: Product (required)
├── qty: int (validated: positive, max 9999)
├── created_at: DateTimeImmutable
└── updated_at: DateTimeImmutable
└── Unique constraint: (cart_id, product_id)
```

#### User
```
User
├── Authentication via email/password
├── Email verification system (SymfonyCasts VerifyEmailBundle)
├── is_verified: boolean flag
└── Reset password functionality (SymfonyCasts ResetPasswordBundle)
```

#### Store
```
Store
├── id: int
├── title: string (255 chars)
├── status: BaseStatus (enum)
├── storeDomains: Collection<StoreDomain>
├── created_at: DateTimeImmutable
└── updated_at: DateTimeImmutable
```

#### StoreDomain
```
StoreDomain
├── id: int
├── store: Store (required)
└── domain: string (255 chars)
```

### Info Entities

#### CategoryInfo
- `locale`: string (6 chars)
- `title`: string
- `slug`: string
- `description`: text

#### ProductInfo
- `locale`: string (6 chars)
- `title`: string (255 chars)
- `slug`: string (255 chars)
- `short_description`: text (nullable)
- `description`: text
- `seoTitle`: string (60 chars, nullable)
- `seoDescription`: string (160 chars, nullable)

## Common Commands

### Development Server
```bash
symfony server:start
# or
php -S localhost:8000 -t public/
```

### Database Management
```bash
# Create database
php bin/console doctrine:database:create

# Run migrations
php bin/console doctrine:migrations:migrate

# Generate migration from entity changes
php bin/console make:migration

# Check database status
php bin/console doctrine:migrations:status
```

### Code Generation
```bash
# Generate new entity
php bin/console make:entity

# Generate controller
php bin/console make:controller

# Generate form
php bin/console make:form

# Generate repository
php bin/console make:repository
```

### Cache & Assets
```bash
# Clear cache
php bin/console cache:clear

# Install assets
php bin/console assets:install

# Install JavaScript dependencies
php bin/console importmap:install
```

### Debugging
```bash
# List all routes
php bin/console debug:router

# Show specific route details
php bin/console debug:router app_login

# List services
php bin/console debug:container

# Debug autowiring
php bin/console debug:autowiring
```

## Development Guidelines

### Adding New Translatable Entity

1. Create core entity with ID, status, relationships
2. Create corresponding Info entity with locale, slug, title, descriptions
3. Add OneToMany relationship from core → info entities
4. Generate migration: `php bin/console make:migration`
5. Run migration: `php bin/console doctrine:migrations:migrate`

### Working with Entities

- All entities use PHP 8.2+ attributes for ORM mapping
- Use `make:entity` to generate/modify entities
- Repository pattern is used for custom queries
- Entities follow snake_case for database columns, camelCase for PHP properties

### Security

- **User Provider**: Entity-based, using `email` as identifier
- **Password Hashing**: Auto algorithm (bcrypt/argon2)
- **Form Login**: Login path at `/login` (`app_login`)
- **Access Control**:
  - Public: `/login`, `/register`
  - Protected: Everything else requires `ROLE_USER`

### Frontend

- **Template Engine**: Twig
- **Asset Management**: Symfony AssetMapper (no Node.js build step)
- **JavaScript**: Stimulus controllers via Symfony UX
- **Turbo**: Enabled via `symfony/ux-turbo`

### Environment Files

- `.env`: Default values (committed)
- `.env.local`: Local overrides (not committed)
- `.env.dev`, `.env.test`: Environment-specific defaults
- Database configured via `DATABASE_URL` in `.env`

## Testing

```bash
# Run all tests
php bin/phpunit

# Run specific test file
php bin/phpunit tests/ExampleTest.php

# Run tests with coverage
php bin/phpunit --coverage-html var/coverage
```

## License

[Add license information]

## Contributing

[Add contribution guidelines]
