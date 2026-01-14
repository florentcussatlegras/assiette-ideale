import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        url: String,
        urlRemoveItem: String,
        urlReload: String
    };

    static targets = ['quantity', 'btnToggleEdit', 'newQuantity', 'newUnitMeasure', 'modifyQuantityToken', 'selectFood', 'selectAllFoods', 'btnRemoveFoods'];

    toggleFormEdit(event) {
        const quantity = this.quantityTarget.querySelector('.quantity-food');
        const formQuantity = this.quantityTarget.querySelector('.form-quantity-food');

        if(quantity.classList.contains('hidden')) {
            this.btnToggleEditTarget.classList.remove('hidden');
            quantity.classList.remove('hidden');
            formQuantity.classList.add('hidden');
        }else{
            this.btnToggleEditTarget.classList.add('hidden');
            quantity.classList.add('hidden');
            formQuantity.classList.remove('hidden');
        }
    }

    async editQuantity() {
        const newQuantity = this.newQuantityTarget.value;
        const newUnitMeasure = this.newUnitMeasureTarget.value;
        const token = this.modifyQuantityTokenTarget.value;

        const params = new URLSearchParams({
            'new_quantity': newQuantity,
            'new_unit_measure': newUnitMeasure,
            '_token': token,
            'ajax': 1
        });
        console.log(`${this.urlValue}?${params.toString()}`);
        const response = await fetch(`${this.urlValue}?${params.toString()}`);
        this.quantityTarget.innerHTML = await response.text();
        this.btnToggleEditTarget.classList.remove('hidden');
    }
}