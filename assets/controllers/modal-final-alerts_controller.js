import { Controller } from '@hotwired/stimulus';

/**
 * Stimulus controller chargé de gérer l'affichage d'une modal contenant
 * toutes les alertes des repas en cours de saisie/edition
 */
export default class extends Controller {

    static values = {
        url: String,
    }
    static targets = ['content', 'background', 'container'];

    showAlerts() {
        document.getElementById('modal-final-alerts').classList.replace('hidden', 'flex');
    }

    hideAlerts() {
        document.getElementById('modal-final-alerts').classList.replace('flex', 'hidden');
    }

}