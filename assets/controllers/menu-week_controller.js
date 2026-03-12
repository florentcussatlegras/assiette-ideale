import { Controller } from '@hotwired/stimulus';

/**
 * Stimulus controller chargé de gérer le rechargement du menu de la semaine
 * via AJAX en fonction de la date et du format d'affichage (mobile ou desktop).
 */
export default class extends Controller {

    static values = {
        url: String
    }

    // Recharge le menu de la semaine selon la date et le format demandé
    async reloadMenu(event) {
        const btnReload = event.currentTarget;
        const format = btnReload.dataset.format;

        // Préparation des paramètres de la requête
        const params = new URLSearchParams({
            'startingDate': btnReload.dataset.startingDate,
            'ajax': 1,
            'format': format,
        });

        // Réduction de l'opacité pour indiquer un chargement
        document.getElementById('titleMenuWeek').style.opacity = .5;
        document.getElementById('tableMenuWeek').style.opacity = .5;

        // Requête AJAX pour récupérer le nouveau contenu
        const response = await fetch(`${this.urlValue}?${params.toString()}`);

        // Mise à jour du DOM selon le format d'affichage
        if(format === 'mobile') {
            document.getElementById('wrapperMenuWeekMobile').innerHTML = await response.text();
        } else {
            document.getElementById('wrapperMenuWeekDesktop').innerHTML = await response.text();
        }

        // Restauration de l'opacité après chargement
        document.getElementById('titleMenuWeek').style.opacity = 1;
        document.getElementById('tableMenuWeek').style.opacity = 1;
    }

}