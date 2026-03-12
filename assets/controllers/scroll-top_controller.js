import { Controller } from "@hotwired/stimulus"

/**
 * Stimulus controller pour gérer un bouton "retour en haut" (scroll-to-top) (notamment sur la page des repas du jour)
 * - Le bouton apparaît après un certain scroll vertical
 * - Permet un retour fluide en haut de la page
 */
export default class extends Controller {

    static targets = ["button"] // cible le bouton scroll-top

    connect() {
        // Bind de la fonction pour conserver le contexte "this"
        this.onScroll = this.onScroll.bind(this)

        // Écouteur d'événement scroll pour détecter le déplacement vertical
        window.addEventListener("scroll", this.onScroll)
    }

    disconnect() {
        // Supprime l'écouteur pour éviter les fuites de mémoire
        window.removeEventListener("scroll", this.onScroll)
    }

    /**
     * Détecte le scroll vertical
     * - Si le scroll dépasse 300px → affiche le bouton
     * - Sinon → cache le bouton
     */
    onScroll() {
        if (window.scrollY > 300) {
            // Affiche le bouton
            this.buttonTarget.classList.remove("opacity-0", "pointer-events-none")
            this.buttonTarget.classList.add("opacity-100")
        } else {
            // Cache le bouton
            this.buttonTarget.classList.add("opacity-0", "pointer-events-none")
            this.buttonTarget.classList.remove("opacity-100")
        }
    }

    /**
     * Fonction déclenchée au clic sur le bouton
     * - Scroll fluide jusqu'en haut de la page
     */
    scrollTop() {
        window.scrollTo({
            top: 0,
            behavior: "smooth" // animation fluide
        })
    }

}