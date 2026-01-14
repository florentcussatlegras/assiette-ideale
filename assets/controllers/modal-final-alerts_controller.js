import { Controller } from '@hotwired/stimulus';

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