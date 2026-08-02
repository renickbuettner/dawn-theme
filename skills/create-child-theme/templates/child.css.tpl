/* {{NAME}} — child of Renick Dawn.
   Loaded LAST (after Dawn's theme.css) by this theme's layouts/default.htm, so
   these :root redefinitions win the cascade and re-skin the whole site. */

:root {
    /* ── Brand palette (fill from the chosen scheme) ─────────────── */
    --dawn-bg: {{BG}};          /* page background        (Dawn: #ffffff) */
    --dawn-ink: {{INK}};        /* primary text           (Dawn: #14151a) */
    --dawn-muted: {{MUTED}};    /* secondary text         (Dawn: #5c6270) */
    --dawn-line: {{LINE}};      /* borders / hairlines    (Dawn: #e7e9ef) */
    --dawn-brand: {{BRAND}};    /* primary brand          (Dawn: #c2410c) */
    --dawn-brand-2: {{BRAND2}}; /* darker brand (hover)   (Dawn: #9a3412) */
    --dawn-accent: {{ACCENT}};  /* accent  (Dawn --dawn-accent: #d97706) */
    /* Dawn's live gradient ends on a slightly lighter #e08c1e, not the accent;
       a child simply ends on its own {{ACCENT}} — visually equivalent. */
    --dawn-grad: linear-gradient(120deg, {{BRAND2}} 0%, {{BRAND}} 45%, {{ACCENT}} 100%);
    --dawn-grad-soft: linear-gradient(120deg, {{GRAD_SOFT}});

    /* Corner radius: keep round like Dawn (16px) or set 0 for square. */
    --dawn-radius: {{RADIUS}};

    /* Re-point Bootstrap so plugin blocks (bg-primary CTAs, .rc-card-icon,
       text-primary, open accordion) follow the new brand. */
    --bs-primary: {{BRAND}};
    --bs-primary-rgb: {{BRAND_RGB}};
    --bs-link-color: var(--dawn-brand);
    --bs-link-color-rgb: {{BRAND_RGB}};
    --bs-link-hover-color: var(--dawn-brand-2);
    --bs-primary-bg-subtle: {{PRIMARY_BG_SUBTLE}};
    --bs-primary-border-subtle: {{PRIMARY_BORDER_SUBTLE}};
    --bs-primary-text-emphasis: {{BRAND2}}; /* Dawn: #7c2d12 (a touch darker) */
}

/* ── OPTIONAL A: hardcoded-value overrides ───────────────────────────
   Dawn hardcodes a few colours/radii that the variables above do NOT reach.
   Uncomment + tune this block ONLY if you chose a palette far from Dawn's
   autumn (otherwise the defaults still read as autumn accents). Two INDEPENDENT
   comment blocks follow (A colours, B square corners) so you can enable either
   on its own — CSS comments do not nest, so keep each block's own delimiters.

.btn-primary { --bs-btn-hover-bg: {{BRAND2}}; --bs-btn-border-color: {{BRAND}};
    --bs-btn-active-bg: {{BRAND2}}; --bs-btn-hover-border-color: {{BRAND2}}; }

.dawn-blob-1 { background: {{ACCENT}}; }          /* Dawn: #d97706 */
.dawn-blob-2 { background: {{BRAND2}}; }          /* Dawn: #b45309 (a mid tone) */

.dawn-section-tint,
.dawn-megapanel__card,
.dawn-features.rc-bg-light { background: linear-gradient(180deg, {{TINT}}, var(--dawn-bg));
    border-color: var(--dawn-line); }
.dawn-features .rc-card-grid .rc-column-inner { border-color: var(--dawn-line); }

.dawn-table { --bs-table-striped-bg: {{TINT}}; }
.dawn-bubbles__backdrop--circle { background: linear-gradient(180deg, {{TINT}}, var(--dawn-line)); }
.rc-form .rc-form-success { background: var(--dawn-grad-soft); color: {{BRAND2}};
    border-color: {{PRIMARY_BORDER_SUBTLE}}; }
*/

/* ── OPTIONAL B: square corners — neutralise the literal radii + pills ──
.dawn-tile, .dawn-tile-icon, .dawn-langpicker__toggle, .dawn-langpicker__menu,
.dawn-features.rc-bg-light, .dawn-nav .nav-link, .dawn-lang a, .dawn-skip-link {
    border-radius: 0 !important;
}
*/
