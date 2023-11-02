//hamburger
const hamburger = document.querySelector('.hamburger');
const adaptiveMenu = document.querySelector('.adaptive-menu');
const navList = document.querySelector('.nav-ul');

function toggleMenu() {
    adaptiveMenu.classList.toggle('is-open');
}

hamburger.addEventListener('click', toggleMenu);

function closeMenu(event) {
    if (event.target.classList.contains('nav-link')) {
        adaptiveMenu.classList.remove('is-open');
    }
}

navList.addEventListener('click', closeMenu);