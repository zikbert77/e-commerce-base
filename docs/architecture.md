# Architecture

### Multilingual Content Pattern

The application uses a **separate Info entities pattern** for internationalization:

- **Core entities** (`Category`, `Product`): Store language-independent data (ID, status, relationships, timestamps)
- **Info entities** (`CategoryInfo`, `ProductInfo`): Store locale-specific content (title, slug, description)
- Each info entity has a `locale` field (6 chars) and references its parent entity
- One-to-many relationship: one core entity → many info entities (one per locale)

**Example**: A `Product` has multiple `ProductInfo` records, each with translations for different locales.

### Entity Relationships

```
Category (hierarchical, store-scoped)
├── parent → Category (nullable, self-referencing)
├── childCategories → Collection<Category>
└── categoryInfos → Collection<CategoryInfo>

Product (store-scoped)
├── categories → Collection<Category> (many-to-many)
└── productInfos → Collection<ProductInfo>

Order (store-scoped)
└── orderItems → Collection<OrderItem>

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

The storefront resolves its store per-request by the incoming HTTP host. The admin module (see [admin.md](admin.md)) is the one exception: it lives on a single fixed host (`%admin_host%`) and instead resolves its store from a session-backed switcher, via a separate subscriber. Store resolution is split into two cached steps: host → store ID, then store ID → full store config DTO — the second step is shared by both paths.

- **`Store`**: Core tenant entity (`title`, `status` via `BaseStatus` enum, `storeDomains`). Many-to-many with `User` via the `store_user` pivot table (`Store::$users` owning side, `User::$stores` inverse, `mappedBy: 'users'`) — one store can have many users and one user can belong to many stores. Drives the admin store switcher and its aggregate-mode scoping (see below and [admin.md](admin.md)); still not used by the storefront's host-based resolution.
- **`StoreDomain`**: Maps a domain string to a `Store` (many-to-one). `StoreDomainRepository::findByHost()` (`src/Repository/StoreDomainRepository.php`) looks up the active store domain for a given host.
- **`StoreResolver`** (`src/Store/StoreResolver.php`): `resolveStoreIdByHost()` normalizes the host (lowercase, strips port, strips `www.`, via the public `normalizeHost()`) and resolves it to a store **ID** (not the entity), via `StoreDomainRepository::findByHost()`. The ID is cached in the `store.cache` pool (APCu, tagged `store_domains`, TTL from `CacheLifetime::STORE_RESOLVER` = 300s). Null results are cached too, to avoid repeated DB lookups for unknown hosts.
- **`StoreConfigProvider`** (`src/Store/StoreConfigProvider.php`): `getConfig(int $storeId)` loads the full store configuration as a `StoreDTO`, cached in `store.cache` (tagged `store_{id}`, TTL from `CacheLifetime::ONE_HOUR` = 3600s). Throws `StoreNotFoundException` if the store entity no longer exists. Builds the DTO via `StoreDTOFactory::fromEntity()`. Purely `id → StoreDTO`, host-agnostic, so it's reused as-is by both resolution paths below.
- **`StoreDTO`** (`src/Store/DTO/StoreDTO.php`): Immutable-by-convention read model for store config — `id`/`title`/`status`, plus a nested `TemplateDTO` (`code`/`title`/`config`, the template's default config merged with the store's `StoreTemplateConfig` override). This is what's cached and passed around per-request, instead of the Doctrine entity.
- **`StoreResolverSubscriber`** (`src/Store/EventSubsriber/StoreResolverSubscriber.php`): On `kernel.request` (priority 30, main requests only), skips `/_*` profiler/dev paths **and skips the admin host** (`%admin_host%` — handled by `AdminStoreScopeSubscriber` instead). Otherwise resolves the store ID for the host via `StoreResolver`, throws `App\Store\Exception\StoreNotFoundException` (extends `NotFoundHttpException`) if none matches, loads the `StoreDTO` via `StoreConfigProvider`, stores it in `StoreContext`, and enables Doctrine's `store_filter` with a 1-element `storeIds` list.
- **`AdminStoreScopeSubscriber`** (`src/Store/EventSubsriber/AdminStoreScopeSubscriber.php`): On `kernel.request` (priority 0, so it runs after Symfony's `FirewallListener`), only for `/admin` paths. Reads the logged-in user's linked stores (`User::$stores`) and a session-stored `admin_selected_store_id`. If a specific store is selected (and still linked to the user), behaves like the storefront subscriber for that one store — sets `StoreContext` and a 1-element `store_filter` list. If not (the default, or a stale selection reset after the linked store was removed), leaves `StoreContext` uninitialized and enables `store_filter` with the *full* list of the user's linked store IDs — aggregate mode. `admin_store_switch` (`StoreController::switchStore()`) writes the session key from the sidebar switcher form.
- **`StoreContext`** (`src/Store/StoreContext.php`): Request-scoped holder for the current `StoreDTO` (no longer the `Store` entity); reset automatically between requests via the `kernel.reset` tag. `isInitialized()` doubles as "exactly one concrete store is in scope" — true on the storefront always, true in admin only when a specific store is selected (never in aggregate mode).
- **`StoreFilter`** (`src/Doctrine/StoreFilter.php`): A Doctrine `SQLFilter` (registered as `store_filter`, disabled by default) that adds a `store_id IN (:storeIds)` constraint (via `SQLFilter::setParameterList()`/`getParameterList()`) to any entity implementing `App\Entity\Interface\StoreScopedInterface`. An empty id list (e.g. an admin linked to zero stores) yields `1 = 0` rather than an invalid empty `IN ()`.

**To scope an entity to a store**: implement `StoreScopedInterface` and add a `store` relation — the `store_filter` will then automatically restrict queries to whichever store(s) are in scope, once enabled by `StoreResolverSubscriber` (storefront) or `AdminStoreScopeSubscriber` (admin). `Product`, `Category`, `Order`, and `StoreTemplateConfig` do this; `Cart` has the `store_id` column (from the same migration that added it to the other four) but is not yet scoped — see [admin.md](admin.md#known-gaps).

> Note: the event subscriber's directory is `src/Store/EventSubsriber/` (typo, missing a `c`) — matches the current codebase, not a doc error.

### Theme System

See [docs/template.md](template.md) for the full theme system documentation (theme resolution, directory layout, routing table, and how to add a new theme).

### Timestamps

All entities implement `TimestampableInterface` using `andanteproject/timestampable-bundle`:
- Automatically manages `created_at` and `updated_at` fields
- Applied via `TimestampableTrait`

### Database

See [docs/infrastructure/database.md](infrastructure/database.md) for the full database documentation (platform, naming strategy, connection, migrations, and database management commands).

### Caching

- **`store.cache` pool**: APCu-backed (`cache.adapter.apcu`), tag-aware, 300s default lifetime (see `config/packages/cache.yaml`). Holds two kinds of entries:
  - host → store ID, written by `StoreResolver` (tagged `store_domains`, TTL `CacheLifetime::STORE_RESOLVER` = 300s)
  - store ID → `StoreDTO` config, written by `StoreConfigProvider` (tagged `store_{id}`, TTL `CacheLifetime::ONE_HOUR` = 3600s)
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
