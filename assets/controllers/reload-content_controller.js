import { Controller } from '@hotwired/stimulus';
import { useDebounce } from 'stimulus-use';
import Swal from 'sweetalert2';

/**
 * 🔹 Stimulus controller chargé de gérer :
 * - Filtrage des aliments par groupe, type, gluten/lactose, autorisation
 * - Recherche et pagination
 * - Ajout / suppression d’items dans le repas
 * - Mise à jour dynamique du contenu avec AJAX
 */
export default class extends Controller {

    // ===============================
    // 🔹 Valeurs injectées depuis HTML / backend
    // ===============================
    static values = {
        urlList: String,                 // URL pour récupérer la liste filtrée des plats
        urlAddItem: String,              // URL pour ajouter un item à un repas
        urlRemoveItem: String,           // URL pour supprimer un item d’un repas
        urlReloadMeals: String,          // URL pour recharger la liste complète des repas
        urlReloadEnergyLabel: String,    // URL pour recalculer l’énergie totale du repas
        typeDish: String,                // Type de plat sélectionné (entrée, plat, dessert, etc.)
        page: Number                     // Numéro de page actuel pour la pagination
    }

    // ===============================
    // 🔹 Cibles DOM
    // ===============================
    static targets = [
        'content', 'loader', 'loadMore', 'search', 'foodGroup', 'typeDish', 
        'typeItem', 'lastResults', 'selectAllFoodGroup', 'deselectAllFoodGroup', 
        'clearButton', 'chooseButton'
    ];

    // Debounce automatique sur la recherche pour limiter les appels AJAX
    static debounces = ['onSearchInput']; 

    // ===============================
    // 🔹 Propriétés internes
    // ===============================
    selectedFoodGroupId = [];    // IDs des groupes alimentaires sélectionnés
    typeItemSelected = [];       // Types d’items sélectionnés (par exemple : légume, viande)
    freeGluten = 0;              // Filtre sans gluten (0 = non, 1 = oui)
    freeLactose = 0;             // Filtre sans lactose (0 = non, 1 = oui)
    onlyAllowed = 0;             // Filtre “seulement aliments autorisés”
    pageValue = 0;               // Pagination actuelle
    allFgUnchecked = false;      // Tous les groupes alimentaires sont décochés ?

    // ===============================
    // 🔹 Méthode d'initialisation
    // ===============================
    connect() {
        useDebounce(this); // Active le debounce pour onSearchInput

        // Initialisation : récupérer les groupes alimentaires déjà sélectionnés
        this.foodGroupTargets.forEach(el => {
            if(el.classList.contains('selected')) {
                this.selectedFoodGroupId.push(el.dataset.foodGroupId);
            }
        });

        // Initialisation : récupérer les types d’items déjà sélectionnés
        this.typeItemSelected = this.typeItemTargets
            .filter(el => el.classList.contains('selected'))
            .map(el => el.dataset.typeItem);

        // Détecter si les filtres gluten/lactose sont déjà activés
        const glutenBtn = this.element.querySelector('[data-action*="onSelectGlutenFood"]');
        this.freeGluten = glutenBtn?.classList.contains('selected') ? 1 : 0;

        const lactoseBtn = this.element.querySelector('[data-action*="onSelectLactoseFood"]');
        this.freeLactose = lactoseBtn?.classList.contains('selected') ? 1 : 0;
    }

    // ===============================
    // 🔹 Gestion recherche avec debounce
    // ===============================

    /**
     * Déclenché à chaque saisie dans la barre de recherche
     * Utilise debounce pour limiter les appels AJAX
     */
    onSearchInput() {
        this.pageValue = 0; // Repartir de la première page
        this.toggleClear(); // Affiche ou cache le bouton clear
        this.refreshContent(); // Recharge le contenu filtré
    }

    // Affiche ou cache le bouton “X” pour vider la recherche
    toggleClear() {
        this.clearButtonTarget.classList.toggle("hidden", this.searchTarget.value.length === 0);
    }

    // Vide la barre de recherche et déclenche la mise à jour
    clearSearch() {
        this.searchTarget.value = '';
        this.toggleClear();
        this.searchTarget.dispatchEvent(new Event('input')); // Déclenche onSearchInput
    }

