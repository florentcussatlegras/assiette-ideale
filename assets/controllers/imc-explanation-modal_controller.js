import { Controller } from '@hotwired/stimulus';

/**
 * Stimulus controller chargé de gérer l'ouverture et la fermeture d'une modale d'explications avec chargement de contenu via AJAX.
 */
export default class extends Controller {

    // Targets HTML
    static targets = ["modal", "content", "loader"];
    
    // Valeurs passées depuis le HTML
    static values = {
        url: String  // URL pour récupérer le contenu AJAX
    }

    // Méthode exécutée à la connexion du controller
    connect() {
        console.log('connect icm explanations modal');
    }

    // Ouvre la modal et charge le contenu via AJAX
    async open(event) {
        event.preventDefault();

        // Affiche la modal et empêche le scroll du body
        this.modalTarget.classList.remove('hidden');
        this.modalTarget.classList.add('flex');
        document.body.style.overflow = 'hidden';

        // Affiche le loader
        this.loaderTarget.classList.remove('hidden');
        this.loaderTarget.classList.add('flex');

        // Vide le contenu avant chargement
        this.contentTarget.innerHTML = "";

        try {
            const response = await fetch(this.urlValue, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            console.log(this.urlValue);

            if (!response.ok) {
                throw new Error('Erreur réseau');
            }

            const html = await response.text();

            // Masque le loader et affiche le contenu
            this.loaderTarget.classList.add('hidden');
            this.loaderTarget.classList.remove('flex');
            console.log(html);
            this.contentTarget.innerHTML = html;

        } catch (error) {
            // En cas d'erreur, masque le loader et affiche un message d'erreur
            this.loaderTarget.classList.add('hidden');

            this.contentTarget.innerHTML = `
                <div class="text-red-600 text-center py-6">
                    Une erreur est survenue lors du chargement.
                </div>
            `;

            console.log(error);
        }
    }

    // Ferme la modal et réactive le scroll du body
    close() {
        this.modalTarget.classList.add('hidden');
        this.modalTarget.classList.remove('flex');
        document.body.style.overflow = 'auto';
    }

}