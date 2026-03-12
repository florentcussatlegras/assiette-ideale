import { Controller } from '@hotwired/stimulus';

/**
 * Stimulus controller chargé de gérer l'affichage d'une modal contenant
 * un graphique de répartition FGP (groupes d'aliments) et nutriments chargé via AJAX.
 */
export default class extends Controller {

    static values = {
        urlModalChartFgp: String,
    }

    static targets = ['content', 'background', 'container'];

    // Affiche la modal et charge le contenu du graphique via AJAX
    async show(event) {

        // Affiche la modal
        document.getElementById('modal-chart-balance-sheet').classList.replace('hidden', 'flex');

        // Préparation des paramètres envoyés à la requête
        const params = new URLSearchParams({
            'average_fgp': event.currentTarget.dataset.averageFgp
        });

        // Récupération du contenu HTML du graphique
        const response = await fetch(`${this.urlModalChartFgpValue}?${params.toString()}`);
        this.contentTarget.innerHTML = await response.text();

    }

    // Ferme la modal
    hide() {
        document.getElementById('modal-chart-balance-sheet').classList.replace('flex', 'hidden');
    }

}