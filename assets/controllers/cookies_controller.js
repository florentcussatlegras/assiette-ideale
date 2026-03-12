import { Controller } from '@hotwired/stimulus';

/**
 * Stimulus controller chargé de gérer le consentement cookies.
 */
export default class extends Controller {

    /**
     * Lifecycle Stimulus.
     * Vérifie si un choix utilisateur existe déjà dans le stockage local.
     */
    connect() {
        const choice = localStorage.getItem('cookie-consent');

        // Si aucun choix n'existe → on affiche la bannière
        if (!choice) {
            this.element.classList.remove('hidden');

        // Si le consentement a déjà été donné → possibilité de charger les scripts
        } else if (choice === 'accepted') {
            // this.loadTrackingScripts();
        }
    }

    /**
     * Action appelée lorsque l'utilisateur accepte les cookies.
     * Enregistre le consentement et active les scripts de tracking.
     */
    accept() {
        localStorage.setItem('cookie-consent', 'accepted');

        // Masque la bannière
        this.element.classList.add('hidden');

        // Chargement conditionnel des scripts analytics
        this.loadTrackingScripts();
    }

    /**
     * Action appelée lorsque l'utilisateur refuse les cookies.
     * Le refus est mémorisé afin de ne plus afficher la bannière.
     */
    reject() {
        localStorage.setItem('cookie-consent', 'rejected');

        // Masque la bannière sans activer de tracking
        this.element.classList.add('hidden');
    }

    /**
     * Point central pour initialiser les scripts de tracking.
     * Permet de contrôler facilement quels scripts sont chargés
     * uniquement après consentement (analytics, pixels, etc.).
     */
    loadTrackingScripts() {
        console.log("Cookies acceptés");
    }
    
}