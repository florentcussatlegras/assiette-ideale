import { Controller } from "@hotwired/stimulus"

/**
 * Stimulus controller chargé de gérer la fixation css des boutons d'ajout de nouveau repas du jour
 */
export default class extends Controller {
    static targets = ["buttons", "container"]

    connect() {
        // bind pour conserver le "this"
        this.updatePosition = this.updatePosition.bind(this)
        window.addEventListener("scroll", this.updatePosition)
        this.updatePosition() // check initial
    }

    disconnect() {
        window.removeEventListener("scroll", this.updatePosition)
    }

    updatePosition() {
        const rect = this.containerTarget.getBoundingClientRect()

        if (rect.bottom > window.innerHeight) {
            // le bas du container est sous la fenêtre => bouton fixé
            this.buttonsTarget.classList.add("fixed", "bottom-6", "right-12")
        } else {
            // bouton retourne dans le flux normal
            this.buttonsTarget.classList.remove("fixed", "bottom-6", "right-12")
        }
    }
}