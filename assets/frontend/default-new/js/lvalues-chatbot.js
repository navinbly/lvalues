(function () {
    function ready(callback) {
        if (document.readyState !== 'loading') {
            callback();
            return;
        }
        document.addEventListener('DOMContentLoaded', callback);
    }

    ready(function () {
        var root = document.querySelector('.lvalues-chatbot');
        if (!root) {
            return;
        }

        var endpoint = root.getAttribute('data-chatbot-endpoint');
        var launcher = root.querySelector('.lvalues-chatbot__launcher');
        var closeButton = root.querySelector('.lvalues-chatbot__close');
        var form = root.querySelector('.lvalues-chatbot__form');
        var input = form.querySelector('input[name="question"]');
        var messages = root.querySelector('.lvalues-chatbot__messages');
        var suggestions = root.querySelector('.lvalues-chatbot__suggestions');
        var isWaiting = false;

        function toggle(open) {
            root.classList.toggle('is-open', open);
            launcher.setAttribute('aria-expanded', open ? 'true' : 'false');
            root.querySelector('.lvalues-chatbot__panel').setAttribute('aria-hidden', open ? 'false' : 'true');
            if (open) {
                setTimeout(function () { input.focus(); }, 80);
            }
        }

        function addBubble(text, type, actions) {
            var bubble = document.createElement('div');
            bubble.className = 'lvalues-chatbot__bubble lvalues-chatbot__bubble--' + type;
            bubble.textContent = text;

            if (actions && actions.length) {
                var actionWrap = document.createElement('div');
                actionWrap.className = 'lvalues-chatbot__actions';
                actions.forEach(function (action) {
                    var link = document.createElement('a');
                    link.href = action.url;
                    link.textContent = action.label;
                    link.target = action.label === 'WhatsApp Us' ? '_blank' : '_self';
                    link.rel = 'noopener';
                    actionWrap.appendChild(link);
                });
                bubble.appendChild(actionWrap);
            }

            messages.appendChild(bubble);
            messages.scrollTop = messages.scrollHeight;
            return bubble;
        }

        function ask(question) {
            question = (question || '').trim();
            if (!question || isWaiting) {
                return;
            }

            isWaiting = true;
            addBubble(question, 'user');
            var thinking = addBubble('Searching FAQs...', 'bot');
            input.value = '';

            var payload = new FormData();
            payload.append('question', question);

            fetch(endpoint, {
                method: 'POST',
                body: payload,
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(function (response) { return response.json(); })
                .then(function (data) {
                    thinking.remove();
                    if (!data || data.status === false) {
                        addBubble((data && data.message) ? data.message : 'Sorry, something went wrong. Please try again.', 'bot');
                        return;
                    }
                    addBubble(data.answer, 'bot', data.fallback_actions || []);
                })
                .catch(function () {
                    thinking.remove();
                    addBubble('Sorry, something went wrong. Please try again.', 'bot');
                })
                .finally(function () {
                    isWaiting = false;
                });
        }

        launcher.addEventListener('click', function () {
            toggle(!root.classList.contains('is-open'));
        });

        closeButton.addEventListener('click', function () {
            toggle(false);
        });

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            ask(input.value);
        });

        suggestions.addEventListener('click', function (event) {
            var button = event.target.closest('[data-chatbot-question]');
            if (!button) {
                return;
            }
            ask(button.getAttribute('data-chatbot-question'));
        });
    });
})();