# Renick Dawn — Theme Seeds

October-native seeding (October ≥ 4.3). One command installs everything the
demo needs — blueprints, media, blog content and the editable pages, per site
(EN + DE):

```bash
php artisan theme:seed <theme-folder>
# frontend UI strings (RainLab.Translate message store):
php artisan translate:scan
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
| `data.yaml` | The seed script: media first, then tags → categories → posts → pages, each EN before DE. |
| `data/*.json` | Entry rows per section and site. DE rows carry explicit `id` + `site_root_id` pointing at their EN counterpart, so the backend language switcher links translations. |
| `media/dawn/` | Demo images + PDF, imported into the media library (`dawn/…`) via `Media\Models\MediaLibraryItemImport`. |

## Multisite

Stock `Tailor\Models\RecordImport` imports into the *current* site context
only. The entry steps therefore use the plugin's generic
`Renick\Content\Models\SiteAwareRecordImport`, whose `site` attribute
(code or id) pins each step to its target site via `Site::withContext()`.

The blueprints are intentionally **not** duplicated in a theme-level
`blueprints/` directory: Tailor would index active-theme `blueprints/*.yaml`
a second time next to the `app/blueprints/` copies and fail
`tailor:migrate` with duplicate-UUID errors.
