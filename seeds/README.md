# Renick Dawn — Theme Seeds

October-native seeding (October ≥ 4.3). One command installs everything the
demo needs — the EN/DE sites, blueprints, media, blog content and the editable
pages:

```bash
php artisan theme:seed <theme-folder>
```

`theme:seed` takes the theme's **directory name under `themes/`**, not its
`Renick.Dawn` code — `renick-dawn` on a Marketplace/Composer install (Composer's
`october-theme` installer always writes `themes/{vendor}-{name}/`), `dawn` from a
manual unzip or this repo. `php artisan theme:list` prints the real name; the
wrong one fails with *"The theme … does not exist."*

Also available from the backend: *Settings → Frontend Theme → Manage → Seed
Content*. Seeding is idempotent — `update_existing: true` makes re-runs update
in place instead of duplicating.

## Layout

| Path | Purpose |
|---|---|
| `blueprints/*.yaml` | Tailor blueprints (`Dawn\BlogTag`, `Dawn\BlogCategory`, `Dawn\BlogPost`, `Dawn\Page`, `Dawn\Theme` global). Copied **verbatim** to `app/blueprints/<theme-folder>/` by `theme:seed`, then migrated. **This is the single source of truth** — edit here, then re-run `theme:seed`. Because the copy is verbatim, blueprints must never reference a `themes/<folder>/…` path (e.g. `iconSvg`): the folder name is install-dependent. |
| `data.yaml` | The seed script: sites first, then media, then tags → categories → posts → pages, each EN before DE. |
| `data/sites.json` | The `dawn_en` (root) + `dawn_de` (`/de`) site definitions every later step targets, imported by `Renick\Content\Models\SiteDefinitionImport`. |
| `data/messages.json` | The locales whose `translate/*.yaml` catalog is imported into the RainLab.Translate message store by `Renick\Content\Models\ThemeMessageImport`. Only locale names — the strings stay in `translate/`, their single source of truth. |
| `data/*.json` | Entry rows per section and site. DE rows carry explicit `id` + `site_root_id` pointing at their EN counterpart, so the backend language switcher links translations. |
| `media/dawn/` | Demo images + PDF, imported into the media library (`dawn/…`) via `Media\Models\MediaLibraryItemImport`. |

## Multisite

Stock `Tailor\Models\RecordImport` imports into the *current* site context
only. The entry steps therefore use the plugin's generic
`Renick\Content\Models\SiteAwareRecordImport`, whose `site` attribute
(code or id) pins each step to its target site via `Site::withContext()`.

Those steps **skip** every row when the site they name is missing, rather than
mis-placing content — so `data.yaml` opens with a `SiteDefinitionImport` step
that creates `dawn_en` and `dawn_de` from `data/sites.json`. Without it a stock
install (one `primary` site) would report a clean run that imported nothing.

The import is deliberately conservative on an install that is already set up:

- A site whose `code` already exists keeps its name, locale and routing; only
  its theme binding is refreshed. Re-seeding never undoes your edits.
- `dawn_en` wants to be the unprefixed primary, so it **adopts** the install's
  existing root site (a stock install's `primary`) instead of adding a second
  competing root. On an install that already runs several sites, no adoption
  happens and `dawn_en` is created alongside them.
- The theme binding is derived from the directory being seeded, not from the
  JSON, so it is correct for both `dawn` and `renick-dawn`. It is *derived*
  rather than copied because a Marketplace install cannot run the seeded
  directory: `theme:check` locks a Composer theme and generates
  `<folder>-child`, and a site bound to a locked theme fails every request.
  `SiteDefinitionImport` therefore binds to the editable theme in that lineage —
  preferring one you selected yourself with `theme:use`.

## Translations

Frontend `{{ 'key'|_ }}` strings resolve through RainLab.Translate's message
store and nothing else — a missing import is visible to visitors as a raw
`dawn.front.nav.home`. `php artisan translate:scan` does not fill it after a CLI
install: it reads the *active* theme, which on the command line is the
`ACTIVE_THEME` env value (`demo` on a stock install — `theme:use` writes the site
row, not `.env`). The `ThemeMessageImport` step reads the seeded directory
instead, so the strings arrive with the rest of the seed.

The blueprints are intentionally **not** duplicated in a theme-level
`blueprints/` directory: Tailor would index active-theme `blueprints/*.yaml`
a second time next to the `app/blueprints/` copies and fail
`tailor:migrate` with duplicate-UUID errors.
