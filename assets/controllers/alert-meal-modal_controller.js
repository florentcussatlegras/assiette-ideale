import { Controller } from '@hotwired/stimulus';

export default class AlertMealModal extends Controller {
  
    static targets = ['content', 'container', 'background', 'loader'];

    connect() {
        console.log('connect alert meal modal controller');
    }

    async showMessages(event) {
        // const url = event.currentTarget.dataset.url;

        // const params = new URLSearchParams({
        //     'showMessages': 1
        // });

        // console.log(`${url}?${params.toString()}`);

        // const response = await fetch(`${url}?${params.toString()}`);
     
        // this.contentTarget.classList.replace('hidden', 'flex');
        
        // this.contentTarget.innerHTML = await response.text();



        const url = event.currentTarget.dataset.url;

        this.loaderTarget.classList.remove('hidden');
        this.contentTarget.classList.add('hidden');

        try {
            const params = new URLSearchParams({ showMessages: 1 });
            const response = await fetch(`${url}?${params.toString()}`);

            const html = await response.text();

          
            this.contentTarget.innerHTML = html;
        } catch (err) {
            console.error('Erreur fetch alert meal modal', err);
            this.contentTarget.innerHTML = '<p class="text-red-500">Impossible de charger le contenu.</p>';
        } finally {
      
            this.loaderTarget.classList.add('hidden');
            this.contentTarget.classList.remove('hidden');
        }

    }

    hideMessages() {
        document.getElementById('alert-meal-modal').classList.replace('flex', 'hidden');
        // document.getElementById('alert-meal-modal').classList.remove('show'); // bootstrap 4
    }
}