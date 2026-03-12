import { Controller } from '@hotwired/stimulus';

/**
 * Stimulus controller chargé de gérer l'édition des quantités dans la liste d'aliments du formulaire des recettes
 */
export default class extends Controller {
    // Valeurs passées depuis le HTML
    static values = {
        url: String,            // URL pour enregistrer la nouvelle quantité
        urlRemoveItem: String,  // URL pour supprimer un item (non utilisé ici mais disponible)
        urlReload: String       // URL pour recharger la liste (non utilisé ici mais disponible)
    };

    // Targets HTML
    static targets = [
        'quantity',             // Conteneur affichant la quantité actuelle
        'btnToggleEdit',        // Bouton pour basculer l'édition
        'newQuantity',          // Input pour la nouvelle quantité
        'newUnitMeasure',       // Select pour la nouvelle unité
        'modifyQuantityToken',  // Token CSRF pour la requête
        'selectFood',           // Input de sélection d'un plat (non utilisé ici)
        'selectAllFoods',       // Input de sélection globale (non utilisé ici)
        'btnRemoveFoods'        // Bouton de suppression (non utilisé ici)
    ];

    // ------------------------------------------------------
    // 📌 Basculer l'affichage du formulaire d'édition
    // ------------------------------------------------------
    toggleFormEdit(event) {
        const quantity = this.quantityTarget.querySelector('.quantity-food'); // affichage classique
        const formQuantity = this.quantityTarget.querySelector('.form-quantity-food'); // formulaire d'édition

        if(quantity.classList.contains('hidden')) {
            // Si le formulaire est déjà visible, revenir à l'affichage normal
            this.btnToggleEditTarget.classList.remove('hidden');
            quantity.classList.remove('hidden');
            formQuantity.classList.add('hidden');
        } else {
            // Sinon, cacher la quantité et montrer le formulaire
            this.btnToggleEditTarget.classList.add('hidden');
            quantity.classList.add('hidden');
            formQuantity.classList.remove('hidden');
        }
    }

    // ------------------------------------------------------
    // 📌 Envoyer la nouvelle quantité via AJAX et mettre à jour le DOM
    // ------------------------------------------------------
    async editQuantity() {
        const newQuantity = this.newQuantityTarget.value;        // Récupère la nouvelle valeur
        const newUnitMeasure = this.newUnitMeasureTarget.value;  // Récupère l'unité sélectionnée
        const token = this.modifyQuantityTokenTarget.value;      // Récupère le token CSRF

        // Préparer les paramètres de la requête GET
        const params = new URLSearchParams({
            'new_quantity': newQuantity,
            'new_unit_measure': newUnitMeasure,
            '_token': token,
            'ajax': 1
        });

        console.log(`${this.urlValue}?${params.toString()}`); // Pour debug

        // Envoi de la requête fetch
        const response = await fetch(`${this.urlValue}?${params.toString()}`);

        // Met à jour le contenu du DOM avec la nouvelle quantité renvoyée par le serveur
        this.quantityTarget.innerHTML = await response.text();

        // Affiche à nouveau le bouton pour basculer l'édition
        this.btnToggleEditTarget.classList.remove('hidden');
    }
}