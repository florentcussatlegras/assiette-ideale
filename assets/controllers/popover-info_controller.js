import { Controller } from '@hotwired/stimulus';
import { useClickOutside } from 'stimulus-use';

export default class extends Controller {
    static targets = ["background", "container", "content", "loader"];
    static values = { url: String };

    connect() {
        useClickOutside(this, { element: this.containerTarget });
    }

    async show(event) {
        // Show backdrop
        this.backgroundTarget.classList.remove("hidden");

        // Animation: fade in
        requestAnimationFrame(() => {
            this.backgroundTarget.classList.add("opacity-100");
        });

        // Show modal content
        this.containerTarget.classList.remove("hidden");
        requestAnimationFrame(() => {
            this.containerTarget.classList.remove("opacity-0", "scale-95");
            this.containerTarget.classList.add("opacity-100", "scale-100");
        });

        // Load data
        this.loaderTarget.classList.remove("hidden");
        this.contentTarget.classList.add("hidden");

        const response = await fetch(this.urlValue);
        this.contentTarget.innerHTML = await response.text();

        this.loaderTarget.classList.add("hidden");
        this.contentTarget.classList.remove("hidden");
    }

    hide() {
        // Animation out
        this.containerTarget.classList.remove("opacity-100", "scale-100");
        this.containerTarget.classList.add("opacity-0", "scale-95");

        this.backgroundTarget.classList.remove("opacity-100");

        // Remove after animation
        setTimeout(() => {
            this.containerTarget.classList.add("hidden");
            this.backgroundTarget.classList.add("hidden");
        }, 200);
    }

    clickOutside() {
        this.hide();
    }
}
