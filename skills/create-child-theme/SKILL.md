---
name: create-child-theme
description: Generate an OctoberCMS child theme that inherits from Renick Dawn with a custom colour scheme, corner style, page set, and optional blog. Use when a user wants a new theme "based on Dawn", "a Dawn child theme", a re-skinned Dawn, or a client variant of Dawn. Walks the user through choices, scaffolds themes/<slug> with theme.yaml parent set to Dawn's real directory name and a child.css override layer, then verifies it via a throwaway prefixed site without touching the primary site.
---

# Create a Dawn child theme

This skill scaffolds a new OctoberCMS **child theme** that inherits everything
from **Renick Dawn** and overrides only what the user wants: the
colour scheme, corner radius (round vs. square), which pages exist, whether the
blog is included, and optionally the font. The child ships nothing it doesn't
change — every page, partial, layout, blueprint and asset it doesn't override
falls back to Dawn automatically.

Ship-with-the-theme note: this skill lives inside the Dawn theme
(`themes/dawn/skills/create-child-theme/`) so it travels with the theme.
A thin pointer copy under the repo's `.claude/skills/create-child-theme/` makes
it discoverable in Claude Code; both resolve to this file.

## How OctoberCMS child themes actually work (verified against October 4.x)

These facts were verified against the running install's core
(`src/app/modules/cms/classes/Theme.php` and `ThemeManager.php`) and the
official docs. Do not deviate from them.

- **Parent is declared in `theme.yaml`** with a top-level `parent:` key whose
  value is the **parent theme's directory name**, NOT the `code:`
  (`Renick.Dawn`) inside its `theme.yaml`. Core resolves it via
  `Theme::load((string) $this->getConfigValue('parent'))`, which loads by
  directory name. Getting this wrong means no fallback.
- **Dawn's directory name is install-dependent.** It is `dawn` in this repo (and
  from a manual unzip), but `renick-dawn` on a Marketplace / `theme:install` /
  Composer install — Composer's `october-theme` installer always writes
  `themes/{vendor}-{name}/`, and a package's `extra.installer-name` only replaces
  `{name}`, so the vendor prefix stays. **Resolve it before writing `parent:`**
  (`php artisan theme:list`, or `ls themes/`) instead of assuming `dawn`. Below,
  `<dawn>` means that resolved name.
- **`php artisan theme:copy <dawn> <child> --child`** is the built-in way
  to create a child. It copies Dawn's `theme.yaml` into `themes/<child>/` and
  writes `parent: <dawn>` into it. That is *all* the CLI does — the child
  starts as an empty directory plus a parented `theme.yaml`. This skill prefers
  to scaffold the child by hand (below) so it lands with a sensible `child.css`,
  a curated page set, and correct `theme.yaml` metadata, but `theme:copy` is a
  valid shortcut if you then edit the result.
- **File resolution / fallback order** (from `registerHalcyonDatasource`):
  database layer (if enabled) → **child theme files** → **parent theme files**.
  Any `pages/`, `partials/`, `layouts/` or `content/` file present in the child
  wins; anything absent is served transparently from Dawn. A URL that only
  exists as a page in Dawn is live in the child too.
- **Assets fall back per file** (`useParentAsset`): if the child does not have
  `assets/<path>`, the parent's asset is used. So a child that only ships
  `assets/css/child.css` still gets Dawn's `theme.js`, images, favicon, fonts.
- **Blueprints are shared, not re-declared.** Dawn runs on Tailor blueprints:
  `Dawn\Page` (the content pages + nav tree), `Dawn\BlogPost`, `Dawn\BlogCategory`,
  `Dawn\BlogTag`, `Dawn\JobsPosition` (the jobs/careers entries behind `job.htm` +
  the `jobs-listing` block), the `Dawn\Theme` settings global and the `Dawn\Menu`
  menu global — NOT RainLab.Blog or RainLab.Pages. The child MUST NOT ship its own copies
  — duplicate handles/UUIDs break `tailor:migrate`. The data model exists
  install-wide.
- **The header nav has two modes** (per-site `Dawn\Menu` global): `normal`
  builds it from the Dawn\Page tree + a fixed Blog link; `mega` renders the
  composed menu_items (page-finder links, disclosure buttons, full-width
  panels — see partials/site/header.htm + partials/site/menu-items.php +
  the .dawn-megapanel styles). A child inherits all of it; its site simply
  composes its own Dawn\Menu record (or stays on `normal`).
