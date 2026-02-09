import { Controller } from '@hotwired/stimulus';

export default class extends Controller {

    static targets = ["modal", "content"]
    static values = {
        url: String
    }

    connect() {
        console.log('connct weight chart modal');
    }

    open(event) {
        event.preventDefault();

        this.modalTarget.classList.remove('hidden');
        this.modalTarget.classList.add('flex');

        document.body.style.overflow = 'hidden';

        this.loadChart();
    }

    close() {
        this.modalTarget.classList.add('hidden');
        this.modalTarget.classList.remove('flex');

        document.body.style.overflow = 'auto';
    }

    loadChart() {
        this.contentTarget.innerHTML =
            '<div class="text-center py-6">Chargement...</div>';

        fetch(this.urlValue, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.text())
        .then(html => {
            this.contentTarget.innerHTML = html;
        })
        .catch(error => {
            this.contentTarget.innerHTML =
                '<div class="text-red-600">Erreur lors du chargement du graphique</div>';
        });
    }
}
