<?php
/**
 * SEO + schema.org JSON-LD for Dawn\Page entries; sets the seo* vars
 * partials/seo/head.htm consumes. `schema_type` picks the JSON-LD shape.
 */

if (!function_exists('dawn_page_seo')) {
    /**
     * Resolve the theme brand name: Dawn\Theme global → theme customize form → 'Dawn'.
     */
    function dawn_brand_name(): string
    {
        // Reuse the cached chrome bundle instead of re-querying the global.
        require_once __DIR__ . '/chrome.php';
        $globalBrand = dawn_chrome()['settings']['brand_name'] ?? null;
        $themeData = \Cms\Classes\Theme::getActiveTheme()?->getCustomData();

        return trim((string) ($globalBrand ?? ''))
            ?: (trim((string) ($themeData->brand_name ?? '')) ?: 'Dawn');
    }

    /**
     * Turn a mediafinder value (a stored media path) into an absolute URL.
     */
    function dawn_media_url(?string $path): ?string
    {
        $path = trim((string) $path);
        if ($path === '') {
            return null;
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        // Mirrors pages/blog-post.htm rather than pulling in the Media module.
        return url('/storage/app/media/' . ltrim($path, '/'));
    }

    /**
     * Parse an "opening hours" textarea into OpeningHoursSpecification entries.
     * Lines are `<days> <open>-<close>` (Mo-Fr 09:00-18:00); days accept ranges
     * and lists (Mo,We,Fr). Unparseable lines are skipped.
     */
    function dawn_parse_hours(?string $raw): array
    {
        $dayMap = [
            'Mo' => 'Monday', 'Tu' => 'Tuesday', 'We' => 'Wednesday',
            'Th' => 'Thursday', 'Fr' => 'Friday', 'Sa' => 'Saturday', 'Su' => 'Sunday',
        ];
        $order = array_keys($dayMap);

        $specs = [];
        foreach (preg_split('/\r\n|\r|\n/', (string) $raw) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            // Split into the day part and the time part on the last whitespace run.
            if (!preg_match('/^(.+?)\s+(\d{1,2}:\d{2})\s*[-–]\s*(\d{1,2}:\d{2})$/u', $line, $m)) {
                continue;
            }
            [$dayPart, $opens, $closes] = [trim($m[1]), $m[2], $m[3]];

            $days = [];
            foreach (explode(',', $dayPart) as $token) {
                $token = trim($token);
                if (preg_match('/^([A-Za-z]{2})\s*[-–]\s*([A-Za-z]{2})$/u', $token, $r)) {
                    $from = ucfirst(strtolower($r[1]));
                    $to = ucfirst(strtolower($r[2]));
                    $i = array_search($from, $order, true);
                    $j = array_search($to, $order, true);
                    if ($i !== false && $j !== false && $i <= $j) {
                        for ($k = $i; $k <= $j; $k++) {
                            $days[] = $order[$k];
                        }
                    }
                } else {
                    $abbr = ucfirst(strtolower($token));
                    if (isset($dayMap[$abbr])) {
                        $days[] = $abbr;
                    }
                }
            }
            if (!$days) {
                continue;
            }

            $specs[] = [
                '@type' => 'OpeningHoursSpecification',
                'dayOfWeek' => array_values(array_map(fn ($d) => $dayMap[$d], array_unique($days))),
                'opens' => $opens,
                'closes' => $closes,
            ];
        }

        return $specs;
    }

    /**
     * Populate the seo* vars and the JSON-LD string on $page (ArrayAccess).
     */
    function dawn_page_seo($page, $entry, string $locale = 'en'): void
    {
        if (!$entry) {
            return;
        }

        $brand = dawn_brand_name();
        $url = \Request::url();
        $ogImage = dawn_media_url($entry->og_image ?? null);

        // ── Variables consumed by partials/seo/head.htm ──────────────
        $page['seoTitle'] = $entry->meta_title ?: $entry->title;
        $page['seoDescription'] = $entry->meta_description ?: null;
        // canonical_url wins over the page's own URL; head.htm canonicals seoUrl.
        $canonical = trim((string) ($entry->canonical_url ?? ''));
        $page['seoUrl'] = $canonical !== '' ? $canonical : $url;
        $page['seoType'] = 'website';
        $page['seoImage'] = $ogImage;
        $page['seoNoindex'] = (bool) ($entry->seo_noindex ?? false);

        // ── schema.org JSON-LD per schema_type ───────────────────────
        $type = (string) ($entry->schema_type ?: 'WebPage');
        $siteUrl = url('/');

        switch ($type) {
            case 'BlogPosting':
                $published = $entry->published_at ?? $entry->created_at ?? null;
                $modified = $entry->updated_at ?? null;
                $jsonLd = [
                    '@context' => 'https://schema.org',
                    '@type' => 'BlogPosting',
                    'headline' => $entry->title,
                    'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $url],
                    'inLanguage' => $locale,
                ];
                if ($entry->meta_description) {
                    $jsonLd['description'] = $entry->meta_description;
                }
                if ($ogImage) {
                    $jsonLd['image'] = $ogImage;
                }
                if ($published) {
                    $jsonLd['datePublished'] = $published->toIso8601String();
                }
                if ($modified) {
                    $jsonLd['dateModified'] = $modified->toIso8601String();
                }
                $authorName = trim((string) ($entry->article_author ?? '')) ?: $brand;
                $author = ['@type' => 'Person', 'name' => $authorName];
                if ($authorUrl = trim((string) ($entry->article_author_url ?? ''))) {
                    $author['url'] = $authorUrl;
                }
                $jsonLd['author'] = $author;
                $jsonLd['publisher'] = ['@type' => 'Organization', 'name' => $brand];
                break;

            case 'Organization':
                $jsonLd = [
                    '@context' => 'https://schema.org',
                    '@type' => 'Organization',
                    'name' => trim((string) ($entry->org_name ?? '')) ?: $brand,
                    'url' => $siteUrl,
                ];
                if ($logo = dawn_media_url($entry->org_logo ?? null)) {
                    $jsonLd['logo'] = $logo;
                }
                if ($email = trim((string) ($entry->org_email ?? ''))) {
                    $jsonLd['email'] = $email;
                }
                if ($phone = trim((string) ($entry->org_phone ?? ''))) {
                    $jsonLd['telephone'] = $phone;
                }
                $sameAs = array_values(array_filter(array_map('trim',
                    preg_split('/\r\n|\r|\n/', (string) ($entry->org_same_as ?? '')))));
                if ($sameAs) {
                    $jsonLd['sameAs'] = $sameAs;
                }
                break;

            case 'LocalBusiness':
                $jsonLd = [
                    '@context' => 'https://schema.org',
                    '@type' => 'LocalBusiness',
                    'name' => trim((string) ($entry->org_name ?? '')) ?: ($entry->title ?: $brand),
                    'url' => $url,
                ];
                $address = array_filter([
                    '@type' => 'PostalAddress',
                    'streetAddress' => trim((string) ($entry->lb_street ?? '')) ?: null,
                    'addressLocality' => trim((string) ($entry->lb_city ?? '')) ?: null,
                    'postalCode' => trim((string) ($entry->lb_zip ?? '')) ?: null,
                    'addressCountry' => trim((string) ($entry->lb_country ?? '')) ?: null,
                ], fn ($v) => $v !== null);
                if (count($address) > 1) {
                    $jsonLd['address'] = $address;
                }
                if ($phone = trim((string) ($entry->lb_phone ?? ''))) {
                    $jsonLd['telephone'] = $phone;
                }
                $lat = trim((string) ($entry->lb_lat ?? ''));
                $lng = trim((string) ($entry->lb_lng ?? ''));
                if ($lat !== '' && $lng !== '' && is_numeric($lat) && is_numeric($lng)) {
                    $jsonLd['geo'] = [
                        '@type' => 'GeoCoordinates',
                        'latitude' => round((float) $lat, 5),
                        'longitude' => round((float) $lng, 5),
                    ];
                }
                if ($price = trim((string) ($entry->lb_price_range ?? ''))) {
                    $jsonLd['priceRange'] = $price;
                }
                if ($hours = dawn_parse_hours($entry->lb_hours ?? null)) {
                    $jsonLd['openingHoursSpecification'] = $hours;
                }
                if ($ogImage) {
                    $jsonLd['image'] = $ogImage;
                }
                break;

            // WebPage / AboutPage / ContactPage share one skeleton.
            case 'AboutPage':
            case 'ContactPage':
            case 'WebPage':
            default:
                $webType = in_array($type, ['AboutPage', 'ContactPage'], true) ? $type : 'WebPage';
                $jsonLd = [
                    '@context' => 'https://schema.org',
                    '@type' => $webType,
                    'name' => $entry->meta_title ?: $entry->title,
                    'url' => $url,
                    'inLanguage' => $locale,
                    'isPartOf' => [
                        '@type' => 'WebSite',
                        'name' => $brand,
                        'url' => $siteUrl,
                    ],
                ];
                if ($entry->meta_description) {
                    $jsonLd['description'] = $entry->meta_description;
                }
                if ($modified = $entry->updated_at ?? null) {
                    $jsonLd['dateModified'] = $modified->toIso8601String();
                }
                break;
        }

        // JSON_HEX_TAG|JSON_HEX_AMP: emitted |raw inside a <script> element, so a
        // literal </script> in an author-set field must be escaped to stay inside
        // it — JSON_UNESCAPED_SLASHES alone would leave it intact.
        $page['seoJsonLd'] = json_encode(
            $jsonLd,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP
        );
    }
}
