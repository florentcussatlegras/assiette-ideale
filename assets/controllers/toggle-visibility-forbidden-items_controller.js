import { Controller } from "@hotwired/stimulus"

export default class extends Controller {

    static values = {
        url: String
    }

    toggle(event) {
        const value = event.target.checked;
        console.log(`${this.urlValue}/${value}`);
    
        // On envoie la valeur dans l'URL directement
        fetch(`${this.urlValue}/${value}`, {
            method: "GET"
        });
    }

}