- **Content pages are DATA, not theme files.** `pages/` holds only the route
  shells: `index.htm` (site root), `page.htm` (catch-all `/:path*` resolving a
  `Dawn\Page` entry by fullslug), `blog.htm`, `blog-post.htm`, `job.htm`
  (resolves a `Dawn\JobsPosition` entry), `404.htm`. The
  actual pages (About, Pricing, Contact, Imprint, …) are Tailor `Dawn\Page`
  entries per site (`multisite: all`), and the nav/footer are built from that
  tree in `layouts/default.htm` (`hide_from_nav`, `show_in_footer` flags).
  Consequence (verified): a fresh site pointed at the child 404s on `/about`
  etc. until its entries exist — theme-file fallback does NOT cover content.
  Backfill a new site with `php artisan tailor:propagate --site=<source-id>`
  (source = an existing Dawn site id).
- **Backend block labels come from theme lang JSONs.** Dawn's content-blocks
  reference dotted keys (`dawn.block.<handle>.*`) resolved against
  `lang/{en,de,es}.json`. Verified in core: `ThemeManager::bootAll()` boots the
  active theme AND its parent, so a child inherits Dawn's JSON translations
  automatically; a child adding its own blocks ships its own `lang/*.json`
  with its own dotted keys (both paths are registered).
- **Activation is per site.** A theme is bound to a site via the site's `theme`
  column (the theme directory name). This install serves Dawn per-site under
  route prefixes (`/en`, `/de`) while a different theme is primary. To see a
  child theme you point a site's `theme` at the child's directory name. There
  is also `php artisan theme:use <dir>` (sets the active/primary theme) and
  Settings → Frontend Theme in the backend, but on THIS multisite install you
  must use the per-site approach and never change the primary site.

