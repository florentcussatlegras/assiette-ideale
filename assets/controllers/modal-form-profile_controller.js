import { Controller } from '@hotwired/stimulus';

/**
 * Stimulus controller chargé de gérer une modal contenant
 * un formulaire de profil soumis en AJAX.
 */
export default class extends Controller {

    /**
     * Targets DOM utilisées par le controller :
     * - content : conteneur dans lequel la réponse HTML est injectée
     * - modal : élément représentant la fenêtre modale
     * - messageSuccess : message affiché après succès
     */
    static targets = ["content", "modal", "messageSuccess"]

    /**
     * Lifecycle Stimulus.
     * Initialise l'écoute de la soumission du formulaire.
     */
    connect() {
        console.log('connect modal form profile');
        this.element.addEventListener('submit', this.submit.bind(this));
    }

    /**
     * Ouvre la modal et bloque le scroll de la page.
     */
    open(event) {
        event.preventDefault();

        this.modalTarget.classList.remove('hidden');
        this.modalTarget.classList.add('flex');

        document.body.style.overflow = 'hidden';
    }

    /**
     * Ferme la modal et réactive le scroll.
     */
    close() {
        this.modalTarget.classList.add('hidden');
        this.modalTarget.classList.remove('flex');

        document.body.style.overflow = 'auto';
    }

    /**
     * Ferme la modal si l'utilisateur clique sur l'arrière-plan.
     */
    closeIfOutside(event) {
        if (event.target === this.modalTarget) {
            this.close();
        }
    }

    /**
     * Soumet le formulaire via AJAX.
     * Met à jour le contenu de la modal avec la réponse HTML.
     */
    submit(event) {

        const form = event.target;

        if (form.tagName !== 'FORM') {
            return;
        }

        event.preventDefault();

        const url = form.getAttribute('action');
        const formData = new FormData(form);

        fetch(url, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.text())
        .then(html => {
            this.contentTarget.innerHTML = html;
            this.formTarget.classList.add('opacity-0');
        })
        .catch(error => console.error('Erreur AJAX:', error));
    }

    /**
     * Gestion de l'affichage du message de succès.
     * Ferme la modal après quelques secondes puis recharge la page.
     */
    messageSuccessTargetConnected(element) {

        setTimeout(() => {

            element.classList.add('opacity-0');

            setTimeout(() => {
                element.remove();
                this.close();
                window.location.reload();
            }, 300);

        }, 3000);
    }

}