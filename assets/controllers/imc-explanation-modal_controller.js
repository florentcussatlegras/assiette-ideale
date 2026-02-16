import { Controller } from '@hotwired/stimulus';

export default class extends Controller {

    static targets = ["modal", "content"]
    static values = {
        url: String
    }

    connect() {
        console.log('connect icm explanations modal');
    }

    open(event) {
        event.preventDefault();

        this.modalTarget.classList.remove('hidden');
        this.modalTarget.classList.add('flex');
    }

    close() {
        this.modalTarget.classList.add('hidden');
        this.modalTarget.classList.remove('flex');

        document.body.style.overflow = 'auto';
    }

}