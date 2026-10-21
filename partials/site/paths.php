<?php
/**
 * Never hardcode Dawn's folder: it is `renick-dawn` from the Marketplace
 * (composer/installers writes themes/{vendor}-{name}/) and the parent's on a
 * child theme. The layout walks the chain and calls dawn_paths_use().
 */

if (!function_exists('dawn_paths_use')) {
    /**
     * Takes the root of the RENDERING controller's chain — not
     * Theme::getActiveTheme(), which differs on Tailor preview renders.
     */
    function dawn_paths_use(\Cms\Classes\Theme $theme): void
    {
        dawn_dir($theme->getDirName());
    }
}

if (!function_exists('dawn_dir')) {
    /**
     * Dawn's directory name. Falls back to walking the active theme so a page
     * rendered without Dawn's layout resolves instead of fataling.
     */
    function dawn_dir(?string $setTo = null): string
    {
        static $dirName = null;

        if ($setTo !== null) {
            return $dirName = $setTo;
        }

        if ($dirName !== null) {
            return $dirName;
        }

        $theme = \Cms\Classes\Theme::getActiveTheme();
        while ($theme && ($parent = $theme->getParentTheme())) {
            $theme = $parent;
        }

        return $dirName = $theme ? $theme->getDirName() : 'dawn';
    }
}

if (!function_exists('dawn_path')) {
    /**
     * Absolute path inside Dawn, for require_once of the sibling helpers.
     */
    function dawn_path(string $relative = ''): string
    {
        $base = themes_path(dawn_dir());

        return $relative === '' ? $base : $base . '/' . ltrim($relative, '/');
    }
}

if (!function_exists('dawn_asset')) {
    /**
     * CombineAssets `~/` alias for a file under Dawn's assets/.
     */
    function dawn_asset(string $relative): string
    {
        return '~/themes/' . dawn_dir() . '/assets/' . ltrim($relative, '/');
    }
}

if (!function_exists('dawn_locale')) {
    /**
     * Active site locale for SEO/JSON-LD. `$this->site` is unset in onStart,
     * so the page code cannot read it from the page object.
     */
    function dawn_locale(): string
    {
        $locale = \Site::getActiveSite()?->locale ?: \App::getLocale();

        return is_string($locale) && $locale !== '' ? $locale : 'en';
    }
}
