import { Controller } from '@hotwired/stimulus';
import { useDispatch } from 'stimulus-use';
import Swal from 'sweetalert2';

/**
 * Controller Stimulus chargé de gérer les interactions liées aux items
 * alimentaires dans un repas.
 *
 * Fonctionnalités principales :
 * - ajout et mise à jour d’items
 * - suppression d’items
 * - gestion d’une liste d’items présélectionnés
 * - recalcul de l’énergie totale
 * - rechargement dynamique des repas via AJAX
 * - ouverture / fermeture du slideover
 */
export default class extends Controller {

    /**
     * URLs injectées depuis Twig.
     */
    static values = {
        urlReloadMeals: String,
        urlAddItem: String,
        urlRemoveItem: String,
        urlRemovePreselectedItems: String,
        urlReloadEnergyLabel: String
    }

    connect() {
        /**
         * Active la fonctionnalité dispatch fournie par stimulus-use.
         */
        useDispatch(this);
    }

    /**
     * Vérifie que la quantité saisie est correcte.
     */
    validateQuantity(quantity) {

        if (!quantity || quantity <= 0 || quantity === 'Aucune') {

            Swal.fire({
                title: "Attention!",
                text: "Veuillez saisir une quantité valide",
                icon: "warning",
                confirmButtonColor: "#0284c7"
            });

            return false;
        }

        return true;
    }

    /**
     * Récupère les données saisies dans l'interface
     * pour un item spécifique.
     */
    getItemData(id) {

        // Sélectionne l'input contenant la quantité pour cet item
        const quantity = document.querySelector(`.quantity-${id}`)?.value;

        // Sélectionne le select contenant l'unité de mesure
        const unitMeasureId = document.querySelector(`.unitMeasure-${id}`)?.value;

        // Retourne les deux valeurs sous forme d'objet
        return { quantity, unitMeasureId };
    }

    /**
     * Recalcule l'énergie totale affichée dans la sidebar.
     */
    async updateEnergy() {

        // Appel AJAX vers la route de recalcul d'énergie
        const response = await fetch(this.urlReloadEnergyLabelValue);

        // Récupère le HTML retourné par le serveur
        const html = await response.text();

        // Remplace le contenu actuel de l'affichage énergie
        document.getElementById('sidebarTotalEnergy').innerHTML = html;
    }

    /**
     * Méthode utilitaire qui construit une requête fetch
     * avec des paramètres sous forme de query string.
     */
    async fetchWithParams(url, params) {

        // Construit l'URL finale avec les paramètres
        return fetch(`${url}?${params.toString()}`);
    }

    /**
     * Ajoute un item dans un repas.
     */
    async addItem(event) {

        // Bouton cliqué
        const btn = event.currentTarget;

        // ID de l'item récupéré depuis le data attribute
        const id = btn.dataset.itemId;

        // Récupération des données saisies dans l'interface
        const { quantity, unitMeasureId } = this.getItemData(id);

        // Validation de la quantité
        if (!this.validateQuantity(quantity)) return;

        // Construction des paramètres envoyés au backend
        const params = new URLSearchParams({
            id,
            type: btn.dataset.itemType,
            rankMeal: btn.dataset.rankMeal,
            rankDish: btn.dataset.rankDish,
            quantity,
            unitMeasure: unitMeasureId,
            ajax: 1
        });

        // Appel AJAX pour enregistrer l'item
        await this.fetchWithParams(this.urlAddItemValue, params);

        // Fermeture du panneau latéral
        this.toggleSlideover();

        // Rechargement de l'affichage des repas
        this.reloadMeals();
    }

    /**
     * Met à jour la quantité d'un item déjà existant.
     */
    async updateItem(event) {

        const btn = event.currentTarget;
        const id = btn.dataset.itemId;

        const { quantity, unitMeasureId } = this.getItemData(id);

        if (!this.validateQuantity(quantity)) return;

        const params = new URLSearchParams({
            id,
            type: btn.dataset.itemType,
            rankMeal: btn.dataset.rankMeal,
            rankDish: btn.dataset.rankDish,
            quantity,
            unitMeasure: unitMeasureId,
            ajax: 1
        });

        await this.fetchWithParams(this.urlAddItemValue, params);

        this.toggleSlideover();
        this.reloadMeals();
    }

