import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        const el = this.element;
        // force le browser à calculer le style avant d'ajouter la classe
        requestAnimationFrame(() => {
            el.classList.add('visible');
        });
    }
}