    // ===============================
    // 🔹 Gestion des classes CSS “selected”
    // ===============================
    /**
     * Active / désactive les classes CSS pour un élément sélectionné
     * Utilisé pour : boutons filtres, groupes alimentaires, types d’items
     */
    toggleClasses(el, selected) {
        el.classList.toggle('selected', selected);
        el.classList.replace(selected ? "text-gray-900" : "text-white", selected ? "text-white" : "text-gray-900");
        el.classList.replace(selected ? "bg-gray-100" : "bg-sky-600", selected ? "bg-sky-600" : "bg-gray-100");
        el.classList.toggle("hover:text-white", selected);
        el.classList.toggle("hover:text-gray-900", !selected);
        el.classList.toggle("hover:bg-sky-600", selected);
        el.classList.toggle("hover:bg-gray-900", !selected);
    }

    // ===============================
    // 🔹 Gestion des filtres
    // ===============================

    // Méthodes déclenchées par les boutons HTML
    onSelectFoodGroup(event) { this.toggleFoodGroup(event.currentTarget); }
    onSelectTypeDish(event) { this.toggleTypeDish(event.currentTarget); }
    onSelectTypeItem(event) { this.toggleTypeItem(event.currentTarget); }
    onSelectGlutenFood(event) { this.toggleFilter(event.currentTarget, 'freeGluten'); }
    onSelectLactoseFood(event) { this.toggleFilter(event.currentTarget, 'freeLactose'); }
    onToggleOnlyAllowed(event) { this.toggleFilter(event.currentTarget, 'onlyAllowed'); }

    // Toggle un groupe alimentaire (ajout / suppression de selectedFoodGroupId)
    toggleFoodGroup(el) {
        const id = el.dataset.foodGroupId;
        const selected = !el.classList.contains('selected');
        this.toggleClasses(el, selected);

        if(selected) this.selectedFoodGroupId.push(id);
        else this.selectedFoodGroupId = this.selectedFoodGroupId.filter(fg => fg !== id);

        this.allFgUnchecked = this.selectedFoodGroupId.length === 0;

        // Mise à jour visuelle des boutons “Tout sélectionner / Tout décocher”
        this.updateBtnAllFgpCheckedClasses(this.selectedFoodGroupId.length === this.foodGroupTargets.length);
        this.updateBtnAllFgpUnCheckedClasses(this.allFgUnchecked);

        this.pageValue = 0;
        this.refreshContent(); // Rechargement AJAX
    }

    // Toggle le type de plat sélectionné
    toggleTypeDish(el) {
        this.typeDishValue = el.dataset.typeDish;
        this.typeDishTargets.forEach(d => this.toggleClasses(d, d.dataset.typeDish === this.typeDishValue));
        this.pageValue = 0;
        this.refreshContent();
    }

    // Toggle type d’item (légume, viande, etc.)
    toggleTypeItem(el) {
        el.classList.toggle('selected');
        this.typeItemSelected = this.typeItemTargets
            .filter(e => e.classList.contains('selected'))
            .map(e => e.dataset.typeItem);

        // Si tous décochés → on coche tous automatiquement
        if(this.typeItemSelected.length === 0) this.typeItemTargets.forEach(e => e.classList.add('selected'));
        this.typeItemSelected = this.typeItemTargets
            .filter(e => e.classList.contains('selected'))
            .map(e => e.dataset.typeItem);

        this.pageValue = 0;
        this.refreshContent();
    }

    // Toggle les filtres gluten, lactose, onlyAllowed
    toggleFilter(el, prop) {
        el.classList.toggle('selected');
        this[prop] = el.classList.contains('selected') ? 1 : 0;
        this.pageValue = 0;
        this.refreshContent();
    }

    // ===============================
    // 🔹 Sélection / désélection tous les groupes alimentaires
    // ===============================
    onSelectAllFoodGroup() { this.selectAllFoodGroups(true); }
    onDeselectAllFoodGroup() { this.selectAllFoodGroups(false); }

    selectAllFoodGroups(select) {
        this.foodGroupTargets.forEach(el => this.toggleClasses(el, select));
        this.selectedFoodGroupId = select ? this.foodGroupTargets.map(el => el.dataset.foodGroupId) : [];
        this.updateBtnAllFgpCheckedClasses(select);
        this.updateBtnAllFgpUnCheckedClasses(!select);
        this.allFgUnchecked = !select;
        this.pageValue = 0;
        this.refreshContent();
    }

