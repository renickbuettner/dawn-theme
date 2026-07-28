<?php

/**
 * Cached site chrome per site + locale, invalidated on Dawn\Page / Dawn\Theme /
 * Dawn\Menu saves (CacheManager::bootInvalidation). Plain arrays only — a Tailor
 * model would drag lazy DB relations into the cache.
 */

require_once __DIR__ . '/menu-items.php';

if (!function_exists('dawn_chrome')) {
    function dawn_chrome(): array
    {
        $build = function () {
            return [
                'nav' => dawn_nav_pages(),
                'footer' => dawn_footer_pages(),
                'settings' => dawn_settings(),
                'mega' => dawn_mega_items(),
            ];
        };

        // Theme must degrade gracefully if the plugin is somehow absent.
        if (!class_exists(\Renick\Content\Classes\CacheManager::class)) {
            return $build();
        }

        $site = \Site::getActiveSite();
        $key = 'dawn:chrome:' . ($site->id ?? '-') . ':' . ($site->locale ?? 'en');

        return \Renick\Content\Classes\CacheManager::remember(
            \Renick\Content\Classes\CacheManager::NS_CHROME,
            $key,
            null,
            $build
        );
    }

    /**
     * Top-level published Dawn\Page nav items, in tree order, home excluded.
     *
     * @return list<array{fullslug: string, title: string}>
     */
    function dawn_nav_pages(): array
    {
        try {
            return \Tailor\Models\EntryRecord::inSection('Dawn\\Page')
                ->applyPublishedStatus()
                ->whereNull('parent_id')
                ->where('slug', '<>', 'home')
                ->where(function ($q) {
                    $q->whereNull('hide_from_nav')->orWhere('hide_from_nav', 0);
                })
                ->orderBy('nest_left')
                ->get()
                ->map(fn ($p) => ['fullslug' => (string) $p->fullslug, 'title' => (string) $p->title])
                ->all();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Published Dawn\Page items flagged show_in_footer (imprint, privacy, …).
     *
     * @return list<array{fullslug: string, title: string}>
     */
    function dawn_footer_pages(): array
    {
        try {
            return \Tailor\Models\EntryRecord::inSection('Dawn\\Page')
                ->applyPublishedStatus()
                ->where('show_in_footer', 1)
                ->orderBy('nest_left')
                ->get()
                ->map(fn ($p) => ['fullslug' => (string) $p->fullslug, 'title' => (string) $p->title])
                ->all();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Dawn\Theme global, or null when no record exists. Null booleans are kept
     * so `?? true` in the templates still tells "unset" from an explicit false.
     *
     * @return array<string, mixed>|null
     */
    function dawn_settings(): ?array
    {
        try {
            $s = \Tailor\Models\GlobalRecord::inGlobal('Dawn\\Theme')->first();
        } catch (\Throwable $e) {
            $s = null;
        }
        if (!$s) {
            return null;
        }

        $footerLinks = [];
        foreach ($s->footer_links ?? [] as $link) {
            $label = trim((string) ($link->label ?? ''));
            $url = trim((string) ($link->url ?? ''));
            if ($label !== '' && $url !== '') {
                $footerLinks[] = ['label' => $label, 'url' => $url];
            }
        }

        return [
            'brand_name' => $s->brand_name,
            'brand_logo' => $s->brand_logo,
            'show_header_cta' => $s->show_header_cta,
            'show_language_chooser' => $s->show_language_chooser,
            'header_cta_url' => $s->header_cta_url,
            'header_cta_label' => $s->header_cta_label,
            'footer_links' => $footerLinks,
            // Read by pages/blog.htm, which dedupes its own global read.
            'blog_layout' => $s->blog_layout,
            'blog_per_page' => $s->blog_per_page,
        ];
    }
}
