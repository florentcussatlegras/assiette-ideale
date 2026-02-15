import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    connect() {
        console.log('connect fade in');
        this.hasAnimated = false;

        const observer = new IntersectionObserver(
            (entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting && !this.hasAnimated) {
                        this.element.classList.remove("opacity-0", "translate-y-8");
                        this.element.classList.add("opacity-100", "translate-y-0");
                        this.hasAnimated = true;
                        observer.disconnect();
                    }
                });
            },
            { threshold: 0.3 }
        );

        observer.observe(this.element);
    }
}
