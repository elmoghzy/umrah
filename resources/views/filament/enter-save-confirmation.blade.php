<script>
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
</script>
