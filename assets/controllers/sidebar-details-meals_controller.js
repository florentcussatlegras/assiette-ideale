import { Controller } from '@hotwired/stimulus';

export default class extends Controller {   

    date = null;

    static values = {
        url: String
    }

    static targets = ['content'];

    connect() {
        const menuDropdowns = document.querySelectorAll('.menuDropdown');
        menuDropdowns.forEach((el) => {
            const dimensions = el.parentNode.getBoundingClientRect();
            el.height = dimensions.height;
        });
    }

    toggleSlideover() {
        document.getElementById('slideover-container').classList.toggle('invisible');
        document.getElementById('slideover-bg').classList.toggle('opacity-0');
        document.getElementById('slideover-bg').classList.toggle('opacity-50');
        document.getElementById('slideover').classList.toggle('translate-y-full');
    }

    setMeals(event) {
        this.date = event.currentTarget.dataset.date;
        this.loadMeals();
    }

    async loadMeals() {
        const params = new URLSearchParams({
            'date': this.date,
            'ajax': 1
        });

        // console.log(`${this.urlValue}?${params.toString()}`);
        const response = await fetch(`${this.urlValue}?${params.toString()}`);
        this.contentTarget.innerHTML = await response.text();
    }

    openMenuDropdown(event) {
        event.stopPropagation();
        const menuDropdown =document.getElementById(event.currentTarget.dataset.menuDropdown);
        menuDropdown.classList.remove('hidden');
    }

    closeMenuDropdown(event) {
        const menuDropdown =document.getElementById(event.currentTarget.dataset.menuDropdown);
        menuDropdown.classList.add('hidden');
    }

}