    // Mise à jour visuelle des boutons “Tout sélectionner / Tout décocher”
    updateBtnAllFgpCheckedClasses(active) { this.toggleClasses(this.selectAllFoodGroupTarget, active); }
    updateBtnAllFgpUnCheckedClasses(active) { this.toggleClasses(this.deselectAllFoodGroupTarget, active); }

    // ===============================
    // 🔹 Pagination / Load More
    // ===============================
    onAddItem() { this.pageValue = 0; this.refreshContent(); }
    onRemoveItem() { this.pageValue = 0; this.refreshContent(); }
    onLoadMore() { this.pageValue++; this.refreshContent(); }

    // ===============================
    // 🔹 Ajout / suppression d’items via AJAX
    // ===============================

    async addItem(event) {
        const btn = event.currentTarget;
        const id = btn.dataset.itemId;
        const quantityEl = document.querySelector(".quantity-" + id);
        const quantity = quantityEl?.value || 0;

        if(quantity <= 0) {
            Swal.fire({title:"Attention!", text:"Veuillez saisir une quantité valide", icon:"warning", confirmButtonColor:"#0284c7"});
            return;
        }

        const unitMeasureId = document.querySelector(".unitMeasure-" + id)?.value || '';
        const params = new URLSearchParams({
            id, type: btn.dataset.itemType, rankMeal: btn.dataset.rankMeal,
            rankDish: btn.dataset.rankDish, quantity, unitMeasure: unitMeasureId, ajax:1
        });

        await fetch(`${this.urlAddItemValue}?${params.toString()}`);
        this.reloadMeals(); // Recharge la liste des repas
    }

    async removeItem(event) {
        const btn = event.currentTarget;
        const params = new URLSearchParams({rankMeal: btn.dataset.rankMeal, rankDish: btn.dataset.rankDish, ajax:1});
        await fetch(`${this.urlRemoveItemValue}?${params.toString()}`);
        this.reloadMeals();
    }

    // Recharge la liste complète des repas depuis le serveur
    async reloadMeals() {
        const params = new URLSearchParams({ajax:1});
        const url = `${this.urlReloadMealsValue}?${params.toString()}`;
        const response = await fetch(url);
        const html = await response.text();
        document.getElementById('meals-day').innerHTML = html;
        fetch(this.urlRemoveItemValue); // Vide la liste des items pré-sélectionnés
    }

    // ===============================
    // 🔹 Rafraîchissement AJAX du contenu filtré
    // ===============================
    async refreshContent() {
        if(this.hasLoaderTarget) this.loaderTarget.classList.remove('hidden'); // Affiche loader
        if(this.hasLoadMoreTarget) this.loadMoreTarget.classList.add('hidden');  // Cache bouton “load more” temporairement

        const target = this.hasContentTarget ? this.contentTarget : this.element;
        const fg = this.allFgUnchecked ? "none" : this.selectedFoodGroupId;

        const params = new URLSearchParams({
            q: this.searchTarget.value,
            fg,
            type: this.typeDishValue,
            rankMeal: document.getElementById('slideOverRankMeal')?.value || null,
            page: this.pageValue,
            updateDish: document.getElementById('slideOverUpdateDish')?.value || null,
            typeItem: this.typeItemSelected,
            freeGluten: this.freeGluten,
            freeLactose: this.freeLactose,
            onlyAllowed: this.onlyAllowed,
            ajax: 1
        });

        try {
            const response = await fetch(`${this.urlListValue}?${params.toString()}`);
            const html = await response.text();

            // Si page > 0 → ajouter contenu à la fin
            if(this.pageValue > 0) {
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = html;
                while(tempDiv.firstChild) target.appendChild(tempDiv.firstChild);
            } else {
                // Page 0 → remplacer tout le contenu
                target.innerHTML = html;
            }

            if(this.hasLoaderTarget) this.loaderTarget.classList.add('hidden');
            target.classList.remove('hidden');
            if(this.hasLoadMoreTarget && this.lastResultsTargets.reverse()[0].value != 1) {
                this.loadMoreTarget.classList.remove('hidden');
            }
        } catch (error) {
            console.error("Erreur refresh:", error);
            target.innerHTML = "<p class='text-red-600'>Erreur lors du chargement.</p>";
            if(this.hasLoaderTarget) this.loaderTarget.classList.add('hidden');
        }
    }

}