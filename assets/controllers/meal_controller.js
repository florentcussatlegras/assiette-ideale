import { Controller } from '@hotwired/stimulus';
import Swal from 'sweetalert2';

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
                const params = new URLSearchParams({
                    'rankMeal': rankMeal
                });
                fetch(`${this.urlRemoveMealValue}?${params.toString()}`)
                    .then((response) => {
                        return this.refreshContent()
                    });
            }
        });
    }

    onAddMeal(event) {

        if(this.lastRankMealValue !== "none") {

            const lastMealElement = document.getElementById('meal-' + this.lastRankMealValue);

            // On vérifie que le dernier repas a bien un type
            const types = lastMealElement.getElementsByClassName('type-meal');
            let typeChecked = false;

            Array.from(types).forEach((element) => {
                if(element.checked == true) {
                    typeChecked = true;
                }
            });

            if(typeChecked == false) {
                
                Swal.fire({
                    title: "Attention!",
                    text: "Vous n'avez pas précisé de type pour votre dernier repas",
                    icon: "warning"
                })

                return;
            }

            // On vérifie que le dernier repas contient bien des plats/aliments
            const dishes = lastMealElement.getElementsByClassName('row-dish');
            if (dishes.length === 0 ) {
                Swal.fire({
                    title: "Attention!",
                    text: "Vous n'avez pas saisis de plats pour votre dernier repas",
                    icon: "warning"
                })

                return;
            }
        }
        
        document.getElementById('loader-new-meal').classList.replace('flex', 'hidden');

        fetch(this.urlAddMealValue)
            .then((response) => {
                this.refreshContent();
            })
            .then((text) => {
                document.getElementById('loader-new-meal').classList.replace('hidden', 'flex');
            });

    }

    async refreshContent() {
        const params = new URLSearchParams({
            'ajax': 1
        })
        const response = await fetch(`${this.urlReloadValue}?${params.toString()}`);
        document.getElementById('meals-day').innerHTML = await response.text();
    }

    saveMeals(event) {

        const urlRedirect = event.currentTarget.dataset.url;
        const meals = document.querySelectorAll('.meal');
     
        let displayErrorType = false;

        meals.forEach((element) => {
            let typeChecked = false;
            const typeMeals = element.querySelectorAll('.type-meal');
            typeMeals.forEach((element) => {
                if(element.checked == true) {
                    typeChecked = true;
                }
            });
            if (typeChecked == false) {
                displayErrorType = true;
            }
        });

        console.log("displayErrorType :" + displayErrorType);

        if(displayErrorType == true) {
            Swal.fire({
                title: "Attention!",
                text: "Merci d'indiquer un type pour tous vos repas",
                icon: "warning",
                confirmButtonColor: "#0284c7"
            });

            return;
        }

        // On vérifie que tous les repas contiennent bien des plats/aliments
        let noDishesSelected = false;
        meals.forEach((element) => {
            const dishes = element.querySelectorAll('.row-dish');
            if (dishes.length === 0 ) {
                noDishesSelected = true;
            }
        });

        if(noDishesSelected === true) {
            Swal.fire({
                title: "Attention!",
                text: "Un de vos repas ne contient pas de plats ou d'aliments",
                icon: "warning",
                confirmButtonColor: "#0284c7"
            });

            return;
        }
        
        let wellBalanced = true;
        let text = '<ul><li>Vos repas sont déséquilibrés.</li>';

        if(this.alertCountTarget.value > 0) {
            wellBalanced = false;
            text += '<li>Certains aliments ou plats sont déconseillés.</li>';
        }
        if(this.fgpRemainingTargets.length > 0) {
            wellBalanced = false;
            text += '<li>Certains groupes d\'aliments n\'apparaissent pas dans vos choix.</li>';
        }
        if(this.alertEnergyTarget.value == "1") {
            text += '<li>Votre total énergétique est trop fort. </li>';
        }
        if(this.alertEnergyTarget.value == "-1") {
            text += '<li>Votre total énergétique est trop faible.</li>';
        }
        text += '<li>Etes-vous sûr de vouloir les enregistrer?</li></ul>';

        if(wellBalanced == false) {
            Swal.fire({
                title: 'Etes-vous sûr?',
                html: text,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Oui',
                showLoaderOnConfirm: true,
                preConfirm: () => {
                    document.location.href = urlRedirect;
                }
            });
            
            return
        }
        
        document.location.href = urlRedirect;
    }

    selectDish(event) {

        var displayBtnRemoveSelection = false;
        var rankMeal = event.currentTarget.dataset.rankMeal;

        this.selectDishTargets.forEach((element) => {
            if(element.dataset.rankMeal == rankMeal && element.checked == true) {
                displayBtnRemoveSelection = true;
            }
        });

        if(displayBtnRemoveSelection == true) {
            this.btnRemoveSelectionTargets[parseInt(rankMeal)].classList.remove('hidden');
        }else{
            this.btnRemoveSelectionTargets[parseInt(rankMeal)].classList.add('hidden');
            this.selectAllDishesTargets[parseInt(rankMeal)].checked = false;
        }

    }

    selectAllDishes(event) {

        var rankMeal = event.currentTarget.dataset.rankMeal;

        const btnRemoveMealSelection = this.btnRemoveSelectionTargets.find((element) => element.dataset.rankMeal == rankMeal);

        if(event.currentTarget.checked == true) {
            btnRemoveMealSelection.classList.remove('hidden');
        }else{
            btnRemoveMealSelection.classList.add('hidden');
        }

        this.selectDishTargets.forEach((element) => {
            if(element.dataset.rankMeal == rankMeal) {
                if(event.currentTarget.checked == true) {
                    element.checked = true;
                } else {
                    element.checked = false;
                }
            }
        });

    }

    onRemoveSelection(event) {

        this.urlRemoveSelection = event.currentTarget.dataset.urlRemoveSelection;
        const dishesSelected = this.selectDishTargets.filter((element) => element.checked == true);

        console.log(dishesSelected);
    
        dishesSelected.forEach((element) => {
            this.indexDishesSelected.push(element.value);
        });

        console.log(this.indexDishesSelected);

        Swal.fire({
            title: 'Confirmation',
            text: 'Etes-vous sûr de vouloir supprimer ces plats?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Oui',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                this.removeSelection();
            }
        });

    }

    async removeSelection() {
        const params = new URLSearchParams({
            'ajax': 1,
            'rankDishes': this.indexDishesSelected
        });
        console.log(`${this.urlRemoveSelection}?${params.toString()}`);
        await(fetch(`${this.urlRemoveSelection}?${params.toString()}`));
        console.log(this.urlReloadValue);
        const response = await fetch(this.urlReloadValue);
        document.getElementById('meals-day').innerHTML = await response.text();
    }

    selectMeal(event) {
        var displayBtnRemoveSelection = false;
        this.selectMealTargets.forEach((element) => {
            if(element.checked == true) {
                displayBtnRemoveSelection = true;
            }
        });

        if(displayBtnRemoveSelection == true) {
            this.btnRemoveMealSelectionTarget.classList.remove('hidden');
        }else{
            this.btnRemoveMealSelectionTarget.classList.add('hidden');
            this.selectAllMealsTarget.checked = false;
        }
    }

    selectAllMeals(event) {
        if(event.currentTarget.checked == true) {
            this.btnRemoveMealSelectionTarget.classList.remove('hidden');
        }else{
            this.btnRemoveMealSelectionTarget.classList.add('hidden');
        }

        this.selectMealTargets.forEach((element) => {
            if(event.currentTarget.checked == true) {
                element.checked = true;
            } else {
                element.checked = false;
            }
        });
    }

    onRemoveMealSelection(event) {

        this.urlRemoveSelection = event.currentTarget.dataset.urlRemoveSelection;
        const mealsSelected = this.selectMealTargets.filter((element) => element.checked == true);

        console.log(mealsSelected);
    
        mealsSelected.forEach((element) => {
            this.indexMealsSelected.push(element.value);
        });

        console.log(this.indexMealsSelected);

        Swal.fire({
            title: 'Confirmation',
            text: 'Etes-vous sûr de vouloir supprimer ces repas?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Oui',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                this.removeMealSelection();
            }
        });

    }

    async removeMealSelection() {
        const params = new URLSearchParams({
            'ajax': 1,
            'rankMeals': this.indexMealsSelected
        });
        console.log(`${this.urlRemoveSelection}?${params.toString()}`);
        await(fetch(`${this.urlRemoveSelection}?${params.toString()}`));
        console.log(this.urlReloadValue);
        const response = await fetch(this.urlReloadValue);
        document.getElementById('meals-day').innerHTML = await response.text();
    }

}