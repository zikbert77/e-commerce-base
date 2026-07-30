# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Symfony 7.3 e-commerce application with PostgreSQL database. The project implements a multilingual product catalog system with user authentication, email verification, hierarchical category structure, and multi-store (multi-tenant) support resolved by domain.

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

### Testing
```bash
# Run all tests
php bin/phpunit

# Run specific test file
php bin/phpunit tests/ExampleTest.php

# Run tests with coverage
php bin/phpunit --coverage-html var/coverage
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

## Architecture

### Multilingual Content Pattern

The application uses a **separate Info entities pattern** for internationalization:

- **Core entities** (`Category`, `Product`): Store language-independent data (ID, status, relationships, timestamps)
- **Info entities** (`CategoryInfo`, `ProductInfo`): Store locale-specific content (title, slug, description)
- Each info entity has a `locale` field (6 chars) and references its parent entity
- One-to-many relationship: one core entity → many info entities (one per locale)

**Example**: A `Product` has multiple `ProductInfo` records, each with translations for different locales.

### Entity Relationships

```
Category (hierarchical)
├── parent → Category (nullable, self-referencing)
├── childCategories → Collection<Category>
└── categoryInfos → Collection<CategoryInfo>

Product
├── category → Category (required)
└── productInfos → Collection<ProductInfo>

Store
└── storeDomains → Collection<StoreDomain>

StoreDomain
└── store → Store (required)

User
├── Authentication via email/password
├── Email verification system (SymfonyCasts VerifyEmailBundle)
└── Reset password functionality (SymfonyCasts ResetPasswordBundle)
```

### Multi-Store (Multi-Tenant) Resolution

The application supports multiple stores served from a single codebase, resolved per-request by the incoming HTTP host:

- **`Store`**: Core tenant entity (`title`, `status` via `BaseStatus` enum, `storeDomains`)
- **`StoreDomain`**: Maps a domain string to a `Store` (many-to-one)
- **`StoreResolver`** (`src/Service/Store/StoreResolver.php`): Resolves a `Store` from the request host, normalizing it (lowercase, strips port, strips `www.`) and caching the `store_id` lookup in the `store.cache` pool (APCu, tagged `store_domains`, TTL from `CacheLifetime::STORE_RESOLVER` = 300s). Null results are cached too, to avoid repeated DB lookups for unknown hosts.
- **`StoreResolverSubscriber`** (`src/EventSubscriber/StoreResolverSubscriber.php`): On `kernel.request` (priority 30, main requests only, skips `/_*` profiler/dev paths), resolves the store for the host and throws `NotFoundHttpException` if no store matches. Otherwise stores it in `StoreContext` and enables Doctrine's `store_filter` SQL filter with the resolved `storeId`.
- **`StoreContext`** (`src/Service/Store/StoreContext.php`): Request-scoped holder for the current `Store`; reset automatically between requests via the `kernel.reset` tag.
- **`StoreFilter`** (`src/Doctrine/StoreFilter.php`): A Doctrine `SQLFilter` (registered as `store_filter`, disabled by default) that automatically adds a `store_id = :storeId` constraint to any entity implementing `App\Entity\Interface\StoreScopedInterface`.

**To scope an entity to a store**: implement `StoreScopedInterface` and add a `store` relation — the `store_filter` will then automatically restrict queries to the current store once enabled by `StoreResolverSubscriber`.

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

- **`store.cache` pool**: APCu-backed (`cache.adapter.apcu`), tag-aware, 300s default lifetime — used by `StoreResolver` to cache host-to-store lookups (see `config/packages/cache.yaml`)
- APCu is enabled in the Docker PHP image (`docker/php/Dockerfile`, `docker/php/php.ini`)

### Security

- **User Provider**: Entity-based, using `email` as identifier
- **Password Hashing**: Auto algorithm (bcrypt/argon2)
- **Form Login**: Login path at `/login` (`app_login`)
- **Access Control**:
  - Public: `/login`, `/register`
  - Protected: Everything else requires `ROLE_USER`
- **Email Verification**: Users have `is_verified` boolean flag

### Frontend

- **Template Engine**: Twig
- **Asset Management**: Symfony AssetMapper (no Node.js build step)
- **JavaScript**: Stimulus controllers via Symfony UX
- **Turbo**: Enabled via `symfony/ux-turbo`

## Development Workflow

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

### Environment Files

- `.env`: Default values (committed)
- `.env.local`: Local overrides (not committed)
- `.env.dev`, `.env.test`: Environment-specific defaults
- Database configured via `DATABASE_URL` in `.env`
