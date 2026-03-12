import { Controller } from '@hotwired/stimulus';
import Swal from 'sweetalert2';

/**
 * Stimulus controller chargé de gérer la sélection, l'ajout et la suppression 
 * de repas et de plats dans une journée, avec vérifications et alertes.
 */
export default class extends Controller {

    indexDishesSelected = [];
    indexMealsSelected = [];
    urlRemoveSelection = '';

    static targets = [
        'alertCount', 
        'alertEnergy', 
        'fgpRemaining', 
        'selectDish', 
        'selectAllDishes', 
        'btnRemoveSelection',
        'selectMeal',
        'selectAllMeals',
        'btnRemoveMealSelection'
    ];

    static values = {
        urlReload: String,
        urlAddMeal: String,
        urlRemoveMeal: String,
        lastRankMeal: String,
    }

    connect() {
        // Associer les listeners pour mettre à jour les boutons de suppression
        this.selectMealTargets.forEach(el => el.addEventListener('change', () => this.updateRemoveButton()));
        this.selectAllMealsTargets.forEach(el => el.addEventListener('change', () => this.selectAllMeals({currentTarget: el})));
        
        // Premier check au cas où certaines checkbox sont déjà cochées
        this.updateRemoveButton();
    }
    
    // Supprime tous les repas sélectionnés après confirmation
    removeMeals(event) {
        Swal.fire({
            title: 'Confirmation',
            text: 'Etes-vous sûr de vouloir supprimer tous ces repas?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Oui',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                document.location.href = this.urlRemoveMealValue;
            }
        });
    }

    // Supprime un repas spécifique après confirmation
    onRemoveMeal(event) {
        const rankMeal = event.currentTarget.dataset.rankMeal;
       
        Swal.fire({
            title: 'Confirmation',
            text: 'Etes-vous sûr de vouloir supprimer ce repas?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Oui',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                const params = new URLSearchParams({ 'rankMeal': rankMeal });
                fetch(`${this.urlRemoveMealValue}?${params.toString()}`)
                    .then(() => this.refreshContent());
            }
        });
    }

    // Ajoute un nouveau repas après vérifications
    onAddMeal(event) {
        document.getElementById('loader-new-meal').classList.replace('flex', 'hidden');

        if(this.lastRankMealValue !== "none") {
            const lastMealElement = document.getElementById('meal-' + this.lastRankMealValue);

            // Vérifie que le dernier repas a un type sélectionné
            const types = lastMealElement.getElementsByClassName('type-meal');
            let typeChecked = Array.from(types).some(element => element.checked);
            if(!typeChecked) {
                Swal.fire({
                    title: "Attention!",
                    text: "Vous n'avez pas précisé de type pour votre dernier repas",
                    icon: "warning",
                    customClass: { confirmButton: "text-white bg-sky-600 hover:bg-sky-700 transition duration-300 font-semibold rounded-lg px-4 py-2" },
                    buttonsStyling: false
                });
                return;
            }

            // Vérifie que le dernier repas contient au moins un plat
            const dishes = lastMealElement.getElementsByClassName('row-dish');
            if (dishes.length === 0 ) {
                Swal.fire({
                    title: "Attention!",
                    text: "Vous n'avez pas saisis de plats pour votre dernier repas",
                    icon: "warning",
                    customClass: { confirmButton: "text-white bg-sky-600 hover:bg-sky-700 transition duration-300 font-semibold rounded-lg px-4 py-2" },
                    buttonsStyling: false
                });
                return;
            }
        }

        // Requête pour ajouter le repas
        fetch(this.urlAddMealValue)
            .then(() => this.refreshContent())
            .then(() => document.getElementById('loader-new-meal').classList.replace('hidden', 'flex'));
    }

    // Recharge le contenu des repas via AJAX
    async refreshContent() {
        const params = new URLSearchParams({ 'ajax': 1 });
        const response = await fetch(`${this.urlReloadValue}?${params.toString()}`);
        document.getElementById('meals-day').innerHTML = await response.text();
    }

    // Sauvegarde les repas après vérifications et alertes de déséquilibre
    saveMeals(event) {
        const urlRedirect = event.currentTarget.dataset.url;
        const meals = document.querySelectorAll('.meal');

        // Vérifie que chaque repas a un type
        const displayErrorType = Array.from(meals).some(element => {
            const typeMeals = element.querySelectorAll('.type-meal');
            return !Array.from(typeMeals).some(type => type.checked);
        });

        if(displayErrorType) {
            Swal.fire({ title: "Attention!", text: "Merci d'indiquer un type pour tous vos repas", icon: "warning", confirmButtonColor: "#0284c7" });
            return;
        }

        // Vérifie que tous les repas contiennent des plats
        const noDishesSelected = Array.from(meals).some(element => element.querySelectorAll('.row-dish').length === 0);
        if(noDishesSelected) {
            Swal.fire({ title: "Attention!", text: "Un de vos repas ne contient pas de plats ou d'aliments", icon: "warning", confirmButtonColor: "#0284c7" });
            return;
        }

        // Vérification de l'équilibre des repas
        let wellBalanced = true;
        let text = '<ul><li>Vos repas sont déséquilibrés.</li>';
        if(this.alertCountTarget.value > 0) { wellBalanced = false; text += '<li>Certains aliments ou plats sont déconseillés.</li>'; }
        if(this.fgpRemainingTargets.length > 0) { wellBalanced = false; text += '<li>Certains groupes d\'aliments n\'apparaissent pas dans vos choix.</li>'; }
        if(this.alertEnergyTarget.value == "1") { text += '<li>Votre total énergétique est trop fort. </li>'; }
        if(this.alertEnergyTarget.value == "-1") { text += '<li>Votre total énergétique est trop faible.</li>'; }
        text += '<li>Etes-vous sûr de vouloir les enregistrer?</li></ul>';

        if(!wellBalanced) {
            Swal.fire({
                title: 'Etes-vous sûr?',
                html: text,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Oui',
                showLoaderOnConfirm: true,
                preConfirm: () => { document.location.href = urlRedirect; }
            });
            return;
        }

        document.location.href = urlRedirect;
    }

    // Gestion de la sélection individuelle des plats
    selectDish(event) {
        const rankMeal = event.currentTarget.dataset.rankMeal;
        const displayBtnRemoveSelection = this.selectDishTargets.some(el => el.dataset.rankMeal == rankMeal && el.checked);

        if(displayBtnRemoveSelection) {
            this.btnRemoveSelectionTargets[parseInt(rankMeal)].classList.remove('hidden');
        } else {
            this.btnRemoveSelectionTargets[parseInt(rankMeal)].classList.add('hidden');
            this.selectAllDishesTargets[parseInt(rankMeal)].checked = false;
        }
    }

    // Gestion de la sélection de tous les plats d'un repas
    selectAllDishes(event) {
        const rankMeal = event.currentTarget.dataset.rankMeal;
        const btnRemoveMealSelection = this.btnRemoveSelectionTargets.find(el => el.dataset.rankMeal == rankMeal);

        btnRemoveMealSelection.classList.toggle('hidden', !event.currentTarget.checked);

        this.selectDishTargets.forEach(el => {
            if(el.dataset.rankMeal == rankMeal) el.checked = event.currentTarget.checked;
        });
    }

    // Supprime les plats sélectionnés après confirmation
    onRemoveSelection(event) {
        this.urlRemoveSelection = event.currentTarget.dataset.urlRemoveSelection;
        this.indexDishesSelected = this.selectDishTargets.filter(el => el.checked).map(el => el.value);

        Swal.fire({
            title: 'Confirmation',
            text: 'Etes-vous sûr de vouloir supprimer ces plats?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Oui',
            showLoaderOnConfirm: true,
            preConfirm: () => this.removeSelection()
        });
    }

    // Exécute la suppression des plats sélectionnés et recharge le contenu
    async removeSelection() {
        const params = new URLSearchParams({ 'ajax': 1, 'rankDishes': this.indexDishesSelected });
        await fetch(`${this.urlRemoveSelection}?${params.toString()}`);
        const response = await fetch(this.urlReloadValue);
        document.getElementById('meals-day').innerHTML = await response.text();
    }

    // Met à jour l'affichage du bouton de suppression pour les repas
    updateRemoveButton() {
        const checkedValues = this.selectMealTargets.filter(el => el.checked).map(el => el.value);
        this.btnRemoveMealSelectionTarget.classList.toggle('hidden', checkedValues.length === 0);

        const allChecked = this.selectMealTargets.every(el => el.checked);
        this.selectAllMealsTargets.forEach(el => el.checked = allChecked);
    }

    // Gestion de la sélection individuelle des repas
    selectMeal(event) {
        const value = event.currentTarget.value;
        const checked = event.currentTarget.checked;

        this.selectMealTargets.forEach(el => { if(el.value === value) el.checked = checked; });
        this.updateRemoveButton();
    }

    // Coche toutes les meals (utilitaires)
    testToggleSelectAll() { this.selectAllMealsTarget.checked = true; }
    forceCheck() { this.selectAllMealsTarget.checked = true; }

    // Gestion de la sélection globale des repas
    selectAllMeals(event) {
        const checked = event.currentTarget.checked;
        this.selectMealTargets.forEach(el => el.checked = checked);
        this.updateRemoveButton();
    }

    // Supprime plusieurs repas sélectionnés après confirmation
    onRemoveMealSelection(event) {
        event.preventDefault();
        this.urlRemoveSelection = event.currentTarget.dataset.urlRemoveSelection;

        this.indexMealsSelected = [
            ...new Set(this.selectMealTargets.filter(el => el.checked).map(el => el.value))
        ];

        Swal.fire({
            title: 'Confirmation',
            text: 'Etes-vous sûr de vouloir supprimer ces repas?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Oui',
            showLoaderOnConfirm: true,
            preConfirm: () => this.removeMealSelection()
        });
    }

    // Exécute la suppression des repas sélectionnés et recharge le contenu
    async removeMealSelection() {
        const params = new URLSearchParams({ 'ajax': 1, 'rankMeals': this.indexMealsSelected });
        console.log(`${this.urlRemoveSelection}?${params.toString()}`);
        await fetch(`${this.urlRemoveSelection}?${params.toString()}`);
        const response = await fetch(this.urlReloadValue);
        document.getElementById('meals-day').innerHTML = await response.text();
    }

}