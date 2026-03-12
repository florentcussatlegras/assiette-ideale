import { Controller } from "@hotwired/stimulus";

/**
 * Stimulus controller chargéd d'animer un élément au scroll (fade in + translation)
 */
export default class extends Controller {
    connect() {

        // Indique si l'animation a déjà été jouée pour éviter les répétitions
        this.hasAnimated = false;

        // Création d'un IntersectionObserver pour détecter quand l'élément entre dans la zone de viewport
        const observer = new IntersectionObserver(
            (entries) => {
                entries.forEach(entry => {
                    // Si l'élément est visible et n'a pas encore été animé
                    if (entry.isIntersecting && !this.hasAnimated) {
                        // Retire les classes initiales invisibles et décalées
                        this.element.classList.remove("opacity-0", "translate-y-8");

                        // Ajoute les classes finales pour l'animation (visible et position normale)
                        this.element.classList.add("opacity-100", "translate-y-0");

                        // Marque comme animé pour ne pas rejouer l'animation
                        this.hasAnimated = true;

                        // On arrête l'observation, inutile après l'animation
                        observer.disconnect();
                    }
                });
            },
            {
                // L'élément doit être visible à au moins 30% pour déclencher l'animation
                threshold: 0.3
            }
        );

        // Commence à observer l'élément lié à ce controller
        observer.observe(this.element);
    }
}