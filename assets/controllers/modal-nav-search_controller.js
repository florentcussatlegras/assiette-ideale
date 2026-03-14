import { Controller } from '@hotwired/stimulus'

/**
 * Stimulus controller pour gérer la modals de navigation/recherche.
 */
export default class extends Controller {

    /**
     * Lifecycle Stimulus.
     * Lie la méthode handleEscape pour conserver le contexte "this".
     */
    connect() {
        this.handleEscape = this.handleEscape.bind(this)
    }

    /**
     * Ouvre la modal.
     * - supprime les classes CSS qui la cachent
     * - ajoute une classe au body pour le style global
     * - active l'écoute de la touche Échap
     */
    open(event) {
        event.preventDefault()

        const modal = this.element.querySelector('.modal-nav-search')

        modal.classList.remove('opacity-0', 'pointer-events-none')
        document.body.classList.add('modal-nav-search-active')

        document.addEventListener('keydown', this.handleEscape)

        this.dispatch("opened");
    }

    /**
     * Ferme la modal.
     */
    close(event) {
        if (event) event.preventDefault()

        const modal = this.element.querySelector('.modal-nav-search')

        modal.classList.add('opacity-0', 'pointer-events-none')
        document.body.classList.remove('modal-nav-search-active')

        document.removeEventListener('keydown', this.handleEscape)
    }

    /**
     * Gestion de la touche Échap pour fermer la modal
     */
    handleEscape(event) {
        if (event.key === 'Escape') {
            this.close()
        }
    }
}