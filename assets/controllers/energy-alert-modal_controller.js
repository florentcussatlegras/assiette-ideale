import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["modal"]; // target pour la modal

    connect() {
        // Optionnel : console.log pour debug
        // console.log("EnergyAlertController connecté !");
    }

    open() {
        this.modalTarget.classList.remove("hidden");
    }

    close() {
        this.modalTarget.classList.add("hidden");
    }

    // ferme la modal si on clique sur le fond
    overlayClick(event) {
        if (event.target === this.modalTarget) {
            this.close();
        }
    }
}
