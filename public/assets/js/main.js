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
