# create-child-theme skill

Ships inside Renick Dawn so it distributes with the theme. Generates an
OctoberCMS child theme (`parent: dawn`) with a custom colour scheme,
corner style, curated page set and optional blog.

- `SKILL.md` — the skill (verified October 4.x child-theme mechanics + steps).
- `templates/theme.yaml.tpl` — child `theme.yaml` with the `parent:` key.
- `templates/child.css.tpl` — `:root` palette + `--dawn-radius` override layer.
- `templates/default.htm.tpl` — layout that appends `child.css` LAST so the
  overrides win the cascade.

A thin pointer copy lives at `.claude/skills/create-child-theme/SKILL.md` in the
repo root so Claude Code can discover it during development; it just redirects
here. If you distribute the theme, this directory is the source of truth.
