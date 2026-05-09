# KHFinaM — Frontend design system & UI implementation guide

This document describes the **visual and structural patterns** used across KHFinaM so new work stays consistent with the current **premium fintech / analytics-first** direction.

**Stack (reference):**

- PHP views under `resources/views/`, layouts in `resources/views/layouts/`
- Tailwind CSS via CDN, `darkMode: 'class'` (see `components/layout/analytics-shell-head.php`)
- Font: **Inter** (Google Fonts)
- Icons: **Lucide** (`data-lucide`, `lucide.createIcons()` on load)
- Charts: **ApexCharts** 3.x (dashboards and analytics)

---

## 1. Color system

### 1.1 Semantic roles

| Role | Light mode | Dark mode | Usage |
|------|------------|-----------|--------|
| **App canvas** | `bg-slate-100` | `bg-[#090e1a]` | `shell-body-open` — page background behind panels |
| **Primary panel** | `bg-white` | `bg-[#0d1424]` | Sidebars, cards, main surfaces |
| **Accent (brand / CTAs)** | Teal family (`teal-600`, `teal-700`, `teal-800`) | `teal-400`, `teal-300` | Links, highlights, active states |
| **Ink (primary text)** | `slate-900`, `slate-800` | `white`, `slate-100`, `slate-200` | Headings and key values |
| **Muted (secondary)** | `slate-600` (prefer over `slate-500` on white) | `slate-400`, `slate-500` | Subtitles, helper copy |
| **Labels / eyebrows** | `slate-600` + uppercase tracking | `slate-400`–`slate-500` | Section labels |
| **Borders** | `slate-300` at ~85–92% opacity | `slate-700`–`slate-800` / ~55–65% opacity | Cards, dividers |
| **Positive** | `emerald-*`, `teal-*` | Same families, slightly brighter on dark | Income, savings, success |
| **Negative** | `rose-*`, `rose-600` text | Same | Expense deltas, destructive tone |
| **Warning** | `amber-*` | Same | Alerts, caution chips |
| **Secondary accent** | `violet-*` (recurring, secondary actions) | `violet-400` | Differentiate automation clusters |

Charts use explicit hex where needed for Apex (e.g. income `#10b981`, expense `#f43f5e` on admin dashboard). Prefer **consistent series colors** across similar chart types site-wide.

### 1.2 Gradients

- **Hero KPI strips:** `bg-gradient-to-br` across a **single hue family** into deep `*-900` / `slate-900`; keep **readable white** typography on top.
- **Insight / intel bands:** subtle `via-slate-50`, `to-teal-50/65` (light); dark uses `from-[#0c1426] via-[#0d1629] to-teal-950/25` style washes.
- **Glass insight cards (`insight-glass-card`):** light = white + soft shadow + light ring; dark = layered dark blues + inset highlight.

Avoid large flat white regions on dashboards; prefer **.cards on tinted canvas**.

### 1.3 Rings

Light mode panels often combine:

- `border border-slate-300/85` (or `/88`)
- `ring-1 ring-slate-900/[0.06]`–`[0.07]`

Dark mode:

- `ring-1 ring-white/[0.05]`–`[0.06]` with softer borders — **do not** copy light ring opacity to dark verbatim.

---

## 2. Typography hierarchy

| Level | Typical classes | Usage |
|-------|------------------|-------|
| **Page title (desktop)** | `text-2xl font-bold text-slate-900 dark:text-white` | `desktop-page-header.php` |
| **Section title (card)** | `text-sm font-bold` or `font-semibold text-slate-800 dark:text-slate-100` | Chart cards, feature blocks |
| **Eyebrow / platform label** | `text-[10px] font-extrabold uppercase tracking-[0.18em]–[0.22em] text-teal-600 dark:text-teal-400` | Admin intelligence strip |
| **Body** | `text-xs`–`text-sm` with relaxed `leading-snug` / `leading-relaxed` | Dense UI, mobile |
| **Numeric KPI** | `text-3xl` or `text-2xl font-extrabold tabular-nums tracking-tight` | KPI values; always `tabular-nums` for alignment |
| **Meta / date row** | `text-xs font-medium text-slate-500 dark:text-slate-400` | Header date line |

**Rule:** On **light** backgrounds, avoid `text-slate-400` alone for critical secondary copy; prefer **`text-slate-600`** for scanability unless context is tertiary.

---

## 3. Spacing rules

