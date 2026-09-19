/* Renick Dawn — OFFLINE.Mall shop pages: keyboard filters, slider labels, filter offcanvas,
   sign-in tabs and the product detail tabs. Loaded by layouts/shop.htm only. */
(function () {
    'use strict';

    // Mall's filter chips are click-driven <div>s; make them operable from the keyboard
    document.addEventListener('keydown', function (e) {
        if ((e.key === 'Enter' || e.key === ' ') && e.target.classList.contains('js-mall-filter')) {
            e.preventDefault();
            e.target.click();
        }
    });
    // Bubble phase on document: Mall flips the hidden checkbox in a delegated body handler, which runs first
    document.addEventListener('click', function (e) {
        var chip = e.target.closest('.js-mall-filter');
        if (!chip) return;
        var box = chip.querySelector('input[type="checkbox"]');
        if (box) chip.setAttribute('aria-checked', box.checked ? 'true' : 'false');
    });

    // noUiSlider handles carry aria-value* but no name; take it from the filter's own label.
    // The sliders are created after DOM ready and re-created on filter updates, hence the observer.
    function labelSliderHandles() {
        document.querySelectorAll('.mall-slider-handles').forEach(function (slider) {
            var scope = slider.closest('.mall-property') || slider.closest('.mall-property-group');
            var label = scope && scope.querySelector('.mall-property__label, .mall-property-group__name');
            var name = label ? label.textContent.replace(/\s+/g, ' ').trim() : '';
            slider.querySelectorAll('.noUi-handle').forEach(function (handle) {
                if (!handle.hasAttribute('aria-label')) {
                    handle.setAttribute('aria-label', name + (handle.classList.contains('noUi-handle-lower') ? ' (min)' : ' (max)'));
                }
            });
        });
    }
    labelSliderHandles();
    new MutationObserver(labelSliderHandles).observe(document.body, { childList: true, subtree: true });
})();

/* Category page: the filter panel is a Bootstrap offcanvas below lg */
(function () {
    'use strict';

    var panel = document.getElementById('mall-filter');
    var toggle = document.querySelector('[data-bs-toggle="offcanvas"][data-bs-target="#mall-filter"]');
    if (!panel || !toggle) return;

    // renick-content.js exposes window.bootstrap in a microtask, so it is not there yet inside the same combined bundle
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else setTimeout(init, 0);

    function init() {
        var Offcanvas = window.bootstrap && window.bootstrap.Offcanvas;
        if (!Offcanvas) {
            document.documentElement.classList.remove('mall-filter-js');
            return;
        }

        panel.addEventListener('show.bs.offcanvas', function () { toggle.setAttribute('aria-expanded', 'true'); });
        panel.addEventListener('hidden.bs.offcanvas', function () { toggle.setAttribute('aria-expanded', 'false'); });

        // Bootstrap only hears Escape from inside the panel; a tap on blank panel space moves focus to body
        document.addEventListener('keydown', function (e) {
            var instance = Offcanvas.getInstance(panel);
            if (e.key === 'Escape' && instance && panel.classList.contains('show')) instance.hide();
        });

        // Bootstrap leaves the backdrop and scroll lock behind once the panel turns static at >= 992px
        window.matchMedia('(min-width: 992px)').addEventListener('change', function (e) {
            var instance = Offcanvas.getInstance(panel);
            if (e.matches && instance) instance.hide();
        });

        var badge = toggle.querySelector('[data-mall-filter-count]');
        if (!badge) return;

        var activeFilters = function () {
            var form = panel.querySelector('form.mall-products-filter');
            if (!form) return 0;
            var count = form.querySelectorAll('.js-mall-filter input[type="checkbox"]:checked').length;
            form.querySelectorAll('select[name^="filter"]').forEach(function (select) {
                if (select.value) count++;
            });
            var ranges = {};
            form.querySelectorAll('input[data-min], input[data-max]').forEach(function (input) {
                if (input.value) ranges[input.dataset.min || input.dataset.max] = true;
            });
            return count + Object.keys(ranges).length;
        };

        var updateBadge = function () {
            var count = activeFilters();
            badge.textContent = count;
            badge.hidden = count === 0;
        };

        updateBadge();
        panel.addEventListener('change', updateBadge);
        // Chips flip their hidden checkbox in Mall's click handler, which runs after this one
        panel.addEventListener('click', function () { setTimeout(updateBadge, 0); });
        // Mall re-renders the filter form after every AJAX update
        new MutationObserver(updateBadge).observe(panel, { childList: true, subtree: true });
    }
})();

