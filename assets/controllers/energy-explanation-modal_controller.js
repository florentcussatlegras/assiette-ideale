import { Controller } from '@hotwired/stimulus';

/**
 * Stimulus controller chargé de gérer une modal d'explication des énergies conseillées.
 */
export default class extends Controller {

    /**
     * Targets DOM :
     * - modal : conteneur global de la modal
     * - content : zone où le contenu AJAX sera injecté
     * - loader : zone d'indication de chargement
     */
    static targets = ["modal", "content", "loader"]

    /**
     * Valeur injectée depuis Twig :
     * URL pour récupérer le contenu HTML via AJAX.
     */
    static values = {
        url: String
    }

    /**
     * Ouvre la modal et charge son contenu.
     *
     * Étapes :
     * 1) Empêche le comportement par défaut si appelé via un lien ou bouton.
     * 2) Affiche la modal et désactive le scroll du body.
     * 3) Affiche le loader et vide le contenu précédent.
     * 4) Récupère le contenu via fetch et l'injecte.
     * 5) En cas d'erreur, affiche un message d'erreur.
     */
    async open(event) {
        event.preventDefault();

        // Affiche la modal
        this.modalTarget.classList.remove('hidden');
        this.modalTarget.classList.add('flex');

        // Bloque le scroll du body pendant la modal ouverte
        document.body.style.overflow = 'hidden';

        // Affiche le loader
        this.loaderTarget.classList.remove('hidden');
        this.loaderTarget.classList.add('flex');

        // Vide le contenu existant
        this.contentTarget.innerHTML = "";

        try {
            const response = await fetch(this.urlValue, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            if (!response.ok) throw new Error('Erreur réseau');

            const html = await response.text();

            // Masque le loader
            this.loaderTarget.classList.add('hidden');
            this.loaderTarget.classList.remove('flex');

            // Injecte le contenu HTML récupéré
            this.contentTarget.innerHTML = html;

        } catch (error) {
            // Masque le loader et affiche un message d'erreur UX friendly
            this.loaderTarget.classList.add('hidden');

            this.contentTarget.innerHTML = `
                <div class="text-red-600 text-center py-6">
                    Une erreur est survenue lors du chargement.
                </div>
            `;

            console.error(error);
        }
    }

    /**
     * Ferme la modal.
     * - Masque le conteneur modal
     * - Réactive le scroll du body
     */
    close() {
        this.modalTarget.classList.add('hidden');
        this.modalTarget.classList.remove('flex');

        document.body.style.overflow = 'auto';
    }

}