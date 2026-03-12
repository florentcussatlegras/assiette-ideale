import { Controller } from "@hotwired/stimulus";

/**
 * Stimulus controller chargé de gérer le slider horizontal des reviews google de la page d'accueil
 * - Affiche jusqu'à 5 dots visibles
 * - Gère le scroll du slider avec boutons "prev" / "next"
 * - Met à jour le style des dots pour indiquer l’élément actif et les voisins
 */
export default class extends Controller {

    // Déclaration des targets : 
    // - slider : conteneur horizontal des éléments
    // - dots : conteneur des boutons représentant les positions
    static targets = ["slider", "dots"];

    connect() {
        this.maxVisibleDots = 5; // nombre maximum de dots visibles simultanément
        this.scrollAmount = 300; // quantité de scroll par clic (non utilisée ici directement)
        this.createDots(); // crée les dots en fonction du nombre d'éléments
        this.updateDots(); // initialise la mise à jour visuelle des dots

        // Écoute le scroll du slider pour mettre à jour les dots actives
        this.sliderTarget.addEventListener("scroll", () => {
            this.updateDots();
        });
    }

    // ===============================
    // Calculs dynamiques
    // ===============================

    // Retourne l'espacement entre les éléments (column-gap)
    get gap() {
        const style = window.getComputedStyle(this.sliderTarget);
        return parseInt(style.columnGap) || 0;
    }

    // Largeur totale d’un élément, y compris l’espace entre eux
    get itemWidth() {
        return this.sliderTarget.children[0].offsetWidth + this.gap;
    }

    // Nombre total d’éléments dans le slider
    get totalItems() {
        return this.sliderTarget.children.length;
    }

    // ===============================
    // Navigation
    // ===============================

    // Scroll vers l'élément suivant
    next() {
        const maxScroll = this.sliderTarget.scrollWidth - this.sliderTarget.clientWidth;
        this.sliderTarget.scrollTo({
            left: Math.min(this.sliderTarget.scrollLeft + this.itemWidth, maxScroll),
            behavior: "smooth" // animation fluide
        });
    }

    // Scroll vers l'élément précédent
    prev() {
        this.sliderTarget.scrollBy({
            left: -this.itemWidth,
            behavior: "smooth"
        });
    }

    // ===============================
    // Dots de navigation
    // ===============================

    // Crée les dots pour chaque élément du slider
    createDots() {
        this.dotsTarget.innerHTML = ""; // vide le conteneur avant création

        for (let i = 0; i < this.totalItems; i++) {
            const dot = document.createElement("button");
            dot.className = "dot w-2 h-2 rounded-full bg-gray-300 transition-all duration-300";
            dot.dataset.index = i;

            // Scroll jusqu’à l’élément correspondant au clic sur le dot
            dot.addEventListener("click", () => {
                this.sliderTarget.scrollTo({
                    left: i * this.itemWidth,
                    behavior: "smooth"
                });
            });

            this.dotsTarget.appendChild(dot);
        }
    }

    // Met à jour l’affichage des dots selon la position du scroll
    updateDots() {
        const index = Math.round(this.sliderTarget.scrollLeft / this.itemWidth); // élément actif
        const allDots = Array.from(this.dotsTarget.querySelectorAll(".dot"));

        // Détermine la fenêtre de dots visibles centrée sur l’élément actif
        let start = Math.max(0, index - Math.floor(this.maxVisibleDots / 2));
        let end = Math.min(this.totalItems, start + this.maxVisibleDots);
        start = Math.max(0, end - this.maxVisibleDots);

        allDots.forEach((dot, i) => {
            // Affiche seulement les dots dans la fenêtre
            dot.classList.toggle("hidden", i < start || i >= end);

            // Reset du style
            dot.classList.remove("w-3", "h-3", "bg-gray-700", "w-2.5", "h-2.5", "bg-gray-500");
            dot.classList.add("w-2", "h-2", "bg-gray-300");

            // Dot active → plus grosse et couleur sombre
            if (i === index) {
                dot.classList.remove("w-2", "h-2", "bg-gray-300");
                dot.classList.add("w-3", "h-3", "bg-gray-700");
            } 
            // Dots voisines → légèrement plus grosses et couleur intermédiaire
            else if (i === index - 1 || i === index + 1) {
                dot.classList.remove("w-2", "h-2", "bg-gray-300");
                dot.classList.add("w-2.5", "h-2.5", "bg-gray-500");
            }
        });
    }
}