- **Baseline grid:** Tailwind spacing scale (4px base); favor **4, 5, 6** for rhythm between sections (`gap-4`, `gap-5`, `mb-6`, `py-5`, `lg:py-6`).
- **Main content:** Default from `main-content-open.php`:  
  `p-4 md:px-5 md:py-5 lg:px-7 xl:px-8 lg:py-6` — widen horizontal padding at `lg` / `xl`, not vice versa.
- **Card interiors:** Commonly `p-5 sm:p-6`; compact rows use `px-3.5 py-2.5`.
- **Stacks:** Prefer `space-y-*` or explicit `gap-*` in grids; keep **dense finance UIs** from feeling cramped via `leading-snug` and consistent chip padding.

---

## 4. Border usage

- **Standard card (light):** `border-slate-300` at **85–90% opacity** — visible but not harsh.
- **Standard card (dark):** `border-slate-700/55`–`slate-800` depending on layer.
- **Hairline / dividers:** `divide-slate-200/90` (light), `divide-slate-800/80` (dark); vertical rules `w-px bg-gradient-to-b from-transparent via-slate-300 dark:via-slate-700`.
- **Dashed empty regions:** `border-dashed border-slate-300/90` with optional `shadow-inner` for light “inset panel” feel.
- **Accent chips:** add `ring-1` in the same hue family (e.g. teal ring on teal-tinted pill).

---

## 5. Elevation / shadow rules

### Light mode (SaaS depth)

- **Primary card:** layered shadow, e.g.  
  `shadow-[0_20px_50px_-24px_rgba(15,23,42,0.15),0_8px_24px_-10px_rgba(15,23,42,0.08)]`  
  Plus **thin ring** (`ring-slate-900/[0.07]`).
- **Small controls / chips:** `shadow-sm shadow-slate-900/10`–`12`.
- **Mobile header:** subtle `shadow-[0_1px_0_0_rgba(15,23,42,0.06)]` — **remove** under `dark:` when needed.

### Dark mode (premium depth)

- Heavier diffuse shadow without gray “fog”: e.g.  
  `shadow-[0_24px_56px_-36px_rgba(0,0,0,0.65)]`  
- Optional **inner highlight** on special glass panels (`insight-glass-card` pattern).

### Hero KPI gradients (light vs dark)

- **Light:** color-tinted **large blur** shadows, e.g. `shadow-[0_24px_48px_-12px_rgba(...,brand...)]` plus `ring-1 ring-{hue}-950/18`.
- **Dark:** preserve existing `shadow-xl shadow-{hue}-500/25`–style glows — mirror with `dark:` on the same element.

**Rule:** Prefer **one** dominant shadow + **one** ring; avoid stacking 3+ unrelated shadow utilities.

---

## 6. Card patterns

| Pattern | Where | Notes |
|---------|------|-------|
| **Analytics chart shell** | `components/analytics/chart-shell-card.php` | Title + subtitle + optional badge + `#chartId` mount; override `cardClass` for premium shells |
| **Hero KPI gradient** | `components/admin/hero-kpi-gradient-card.php` | Full-bleed gradient; label, value, footnote, Lucide icon, optional `trendChip` |
| **Glass insight** | `components/analytics/insight-glass-card.php` | Eyebrow + icon tile + orb; `$contentHtml` must be escaped by caller |
| **Muted empty** | `components/ui/empty-state-muted.php` | Centered Lucide + title + subtitle |
| **Message banner** | `components/ui/message-banner-inline.php` | Inline alerts |

Rounded corners default to **`rounded-2xl`** for dashboards; **`rounded-xl`** for nested blocks and chips; **`rounded-full`** for pills and icon buttons where appropriate.

---

## 7. KPI card standards

When using **`hero-kpi-gradient-card`**:

1. **`$gradientShell`** — Tailwind classes for outer container (gradient bg, padding, shadows, rings). Pass **paired** light + dark behavior via **`dark:`** utilities on the same string.
2. **`$label`** — Short, uppercase eyebrow styling is applied inside partial (`text-[10px] ... tracking-[0.14em]`).
3. **`$value`** — Escaped output; large `tabular-nums`.
4. **`$footnote`** — One line context; opacity handled in partial.
5. **`$icon`** — Lucide icon name; icon sits in **`bg-white/18 ring-2 ring-white/25`** tile (on dark gradients).
6. **`$trendChip` / `$trendChipClass`** — Optional; chips use translucent fills + rings readable on gradients.

