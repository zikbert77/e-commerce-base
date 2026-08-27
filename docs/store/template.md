# Theme System

Each store renders through a **theme**: a directory of Twig templates under `templates/themes/{code}/`, where `{code}` is `Template::code` (the unique key on the `template` table, e.g. `default`, `coffee`). Resolution to a concrete directory happens **inside the Twig loader**, not in controller code:

- **`ThemeAwareLoader`** (`src/Twig/Loader/ThemeAwareLoader.php`): a `FilesystemLoader` subclass registered as a `twig.loader` service (`config/services.yaml`, priority 100) with the `theme` Twig namespace pointed at `templates/themes` (`$this->addPath($themesRootDir, 'theme')`). It intercepts any template name starting with `@theme/`, rewrites it to `@theme/{current store's template code}/{rest}` (via `StoreContext::get()->getTemplate()->getCode()`), and delegates to the parent `FilesystemLoader`. Non-`@theme` names (e.g. the app-level `base.html.twig`) fall through to Symfony's default filesystem loader in the chain, unaffected.
- **`StoreGlobalsExtension`** (`src/Twig/Extension/StoreGlobalsExtension.php`): a Twig extension implementing `GlobalsInterface`, registered automatically by `autoconfigure: true` (no explicit service config needed — any class extending `AbstractExtension` under `App\` is picked up). Its `getGlobals()` injects the current `StoreDTO` as `store`, available in **every** Twig template without a controller passing it. This is why the earlier convention of passing `'store' => $this->storeContext->get()` on every `render()` call is now unnecessary — see below.

Controllers and templates never reference `templates/themes/{code}/...` or interpolate the code themselves — they just use the `@theme/` prefix and the loader resolves it per-request. Controllers also don't need to pass `store` explicitly (it's a Twig global); only pass data Twig genuinely can't derive on its own, like a route's `{slug}`:

```php
// Controller — store is injected as a Twig global, not passed here
return $this->render('@theme/home.html.twig');

// ...unless the action has its own data to hand the template
return $this->render('@theme/product.html.twig', [
    'slug' => $slug,
]);
```
```twig
{# Inside a theme's own templates — `store` is available anywhere, no {% set %} needed #}
{% extends '@theme/base.html.twig' %}
{% include '@theme/partials/_header.html.twig' %}
<h1>{{ store.title }}</h1>
```

Every storefront controller still extends `BaseController` (`src/Controller/BaseController.php`), which constructor-injects `StoreContext` — but as of `StoreGlobalsExtension`, none of the current controller actions actually call `$this->storeContext->get()` themselves; it's kept on the base class for whatever store-scoped logic a future action needs (e.g. querying store-scoped entities), not for template rendering.

**Directory layout of a theme** (`templates/themes/coffee/` is the reference implementation):

```
templates/themes/{code}/
├── base.html.twig       — root layout; {% extends 'base.html.twig' %} (the app shell in templates/)
├── home.html.twig       — page templates, one per controller action (see routing table below)
├── catalog.html.twig
├── product.html.twig
├── checkout.html.twig
├── search.html.twig
├── category.html.twig
├── about.html.twig
├── contacts.html.twig
└── partials/            — shared chrome, included (never routed to) directly
    ├── _topbar.html.twig
    ├── _header.html.twig
    ├── _footer.html.twig
    └── _cart_modal.html.twig
```

**Required files**: `base.html.twig` plus one top-level `*.html.twig` per controller action that renders through that theme (see table below) — a controller 500s if its theme is missing the file it names. `partials/` is a convention, not enforced by any controller; `base.html.twig` is free to structure its own chrome however it likes, but the reference theme keeps shared chrome there.

**Naming**: top-level page templates are named to match exactly what the controller renders (`@theme/catalog.html.twig` → `catalog.html.twig`, case-sensitive) — there's no fallback or fuzzy resolution. Partial/include-only files are prefixed with `_` and live under `partials/`, the standard Twig/Symfony convention for "never rendered as a controller's top-level template." Because resolution goes through the `@theme/` namespace, a theme's own templates reference each other with `@theme/...` too (`{% extends '@theme/base.html.twig' %}`, `{% include '@theme/partials/_header.html.twig' %}`) rather than hardcoding the theme's own code — copying a theme to a new code needs no path changes inside it.

**`base.html.twig` contract**: extends the app-level `templates/base.html.twig` (which defines `title`/`stylesheets`/`javascripts`/`body` blocks), fills `stylesheets` with the theme's design tokens/CSS and `body` with the page chrome around a `content` block. Page templates extend `@theme/base.html.twig` and fill `content`, plus a `page_title` block for the `<title>` (nested inside `base.html.twig`'s `title` block so pages can override it without repeating the `title` block itself).

**Routing table** — which controller action renders which theme file, current as of this writing (`php bin/console debug:router`):

| Route | Method | Path | Controller | Renders | Status |
|---|---|---|---|---|---|
| `app_home` | GET | `/` | `MainController::index()` | `@theme/home.html.twig` | built (coffee) |
| `app_catalog` | GET | `/catalog` | `CatalogController::catalog()` | `@theme/catalog.html.twig` | built (coffee) |
| `app_search` | GET | `/catalog/search` | `CatalogController::search()` | `@theme/search.html.twig` | **missing template** |
| `app_category` | GET | `/catalog/category/{slug}` | `CatalogController::category(string $slug)` | `@theme/category.html.twig` | **missing template** |
| `app_product_view` | GET | `/product/{slug}` | `ProductController::view(string $slug)` | `@theme/product.html.twig` | built (coffee) |
| `app_order_checkout` | GET | `/order/checkout` | `OrderController::checkout()` | `@theme/checkout.html.twig` | built (coffee) |
| `app_page_about` | GET | `/about` | `PageController::about()` | `@theme/about.html.twig` | **missing template** |
| `app_page_contacts` | GET | `/contacts` | `PageController::contacts()` | `@theme/contacts.html.twig` | **missing template** |

"Missing template" means the route and controller exist and call `render()` correctly, but no theme currently ships that file, so hitting the route 500s until it's added under `templates/themes/{code}/`.

**Not theme-scoped**: authentication and account pages (`app_login`, `app_register`, `app_logout`, email verification) render from `templates/security/` and `templates/registration/` directly via plain (non-`@theme/`) template names, not from `templates/themes/{code}/` — they're shared chrome-free pages common to every store regardless of which theme is active. The cart API (`Api\CartController`, `/api/cart`) returns JSON and has no template at all.

**Adding a new theme**: create `templates/themes/{code}/` with at least `base.html.twig` and the page templates for every route you want that store to serve (start from the table above, all referencing each other via `@theme/...`), create a matching `Template` row (`code`, `title`, `default_config`), and point a `Store.template` at it (or leave it null to fall back to `TemplateRepository::getDefault()`, the `code = 'default'` row). No controller or config changes are needed — the loader picks up the new code automatically.
