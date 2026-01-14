import { Controller } from '@hotwired/stimulus';

export default class FavoriteDishes extends Controller {
  
    static values = {
        url: String,
        urlAdd: String,
        urlRemove: String
    }

    static targets = ['content'];

    connect() {
        // console.log('connect controller favorite dishes');
    }

    async toggle(event) {

        const btn = event.currentTarget;
        const dishId = btn.dataset.dishId;

        btn.querySelector('.heart').classList.toggle('hidden');
        btn.querySelector('.heart-fill').classList.toggle('hidden');

        
        // console.log(`${this.urlShowAlertValue}/${dishId}`);
        // const responseShowAlert = await fetch(`${this.urlShowAlertValue}/${dishId}`);
        // document.getElementById('alert-ajax').innerHTML = await responseShowAlert.text();
        // document.getElementById('alert-ajax').classList.replace('hidden', 'flex');

        const params = new URLSearchParams({
            dish_id: dishId
        });

        if(btn.querySelector('.heart').classList.contains('hidden')) {
            const response = await fetch(`${this.urlAddValue}?${params.toString()}`);
            document.getElementById('alert-ajax').innerHTML = await response.text();
        }else{
            console.log(`${this.urlRemoveValue}?${params.toString()}`);
            const response = await fetch(`${this.urlRemoveValue}?${params.toString()}`);
            document.getElementById('alert-ajax').innerHTML = await response.text();
        }

        /* Message alerte */
        document.getElementById('alert-ajax').classList.replace('hidden', 'flex');

    }

    async remove() {
        console.log('remove favorite dish');
        console.log(this.urlRemoveValue);

        const btn = event.currentTarget;
        const dishId = btn.dataset.dishId;
        const target = this.hasContentTarget ? this.contentTarget : this.element;

        const params = new URLSearchParams({
            dish_id: dishId
        });

        await fetch(`${this.urlRemoveValue}?${params.toString()}`);

        // document.getElementById('alert-ajax').innerHTML = await response.text();

        console.log(this.urlValue);

        fetch(this.urlValue)
            .then((response) => {
                return response.text()
            })
            .then((newContent) => {
                target.innerHTML = newContent;
        });
    }
    
}