    /**
     * Ajoute un item dans la liste des items présélectionnés.
     * Cette liste sert de prévisualisation dans le slideover.
     */
    async addPreSelect(btn) {

        const id = btn.dataset.itemId;

        const { quantity, unitMeasureId } = this.getItemData(id);

        // Élément contenant les informations d'alerte nutritionnelle
        const alert = document.querySelector(`.alert-${id}`);

        const params = new URLSearchParams({
            rankMeal: btn.dataset.rankMeal,
            rankDish: btn.dataset.rankDish,
            quantity,
            unitMeasure: unitMeasureId,
            alertColor: alert.dataset.alertColor,
            alertText: alert.dataset.alertText,
            ajax: 1
        });

        const response = await this.fetchWithParams(btn.dataset.urlAddPreselectItem, params);

        // HTML du nouvel item présélectionné
        const html = await response.text();

        // Ajout du HTML à la liste existante
        document.getElementById("listPreselectedItem").innerHTML += html;

        // Animation d'apparition de l'élément
        document
            .getElementById(`itemPreselected-${id}`)
            .classList.replace('fade-enter-from', 'fade-enter-to');

        // Recalcul de l'énergie totale
        this.updateEnergy();
    }

    /**
     * Réinitialise la quantité saisie pour un item.
     */
    resetSelection(event) {

        const id = event.currentTarget.dataset.itemId;
        const typeItem = event.currentTarget.dataset.itemType;

        const el = document.querySelector(`.quantity-${id}`);
        if (!el) return;

        // Certains items utilisent une valeur vide,
        // d'autres utilisent "Aucune"
        el.value = typeItem === 'Food' ? '' : 'Aucune';

        // Déclenche un événement input pour notifier l'interface
        el.dispatchEvent(new InputEvent('input', { bubbles: true }));
    }

    /**
     * Supprime un item spécifique.
     */
    async removeItem(event) {

        const btn = event.currentTarget;

        const params = new URLSearchParams({
            rankMeal: btn.dataset.rankMeal,
            rankDish: btn.dataset.rankDish,
            ajax: 1
        });

        // Suppression côté serveur
        await this.fetchWithParams(this.urlRemoveItemValue, params);

        // Mise à jour de la liste des présélections
        await this.removePreselect(btn);

        // Déclenche un événement custom
        this.dispatch('async:remove-item');
    }

    /**
     * Supprime tous les items d'un repas.
     */
    async removeAllItem() {

        const params = new URLSearchParams({
            rankMeal: document.getElementById('slideOverRankMeal').value,
            rankDish: document.getElementById('slideOverRankDish').value,
            fromRankDishToTheEnd: true,
            ajax: 1
        });

        await this.fetchWithParams(this.urlRemoveItemValue, params);

        this.removeAllPreselect();
    }

    /**
     * Supprime un item de la liste des présélections.
     */
    async removePreselect(btn) {

        const params = new URLSearchParams({
            rankMeal: btn.dataset.rankMeal,
            rankDish: btn.dataset.rankDish
        });

        const response = await this.fetchWithParams(btn.dataset.urlRemovePreselectItem, params);

        const html = await response.text();

        // Remplace la liste des présélections
        document.getElementById("listPreselectedItem").innerHTML = html;

        this.updateEnergy();
    }

    /**
     * Supprime tous les items présélectionnés.
     */
    async removeAllPreselect() {

        await fetch(this.urlRemovePreselectedItemsValue);

        document.getElementById("listPreselectedItem").innerHTML = '';

        this.updateEnergy();
    }

    /**
     * Recharge l'affichage complet des repas.
     */
    async reloadMeals() {

        const params = new URLSearchParams({ ajax: 1 });

        const response = await this.fetchWithParams(this.urlReloadMealsValue, params);

        const html = await response.text();

        // Remplacement du contenu de la liste des repas
        document.getElementById('meals-day').innerHTML = html;

        // Nettoyage des présélections pour éviter des incohérences
        fetch(this.urlRemovePreselectedItemsValue);
    }

    /**
     * Ouvre ou ferme le slideover (panneau latéral).
     * Les animations sont gérées via des classes Tailwind.
     */
    toggleSlideover() {

        document.getElementById('slideover-container').classList.toggle('invisible');
        document.getElementById('slideover-bg').classList.toggle('opacity-0');
        document.getElementById('slideover-bg').classList.toggle('opacity-50');
        document.getElementById('slideover').classList.toggle('translate-y-full');
    }

}