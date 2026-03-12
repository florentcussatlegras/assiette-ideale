import { Controller } from '@hotwired/stimulus';

/**
 * Stimulus controller chargé de gérer l'affichage d'une modal contenant les messages d'alerte d'un repas.
 */
export default class extends Controller {
  
    // Déclaration des éléments HTML accessibles via Stimulus
    // Ces targets correspondent aux attributs data-*-target dans le template Twig
    static targets = ['content', 'container', 'background', 'loader'];

    /**
     * Charge les messages d'alerte et les affiche dans la modal.
     * Cette méthode est déclenchée lors d'une interaction utilisateur (clic).
     */
    async showMessages(event) {
    
        // Récupération de l'URL depuis l'attribut data-url de l'élément déclencheur
        const url = event.currentTarget.dataset.url;

        // Affiche le loader pendant le chargement
        this.loaderTarget.classList.remove('hidden');
        this.contentTarget.classList.add('hidden');

        try {
            // Ajoute un paramètre pour indiquer au backend
            // qu'on souhaite récupérer uniquement les messages
            const params = new URLSearchParams({ showMessages: 1 });

            // Requête HTTP vers la route Symfony
            const response = await fetch(`${url}?${params.toString()}`);

            // Récupération du HTML renvoyé par le serveur
            const html = await response.text();

            // Injection du contenu HTML dans la modal
            this.contentTarget.innerHTML = html;

        } catch (err) {
            // Log de l'erreur pour faciliter le debug côté développement
            console.error('Erreur fetch alert meal modal', err);

            // Message affiché à l'utilisateur en cas d'échec
            this.contentTarget.innerHTML = '<p class="text-red-500">Impossible de charger le contenu.</p>';
        } finally {
            // Masque le loader et affiche le contenu une fois la requête terminée
            this.loaderTarget.classList.add('hidden');
            this.contentTarget.classList.remove('hidden');
        }

    }

    /**
     * Ferme la modal en remplaçant la classe d'affichage.
     */
    hideMessages() {
        document.getElementById('alert-meal-modal').classList.replace('flex', 'hidden');
    }
}