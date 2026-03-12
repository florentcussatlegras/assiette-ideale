import { Controller } from '@hotwired/stimulus';

/**
 * Stimulus controller chargé de gérer les favoris des utilisateurs (ajout/suppression)
 */
export default class FavoriteDishes extends Controller {
  
    // Valeurs passées depuis le HTML
    static values = {
        url: String,        // URL pour recharger la liste des plats
        urlAdd: String,     // URL pour ajouter un plat aux favoris
        urlRemove: String   // URL pour supprimer un plat des favoris
    }

    // Targets HTML
    static targets = ['content']; // Zone à mettre à jour après suppression

    // ------------------------------------------------------
    // 📌 Toggle favorite (ajout/suppression)
    // ------------------------------------------------------
    async toggle(event) {
        const btn = event.currentTarget;         // Bouton cliqué
        const dishId = btn.dataset.dishId;       // ID du plat à ajouter/retirer

        // Toggle visuel des icônes coeur
        btn.querySelector('.heart').classList.toggle('hidden');
        btn.querySelector('.heart-fill').classList.toggle('hidden');

        // Préparer les paramètres pour la requête
        const params = new URLSearchParams({ dish_id: dishId });

        if(btn.querySelector('.heart').classList.contains('hidden')) {
            // Si le coeur rempli est visible → ajouter aux favoris
            const response = await fetch(`${this.urlAddValue}?${params.toString()}`);
            document.getElementById('alert-ajax').innerHTML = await response.text();
        } else {
            // Si le coeur vide est visible → retirer des favoris
            const response = await fetch(`${this.urlRemoveValue}?${params.toString()}`);
            document.getElementById('alert-ajax').innerHTML = await response.text();
        }

        // Afficher le message d'alerte
        document.getElementById('alert-ajax').classList.replace('hidden', 'flex');
    }

    // ------------------------------------------------------
    // 📌 Supprimer un plat des favoris et recharger le contenu
    // ------------------------------------------------------
    async remove(event) {
        console.log('remove favorite dish');

        const btn = event.currentTarget;                 // Bouton cliqué
        const dishId = btn.dataset.dishId;              // ID du plat à supprimer
        const target = this.hasContentTarget ? this.contentTarget : this.element; // Zone à mettre à jour

        const params = new URLSearchParams({ dish_id: dishId });

        // Supprimer le plat via fetch
        await fetch(`${this.urlRemoveValue}?${params.toString()}`);

        // Recharger la liste des plats depuis l'URL principale
        fetch(this.urlValue)
            .then((response) => response.text())
            .then((newContent) => {
                target.innerHTML = newContent;        // Met à jour le contenu
            });
    }
}