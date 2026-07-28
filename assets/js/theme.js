/* Renick Dawn — small progressive enhancements. Bootstrap JS is injected by the
   renickContent component; this only adds the nav toggle and scroll reveal. */
(function () {
    'use strict';

    // Mobile off-canvas nav (no Bootstrap Offcanvas dependency — keeps the nav
    // working even on pages without the content component). Slides in from the
    // right with a backdrop; traps focus, locks body scroll, Escape closes.
    var toggle = document.querySelector('[data-dawn-nav-toggle]');
    var menu = document.getElementById('dawn-nav-menu');
    var backdrop = document.querySelector('[data-dawn-nav-backdrop]');
    var closeBtn = menu && menu.querySelector('[data-dawn-nav-close]');
    if (toggle && menu) {
        var FOCUSABLE = 'a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])';

        var isOpen = function () { return menu.classList.contains('show'); };

        var onKeydown = function (e) {
            if (e.key === 'Escape') {
                closeMenu();
                return;
            }
            if (e.key !== 'Tab') { return; }
            // Focus trap: keep Tab/Shift+Tab cycling within the panel.
            var items = Array.prototype.filter.call(
                menu.querySelectorAll(FOCUSABLE),
                function (el) { return el.offsetParent !== null; }
            );
            if (!items.length) { return; }
            var first = items[0];
            var last = items[items.length - 1];
            if (e.shiftKey && document.activeElement === first) {
                e.preventDefault();
                last.focus();
            } else if (!e.shiftKey && document.activeElement === last) {
                e.preventDefault();
                first.focus();
            }
        };

        var openMenu = function () {
            if (backdrop) { backdrop.hidden = false; }
            // Force a reflow so the .show transition runs from the hidden state.
            void menu.offsetWidth;
            menu.classList.add('show');
            if (backdrop) { backdrop.classList.add('show'); }
            toggle.setAttribute('aria-expanded', 'true');
            document.body.style.overflow = 'hidden';
            document.addEventListener('keydown', onKeydown);
            // Move focus into the panel (close button first, else first link).
            var target = closeBtn || menu.querySelector(FOCUSABLE);
            if (target) { target.focus(); }
        };

        var closeMenu = function () {
            if (!isOpen()) { return; }
            menu.classList.remove('show');
            if (backdrop) { backdrop.classList.remove('show'); }
            toggle.setAttribute('aria-expanded', 'false');
            document.body.style.overflow = '';
            document.removeEventListener('keydown', onKeydown);
            // Hide the backdrop after its fade-out so it stops catching clicks.
            if (backdrop) {
                window.setTimeout(function () {
                    if (!isOpen()) { backdrop.hidden = true; }
                }, 300);
            }
            toggle.focus();
        };

        toggle.addEventListener('click', function () {
            if (isOpen()) { closeMenu(); } else { openMenu(); }
        });
        if (closeBtn) { closeBtn.addEventListener('click', closeMenu); }
        if (backdrop) { backdrop.addEventListener('click', closeMenu); }
        // Tapping a nav link closes the panel (before the navigation swaps).
        menu.querySelectorAll('a').forEach(function (a) {
            a.addEventListener('click', function () {
                closeMenu();
            });
        });
    }

    // Language picker dropdown — toggle open, close on outside click / Escape.
    var langToggle = document.querySelector('[data-dawn-lang-toggle]');
    var langPicker = langToggle && langToggle.closest('.dawn-langpicker');
    if (langToggle && langPicker) {
        var closeLang = function () {
            langPicker.classList.remove('open');
            langToggle.setAttribute('aria-expanded', 'false');
        };
        langToggle.addEventListener('click', function (e) {
            e.stopPropagation();
            var open = langPicker.classList.toggle('open');
            langToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
        document.addEventListener('click', function (e) {
            if (!langPicker.contains(e.target)) { closeLang(); }
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') { closeLang(); }
        });
    }

    // Mega menu disclosures (Dawn\Menu global, mega mode) — W3C APG disclosure
    // pattern: a chevron button toggles a panel of plain links. One panel open
    // at a time; Escape closes and returns focus to the button; outside click
    // and focus leaving the item close it. Mobile renders the same buttons as
    // accordions inside the off-canvas panel (CSS switches the layout).
    var megaToggles = Array.prototype.slice.call(document.querySelectorAll('[data-dawn-mega-toggle]'));
    if (megaToggles.length) {
        var openMega = null;

        var setMega = function (btn, open) {
            var panel = document.getElementById(btn.getAttribute('aria-controls'));
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
            if (!panel) { return; }
            if (open) {
                panel.hidden = false;
                // Force a reflow so the .open transition runs from hidden.
                void panel.offsetWidth;
                panel.classList.add('open');
            } else {
                panel.classList.remove('open');
                // Re-hide after the fade-out unless it was reopened meanwhile.
                window.setTimeout(function () {
                    if (btn.getAttribute('aria-expanded') === 'false') { panel.hidden = true; }
                }, 200);
            }
        };

        var closeOpenMega = function () {
            if (openMega) {
                setMega(openMega, false);
                openMega = null;
            }
        };

        megaToggles.forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                var wasOpen = btn.getAttribute('aria-expanded') === 'true';
                if (openMega && openMega !== btn) { setMega(openMega, false); }
                setMega(btn, !wasOpen);
                openMega = wasOpen ? null : btn;
            });
        });

        document.addEventListener('click', function (e) {
            if (openMega && !(e.target.closest && e.target.closest('[data-dawn-mega]'))) { closeOpenMega(); }
        });
        // WCAG "content on hover or focus": leaving the item by keyboard closes.
        document.addEventListener('focusin', function (e) {
            if (openMega && !(e.target.closest && e.target.closest('[data-dawn-mega]'))) { closeOpenMega(); }
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && openMega) {
                var btn = openMega;
                closeOpenMega();
                btn.focus();
            }
        });
    }

    // Scroll reveal — skipped entirely when the user prefers reduced motion.
    var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var els = document.querySelectorAll('.dawn-reveal');
    if (reduce || !('IntersectionObserver' in window)) {
        els.forEach(function (el) { el.classList.add('is-in'); });
        return;
    }
    var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-in');
                io.unobserve(entry.target);
            }
        });
    }, { rootMargin: '0px 0px -8% 0px', threshold: 0.05 });
    els.forEach(function (el) { io.observe(el); });
})();
