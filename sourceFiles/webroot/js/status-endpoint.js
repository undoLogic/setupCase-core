/*
 * Status Endpoint monitor page. Spec: docs/features/MVP-Status-Endpoint.md
 *
 * Re-fetches its own URL as JSON every data-check-minutes (no page reload, so a
 * site that goes down shows UNREACHABLE instead of a browser error page), and
 * sets the tab favicon + title: green OK, red ERRORS, grey UNREACHABLE.
 */
(function () {
    var card = document.querySelector('.status-endpoint-card');
    if (!card) {
        return;
    }

    var app = card.getAttribute('data-app');
    var intervalMs = parseInt(card.getAttribute('data-check-minutes'), 10) * 60 * 1000;
    var button = card.querySelector('[data-action="check"]');
    var lastCheck = Date.now();
    var timer = null;

    var states = {
        ok: {color: '#198754', mark: '', banner: 'OK'},
        errors: {color: '#dc3545', mark: '!', banner: 'ERRORS'},
        unreachable: {color: '#6c757d', mark: '?', banner: 'UNREACHABLE'}
    };

    function field(name) {
        return card.querySelector('[data-field="' + name + '"]');
    }

    function favicon(state) {
        var s = states[state];
        var svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32">' +
            '<circle cx="16" cy="16" r="15" fill="' + s.color + '"/>' +
            (s.mark ? '<text x="16" y="23" font-size="20" font-weight="bold" font-family="Arial,sans-serif" text-anchor="middle" fill="#fff">' + s.mark + '</text>' : '') +
            '</svg>';
        document.getElementById('status-endpoint-favicon').href = 'data:image/svg+xml,' + encodeURIComponent(svg);
    }

    function render(state, data) {
        card.setAttribute('data-state', state);
        favicon(state);
        if (data) {
            ['errors', 'warnings', 'ignored', 'window_hours', 'php_version', 'cakephp_version'].forEach(function (key) {
                field(key).textContent = data[key];
            });
        }
        if (state === 'errors') {
            document.title = '(' + field('errors').textContent + ') ERRORS - ' + app;
        } else {
            document.title = states[state].banner + ' - ' + app;
        }
        field('banner').textContent = states[state].banner;
        field('checked').textContent = new Date(lastCheck).toLocaleString();
        field('next').textContent = new Date(lastCheck + intervalMs).toLocaleString();
    }

    function jsonUrl() {
        var url = new URL(window.location.href);
        url.searchParams.set('format', 'json');
        return url.toString();
    }

    function check() {
        button.disabled = true;
        lastCheck = Date.now();
        fetch(jsonUrl(), {cache: 'no-store', headers: {'Accept': 'application/json'}})
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }
                return response.json();
            })
            .then(function (data) {
                render(data.errors > 0 ? 'errors' : 'ok', data);
            })
            .catch(function () {
                render('unreachable', null);
            })
            .finally(function () {
                button.disabled = false;
                schedule();
            });
    }

    function schedule() {
        clearTimeout(timer);
        timer = setTimeout(check, intervalMs);
    }

    // Background tabs can be slowed or unloaded; check on return if overdue
    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible' && Date.now() - lastCheck >= intervalMs) {
            check();
        }
    });
    button.addEventListener('click', check);

    // First paint uses the numbers the server rendered
    render(card.getAttribute('data-state'), null);
    schedule();
})();
