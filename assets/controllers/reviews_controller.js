import { Controller } from "@hotwired/stimulus";

export default class extends Controller {

    static targets = ["slider", "dots"];

    connect() {
        this.maxVisibleDots = 5; // max 5 dots visibles
        this.scrollAmount = 300;
        this.createDots();
        this.updateDots();

        this.sliderTarget.addEventListener("scroll", () => {
            this.updateDots();
        });
    }

    get gap() {
        const style = window.getComputedStyle(this.sliderTarget);
        return parseInt(style.columnGap) || 0;
    }

    get itemWidth() {
        return this.sliderTarget.children[0].offsetWidth + this.gap;
    }

    get totalItems() {
        return this.sliderTarget.children.length;
    }

    next() {
        const maxScroll = this.sliderTarget.scrollWidth - this.sliderTarget.clientWidth;
        this.sliderTarget.scrollTo({
            left: Math.min(this.sliderTarget.scrollLeft + this.itemWidth, maxScroll),
            behavior: "smooth"
        });
    }

    prev() {
        this.sliderTarget.scrollBy({
            left: -this.itemWidth,
            behavior: "smooth"
        });
    }

    createDots() {
        this.dotsTarget.innerHTML = "";

        for (let i = 0; i < this.totalItems; i++) {
            const dot = document.createElement("button");
            dot.className = "dot w-2 h-2 rounded-full bg-gray-300 transition-all duration-300";
            dot.dataset.index = i;

            dot.addEventListener("click", () => {
                this.sliderTarget.scrollTo({
                    left: i * this.itemWidth,
                    behavior: "smooth"
                });
            });

            this.dotsTarget.appendChild(dot);
        }
    }

    updateDots() {
        const index = Math.round(this.sliderTarget.scrollLeft / this.itemWidth);
        const allDots = Array.from(this.dotsTarget.querySelectorAll(".dot"));

        // Limite à maxVisibleDots
        let start = Math.max(0, index - Math.floor(this.maxVisibleDots / 2));
        let end = Math.min(this.totalItems, start + this.maxVisibleDots);
        start = Math.max(0, end - this.maxVisibleDots);

        allDots.forEach((dot, i) => {
            // Affiche seulement les dots dans la fenêtre
            dot.classList.toggle("hidden", i < start || i >= end);

            // Reset style
            dot.classList.remove("w-3", "h-3", "bg-gray-700", "w-2.5", "h-2.5", "bg-gray-500");
            dot.classList.add("w-2", "h-2", "bg-gray-300");

            // Dot active → plus grosse
            if (i === index) {
                dot.classList.remove("w-2", "h-2", "bg-gray-300");
                dot.classList.add("w-3", "h-3", "bg-gray-700");
            } 
            // Dots voisines → légèrement plus grosses
            else if (i === index - 1 || i === index + 1) {
                dot.classList.remove("w-2", "h-2", "bg-gray-300");
                dot.classList.add("w-2.5", "h-2.5", "bg-gray-500");
            }
        });
    }
}
