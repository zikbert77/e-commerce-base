# Admin Module

A store-scoped back-office at `/admin`, covering catalog, orders, customers,
and store/theme configuration. Built with plain Symfony controllers, forms,
and Twig — no admin bundle or SPA.

## Access

- Every route under `/admin` requires `ROLE_ADMIN` (`config/packages/security.yaml`
  `access_control`). Grant it by adding `"ROLE_ADMIN"` to a `User`'s `roles` column.
- `/admin` is reachable from exactly one fixed host, `%admin_host%`
  (`ADMIN_HOST` env var, default `admin.e-commerce.loc`) — every admin
  controller's class-level `#[Route(...)]` carries `host: '%admin_host%'`, so
  the route simply doesn't match on any other host (a request to `/admin` on
  a store's own domain 404s at the routing layer, before reaching any
  controller). Unlike the storefront, admin does **not** resolve its store
  from the request host: `StoreResolverSubscriber` explicitly skips the admin
  host (see [architecture.md](architecture.md)), and a separate
  `AdminStoreScopeSubscriber` scopes `/admin` requests instead, from a
  session-backed store switcher.
- The sidebar store box (`templates/admin/_layout.html.twig`) is a same-host,
  no-JS switcher (`<details>`/`<summary>` plus one `<form method="post">` per
  option, posting to `admin_store_switch`) listing the stores the logged-in
  user is linked to via the `Store`↔`User` many-to-many (`User::$stores`, see
  [architecture.md](architecture.md)), plus an explicit "All stores" option.
  The choice is stored in session (`AdminStoreScopeSubscriber::SESSION_KEY`)
  and survives across requests until changed. **Default (no prior choice) is
  aggregate "All stores" mode** — every page shows combined data from every
  store the admin is linked to, with a Store column on Products/Categories/
  Orders. Picking one store narrows everything to it. If a session-selected
  store is later unlinked from the user, the next request silently falls back
  to aggregate rather than erroring. A user with zero linked stores still
  reaches `/admin`, just sees empty data everywhere (aggregate over zero
  stores).
- Actions that need exactly one concrete store — creating a Product/Category,
  the Settings page — check `StoreContext::isInitialized()` and, in aggregate
  mode, flash an error and redirect instead of guessing a store.

## Structure

- Controllers: `src/Controller/Admin/` (`DashboardController`, `ProductController`,
  `CategoryController`, `OrderController`, `CustomerController`, `StoreController`,
  `ThemeController`, `SettingsController`).
- Forms: `src/Form/Admin/`.
- Templates: `templates/admin/`, extending `templates/admin/_layout.html.twig`
  (sidebar, topbar, flash messages — no theme system involved, this is
  independent of `templates/themes/`).
- Styling: `assets/styles/admin.css`, loaded via its own AssetMapper entrypoint
  (`assets/admin.js`, importmap key `admin`) — separate from the storefront's
  `app`/`app.css` so themes and admin never share global CSS.
- Nav badge counts (product/category/order/customer counts, new-order badge)
  come from `App\Twig\Extension\AdminNavExtension::admin_nav_counts()`, called
  once from the layout.

## Catalog is store-scoped

`Product`, `Category`, and `Order` implement `StoreScopedInterface` and carry a
`store` relation (see [architecture.md](architecture.md) for `StoreFilter`).
`StoreFilter` now restricts queries to `store_id IN (:storeIds)` rather than a
single id: on the storefront it's always a 1-element list (the host-resolved
store); in admin it's either a 1-element list (a specific store selected in
the switcher) or the full list of stores the admin is linked to (aggregate
mode). Admin `new` actions set `->setStore(...)` explicitly from
`StoreContext`, which is only ever populated when the switcher has one
specific store selected; nothing else needs to filter by store manually.

One consequence: **`StoreTemplateConfig` (also store-scoped) can only be read
or edited for the store the admin switcher currently has selected** — never in
aggregate mode. The store edit page (`admin_store_edit`) only shows the
"Template config" card when editing that selected store; editing another
store from the "Stores & domains" list shows profile fields and domains only.

## Content locales

Product/category translations are looked up by a fixed locale list,
`App\Enum\ContentLocale` (`en`, `uk`, `de`) — there's no per-store enabled-locales
setting yet. List and form pages take a `locale` query parameter (default `en`)
and switch which `ProductInfo`/`CategoryInfo` row is shown/edited.

## Known gaps

- **Orders are read-only.** There's no order-status transition UI; nothing in
  the app currently creates `Order` rows either (checkout isn't implemented),
  so this hasn't been exercised against real order-processing flows.
- **New customers get a random unusable password** and no invite/reset email —
  there's no onboarding flow yet. Use the existing reset-password flow
  (`symfonycasts/reset-password-bundle`) to let a new customer set one, or add
  that wiring when checkout/registration needs it.
- **`Cart` still has the same store-scoping gap that `Product`/`Category`/`Order`
  had before this module**: the `cart` table has a `NOT NULL store_id` column
  (migration `Version20260731064629`) but the `Cart` entity has no `store`
  field, so `CartService::createCart()` fails against the current schema. Not
  touched here since it's storefront checkout code, not admin — worth its own
  fix.
