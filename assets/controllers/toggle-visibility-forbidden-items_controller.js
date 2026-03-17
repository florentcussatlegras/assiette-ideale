import { Controller } from "@hotwired/stimulus";

export default class extends Controller {

    // Déclaration des values (données passées depuis le HTML)
    static values = {
        url: String
    };

    // Méthode appelée lors d’un toggle (ex: checkbox)
    toggle(event) {

        // Récupère la valeur checked (true / false)
        const value = event.target.checked;

        // Envoie une requête au serveur avec la valeur dans l'URL
        fetch(`${this.urlValue}/${value}`, {
            method: "GET"
        });
    }

}