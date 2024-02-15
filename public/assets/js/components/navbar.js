window.addEventListener('DOMContentLoaded', function() {
    const navbar = document.getElementById('navbar');
    const pathname = window.location.pathname;

    if (pathname === '/') { // Vérifiez si la page actuelle est la page d'accueil
        window.addEventListener('scroll', function() {
            if (window.matchMedia('(min-width: 992px)').matches) { // Vérifiez si la largeur de l'écran est supérieure à 992px
                if (window.pageYOffset > 50) {
                    navbar.classList.add('scrolled');
                } else {
                    navbar.classList.remove('scrolled');
                }
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    } else { // Pour les autres pages
        if (window.matchMedia('(min-width: 992px)').matches) { // Vérifiez si la largeur de l'écran est supérieure à 992px
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    }

    // Ajoutez un écouteur d'événement pour le redimensionnement de la fenêtre
    window.addEventListener('resize', function() {
        if (window.matchMedia('(min-width: 992px)').matches) { // Vérifiez si la largeur de l'écran est supérieure à 992px
            if (pathname !== '/' || window.pageYOffset > 50) {
                navbar.classList.add('scrolled');
            }
        } else {
            navbar.classList.remove('scrolled');
        }
    });
});