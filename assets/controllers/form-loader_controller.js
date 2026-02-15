import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["label", "button", "spinner"]

    connect() {
        this.element.addEventListener("submit", e => this.showLoader(e))
    }

    showLoader(event) {
        if (this.submitted) {
            event.preventDefault()
            return
        }

        this.submitted = true

        // Changer la couleur
        this.buttonTarget.classList.remove("bg-sky-600")
        this.buttonTarget.classList.add("bg-sky-700")

        // Afficher le spinner
        this.spinnerTarget.classList.remove("hidden")

        // Désactiver le bouton
        this.buttonTarget.disabled = true
        this.buttonTarget.classList.add("cursor-not-allowed")
    }
}