/* Sign in / sign up panels become tabs below 768px */
(function () {
    'use strict';

    var TABBED = window.matchMedia('(max-width: 767.98px)');

    function initSignupTabs(root) {
        var tabs = Array.prototype.slice.call(root.querySelectorAll('.mall-signup__tab'));
        if (tabs.length < 2) return;

        var panels = tabs.map(function (tab) {
            return document.getElementById(tab.getAttribute('aria-controls'));
        });
        if (panels.some(function (panel) { return !panel; })) return;

        var fromHash = function () { return /^#(signup|register)$/.test(location.hash) ? 1 : 0; };
        var current = fromHash();

        function select(index, focus) {
            current = index;
            tabs.forEach(function (tab, i) {
                var active = i === index;
                tab.setAttribute('aria-selected', active ? 'true' : 'false');
                tab.tabIndex = active ? 0 : -1;
                panels[i].hidden = !active;
            });
            root.setAttribute('data-tab', index === 0 ? 'signin' : 'signup');
            if (focus) tabs[index].focus();
        }

        function apply() {
            if (TABBED.matches) {
                select(current, false);
                return;
            }
            root.removeAttribute('data-tab');
            panels.forEach(function (panel) { panel.hidden = false; });
        }

        tabs.forEach(function (tab, i) {
            tab.addEventListener('click', function () { select(i, false); });
            tab.addEventListener('keydown', function (event) {
                var next = null;
                if (event.key === 'ArrowRight' || event.key === 'ArrowDown') next = (i + 1) % tabs.length;
                else if (event.key === 'ArrowLeft' || event.key === 'ArrowUp') next = (i - 1 + tabs.length) % tabs.length;
                else if (event.key === 'Home') next = 0;
                else if (event.key === 'End') next = tabs.length - 1;
                if (next === null) return;
                event.preventDefault();
                select(next, true);
            });
        });

        if (TABBED.addEventListener) TABBED.addEventListener('change', apply);
        else TABBED.addListener(apply);
        window.addEventListener('hashchange', function () { current = fromHash(); apply(); });
        apply();
    }

    function init() {
            document.querySelectorAll('.mall-signup').forEach(initSignupTabs);
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();

/* Product page: description | properties | reviews tabs from 768px, stacked sections below */
(function () {
    'use strict';

    var desktop = window.matchMedia('(min-width: 768px)');
    var lastSelected = null;

    function tabsOf(root) {
        return Array.prototype.slice.call(root.querySelectorAll('[role="tab"]'));
    }

    function select(root, tab, focus) {
        var tabsMode = desktop.matches;
        tabsOf(root).forEach(function (t) {
            var active = t === tab;
            var panel = document.getElementById(t.getAttribute('aria-controls'));
            t.setAttribute('aria-selected', active ? 'true' : 'false');
            t.tabIndex = active ? 0 : -1;
            if (!panel) return;
            panel.hidden = tabsMode && !active;
            if (tabsMode) panel.tabIndex = 0; else panel.removeAttribute('tabindex');
        });
        lastSelected = tab.id;
        if (focus) tab.focus();
    }

    function apply(root) {
        var tabs = tabsOf(root);
        if (!tabs.length) return;
        root.setAttribute('data-mall-tabs-ready', desktop.matches ? 'tabs' : 'stack');
        var current = tabs.filter(function (t) { return t.id === lastSelected; })[0]
            || tabs.filter(function (t) { return t.getAttribute('aria-selected') === 'true'; })[0]
            || tabs[0];
        select(root, current, false);
    }

    function applyAll() {
        document.querySelectorAll('[data-mall-tabs]').forEach(apply);
    }

    document.addEventListener('click', function (e) {
        var tab = e.target.closest('[data-mall-tabs] [role="tab"]');
        if (tab) select(tab.closest('[data-mall-tabs]'), tab, false);
    });

    document.addEventListener('keydown', function (e) {
        var tab = e.target.closest('[data-mall-tabs] [role="tab"]');
        if (!tab) return;
        var root = tab.closest('[data-mall-tabs]');
        var tabs = tabsOf(root);
        var i = tabs.indexOf(tab);
        var next;
        switch (e.key) {
            case 'ArrowRight': next = tabs[(i + 1) % tabs.length]; break;
            case 'ArrowLeft': next = tabs[(i - 1 + tabs.length) % tabs.length]; break;
            case 'Home': next = tabs[0]; break;
            case 'End': next = tabs[tabs.length - 1]; break;
            default: return;
        }
        e.preventDefault();
        select(root, next, true);
    });

    // Mall replaces the innerHTML of .mall-product__info on variant change
    document.querySelectorAll('.mall-product__info').forEach(function (info) {
        new MutationObserver(applyAll).observe(info, { childList: true });
    });

    if (desktop.addEventListener) desktop.addEventListener('change', applyAll);
    else desktop.addListener(applyAll);
    applyAll();
})();

/* Product page: thumbnails swap the main image; the main image opens the lightbox at the active thumbnail */
(function () {
    'use strict';

    var forwarding = false;

    // Capture phase so GLightbox's own click handler on the thumbnail never fires for a swap
    document.addEventListener('click', function (e) {
        var thumb = e.target.closest('[data-mall-gallery-thumb]');
        if (!thumb || forwarding) return;
        var gallery = thumb.closest('[data-mall-gallery]');
        var main = gallery && gallery.querySelector('[data-mall-gallery-main]');
        var img = main && main.querySelector('img');
        if (!img) return;
        e.preventDefault();
        e.stopPropagation();
        img.src = thumb.getAttribute('data-large') || thumb.href;
        img.alt = thumb.getAttribute('aria-label') || '';
        main.href = thumb.href;
        main.setAttribute('aria-label', img.alt);
        gallery.querySelectorAll('[data-mall-gallery-thumb]').forEach(function (t) {
            var active = t === thumb;
            t.classList.toggle('is-active', active);
            if (active) t.setAttribute('aria-current', 'true'); else t.removeAttribute('aria-current');
        });
    }, true);

    document.addEventListener('click', function (e) {
        var main = e.target.closest('[data-mall-gallery-main]');
        if (!main) return;
        var active = main.closest('[data-mall-gallery]').querySelector('[data-mall-gallery-thumb].is-active');
        if (!active) return;
        e.preventDefault();
        forwarding = true;
        active.click();
        forwarding = false;
    });
})();
