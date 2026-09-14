/**
 * ColorScheme — Manages color scheme switching via CSS custom properties.
 * Stores preference in localStorage for instant load + persists to server.
 *
 * Attribute: data-color-scheme on <html> (alongside data-theme for light/dark).
 * Values: default, blue, green, brown, pink, grey, white.
 */
class ColorScheme {
    constructor() {
        this.scheme = 'default';
        this.storageKey = 'mc-color-scheme';
    }

    init() {
        // Namespace localStorage key by context (admin vs customer)
        var context = document.documentElement.getAttribute('data-scheme-context');
        if (context) {
            this.storageKey = 'mc-color-scheme-' + context;
        }

        // Migrate old shared key to namespaced key (one-time)
        var oldShared = localStorage.getItem('mc-color-scheme');
        if (oldShared && !localStorage.getItem(this.storageKey)) {
            localStorage.setItem(this.storageKey, oldShared);
        }
        if (oldShared && this.storageKey !== 'mc-color-scheme') {
            localStorage.removeItem('mc-color-scheme');
        }

        // Load from localStorage (instant, no flash)
        var saved = localStorage.getItem(this.storageKey);
        if (saved) {
            this.scheme = saved;
        } else {
            // Fall back to server-rendered attribute
            var serverScheme = document.documentElement.getAttribute('data-color-scheme');
            if (serverScheme) {
                this.scheme = serverScheme;
            }
        }
        this.apply();

        // Event delegation for scheme card clicks
        document.addEventListener('click', function(e) {
            var card = e.target.closest('.mc-color-scheme-card');
            if (!card) return;

            e.preventDefault();
            e.stopPropagation();

            var newScheme = card.getAttribute('data-color-scheme');
            if (newScheme && newScheme !== this.scheme) {
                this.setScheme(newScheme);
                this.save(card.getAttribute('data-scheme-save-url'));
            }
        }.bind(this));
    }

    setScheme(scheme) {
        this.scheme = scheme;
        localStorage.setItem(this.storageKey, scheme);

        // Smooth transition: add class, change scheme, remove class after animation
        document.documentElement.classList.add('mc-scheme-transitioning');
        this.apply();

        setTimeout(function() {
            document.documentElement.classList.remove('mc-scheme-transitioning');
        }, 350);
    }

    apply() {
        document.documentElement.setAttribute('data-color-scheme', this.scheme);
        this.updateActiveCard();
    }

    updateActiveCard() {
        var cards = document.querySelectorAll('.mc-color-scheme-card');
        for (var i = 0; i < cards.length; i++) {
            cards[i].classList.remove('active');
        }
        var active = document.querySelector('.mc-color-scheme-card[data-color-scheme="' + this.scheme + '"]');
        if (active) {
            active.classList.add('active');
        }
    }

    save(url) {
        if (!url) return;

        var tokenMeta = document.querySelector('meta[name="csrf-token"]');
        var token = tokenMeta ? tokenMeta.getAttribute('content') : '';

        var formData = new FormData();
        formData.append('_token', token);
        formData.append('color_scheme', this.scheme);

        fetch(url, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        }).catch(function() {
            // Silent fail — localStorage already has the preference
        });
    }

    getScheme() {
        return this.scheme;
    }
}

window.McColorScheme = new ColorScheme();
