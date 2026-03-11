import { Controller } from "@hotwired/stimulus"

export default class extends Controller {
    static targets = ["modal"]

    static values = { 
        url: String 
    }

    open() {
        this.modalTarget.classList.remove("hidden")
    }

    close() {
        this.modalTarget.classList.add("hidden")
    }

    get url() {
        return `${window.location.origin}${this.urlValue}`
    }

    whatsapp() {
        const url = `https://api.whatsapp.com/send?text=${encodeURIComponent(this.url)}`
        window.open(url, "_blank")
    }

    email() {
        const url = `mailto:?subject=Regarde cette recette&body=${encodeURIComponent(this.url)}`
        window.location.href = url
    }

    twitter() {
        const url = `https://twitter.com/intent/tweet?text=${encodeURIComponent(this.url)}`
        window.open(url, "_blank")
    }

    async copy(event) {
        event.preventDefault(); // toujours sécuriser
        console.log(event.currentTarget.parentElement);
        const buttonWrapper = event.currentTarget.parentElement;

        await navigator.clipboard.writeText(this.url);

        const flash = buttonWrapper.querySelector(".flash-message-copy-link");

        if (!flash) return;

        flash.textContent = "Lien copié";
        flash.classList.add("show");

        setTimeout(() => {
            flash.classList.remove("show");
        }, 2000);
    }
}