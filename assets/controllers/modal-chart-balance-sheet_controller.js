import { Controller } from '@hotwired/stimulus';

export default class extends Controller {

    static values = {
        urlModalChartFgp: String,
    }
    static targets = ['content', 'background', 'container'];

    async show(event) {

        document.getElementById('modal-chart-balance-sheet').classList.replace('hidden', 'flex');

        const params = new URLSearchParams({
            'average_fgp': event.currentTarget.dataset.averageFgp
        });

        const response = await fetch(`${this.urlModalChartFgpValue}?${params.toString()}`);
        this.contentTarget.innerHTML = await response.text();

    }

    hide() {
        document.getElementById('modal-chart-balance-sheet').classList.replace('flex', 'hidden');
    }

}