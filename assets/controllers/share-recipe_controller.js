import { Controller } from "@hotwired/stimulus"

/**
 * Stimulus controller chargé de gérer le partage des recettes sur les réseaux sociaux.
 */
export default class extends Controller {

    // Target qui correspond à la modale dans le HTML
    static targets = ["modal"]

    // Valeur passée depuis le HTML contenant l’URL à partager
    static values = { 
        url: String 
    }

    /**
     * Ouvre la modale de partage.
     */
    open() {
        this.modalTarget.classList.remove("hidden")
    }

    /**
     * Ferme la modale de partage.
     */
    close() {
        this.modalTarget.classList.add("hidden")
    }

    /**
     * Construit l’URL complète à partager.
     */
    get url() {
        return `${window.location.origin}${this.urlValue}`
    }

    /**
     * Partage via WhatsApp.
     */
    whatsapp() {
        const url = `https://api.whatsapp.com/send?text=${encodeURIComponent(this.url)}`
        window.open(url, "_blank")
    }

    /**
     * Partage par email.
     */
    email() {
        const url = `mailto:?subject=Regarde cette recette&body=${encodeURIComponent(this.url)}`
        window.location.href = url
    }

    /**
     * Partage sur Twitter (X).
     */
    twitter() {
        const url = `https://twitter.com/intent/tweet?text=${encodeURIComponent(this.url)}`
        window.open(url, "_blank")
    }

    /**
     * Copie l’URL dans le presse-papier de l’utilisateur.
     */
    async copy(event) {

        event.preventDefault();

        const buttonWrapper = event.currentTarget.parentElement;

        await navigator.clipboard.writeText(this.url);

        const flash = buttonWrapper.querySelector(".flash-message-copy-link");

        if (!flash) return;

        flash.textContent = "Lien copié";

        flash.classList.add("show");

        // Cache le message après 2 secondes
        setTimeout(() => {
            flash.classList.remove("show");
        }, 2000);
    }
}