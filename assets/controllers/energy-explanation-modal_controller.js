import { Controller } from '@hotwired/stimulus';

export default class extends Controller {

    static targets = ["modal", "content", "loader"]
    static values = {
        url: String
    }

    connect() {
        console.log('connect energy explanations modal');
    }

    async open(event) {
        event.preventDefault();

        this.modalTarget.classList.remove('hidden');
        this.modalTarget.classList.add('flex');

        document.body.style.overflow = 'hidden';

        this.loaderTarget.classList.remove('hidden');
        this.loaderTarget.classList.add('flex');

        this.contentTarget.innerHTML = "";

        try {
            const response = await fetch(this.urlValue, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error('Erreur réseau');
            }

            const html = await response.text();

            this.loaderTarget.classList.add('hidden');
            this.loaderTarget.classList.remove('flex');

            this.contentTarget.innerHTML = html;

        } catch (error) {
            this.loaderTarget.classList.add('hidden');

            this.contentTarget.innerHTML = `
                <div class="text-red-600 text-center py-6">
                    Une erreur est survenue lors du chargement.
                </div>
            `;

            console.log(error);
        }
    }

    close() {
        this.modalTarget.classList.add('hidden');
        this.modalTarget.classList.remove('flex');

        document.body.style.overflow = 'auto';
    }

}