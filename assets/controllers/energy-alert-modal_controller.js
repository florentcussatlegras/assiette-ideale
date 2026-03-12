import { Controller } from "@hotwired/stimulus";

/**
 * Stimulus controller responsable de la gestion d'une modal générique.
 */
export default class extends Controller {

    /**
     * Target DOM correspondant à la modal.
     * C'est le conteneur principal à afficher ou masquer.
     */
    static targets = ["modal"];

    /**
     * Ouvre la modal.
     * - enlève la classe "hidden" pour la rendre visible
     */
    open() {
        this.modalTarget.classList.remove("hidden");
    }

    /**
     * Ferme la modal.
     * - ajoute la classe "hidden" pour la masquer
     */
    close() {
        this.modalTarget.classList.add("hidden");
    }

    /**
     * Ferme la modal si l'utilisateur clique sur le fond (overlay).
     * Permet de ne pas fermer la modal si on clique à l'intérieur du contenu.
     */
    overlayClick(event) {
        if (event.target === this.modalTarget) {
            this.close();
        }
    }
}