Trend chips on colored backgrounds should stay **compact** (`text-[10px] font-bold uppercase`) and WCAG-legible via contrast-aware backgrounds (emerald/rose glass, not faint gray-on-white pasted onto gradients).

---

## 8. Analytics chart standards

### Implementation

1. Include **ApexCharts** from analytics layout (already in `analytics-shell-head.php`).
2. Mount charts on a **`div` with explicit `min-h-*`** so layout doesn’t jump.
3. Read theme: `document.documentElement.classList.contains('dark')` and set Apex `theme.mode`, colors, and grid opacity accordingly (see `admin/dashboard.php`).

### Visual

- **Heights:** ~**232–236px** for area/donut on desktop; use `responsive` breakpoints to lower height on small screens if needed.
- **Area / cashflow:** Smooth curve, gradient fill (`opacityFrom` higher in light for presence), subtle **series dropShadow** (stronger blur/opacity in light when appropriate).
- **Donut:** No data labels cluttering slices by default; **center total** or legend carries meaning; slice stroke separators — light: `#e2e8f0`, dark: near-`#0f172acc`.
- **Radial / gauge:** Rounded caps; track color light `#cbd5e1`, dark `#1e293b`; value label **`textHi`** contrast color from script.
- **Axes:** Label color **darker in light** (`#475569` range), grid `rgba` with slightly higher contrast in light.
- **Tooltips:** Global light-mode polish in `analytics-shell-head.php` under `html:not(.dark) .apexcharts-tooltip...` — keep tooltips bordered and shadowed, not naked white boxes.

### Data absence

Prefer **premium empty blocks** inside the chart container: dashed border, `from-white to-slate-50`, **`shadow-inner`**, icon in tinted rounded square, concise title + explanation (see `admin/dashboard.php` JS helpers).

Every **analytics-heavy page** should include **at least one chart** per product rules (`/.cursor/rules/frontend-ui.mdc`, `charts.mdc`).

---

## 9. Responsive breakpoints

Tailwind defaults:

| Token | Width | Shell usage |
|-------|-------|----------------|
| `sm` | 640px | Minor text/grid tweaks |
| `md` | 768px | Sidebar **visible** (off-canvas drawer below); sidebar **compact rail** begins |
| `lg` | 1024px | Multi-column dashboards (`lg:grid-cols-3`), governance split |
| `xl` | 1280px | Sidebar **always expanded** labels; wider horizontal padding |

**Sidebar mid breakpoint:** **`768px–1279.98px`** — icon-only rail unless `.sidebar-mid-expanded` (`shell-sidebar-slim-css.php`). **≥1280px** — full labels and brand.

---

## 10. Dark / light mode rules

- **Mechanism:** `class="dark"` on `<html>`; initialized from `localStorage` + `prefers-color-scheme` in `analytics-shell-head.php`.
- **Keys:** `khf_admin_theme` (admin layout), `khf_dashboard_theme` (user dashboard layout).
- **Toggle:** `toggleDark()` in `shell-scripts.php` persists choice.
- **Styling convention:** Encode **light as default utilities**, **`dark:*` overrides** — keeps light “SaaS crisp” tuning without weakening dark dashboards.
- **Forms:** `#mainContent` scoped rules in `shell-main-form-css.php`: light inputs get **explicit border + subtle inner highlight**; dark inputs slate border, no faux inner glow unless designed.
- **ApexCharts:** Always branch `isDark` for colors, grids, fills, shadows; optional shared CSS under `html:not(.dark)` for tooltip chrome only.

Avoid **plain `#fff` fullscreen** dashboards in light mode; use **cards + tinted background**.

---

## 11. Sidebar behavior

- **`<aside id="sidebar">`:** Fixed on mobile (`-translate-x-full` until opened); **`md:`** sticky, translated in.
- **Overlay:** `#sideOverlay` + `openSidebar()` / `closeSidebar()` (body scroll locked when open).
- **Width:** ~**260px** expanded; **4.5rem** compact rail in md–xl when collapsed.
- **Brand row classes:** Passed from layout (`admin.php` / `dashboard.php`), includes **bottom border** separation.
- **Nav:** `.sidebar-link` styles in `shell-sidebar-link-css.php` — active teal/mint tray (light **stronger mint** `[rgba(167,243,208,0.78)]`).
- **Footer:** Theme toggle + user + logout (`sidebar-footer-user.php`).
- **After DOM changes:** Call `lucide.createIcons()` if adding icons dynamically (`shell-scripts` already runs once at end).

