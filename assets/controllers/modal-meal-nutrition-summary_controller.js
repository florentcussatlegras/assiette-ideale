// assets/controllers/modal_controller.js
import { Controller } from "@hotwired/stimulus"

/**
 * Stimulus controller chargé de gérer la modale des bilans des repas saisis/edités
 */
export default class extends Controller {

    /**
     * Targets DOM utilisées par le controller :
     * - modal : élément contenant la fenêtre modale
     */
    static targets = ["modal"]

    /**
     * Ouvre la modal et bloque le scroll de la page.
     */
    open(event) {
        event.preventDefault();

        this.modalTarget.classList.remove('hidden');
        this.modalTarget.classList.add('flex');

        document.body.style.overflow = 'hidden';
    }

    /**
     * Ferme la modal et réactive le scroll.
     */
    close() {
        this.modalTarget.classList.add('hidden');
        this.modalTarget.classList.remove('flex');

        document.body.style.overflow = 'auto';
    }

    /**
     * Ferme la modal si l'utilisateur clique
     * sur l'arrière-plan de la modal.
     */
    closeIfOutside(event) {
        if (event.target === this.modalTarget) {
            this.close();
        }
    }
}