<?php

/**
 * JobPosting SEO for a Dawn\JobsPosition entry; reuses page-seo.php's
 * dawn_brand_name()/dawn_media_url(). Never throws — SEO is best-effort chrome,
 * so a missing field is filtered out rather than breaking the page.
 */

require_once __DIR__ . '/page-seo.php';

if (!function_exists('dawn_job_seo')) {
    /**
     * @param  \Cms\Classes\Page|array  $page   The page object (ArrayAccess).
     * @param  object                   $entry  The Dawn\JobsPosition EntryRecord.
     */
    function dawn_job_seo($page, $entry, string $locale = 'en'): void
    {
        $brand = dawn_brand_name();

        $description = trim((string) ($entry->excerpt ?? ''));
        $coverUrl = dawn_media_url($entry->cover ?? null);

        // ── head.htm meta ────────────────────────────────────────────────
        $page['seoTitle'] = $entry->meta_title ?: $entry->title;
        $page['seoDescription'] = $entry->meta_description ?: $description;
        $page['seoType'] = 'article';
        $page['seoImage'] = $coverUrl;
        $page['seoNoindex'] = false;

        // ── JobPosting JSON-LD ───────────────────────────────────────────
        $posted = $entry->published_at ?? null;
        $validThrough = $entry->valid_through ?? null;
        $workplace = (string) ($entry->workplace_type ?? 'onsite');

        $jsonLd = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'JobPosting',
            'title' => (string) $entry->title,
            'description' => $description !== '' ? $description : (string) $entry->title,
            'datePosted' => $posted ? $posted->toIso8601String() : null,
            'validThrough' => $validThrough ? $validThrough->toIso8601String() : null,
            'employmentType' => $entry->employment_type ?: null,
            'hiringOrganization' => array_filter([
                '@type' => 'Organization',
                'name' => trim((string) ($entry->hiring_org_name ?? '')) ?: $brand,
            ]),
            'identifier' => [
                '@type' => 'PropertyValue',
                'name' => $brand,
                'value' => (string) $entry->id,
            ],
            'inLanguage' => $locale,
        ], static fn ($v) => $v !== null && $v !== '' && $v !== []);

        if ($coverUrl) {
            $jsonLd['image'] = $coverUrl;
        }
        if (trim((string) ($entry->apply_url ?? '')) !== '') {
            $jsonLd['directApply'] = true;
        }

        // Physical location for on-site / hybrid roles.
        if ($workplace !== 'remote') {
            $address = array_filter([
                '@type' => 'PostalAddress',
                'addressLocality' => trim((string) ($entry->job_city ?? '')) ?: null,
                'addressRegion' => trim((string) ($entry->job_region ?? '')) ?: null,
                'addressCountry' => trim((string) ($entry->job_country ?? '')) ?: null,
            ], static fn ($v) => $v !== null);
            if (count($address) > 1) {
                $jsonLd['jobLocation'] = ['@type' => 'Place', 'address' => $address];
            }
        }

        // Remote / hybrid → TELECOMMUTE + applicant location requirement.
        if (in_array($workplace, ['remote', 'hybrid'], true)) {
            $jsonLd['jobLocationType'] = 'TELECOMMUTE';
            $applicant = trim((string) ($entry->applicant_location ?? ''));
            if ($applicant !== '') {
                $jsonLd['applicantLocationRequirements'] = ['@type' => 'Country', 'name' => $applicant];
            }
        }

        // baseSalary only when there is a currency and at least one bound.
        $currency = trim((string) ($entry->salary_currency ?? ''));
        $min = $entry->salary_min ?? null;
        $max = $entry->salary_max ?? null;
        if ($currency !== '' && ($min !== null && $min !== '' || $max !== null && $max !== '')) {
            $value = array_filter([
                '@type' => 'QuantitativeValue',
                'minValue' => ($min !== null && $min !== '') ? (float) $min : null,
                'maxValue' => ($max !== null && $max !== '') ? (float) $max : null,
                'unitText' => $entry->salary_unit ?: 'YEAR',
            ], static fn ($v) => $v !== null);
            $jsonLd['baseSalary'] = [
                '@type' => 'MonetaryAmount',
                'currency' => $currency,
                'value' => $value,
            ];
        }

        // JSON_HEX_TAG|JSON_HEX_AMP: emitted |raw inside a <script> element, so a
        // literal </script> in any string must be escaped to stay inside it.
        $page['seoJsonLd'] = json_encode(
            $jsonLd,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP
        );
    }
}