Prefixes **`adm-sb-*`** vs **`dash-sb-*`** control which sidebar labels hide/show in compact mode — reuse when introducing new sidebar rows.

---

## 12. Animation / motion rules

- **Page enter:** `#mainContent` uses short **`fadeUp`** (`shell-main-form-css.php`) — keep duration ~**0.22s** unless a special transition is needed.
- **Sidebar:** `transition-[transform,width,...] duration-200 ease-out`.
- **Hover:** Prefer **short** transitions (`transition-colors`, `transition-all` under ~150–200ms) on rows and pills; avoid long bounces on data views.
- **Charts:** Apex `animations` optional (admin cashflow uses **~760ms ease**); keep animations **subtle on repeat loads** — don’t rely on animation for conveying critical numbers.
- **Counters:** Animated count-ups only where explicitly implemented; otherwise static `tabular-nums` is acceptable.

Avoid **motion that blocks** resizing charts; dispatch `resize` after sidebar toggle (already wrapped in sidebar script).

---

## 13. Empty state rules

- Use **`components/ui/empty-state-muted.php`** when the block is informational, not erroneous.
- **Larger analytic empties:** Dashed **`rounded-xl`** frame, **`shadow-inner`** in light, short headline + explanation; icon in **rounded-2xl** tinted holder + **ring**.
- Tone: calm, neutral copy (“Awaiting ledger activity”), not blaming the user.

---

## 14. Table design rules

Design system direction: **cards over tables for primary insight** (`frontend-ui.mdc`). When tables are required:

1. Prefer **dense, zebra-free** layouts with **`divide-y`** and **row hover** (`hover:bg-slate-50/50 dark:hover:bg-slate-800/25`).
2. Right-align **currency and counts** (`text-right tabular-nums`).
3. Use **muted** column headers (`text-[10px]` or `text-xs uppercase tracking-wider`).
4. On mobile, consider **responsive card stacking** or horizontal scroll — don’t squash multi-metric rows without a deliberate pattern.

Avoid **bare HTML tables** with default browser borders; wrap in padded panel matching **card shells**.

---

## 15. Form / input standards

- **Semantic HTML** + **`Csrf::field()`** on mutating POST forms (controllers verify).
- **Labels:** Visible; use hierarchy consistent with typography section.
- **Inputs / selects / textareas** under `#mainContent` inherit global chrome (border, color-scheme); **prefer Tailwind utilities** (`rounded-lg`, `px-3`, `py-2`) layered on native elements.
- **Focus:** Maintain visible `:focus-visible` outlines (respect browser or add ring utilities if customizing).
- **Validation:** Errors near fields; optionally `message-banner-inline` for summaries.

Escape all dynamic output with **`Str::e()` / `htmlspecialchars`** in PHP views.

---

## 16. Button hierarchy

| Tier | Pattern | Typical classes |
|------|---------|----------------|
| **Primary** | Solid gradient or strong teal fill | Rare for admin shell; CTAs inside marketing-style blocks |
| **Secondary** | Bordered pill or rounded-xl | `border-slate-300/90 bg-white shadow-sm hover:bg-teal-50 dark:border-slate-600 dark:bg-slate-800/80 dark:shadow-none` |
| **Ghost / icon** | Icon-only toolbar | `p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800` (mobile header pattern) |
| **Destructive** | Logout hover | Rose hover `hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40` |

Maintain **consistent border-radius** (`rounded-xl` / `rounded-full`) across a single surface.

---

## 17. Icon usage

- **Library:** Lucide only (keep stroke weight ~**2–2.25** for hero tiles).
- **Pattern:** `<i data-lucide="wallet" class="w-4 h-4 ..."></i>`
- **Color:** Inherited or explicit `text-teal-600 dark:text-teal-400` for accents; **`text-slate-500`** for neutral chrome.
- **Sizing:** KPI tiles **~w-[1.35rem]**; dense lists **w-3.5 h-3.5**; headers **w-5 h-5**.
- **`[data-lucide]`:** `display:inline-block`, `vertical-align:middle` in analytics head.

Always re-run **`lucide.createIcons()`** after injecting icons via JS.

---

## 18. Component usage examples

### Chart card with override shell

