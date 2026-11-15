'use strict';

const menuButton = document.querySelector('.menu-toggle');
const navigation = document.querySelector('.sidebar-nav');

if (menuButton && navigation) {
    const closeMenu = () => {
        navigation.classList.remove('open');
        menuButton.setAttribute('aria-expanded', 'false');
        menuButton.setAttribute('aria-label', 'Abrir menu');
    };

    menuButton.addEventListener('click', () => {
        const isOpen = navigation.classList.toggle('open');

        menuButton.setAttribute('aria-expanded', String(isOpen));
        menuButton.setAttribute(
            'aria-label',
            isOpen ? 'Fechar menu' : 'Abrir menu'
        );
    });

    navigation.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => {
            if (window.innerWidth <= 800) {
                closeMenu();
            }
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeMenu();
        }
    });

    window.addEventListener('resize', () => {
        if (window.innerWidth > 800) {
            closeMenu();
        }
    });
}

const copyInviteButtons = document.querySelectorAll('[data-copy-invite]');

copyInviteButtons.forEach((copyInviteButton) => {
    copyInviteButton.addEventListener('click', async () => {
        const code = copyInviteButton.dataset.inviteCode ?? '';
        const originalText = copyInviteButton.textContent;

        try {
            await navigator.clipboard.writeText(code);
            copyInviteButton.textContent = 'Copiado!';

            window.setTimeout(() => {
                copyInviteButton.textContent = originalText;
            }, 1500);
        } catch {
            window.prompt('Copie o código da república:', code);
        }
    });
});

document.querySelectorAll('[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        const message = form.dataset.confirm ?? 'Deseja continuar?';

        if (!window.confirm(message)) {
            event.preventDefault();
        }
    });
});
