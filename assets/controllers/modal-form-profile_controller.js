import { Controller } from '@hotwired/stimulus';

export default class extends Controller {

    static targets = ["content", "modal", "messageSuccess"]

    connect() {
        console.log('connect modal form profile');
        this.element.addEventListener('submit', this.submit.bind(this));
    }

    open(event) {
        event.preventDefault();

        this.modalTarget.classList.remove('hidden');
        this.modalTarget.classList.add('flex');

        document.body.style.overflow = 'hidden';
    }

    close() {
        this.modalTarget.classList.add('hidden');
        this.modalTarget.classList.remove('flex');

        document.body.style.overflow = 'auto';
    }

    closeIfOutside(event) {
        if (event.target === this.modalTarget) {
            this.close();
        }
    }

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
        .catch(error => {
            console.error('Erreur AJAX:', error);
        });
    }

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
