import { Controller } from '@hotwired/stimulus';

/**
 * Stimulus controller chargé de gérer le graphique d'évolution du poids.
 *
 * Fonctionnalités :
 * - ouverture et fermeture de la modal
 * - chargement du graphique via AJAX
 */
export default class extends Controller {

    /**
     * Targets DOM :
     * - modal : la fenêtre modale
     * - content : conteneur où sera injecté le graphique
     */
    static targets = ["modal", "content"]

    /**
     * Valeurs injectées depuis Twig :
     * - url : URL pour récupérer le graphique
     */
    static values = {
        url: String
    }

    /**
     * Ouvre la modal et charge le graphique
     */
    open(event) {
        event.preventDefault();

        this.modalTarget.classList.remove('hidden');
        this.modalTarget.classList.add('flex');
        document.body.style.overflow = 'hidden';

        this.loadChart();
    }

    /**
     * Ferme la modal
     */
    close() {
        this.modalTarget.classList.add('hidden');
        this.modalTarget.classList.remove('flex');
        document.body.style.overflow = 'auto';
    }

    /**
     * Charge le graphique via AJAX et l'injecte dans le contentTarget
     */
    loadChart() {
        // Affiche un message de chargement
        this.contentTarget.innerHTML =
            '<div class="text-center py-6">Chargement...</div>';

        fetch(this.urlValue, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.text())
        .then(html => {
            this.contentTarget.innerHTML = html;
        })
        .catch(error => {
            this.contentTarget.innerHTML =
                '<div class="text-red-600">Erreur lors du chargement du graphique</div>';
        });
    }
}