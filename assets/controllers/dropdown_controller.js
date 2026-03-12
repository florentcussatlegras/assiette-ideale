import { Controller } from '@hotwired/stimulus';

/**
 * Stimulus controller chargé de gérer un dropdown / menu déroulant.
 */
export default class extends Controller {
    /**
     * Target DOM correspondant au menu déroulant.
     * Doit être un conteneur que l'on souhaite montrer / cacher.
     */
    static targets = ["menu"];

    /**
     * Toggle du dropdown.
     * Bascule les classes CSS pour gérer :
     * - visibilité (visible / invisible)
     * - opacité (opacity-0 / opacity-100)
     * - translation verticale (translate-y-2 / translate-y-0)
     *
     * Cela permet d'avoir une **animation fluide** d'ouverture et fermeture.
     */
    toggle() {
        this.menuTarget.classList.toggle("opacity-100");
        this.menuTarget.classList.toggle("visible");
        this.menuTarget.classList.toggle("opacity-0");
        this.menuTarget.classList.toggle("invisible");
        this.menuTarget.classList.toggle("translate-y-0");
        this.menuTarget.classList.toggle("translate-y-2");
    }
}