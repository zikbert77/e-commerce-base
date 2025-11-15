# E-Commerce Base

Symfony 7.3 e-commerce application with PostgreSQL database. The project implements a multilingual product catalog system with user authentication, email verification, and hierarchical category structure.

## Table of Contents

- [System Requirements](#system-requirements)
- [Installation](#installation)
- [Architecture](#architecture)
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
