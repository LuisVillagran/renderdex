/*
 * Movimiento que CSS no puede hacer solo. Todo es decorativo: el HTML ya trae el
 * texto final, así que sin JS o con prefers-reduced-motion la página se ve igual.
 */
const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

/** Números que cuentan desde cero hasta el valor que ya trae el HTML. */
function countUp() {
    const format = new Intl.NumberFormat(document.documentElement.lang);

    document.querySelectorAll('[data-count-up]').forEach((element) => {
        const final = element.textContent;
        const target = Number.parseInt(final.replace(/\D/g, ''), 10);

        if (!target) {
            return;
        }

        const duration = 800;
        const start = performance.now();

        const tick = (now) => {
            const progress = Math.min((now - start) / duration, 1);
            const eased = 1 - (1 - progress) ** 3;

            element.textContent = progress < 1 ? format.format(Math.round(target * eased)) : final;

            if (progress < 1) {
                requestAnimationFrame(tick);
            }
        };

        requestAnimationFrame(tick);
    });
}

/** Marca con data-revealed los elementos [data-reveal] cuando entran en pantalla (el CSS anima la entrada). */
function revealOnScroll() {
    const elements = document.querySelectorAll('[data-reveal]');

    if (!('IntersectionObserver' in window)) {
        elements.forEach((element) => (element.dataset.revealed = ''));

        return;
    }

    const observer = new IntersectionObserver(
        (entries) => {
            entries
                .filter((entry) => entry.isIntersecting)
                .forEach((entry) => {
                    entry.target.dataset.revealed = '';
                    observer.unobserve(entry.target);
                });
        },
        { rootMargin: '0px 0px -10% 0px' },
    );

    elements.forEach((element) => observer.observe(element));
}

function start() {
    revealOnScroll();

    if (!reducedMotion.matches) {
        countUp();
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start);
} else {
    start();
}
