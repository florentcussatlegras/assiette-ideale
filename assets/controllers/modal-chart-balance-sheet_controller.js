import { Controller } from '@hotwired/stimulus';

export default class extends Controller {

    static values = {
        urlModalChartFgp: String,
    }
    static targets = ['content', 'background', 'container'];

    async show(event) {

        document.getElementById('modal-chart-balance-sheet').classList.replace('hidden', 'flex');

        const params = new URLSearchParams({
            // 'average_fgp': event.currentTarget.dataset.averageFgp
            'average_fgp': event.currentTarget.dataset.averageFgp
        });

        // target.style.opacity = .5;
        // console.log(`${this.urlModalChartFgpValue}?${params.toString()}`);
        const response = await fetch(`${this.urlModalChartFgpValue}?${params.toString()}`);
        this.contentTarget.innerHTML = await response.text();
        // target.style.opacity = 1;

    }

    hide() {
        document.getElementById('modal-chart-balance-sheet').classList.replace('flex', 'hidden');
    }

    // async refreshContent(event) {
    //     const target = this.hasContentTarget ? this.contentTarget : this.element;

    //     target.style.opacity = .5;
    //     const response = await fetch(this.urlValue);
    //     target.innerHTML = await response.text();
    //     target.style.opacity = 1;
    // }

}