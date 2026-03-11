import { Controller } from '@hotwired/stimulus'

export default class extends Controller {

    connect() {
        this.handleEscape = this.handleEscape.bind(this)
    }

    open(event) {
        event.preventDefault()

        const modal = this.element.querySelector('.modal-nav-search')

        modal.classList.remove('opacity-0', 'pointer-events-none')
        document.body.classList.add('modal-nav-search-active')

        document.addEventListener('keydown', this.handleEscape)
    }

    close(event) {
        if (event) event.preventDefault()

        const modal = this.element.querySelector('.modal-nav-search')

        modal.classList.add('opacity-0', 'pointer-events-none')
        document.body.classList.remove('modal-nav-search-active')

        document.removeEventListener('keydown', this.handleEscape)
    }

    handleEscape(event) {
        if (event.key === 'Escape') {
            this.close()
        }
    }
}