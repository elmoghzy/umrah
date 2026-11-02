<script>
    if (! window.__umrahSaveConfirmationBound) {
        window.__umrahSaveConfirmationBound = true;

        const navigationOverlay = document.createElement('div');
        navigationOverlay.setAttribute('aria-busy', 'true');
        navigationOverlay.setAttribute('aria-label', 'جارٍ تحميل الصفحة');
        navigationOverlay.style.cssText = [
            'display:none',
            'position:fixed',
            'inset:0',
            'z-index:9999',
            'cursor:wait',
            'background:rgba(255,255,255,.35)',
        ].join(';');
        document.body.append(navigationOverlay);

        const hideNavigationOverlay = () => {
            navigationOverlay.style.display = 'none';
        };

        document.addEventListener('livewire:navigated', hideNavigationOverlay);
        window.addEventListener('pageshow', () => {
            window.setTimeout(hideNavigationOverlay, 0);
        });
        window.addEventListener('popstate', () => {
            window.setTimeout(hideNavigationOverlay, 0);
        });

        document.addEventListener('submit', (event) => {
            const form = event.target;

            if (!(form instanceof HTMLFormElement)) {
                return;
            }

            const submitAttribute = Array.from(form.attributes)
                .find((attribute) => attribute.name.startsWith('wire:submit'));

            if (! submitAttribute || ! /^(create|save|update)$/.test(submitAttribute.value.trim())) {
                return;
            }

            if (form.dataset.confirmedSubmit === 'true') {
                delete form.dataset.confirmedSubmit;
                return;
            }

            event.preventDefault();

            if (! window.confirm('هل أنت متأكد من حفظ البيانات؟')) {
                event.stopImmediatePropagation();
                event.stopPropagation();
                return;
            }

            form.dataset.confirmedSubmit = 'true';
            form.requestSubmit();
        }, true);
    }
</script>
