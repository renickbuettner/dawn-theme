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
