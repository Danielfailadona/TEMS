import TomSelect from 'tom-select';
import * as bootstrap from 'bootstrap';
import Chart from 'chart.js/auto';

window.bootstrap = bootstrap;
window.Chart = Chart;
window.TomSelect = TomSelect;

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((el) => {
        new bootstrap.Tooltip(el);
    });

    // Flash alerts dismiss themselves after 5 seconds — no close click needed.
    document.querySelectorAll('.alert.alert-dismissible').forEach((el) => {
        setTimeout(() => {
            bootstrap.Alert.getOrCreateInstance(el).close();
        }, 5000);
    });

    const animatedItems = document.querySelectorAll('.animate-on-load');
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.15 });

    animatedItems.forEach((item) => observer.observe(item));
});
