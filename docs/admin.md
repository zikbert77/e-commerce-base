# Admin Module

A store-scoped back-office at `/admin`, covering catalog, orders, customers,
and store/theme configuration. Built with plain Symfony controllers, forms,
and Twig — no admin bundle or SPA.

## Access

- Every route under `/admin` requires `ROLE_ADMIN` (`config/packages/security.yaml`
  `access_control`). Grant it by adding `"ROLE_ADMIN"` to a `User`'s `roles` column.
- Like the storefront, `/admin` still goes through `StoreResolverSubscriber`
  (see [architecture.md](architecture.md)): the request's **host** resolves the
  current store. There is no in-app store switcher — each store's admin is
  reached through a domain mapped to that store in `StoreDomain`. A host with
  no matching domain 404s before reaching any admin controller.

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
Once `StoreResolverSubscriber` enables the filter for the request, every
catalog/order query — admin or storefront — is automatically restricted to the
current store. Admin `new` actions set `->setStore(...)` explicitly from
`StoreContext`; nothing else needs to filter by store manually.

One consequence: **`StoreTemplateConfig` (also store-scoped) can only be read
or edited for the store the current request's host resolves to.** The store
edit page (`admin_store_edit`) only shows the "Template config" card when
editing the current store; editing another store from the "Stores & domains"
list shows profile fields and domains only.

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
