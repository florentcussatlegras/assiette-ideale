import { Controller } from '@hotwired/stimulus';
import noUiSlider from 'nouislider';
import 'nouislider/dist/nouislider.css';

export default class extends Controller {
    static targets = ['slider', 'min', 'max'];
    static values = {
        min: Number,
        max: Number,
    };

    connect() {
        const minInput = this.element.querySelector('input[name$="[caloriesMin]"]');
        const maxInput = this.element.querySelector('input[name$="[caloriesMax]"]');

        // Initialisation du slider
        noUiSlider.create(this.sliderTarget, {
            start: [minInput.value || this.minValue, maxInput.value || this.maxValue],
            connect: true,
            step: 10,
            range: { min: this.minValue, max: this.maxValue },
        });

        // Affiche les valeurs dès le chargement
        const [initialMin, initialMax] = this.sliderTarget.noUiSlider.get().map(v => Math.round(v));
        this.minTarget.textContent = initialMin;
        this.maxTarget.textContent = initialMax;

        // Debounce pour éviter le bégaiement
        let timeout;
        this.sliderTarget.noUiSlider.on('update', values => {
            const min = Math.round(values[0]);
            const max = Math.round(values[1]);

            minInput.value = min;
            maxInput.value = max;

            this.minTarget.textContent = min;
            this.maxTarget.textContent = max;

            clearTimeout(timeout);
            timeout = setTimeout(() => {
                this.dispatch('change', { detail: { min, max }, bubbles: true });
            }, 200);
        });
    }
}

