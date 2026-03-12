import { Controller } from '@hotwired/stimulus';
import noUiSlider from 'nouislider';
import 'nouislider/dist/nouislider.css';

/**
 * Stimulus controller chargé de gérer le critère de filtre des calories basé sur un slider.
 */
export default class extends Controller {

    /**
     * Références vers les éléments UI affichant les valeurs du slider
     * et le conteneur du slider lui-même.
     */
    static targets = ['slider', 'min', 'max'];

    /**
     * Valeurs configurables injectées depuis le template Twig
     * (bornes minimales et maximales du filtre).
     */
    static values = {
        min: Number,
        max: Number,
    };

    /**
     * Lifecycle Stimulus.
     * Initialise le slider et synchronise son état initial avec le formulaire.
     */
    connect() {

        // Récupération des champs du formulaire associés au filtre calories
        const minInput = this.element.querySelector('input[name$="[caloriesMin]"]');
        const maxInput = this.element.querySelector('input[name$="[caloriesMax]"]');

        /**
         * Initialisation du slider.
         * Les valeurs de départ proviennent soit :
         * - du formulaire (filtre déjà appliqué)
         * - des bornes par défaut.
         */
        noUiSlider.create(this.sliderTarget, {
            start: [minInput.value || this.minValue, maxInput.value || this.maxValue],
            connect: true,
            step: 10,
            range: { min: this.minValue, max: this.maxValue },
        });

        /**
         * Synchronisation initiale de l'affichage UI
         * avec les valeurs internes du slider.
         */
        const [initialMin, initialMax] = this.sliderTarget.noUiSlider
            .get()
            .map(v => Math.round(v));

        this.minTarget.textContent = initialMin;
        this.maxTarget.textContent = initialMax;

        /**
         * Debounce pour éviter de déclencher trop d'événements
         * lors du déplacement du slider.
         *
         * Cela évite des rafraîchissements excessifs
         * si le filtre déclenche une requête AJAX.
         */
        let timeout;

        this.sliderTarget.noUiSlider.on('update', values => {

            const min = Math.round(values[0]);
            const max = Math.round(values[1]);

            // Synchronisation avec les champs du formulaire
            minInput.value = min;
            maxInput.value = max;

            // Mise à jour de l'affichage des valeurs
            this.minTarget.textContent = min;
            this.maxTarget.textContent = max;

            // Debounce pour limiter la fréquence de dispatch
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                this.dispatch('change', {
                    detail: { min, max },
                    bubbles: true
                });
            }, 200);
        });
    }
}