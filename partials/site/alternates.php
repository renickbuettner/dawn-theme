<?php
/**
 * hreflang / language-switcher alternates. Dawn\Page routes emit the sibling's
 * real fullslug (matched on site_root_id), because re-prefixing the current path
 * would 404 on a localized slug; other routes fall back to that re-prefixing.
 */

if (!function_exists('dawn_alternates')) {
    /**
     * @return list<array{label:string,locale:string,hreflang:string,url:string,active:bool}>
     */
    function dawn_alternates(): array
    {
        // Theme must degrade gracefully if the plugin is somehow absent.
        if (!class_exists(\Renick\Content\Classes\CacheManager::class)) {
            return dawn_alternates_build();
        }

        // Both sites matter: the context one picks the DB rows, the active one the
        // prefix stripping and the `active` flag (they differ on an editor preview).
        $context = \Site::getSiteFromContext();
        $active = \Site::getActiveSite();
        $variant = ($context->id ?? '-') . ':' . ($active->id ?? '-')
            . ':' . \Renick\Content\Classes\BlockRenderer::requestOrigin()
            . ':' . dawn_alternates_sites_hash()
            . ':/' . ltrim(\Request::path(), '/');

        return \Renick\Content\Classes\CacheManager::remember(
            \Renick\Content\Classes\CacheManager::NS_CHROME,
            'dawn:alternates:' . hash('xxh128', $variant),
            null,
            fn () => dawn_alternates_build()
        );
    }

    /**
     * NS_CHROME is bumped on Dawn\Page saves but not on site definition changes,
     * so a locale/prefix/theme edit has to bust the key by itself.
     */
    function dawn_alternates_sites_hash(): string
    {
        $parts = [];
        foreach (\Site::listEnabled() as $site) {
            $parts[] = implode('|', [
                $site->id,
                $site->locale,
                $site->is_prefixed ? $site->route_prefix : '',
                $site->theme,
            ]);
        }

        return hash('xxh128', implode(';', $parts));
    }

    /**
     * @return list<array{label:string,locale:string,hreflang:string,url:string,active:bool}>
     */
    function dawn_alternates_build(): array
    {
        $sites = \Site::listEnabled();
        if ($sites->count() < 1) {
            return [];
        }

        $active = \Site::getActiveSite();
        $activeId = $active ? $active->id : null;

        // Strip the active site's prefix so the fallback below can re-prefix it.
        $path = '/' . ltrim(\Request::path(), '/');
        if ($active && $active->is_prefixed && $active->route_prefix) {
            $activePrefix = '/' . trim($active->route_prefix, '/');
            if ($path === $activePrefix || str_starts_with($path, $activePrefix . '/')) {
                $path = substr($path, strlen($activePrefix)) ?: '/';
            }
        }

        // Shared translation root, for looking up each locale's real slug.
        // Stays null on non-Page routes → the fallback re-prefix is used.
        $rootId = null;
        if (class_exists(\Tailor\Models\EntryRecord::class)) {
            $slug = trim($path, '/') ?: 'home';
            try {
                $entry = \Tailor\Models\EntryRecord::inSection('Dawn\\Page')
                    ->where('fullslug', $slug)
                    ->first();
                if ($entry) {
                    $rootId = $entry->site_root_id ?: $entry->id;
                }
            } catch (\Throwable $e) {
                $rootId = null;
            }
        }

        $theme = ($active && $active->theme) ? $active->theme : 'dawn';
        $labels = ['en' => 'EN', 'de' => 'DE', 'es' => 'ES'];
        $order = ['en' => 0, 'de' => 1, 'es' => 2];
        $byLocale = [];

        foreach ($sites as $site) {
            if ($site->theme !== $theme) {
                continue;
            }
            $locale = $site->locale ?: 'en';
            if (isset($byLocale[$locale]) && $site->id !== $activeId) {
                continue;
            }
            $prefix = ($site->is_prefixed && $site->route_prefix) ? rtrim($site->route_prefix, '/') : '';

            if ($rootId !== null) {
                // Skip a locale with no published sibling rather than 404.
                $siblingSlug = \Site::withContext($site->id, function () use ($rootId) {
                    try {
                        $sibling = \Tailor\Models\EntryRecord::inSection('Dawn\\Page')
                            ->applyPublishedStatus()
                            ->where('site_root_id', $rootId)
                            ->first();

                        return $sibling ? (string) $sibling->fullslug : null;
                    } catch (\Throwable $e) {
                        return null;
                    }
                });
                if ($siblingSlug === null || $siblingSlug === '') {
                    continue;
                }
                // `home` is served at the site root, not at /home.
                $rel = $prefix . ($siblingSlug === 'home' ? '/' : '/' . ltrim($siblingSlug, '/'));
            } else {
                $rel = $prefix . ($path === '/' ? '/' : $path);
            }

            $byLocale[$locale] = [
                'label' => $labels[$locale] ?? strtoupper($locale),
                'locale' => $locale,
                'hreflang' => $locale,
                'url' => url($rel),
                'active' => $site->id === $activeId,
            ];
        }

        uksort($byLocale, fn ($a, $b) => ($order[$a] ?? 9) <=> ($order[$b] ?? 9));

        return array_values($byLocale);
    }
}
