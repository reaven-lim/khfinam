# KHFinaM — reusable UI partials (`resources/views/components`)

Vanilla PHP only: partials are included via `App\Helpers\View::partial()`. No Blade, no build step.

For colors, spacing, elevation, chart/KPI standards, and theme rules, see **[DESIGN_SYSTEM.md](../DESIGN_SYSTEM.md)** in the repo root.

## Rendering API

```php
use App\Helpers\View;

View::partial('components/ui/empty-state-muted', [
    'icon' => 'users',
    'title' => 'No rows yet',
    'subtitle' => 'Adjust filters',
]);
```

Path is rooted at `resources/views/`. Unknown partials trigger HTTP 500 and a short plain message (same idea as missing views).

---

## Layout (`components/layout`)

| Partial | Purpose |
|--------|---------|
| `analytics-shell-head` | Shared analytics shell `<head>`: Inter, Tailwind CDN, ApexCharts, Lucide, sidebar + `#mainContent` CSS, FOUC-safe theme bootstrap. Props: `titleText` (escaped HTML for `<title>`), `themeLocalStorageKey`, `sbPrefix` (`dash-sb` or `adm-sb`). |
| `shell-body-open` | Opens `<body>` with the slate / dark semantic shell classes. |
| `shell-overlay` | Mobile sidebar scrim `#sideOverlay`. |
| `sidebar-aside-open` / `sidebar-aside-close` | Fixed / sticky sidebar chrome with slim-rail transitions. |
| `sidebar-brand-row` | Logo, titles, compact-rail toggle. Props: `appName`, `brandSubtitle`, `logoIcon`, `sbPrefix`, `brandRowClass`, `brandTitleAttr`, toggle ids + `toggleOnclick` (function name **without** `()`). |
| `sidebar-dash-app-shortcuts` | Dashboard-only shortcuts to `/app` and `/app/add`. |
| `sidebar-nav-groups` | Grouped sidebar links + active dot. Props: `navGroups`, `here`, `sbPrefix`, `navExactRoot` (`/dashboard` or `/admin`), `navWrapClass`. |
| `sidebar-footer-user` | Theme control, initials avatar, logout form. Props: `displayName`, `initials`, `badgeLine`, `footerBadgeClass`, optional `logoutButtonTitle`. |
| `shell-main-column-open` / `shell-main-column-close` | Wrapper around mobile header + desktop header + `#mainContent`. |
| `mobile-shell-header` | `md:hidden` top bar with menu + title + theme. Props: `title` (optional), `fallbackTitle`. |
| `desktop-page-header` | `hidden md:flex` title + date + theme chip. Props: `title`, `fallbackTitle`. |
| `main-content-open` / `main-content-close` | Opens/closes `#mainContent`. **The routed PHP view MUST be `include`d from `layouts/dashboard.php` / `layouts/admin.php`** (same directory scope as pre-refactor) so controller variables remain visible. Optional `mainClasses` on open only. |
| `shell-scripts` | `openSidebar` / `closeSidebar` / `toggleDark`, plus mid-width sidebar expand wiring. Props: `themeLocalStorageKey`, `midToggleFunctionName`, `midToggleButtonId`, `midToggleIconId`, `midToggleStorageKey`. |
| CSS fragments | `shell-sidebar-link-css`, `shell-sidebar-slim-css` (needs `sbPrefix`), `shell-main-form-css` — only included via `analytics-shell-head`. |

**When to edit layout partials**

- Prefer editing these shared pieces when fixing responsive sidebar, theme FOUC, or shell padding so `/dashboard` and `/admin` stay aligned.

---

## Analytics (`components/analytics`)

| Partial | Purpose |
|--------|---------|
| `chart-shell-card` | Standard white / dark chart card with title row and optional Apex anchor `chartId`. Supports optional pill (`badgeText`) or `headerSimple` for donut-style headers. Props: `title`, `subtitle`, `chartId`, optional `badgeText`, `badgeClass`, `cardClass`, `chartContainerClass`, `headerSimple`. |
| `filter-lens-intro` | Icon-led block at the top of large filter surfaces (transactions lens). Props: `eyebrow`, `title`, `description` — **already HTML-escaped** where needed (`$scopeNote` mixes literals and escaped dates); do **not** pass raw user input without escaping upstream. Optional `icon`. |
| `insight-glass-card` | Frosted narrative “smart insight” article. Props: `icon`, `iconClass`, `iconBoxClass`, `orbClass`, `eyebrow`, `contentHtml` (trusted fragment, typically from `ob_get_clean()` in the caller), optional `articleClass` for responsive column spans. |

**Design rules**

- Keep chart-specific Apex options in page-level `<script>` blocks; partials own **containers** only so heights and breakpoints stay untouched.
- For complex prose, capture markup with output buffering and pass `contentHtml` so conditionals remain in one place.

---

## Admin (`components/admin`)

| Partial | Purpose |
|--------|---------|
| `hero-kpi-gradient-card` | Gradient KPI tile used on `/admin` overview. Props: `gradientShell` (full card classes), `label`, `value`, `footnote`, `icon`, optional `valueClass` (defaults to `text-3xl`). |
| `wallet-type-icon-select` | Admin wallet type create/edit: emoji + label options, values are Lucide keys. Props: `selectId`, `selected`, optional `required`, `selectClass`, `extrasIcon` (prepends a row when the saved icon is not in the preset list). |

---

## UI atoms (`components/ui`)

| Partial | Purpose |
|--------|---------|
| `empty-state-muted` | Centered icon + short copy for empty datasets. Props: `icon`, `title`, optional `subtitle`. |
| `message-banner-inline` | Compact success/warning inset banner. Props: `tone` (`success` or `warning`), `icon`, `message` (**plain text**, escaped internally). |

---

## Charts (`components/charts`)

Reserved for Apex-specific snippets or reusable chart wrappers that are heavier than `chart-shell-card`. Prefer `components/analytics/chart-shell-card.php` plus page scripts until a second consumer appears.

---

## Security & escaping

| Pattern | Guidance |
|---------|----------|
| `Str::e()` | Use at boundaries for titles, URLs built with `Url::`, and user-provided scalar text inside partials. |
| Trusted HTML blobs | Used only where the caller built markup with intentional escaping (`insight-glass-card` → `contentHtml`, `filter-lens-intro` → `description`). Never pass unsanitized request input into trusted slots. |

---

## Example: new dashboard page

1. Add controller data as today; keep routes unchanged.
2. Render with `View::renderLayout('dashboard', 'dashboard/my_page', $data)` (or `'admin'` + `admin/...`).
3. Reuse `chart-shell-card` around new Apex anchors; reuse `empty-state-muted` when a list may be empty.
4. Avoid editing `layouts/dashboard.php` for one-off tweaks—extend layout partials only when both shells benefit.

---

## Regression checklist after UI edits

Confirm manually (or smoke-test): `/dashboard`, `/admin`, `/dashboard/transactions`, `/admin/transactions`, `/app` shell, Apex charts render, filters submit, sidebar compact toggle + mobile drawer, theme persistence.
