import { Controller } from "@hotwired/stimulus"

export default class extends Controller {
    static targets = ["content", "icon"]

    connect() {
        this.open = false
        this.contentTarget.style.overflow = "hidden"
        this.contentTarget.style.height = "0px"
    }

    toggle() {
        this.open ? this.close() : this.openSection()
    }

    openSection() {
        this.open = true

        const content = this.contentTarget

        // calcul hauteur réelle
        const height = content.scrollHeight

        content.style.height = height + "px"
        this.iconTarget.classList.add("rotate-180")
    }

    close() {
        this.open = false

        const content = this.contentTarget

        // force recalcul avant fermeture si déjà ouvert
        content.style.height = content.scrollHeight + "px"
        content.offsetHeight // force reflow

        content.style.height = "0px"
        this.iconTarget.classList.remove("rotate-180")
    }
}