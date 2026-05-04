/**
 * landing.js — VoxENSIASD
 * Interactions et animations pour la page d'accueil.
 */

document.addEventListener('DOMContentLoaded', () => {

    // === Smooth scroll pour ancres ===
    document.querySelectorAll('a[href^="#"]').forEach(link => {
        link.addEventListener('click', e => {
            const href = link.getAttribute('href');
            if (href === '#' || href.length < 2) return;
            const target = document.querySelector(href);
            if (target) {
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });

    // === Reveal au scroll (IntersectionObserver) ===
    const reveal = document.querySelectorAll('.reveal');
    const io = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('revealed');
                io.unobserve(entry.target);
            }
        });
    }, { threshold: 0.15 });

    reveal.forEach(el => io.observe(el));

    // === Compteur animé pour les statistiques ===
    const counters = document.querySelectorAll('[data-count]');
    const counterIO = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const el = entry.target;
                const target = parseInt(el.dataset.count, 10);
                const duration = 1600;
                const start = performance.now();
                const animate = (now) => {
                    const progress = Math.min((now - start) / duration, 1);
                    const eased = 1 - Math.pow(1 - progress, 3);
                    el.textContent = Math.floor(target * eased).toLocaleString('fr-FR');
                    if (progress < 1) requestAnimationFrame(animate);
                };
                requestAnimationFrame(animate);
                counterIO.unobserve(el);
            }
        });
    }, { threshold: 0.5 });

    counters.forEach(c => counterIO.observe(c));

    // === Indicateur de force de mot de passe (page register) ===
    const pwd = document.querySelector('#password');
    const bar = document.querySelector('.password-strength .bar');
    const label = document.querySelector('.strength-label');

    if (pwd && bar && label) {
        pwd.addEventListener('input', () => {
            const v = pwd.value;
            let score = 0;
            if (v.length >= 8) score++;
            if (/[A-Z]/.test(v)) score++;
            if (/[0-9]/.test(v)) score++;
            if (/[^A-Za-z0-9]/.test(v)) score++;

            bar.classList.remove('weak', 'medium', 'strong');
            if (v.length === 0) {
                label.textContent = '';
                bar.style.width = '0%';
            } else if (score <= 2) {
                bar.classList.add('weak');
                label.textContent = 'FAIBLE';
            } else if (score === 3) {
                bar.classList.add('medium');
                label.textContent = 'MOYEN';
            } else {
                bar.classList.add('strong');
                label.textContent = 'FORT';
            }
        });
    }

    // === Validation formulaire de connexion / inscription ===
    const authForms = document.querySelectorAll('.auth-form form');
    authForms.forEach(form => {
        form.addEventListener('submit', e => {
            const email = form.querySelector('input[name="email"]');
            const password = form.querySelector('input[name="password"]');
            const confirm = form.querySelector('input[name="password_confirm"]');

            // Si "email" est en fait login admin, ne pas valider format email
            const isAdminLogin = email && email.value === 'ENSIASD';

            if (email && !isAdminLogin && email.value.includes('@') === false && form.dataset.allowLogin !== 'true') {
                e.preventDefault();
                showInlineError(email, 'Veuillez entrer un email valide.');
                return;
            }
            if (password && password.value.length < 6 && form.dataset.context === 'register') {
                e.preventDefault();
                showInlineError(password, 'Mot de passe trop court (min 6 caractères).');
                return;
            }
            if (confirm && password && confirm.value !== password.value) {
                e.preventDefault();
                showInlineError(confirm, 'Les mots de passe ne correspondent pas.');
                return;
            }
        });
    });

    function showInlineError(input, msg) {
        const existing = input.parentNode.querySelector('.inline-err');
        if (existing) existing.remove();
        const err = document.createElement('div');
        err.className = 'inline-err';
        err.style.cssText = 'color:var(--crimson);font-size:13px;margin-top:6px;font-family:JetBrains Mono,monospace;letter-spacing:0.05em';
        err.textContent = msg;
        input.parentNode.appendChild(err);
        input.style.borderBottomColor = 'var(--crimson)';
    }
});
