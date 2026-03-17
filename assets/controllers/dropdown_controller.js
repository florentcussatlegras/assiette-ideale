import { Controller } from "@hotwired/stimulus";

export default class extends Controller {

    // Déclaration des targets (ici "menu")
    static targets = ["menu"];

    // Méthode appelée à l'initialisation du controller
    connect() {
        // On bind la méthode pour garder le bon "this"
        this._handleClickOutside = this.handleClickOutside.bind(this);

        // Ajoute un listener global pour détecter les clics sur toute la page
        document.addEventListener("click", this._handleClickOutside);
    }

    // Méthode appelée quand le controller est détruit
    disconnect() {
        // Nettoyage du listener pour éviter les fuites mémoire
        document.removeEventListener("click", this._handleClickOutside);
    }

    // Méthode appelée pour toggle (ouvrir/fermer) le menu
    toggle(event) {
        // Empêche la propagation du clic (sinon ça fermerait direct via clickOutside)
        event.stopPropagation();

        // Vérifie si le menu est caché
        if (this.menuTarget.classList.contains("opacity-0")) {
            this.open(); // Ouvre le menu
        } else {
            this.close(); // Ferme le menu
        }
    }

    // Ouvre le menu (enlevant les classes Tailwind de masquage)
    open() {
        this.menuTarget.classList.remove("opacity-0", "invisible", "translate-y-2");
    }

    // Ferme le menu (ajoute les classes pour le cacher + animation)
    close() {
        this.menuTarget.classList.add("opacity-0", "invisible", "translate-y-2");
    }

    // Gère le clic en dehors du composant
    handleClickOutside(event) {
        // Si le clic est en dehors de l'élément du controller
        if (!this.element.contains(event.target)) {
            this.close(); // Ferme le menu
        }
    }
}