Sources: OctoberCMS Child Themes doc
(https://docs.octobercms.com/3.x/cms/themes/child-themes.html), Theme Settings
(https://docs.octobercms.com/3.x/cms/themes/settings.html), Seeding Themes
(https://docs.octobercms.com/3.x/cms/themes/seeding-themes.html), and the local
4.x core (`Theme::getParentTheme`, `ThemeManager::createChildTheme`,
`registerHalcyonDatasource`, `useParentAsset`).

## What Dawn exposes to override (the theme's skin surface)

Read these before generating so the overrides actually land:

- **Colour + radius live as CSS custom properties** in
  `themes/dawn/assets/css/theme.css` `:root`:
  - Brand palette: `--dawn-bg`, `--dawn-ink`, `--dawn-muted`, `--dawn-line`,
    `--dawn-brand`, `--dawn-brand-2`, `--dawn-accent`, plus the gradients
    `--dawn-grad` and `--dawn-grad-soft`.
  - Bootstrap re-points so plugin blocks follow the theme: `--bs-primary`,
    `--bs-primary-rgb`, `--bs-link-color`, `--bs-link-color-rgb`,
    `--bs-link-hover-color`, `--bs-primary-bg-subtle`,
    `--bs-primary-border-subtle`, `--bs-primary-text-emphasis`.
  - Corner radius: `--dawn-radius` (16px). Many elements derive from it
    (`var(--dawn-radius)`, `calc(var(--dawn-radius) + 6px)`), but several use
    literal radii (8/10/12/14px, `999px` pills, `50%` circles).
- **The child overrides by loading its own stylesheet AFTER Dawn's.** The
  child's `layouts/default.htm` combines the same three Dawn stylesheets and
  then appends `~/themes/<child>/assets/css/child.css` last, so its `:root`
  redefinitions win the cascade. Redefining the `--dawn-*` and `--bs-*`
  variables re-skins most of the site for free.
- **Some values are hardcoded hex, not variables**, e.g. button hovers
  (`#a83a0a`, `#992f06`), decorative blobs (`#d97706`, `#b45309`), section /
  panel tints (`#fdf7f1`, `#fbf1e9`, `#f2e3d6`, `#f0ddcd`), the striped-table
  background (`#fdf7f1`), the image-bubbles circle backdrop
  (`#faf6f0`/`#f4f5f8`), form success colour, footer darks, white card/panel
  surfaces, the `<meta name="theme-color">` in the layout, the
  `.dawn-brand-dot`/gradient text. Variable overrides cover most of the skin;
  a radically different palette needs explicit selector overrides in
  `child.css`. The template starts with the common skin-affecting selectors,
  and you can add more after inspecting `assets/css/theme.css` for hardcoded
  hex values.
- **Structure bricks** that a child may override as whole files: the route
  shells `pages/{index,page,blog,blog-post,job,404}.htm`,
  `partials/site/header.htm` (nav shell — page links come from the Dawn\Page
  tree or the Dawn\Menu global in mega mode; only the Blog link is hardcoded
  in normal mode), `partials/site/footer.htm`,
  `partials/site/page-render.htm`, `partials/site/menu-items.php` (mega-menu
  resolver helper), `partials/site/alternates.php` (hreflang / language-switcher
  helper), `partials/site/chrome.php` (cached nav/footer/settings/mega bundle),
  `partials/site/job-seo.php` (JobPosting SEO for `job.htm`),
  `partials/seo/head.htm` (+ `partials/site/page-seo.php`),
  `layouts/default.htm`, `layouts/static.htm`. The layouts `require_once` the
  `alternates.php` + `chrome.php` helpers, so a child that overrides the layouts
  keeps referencing Dawn's copies (parent path) unless it ships its own.
- **Theme blocks** (`content-blocks/`: hero, hero-numbers, features,
  pricing-table, blog-slider, data-table, image-bubbles, jobs-listing) are
  inherited per directory and can be overridden or extended. New blocks in a child follow
  the same localization rule as Dawn's (dotted lang keys, see AGENTS.md
  "Dawn theme blocks").

## Step 1 — Ask the user what they want

Use AskUserQuestion (one call, multiple questions) to gather:

1. **Theme name & slug.** Ask for a human name (e.g. "Ember", "Acme Coast").
   Derive the directory slug yourself and confirm it: **kebab-case, lowercase,
   no underscores, no capitals**. Recommend a `<client>-<name>` prefix so
   installs stay tidy (e.g. `acme-ember`). The slug is the directory name AND
   the value sites will point their `theme` column at.
2. **Colour scheme.** Either a primary/brand colour + accent, or a mood
   ("cool teal", "midnight", "forest") you translate into a small palette
   (bg, ink, muted, line, brand, brand-2, accent + a matching gradient). Always
   check brand-on-white contrast (aim WCAG AA for text; large brand fills can
   be looser).
3. **Corners: round or square.** Round keeps Dawn's feel (`--dawn-radius:16px`).
   Square/sharp sets `--dawn-radius: 0` plus overrides for the literal-radius
   selectors and pills (offer to include those; without them a few chips stay
   rounded).
4. **Page set.** Which pages exist: Home, Pricing, About, Blog, Contact, legal
   pages. Content pages are per-site Tailor `Dawn\Page` entries, NOT theme
   files — the child's site starts empty and gets its pages via
   `tailor:propagate --site=<dawn site id>` (copies Dawn's set) or by authoring
   entries for that site in the backend. "Removing" a page means unpublishing /
   deleting its entry on the child's site (or setting `hide_from_nav`); the nav
   updates itself because it is built from the page tree. Ask which pages to
   keep so you know what to prune after propagation.
5. **Blog: include or not.** If yes, keep Dawn's inherited `blog.htm` +
   `blog-post.htm` route shells and the hardcoded Blog link in the header
   (posts arrive via propagation like pages). If no, override
   `partials/site/header.htm` to drop the Blog `<li>` and ship child
   `pages/blog.htm` + `pages/blog-post.htm` whose `onStart()` returns
   `$this->controller->run('404')`. Never touch the blueprints either way.
6. **Font (optional).** Keep Dawn's system font stack, or a self-hosted /
   system alternative. If a web font is wanted, note it must be self-hosted in
   `assets/` (or a `@font-face`); do not add external CDN links (CSP/offline).

If the user has already answered some of these in their prompt, skip those
questions — only ask what's missing.

## Step 2 — Scaffold the child theme

Create `themes/<slug>/` with, at minimum:

1. **`theme.yaml`** — from `templates/theme.yaml.tpl`. MUST contain
   `parent: <dawn>` (Dawn's resolved directory name — see the note above; do not
   assume `dawn`) and a new `code:` — the slug itself — plus
   name, description, author, homepage and the `require:` dependencies
   (`Renick.Content`, `RainLab.Translate`). Copy the `form:` block for the
   brand-name and view-transitions customize fields — form fields are NOT
   inherited; their `dawn.*`
   label keys resolve via the parent's booted lang path.
2. **`version.yaml`** — `1.0.0: First version of <Name>.`
3. **`assets/css/child.css`** — from `templates/child.css.tpl`. Fill in the
   chosen palette in `:root`, set `--dawn-radius`, and (if square corners or a
   far-from-autumn palette) uncomment the hardcoded-selector override block.
   This is the primary deliverable — most themes need ONLY this file plus
   `theme.yaml` and the layout.
4. **`layouts/default.htm`** — from `templates/default.htm.tpl`. This is a copy
   of Dawn's layout with ONE change: the `dawnCss` combine list appends
   `~/themes/<slug>/assets/css/child.css` as the LAST entry so it overrides
   Dawn. Also update the `<meta name="theme-color">` to the new brand colour.
   (You must ship this layout because the combine list references the theme dir
   by name — it can't be inherited unchanged.)
5. **Only if the blog or nav shell changes:** a child copy of
   `partials/site/header.htm` (and 404-ing `pages/blog*.htm` for blog-off, see
   below). The content page set is per-site DATA (see Step 1 #4), so it never
   requires page-file overrides. Everything else falls back to Dawn.

Fill every `{{SLUG}}`, `{{NAME}}`, `{{DESCRIPTION}}`, `{{AUTHOR}}`,
`{{HOMEPAGE}}`, `{{THEME_COLOR}}`, `{{DAWN_DIR}}`, palette and radius
placeholder in the templates. Do not leave a template token unresolved.
`{{DAWN_DIR}}` is Dawn's resolved directory name (`dawn` here, `renick-dawn` on
a Marketplace install) — read it from `php artisan theme:list`, never assume it.

### Blog on/off, concretely

- **Blog ON (default):** ship nothing blog-related. Dawn's `blog.htm`,
  `blog-post.htm` and the header Blog link are inherited. The Tailor
  blueprints exist install-wide; the child site's posts/categories/tags
  arrive via `tailor:propagate --site=<dawn site id>` (or its own authoring).
- **Blog OFF:** (a) ship `partials/site/header.htm` (copy Dawn's, delete the
  `<li>` with the Blog nav link), and (b) ship `pages/blog.htm` and
  `pages/blog-post.htm` in the child whose `onStart()` calls
  `return $this->controller->run('404');` so the inherited Dawn blog URLs stop
  resolving. Do NOT delete or duplicate the blueprints.

### This install symlinks themes per-directory (important)

On this dev install, `src/app/themes/` does NOT contain the theme folders — it
holds one **symlink per theme** into the repo (`dawn ->
../../../themes/dawn`, `content-test -> ...`). A new child created under
the repo `themes/<slug>/` is therefore invisible to the running app until you
add a matching symlink. Core `Theme::all()` / `theme:list` will not show it
otherwise (verified: without the link `demo-ember` was absent; after linking it
appeared with `parent=dawn`). Before any live check:

```
cd src/app/themes && ln -s ../../../themes/<slug> <slug>
cd .. && php artisan theme:clear
```

Remove this symlink again during cleanup (Step 4). On a normal install where
`themes/` holds real directories, skip this — the child is picked up directly.

## Step 3 — Verify (multisite-safe)

Respect the multisite rules: the **content-test / primary site is never
retargeted**. Verify the child with a throwaway prefixed site or file-only
checks.

1. **Static sanity (no server needed).**
   - `themes/<slug>/theme.yaml` contains `parent: <dawn>` matching the real
     parent directory (`ls themes/`), and `themes/<dawn>/` exists.
   - `php artisan theme:list` from `src/app` lists the child.
   - `child.css` has no unresolved `{{...}}` tokens; `default.htm` appends the
     child stylesheet last.
2. **Live check via a disposable prefixed site** (preferred). Create a new
   enabled, prefixed site pointing `theme` at `<slug>` (e.g. prefix `/preview`),
   leaving all existing sites untouched:
   ```
   cd src/app && php artisan tinker --execute="
   \$s = \System\Models\SiteDefinition::where('code','child_preview')->first()
        ?: new \System\Models\SiteDefinition;
   \$s->code = 'child_preview'; \$s->name = 'Child preview';
   \$s->theme = '<slug>'; \$s->locale = 'en';
   \$s->is_primary = false; \$s->is_enabled = true; \$s->is_enabled_edit = true;
   \$s->is_prefixed = true; \$s->route_prefix = '/preview'; \$s->sort_order = 99;
   \$s->save(); \Site::resetCache(); echo 'preview site id='.\$s->id;
   "
   ```
   (Set `code` by direct assignment, NOT via `firstOrNew(['code'=>...])` — it is
   a guarded attribute and mass-assigning it throws MassAssignmentException.)
   Then **backfill the preview site's content pages** — a fresh site has no
   `Dawn\Page`/blog entries and 404s on `/preview/about` etc. (verified):
   ```
   php artisan tailor:propagate --site=<dawn_en site id>
   ```
   Now load `http://localhost:8888/preview/` (and `/preview/about`, etc.)
   headless and confirm: the pages render, the new brand colour is applied
   (inspect a CTA / link / `--bs-primary`), and corners match the chosen style.
3. **Parent-fallback proof.** Confirm a THEME FILE you did NOT copy into the
   child serves from Dawn — e.g. `/preview/blog` renders via Dawn's `blog.htm`,
   the header/footer partials appear, and Dawn's `theme.js`/images load. (Note
   content pages do NOT prove fallback — they are per-site data, hence the
   propagation step above.)

## Step 4 — Clean up the verification artifacts

If the child theme was only a demo/test, remove the propagated entries, the
preview site, the theme and (on this install) the symlink. Delete the entries
FIRST and with `withoutGlobalScopes()` — `inSection()` queries are scoped to
the ACTIVE site, so a plain `where('site_id', …)` silently matches nothing
(verified) and deleting the site would orphan its rows:
```
cd src/app && php artisan tinker --execute="
\$s=\System\Models\SiteDefinition::where('code','child_preview')->first();
if(\$s){
  foreach (['Dawn\\\\Page','Dawn\\\\BlogPost','Dawn\\\\BlogCategory','Dawn\\\\BlogTag','Dawn\\\\JobsPosition'] as \$b) {
    \Tailor\Models\EntryRecord::inSection(\$b)->withoutGlobalScopes()
      ->where('site_id', \$s->id)->get()->each->forceDelete();
  }
  \$s->delete();
}
\Site::resetCache(); echo 'preview site + records removed';
"
rm -f src/app/themes/<slug>              # the symlink (this install only)
php artisan theme:clear
rm -rf themes/<slug>                      # the real theme dir in the repo
```
Note: to delete the child by its site model, set the fields by direct property
assignment (`$s->code = ...`) — `code` is guarded and MassAssignmentException
fires if you pass it to `firstOrNew([...])`/`create([...])`.
If the child is a real deliverable, keep it but leave activation to the user
(tell them which site to point at `<slug>`, and to NOT set it primary on this
install). Never commit; the parent decides what gets committed.

## Guardrails

- `parent:` value is Dawn's **directory** name, never `Renick.Dawn` — and never
  assumed: it is `dawn` here but `renick-dawn` on a Marketplace install.
- Never hardcode `themes/dawn/` in a child's layouts or pages. The layout
  template bootstraps Dawn's `partials/site/paths.php` by walking the parent
  chain, then uses `dawn_path()` / `dawn_asset()`; keep that intact.
- Slug is kebab-case, lowercase, no underscores. Confirm before creating.
- Never duplicate Dawn's Tailor blueprints into the child.
- Never retarget the primary/content-test site; use a disposable prefixed site.
- Content pages are per-site data — after creating a site for the child, run
  `tailor:propagate --site=<dawn site id>` or `/about` & co. will 404.
- Cleanup deletes the preview site's entries via `withoutGlobalScopes()`
  BEFORE the site itself (site-scoped queries otherwise match nothing).
- Never add external font/CDN links; self-host assets.
- Load `child.css` LAST in the combine list or overrides won't win.
- Run the e2e browser headless.
