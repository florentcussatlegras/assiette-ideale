import { Controller } from "@hotwired/stimulus";

/**
 * Stimulus controller chargé de gérer un accordéon/collapse 
 * des différents éléments du bilan des repas en cours de saisie (energie, nutriments, groupes d' aliments).
 */
export default class extends Controller {
    static targets = ["content", "icon"]

    // Initialisation à la connexion du controller
    connect() {
        this.open = false
        this.contentTarget.style.overflow = "hidden"
        this.contentTarget.style.height = "0px"
    }

    // Bascule entre ouverture et fermeture
    toggle() {
        this.open ? this.close() : this.openSection()
    }

    // Ouvre la section avec animation
    openSection() {
        this.open = true
        const content = this.contentTarget

        // Calcul de la hauteur réelle du contenu
        const height = content.scrollHeight
        content.style.height = height + "px"

        // Rotation de l'icône
        this.iconTarget.classList.add("rotate-180")
    }

    // Ferme la section avec animation
    close() {
        this.open = false
        const content = this.contentTarget

        // Force le recalcul de la hauteur avant fermeture
        content.style.height = content.scrollHeight + "px"
        content.offsetHeight // force reflow

        content.style.height = "0px"

        // Retour de l'icône à sa position initiale
        this.iconTarget.classList.remove("rotate-180")
    }
}