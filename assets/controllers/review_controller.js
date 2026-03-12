import { Controller } from "@hotwired/stimulus";

/**
 * 🔹 Stimulus controller pour gérer un texte “collapsible” des reviews google utilisateur
 */
export default class extends Controller {

    // Target DOM
    static targets = ["text", "button"] // text = contenu, button = bouton “Voir la suite / Masquer”

    // Méthode appelée à la connexion du controller
    connect() {
        this.expanded = false // Etat initial : texte réduit (collapsed)

        // 🔹 Vérifie si le texte dépasse 3 lignes (line-clamp-3)
        // scrollHeight > clientHeight signifie que le texte dépasse l’espace visible
        if (this.textTarget.scrollHeight <= this.textTarget.clientHeight) {
            // Si le texte est déjà entièrement visible → on cache le bouton
            this.buttonTarget.classList.add("hidden")
        }
    }

    // Toggle du texte
    toggle() {
        // Inverse l'état (collapsed / expanded)
        this.expanded = !this.expanded

        if (this.expanded) {
            // 🔹 Affiche tout le texte
            this.textTarget.classList.remove("line-clamp-3")
            // 🔹 Change le texte du bouton
            this.buttonTarget.textContent = "Masquer"
        } else {
            // 🔹 Réduit le texte à 3 lignes
            this.textTarget.classList.add("line-clamp-3")
            // 🔹 Change le texte du bouton
            this.buttonTarget.textContent = "Voir la suite"
        }
    }
}