<?php

/**
 * Resolves the Dawn\Menu global into nav arrays for site/header.htm. Returns []
 * unless menu_mode=mega, leaving the header on its Dawn\Page-tree nav. Links are
 * october:// addresses, resolved site-aware by PageManager.
 */
function dawn_mega_items(): array
{
    try {
        $menu = \Tailor\Models\GlobalRecord::inGlobal('Dawn\\Menu')->first();
        if (!$menu || $menu->menu_mode !== 'mega') {
            return [];
        }

        // No `active` flag: this is cached per site/locale, so the header
        // template derives the current-page highlight from `this.url` instead.
        $resolve = function ($item) {
            $link = null;
            if ($item->link ?? null) {
                $link = \Cms\Classes\PageManager::resolve($item->link);
            }
            $url = $link->url ?? '';
            $label = trim((string) ($item->label ?? '')) ?: ($link->title ?? '');
            if ($url === '' || $label === '') {
                return null; // unresolved target (e.g. unpublished page) or nothing to show
            }
            return [
                'label' => $label,
                'url' => $url,
                'description' => trim((string) ($item->description ?? '')),
                'icon' => trim((string) ($item->icon ?? '')),
            ];
        };

        $items = [];
        foreach ($menu->menu_items as $item) {
            if ($item->content_group === 'category') {
                $label = trim((string) ($item->label ?? ''));
                $children = [];
                foreach ($item->children ?? [] as $child) {
                    if ($resolved = $resolve($child)) {
                        $children[] = $resolved;
                    }
                }
                if ($label === '' || !$children) {
                    continue; // a category is only its label + panel
                }

                // Optional hero card; text is trusted backend richeditor HTML.
                $content = null;
                $buttonLink = ($item->content_button_link ?? null)
                    ? \Cms\Classes\PageManager::resolve($item->content_button_link)
                    : null;
                $hasContent = ($item->content_image ?? '') || trim((string) ($item->content_text ?? '')) !== '';
                if ($hasContent) {
                    $content = [
                        'image' => (string) ($item->content_image ?? ''),
                        'html' => (string) ($item->content_text ?? ''),
                        'button_label' => trim((string) ($item->content_button_label ?? '')),
                        'button_url' => $buttonLink->url ?? '',
                    ];
                }

                $items[] = [
                    'label' => $label,
                    'url' => '',
                    'icon' => trim((string) ($item->icon ?? '')),
                    'children' => $children,
                    'content' => $content,
                    'side' => ($item->content_side ?? 'right') === 'left' ? 'left' : 'right',
                ];
                continue;
            }

            // `page` group (and legacy ungrouped rows): a plain link.
            if ($top = $resolve($item)) {
                $top['children'] = [];
                $top['content'] = null;
                $top['side'] = 'right';
                $items[] = $top;
            }
        }

        return $items;
    }
    catch (\Throwable $e) {
        return [];
    }
}
