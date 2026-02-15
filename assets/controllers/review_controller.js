import { Controller } from "@hotwired/stimulus";

export default class extends Controller {

    static targets = ["text", "button"]

    connect() {
        this.expanded = false

        // Si le texte ne dépasse pas 3 lignes → on cache le bouton
        if (this.textTarget.scrollHeight <= this.textTarget.clientHeight) {
            this.buttonTarget.classList.add("hidden")
        }
    }

    toggle() {
        this.expanded = !this.expanded

        if (this.expanded) {
            this.textTarget.classList.remove("line-clamp-3")
            this.buttonTarget.textContent = "Masquer"
        } else {
            this.textTarget.classList.add("line-clamp-3")
            this.buttonTarget.textContent = "Voir la suite"
        }
    }
}
