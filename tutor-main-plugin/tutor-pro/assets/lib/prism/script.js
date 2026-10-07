document.addEventListener('DOMContentLoaded', () => {
    if (!window.Prism) {
        return;
    }

    document.querySelectorAll('.tutor-container pre').forEach((el) => {
        const fallback = 'javascript';

        const lang =
            el.className
                ?.trim()
                .replace('language-', '') || fallback;

        const grammar = Prism.languages[lang] || Prism.languages[fallback];

        el.innerHTML = Prism.highlight(
            el.textContent,
            grammar,
            Prism.languages[lang] ? lang : fallback
        );
    });
});