```php
<?php
$chartCardShell = 'rounded-2xl bg-white dark:bg-[#0d1424] border border-slate-300/85 dark:border-slate-700/65 p-5 sm:p-6 '
    . 'shadow-[0_20px_50px_-24px_rgba(15,23,42,0.15),0_8px_24px_-10px_rgba(15,23,42,0.08)] '
    . 'dark:shadow-[0_24px_56px_-36px_rgba(0,0,0,0.65)] ring-1 ring-slate-900/[0.07] dark:ring-white/[0.06] relative overflow-hidden';

View::partial('components/analytics/chart-shell-card', [
    'title'       => 'Platform Cashflow',
    'subtitle'    => 'Income vs expenses · trailing view',
    'chartId'     => 'cashflowChart',
    'badgeText'   => 'Live',
    'badgeClass'  => 'text-[10px] font-bold bg-teal-50 dark:bg-teal-950/60 text-teal-800 dark:text-teal-300 '
        . 'px-2.5 py-1 rounded-full uppercase tracking-wider ring-1 ring-teal-300/75 dark:ring-teal-800/50 shadow-sm shadow-teal-900/15 dark:shadow-none',
    'cardClass'   => $chartCardShell,
    'chartContainerClass' => 'mt-3 min-h-[228px]',
]);
?>
```

Mount Apex on `#cashflowChart` in a `@push`-style `<script>` or bottom-of-view script; mirror `theme.mode` against `dark` class.

### Hero KPI with trend chip

```php
View::partial('components/admin/hero-kpi-gradient-card', [
    'gradientShell' => 'rounded-2xl bg-gradient-to-br from-emerald-500 via-emerald-600 to-emerald-950 '
        . 'p-5 sm:p-6 text-white shadow-[0_24px_48px_-12px_rgba(5,122,85,0.44)] '
        . 'dark:shadow-xl dark:shadow-emerald-500/25 relative overflow-hidden '
        . 'ring-1 ring-emerald-950/20 dark:ring-white/15',
    'label'     => 'Platform Income',
    'value'     => 'RM ' . number_format($total, 0),
    'footnote'  => 'Base-currency rollup',
    'icon'      => 'trending-up',
    'trendChip' => '+12% MoM',
    'trendChipClass' => 'bg-emerald-400/28 text-emerald-50 ring-1 ring-emerald-300/40',
]);
```

### Muted empty state

```php
View::partial('components/ui/empty-state-muted', [
    'icon'     => 'inbox',
    'title'    => 'No items yet',
    'subtitle' => 'When data exists, this panel updates automatically.',
]);
```

Wrap in dashed **elevated inset** panel on analytics pages when the empty deserves more visual presence.

---

## 19. Consistency checklist (for PRs)

- [ ] Secondary text **`slate-600`** (light) / **`slate-400`** (dark) appropriately?
- [ ] Cards: **border + ring + shadow** tier appropriate to layer (foreground vs inset)?
- [ ] Numbers: **`tabular-nums`** where alignment matters?
- [ ] Charts: **empty**, **responsive**, **`dark` branch**, **consistent palette**?
- [ ] Sidebar / mobile: navigable **without** horizontal breakage at `sm`/`md`?
- [ ] New icons: **`data-lucide`** + **hydrated** after render?
- [ ] User content: escaped; forms: CSRF?

---

## 20. Related code paths

| Concern | File(s) |
|---------|---------|
| Analytics HTML shell | `components/layout/analytics-shell-head.php` |
| Layout body / sidebar chrome | `components/layout/shell-body-open.php`, `sidebar-aside-open.php` |
| Main padding | `components/layout/main-content-open.php` |
| Forms & scrollbar | `components/layout/shell-main-form-css.php` |
| Sidebar link colors | `components/layout/shell-sidebar-link-css.php` |
| Sidebar breakpoints | `components/layout/shell-sidebar-slim-css.php` |
| Theme + sidebar JS | `components/layout/shell-scripts.php` |
| Chart wrapper | `components/analytics/chart-shell-card.php` |
| KPI hero tile | `components/admin/hero-kpi-gradient-card.php` |
| Product UI rules | `.cursor/rules/frontend-ui.mdc`, `.cursor/rules/charts.mdc` |
| Local URLs & demo users | `README.md` (Demo accounts + `APP_URL` paths), `INSTALLATION.md` |

---

*Document version aligns with repo patterns at time of writing; when introducing new primitives, extend this guide in the same sections rather than scattering one-off conventions.*
