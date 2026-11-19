<?php
/**
 * Backend draft preview for Dawn's manually-routed Tailor pages. Dawn looks
 * records up by slug with applyPublishedStatus() in page onStart(), which runs
 * BEFORE the section component validates `_preview_token` — so unpublished
 * drafts 404ed. This mirrors Tailor's own SectionComponent::getPreviewModel():
 * token validity/expiry/use-count and the URI match stay enforced by core, and
 * visitors without a valid token keep getting the 404.
 */

if (!function_exists('dawn_preview_entry')) {
    function dawn_preview_entry(string $handle): ?\Tailor\Models\EntryRecord
    {
        $token = trim((string) get('_preview_token'));
        if ($token === '' || !class_exists(\Tailor\Models\PreviewToken::class)) {
            return null;
        }

        try {
            \Tailor\Models\PreviewToken::checkTokenForCurrentUrl($token);

            $enabled = \Tailor\Models\PreviewToken::getEnabledToken();
            $previewId = $enabled?->getRouteParam('id');
            if (!$previewId) {
                return null;
            }

            return \Tailor\Models\EntryRecord::inSection($handle)
                ->withoutGlobalScopes()
                ->find($previewId);
        } catch (\Throwable) {
            return null;
        }
    }
}
