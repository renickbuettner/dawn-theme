description = "Default — site chrome, SEO head, Renick.Content assets ({{NAME}}, child of Renick Dawn)"

[renickContent]
includeAssets = 0

==
function onStart()
{
    // Resolve Dawn's folder (`renick-dawn` on a Marketplace install, the parent
    // on a child theme) and expose dawn_path()/dawn_asset() to the pages too.
    $dawnTheme = $this->controller->getTheme();
    while ($parentTheme = $dawnTheme->getParentTheme()) {
        $dawnTheme = $parentTheme;
    }
    require_once $dawnTheme->getPath() . '/partials/site/paths.php';
    dawn_paths_use($dawnTheme);

    // Resolves each locale's real Dawn\Page slug, not just a re-prefixed path.
    require_once dawn_path('partials/site/alternates.php');
    $this['dawnAlternates'] = dawn_alternates();

    // Canonical/OG URL, query string excluded.
    $this['seoUrl'] = \Request::url();

    // child.css comes LAST so its :root overrides win the cascade.
    $this['dawnCss'] = \System\Classes\CombineAssets::combine([
        '$/renick/content/assets/frontend/bootstrap.css',
        '$/renick/content/assets/frontend/renick-content.css',
        dawn_asset('css/theme.css'),
        '~/themes/{{SLUG}}/assets/css/child.css',
    ]);
    $this['dawnScripts'] = \System\Classes\CombineAssets::combine([
        '$/renick/content/assets/frontend/renick-content.js',
        dawn_asset('js/theme.js'),
    ]);

    // Cached site chrome, keyed on the active site — one cache read per
    // site/locale instead of four DB queries per request.
    require_once dawn_path('partials/site/chrome.php');
    $chrome = dawn_chrome();
    $this['dawnNavPages'] = $chrome['nav'];
    $this['dawnFooterPages'] = $chrome['footer'];
    $this['dawnSettings'] = $chrome['settings'];
    $this['dawnMegaItems'] = $chrome['mega'];

    // For the header's nav-active check; per-URL, so kept out of the cache.
    $this['dawnCurrentUrl'] = rtrim(\Request::url(), '/');
}
==
<!DOCTYPE html>
<html lang="{{ this.site.locale|default('en') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="{{THEME_COLOR}}">
    {# Turbo View Transitions (October v4.3+). null = never customised → on;
       only an explicit false disables. `|default` swallows false, hence the
       long-hand test. #}
    {% if this.theme.view_transitions is same as(null) or this.theme.view_transitions %}
        <meta name="turbo-visit-control" content="enable">
        <meta name="turbo-view-transition" content="same-origin">
    {% endif %}
    {% partial 'seo/head' %}

    {# Combined in onStart; {% styles %} stays for per-block extras. #}
    <link rel="stylesheet" href="{{ dawnCss }}">
    {% styles %}
</head>
<body data-lang="{{ this.site.locale|default('en') }}"{% if this.theme.show_slider_pause is same as(false) or this.theme.show_slider_pause is same as(0) or this.theme.show_slider_pause is same as('0') %} class="dawn-no-slider-pause"{% endif %}>
    <a class="dawn-skip-link" href="#main">{{ 'dawn.front.common.skip_to_content'|_ }}</a>
    {% partial 'site/header' %}

    <main id="main" tabindex="-1">
        {% page %}
    </main>

    {% partial 'site/footer' %}

    <script src="{{ dawnScripts }}" defer></script>
    {# The `turbo` option is required: plain {% framework %} omits the router
       and leaves the head's turbo-view-transition meta inert. #}
    {% framework turbo %}
    {% scripts %}
</body>
</html>
