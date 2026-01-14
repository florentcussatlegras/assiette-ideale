import { Controller } from '@hotwired/stimulus';
import Swal from 'sweetalert2';

export default class extends Controller {

    static values = {
        urlRemoveItem: String,
        urlReload: String
    }

    onRemoveItem(event) {
        Swal.fire({
            title: 'Confirmation',
            text: 'Etes-vous sûr de vouloir supprimer ce plat?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Oui',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                return this.removeItem()
            }
        });
    }

    async removeItem() {
        await(fetch(this.urlRemoveItemValue));
        console.log(this.urlReloadValue);
        const response = await fetch(this.urlReloadValue);
        document.getElementById('meals-day').innerHTML = await response.text();
    }
}