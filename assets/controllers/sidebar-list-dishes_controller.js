import { Controller } from '@hotwired/stimulus';
import { useDispatch } from 'stimulus-use';
import Swal from 'sweetalert2';

export default class  extends Controller {   

    static values = {
        urlReloadMeals: String,
        urlAddItem: String,
        urlRemoveItem: String,
        urlRemovePreselectedItems: String,
        urlReloadEnergyLabel: String
    }

    connect() {
        // console.log('connection sidebar-list-dishes réussie');
        useDispatch(this);
    }

    async addItem(event) {
        const btnAdd = event.currentTarget;
        const id = btnAdd.dataset.itemId;
        const quantity = document.querySelector(".quantity-" + String(id)).value;

        if(quantity === '' || quantity === null || quantity === 'undefined' || quantity <= 0 || quantity === 'Aucune') {
            Swal.fire({
                title: "Ouch!",
                text: "Veuillez saisir une quantité valide",
                icon: "warning"
            })
    
            return;
        }

        const unitMeasureId = document.querySelector(".unitMeasure-" + String(id)).value;

        let params = new URLSearchParams({
            id: id,
            type: btnAdd.dataset.itemType,
            rankMeal: btnAdd.dataset.rankMeal,
            rankDish: btnAdd.dataset.rankDish,
            quantity: quantity,
            unitMeasure: unitMeasureId,
            ajax: 1
        });

        console.log("j'ajoute l'element à la session");
        console.log(`${this.urlAddItemValue}?${params.toString()}`);

        fetch(`${this.urlAddItemValue}?${params.toString()}`)
            .then((response) => {
                this.toggleSlideover();
                this.reloadMeals();
            });

        // btnAdd.dataset.rankDish += 1;


        // this.dispatch('async:reload-list', {
        //     response
        // });

        // params = new URLSearchParams({
        //     'ajax': 1
        // });
        // const response = await fetch(`${urlReloadMeals}?${params.toString()}`);
        // document.getElementById('meals-day').innerHTML = await response.text();
    }

    async addPreSelect(btnAdd) {

        const urlPreselectItem = btnAdd.dataset.urlAddPreselectItem;
        const id = btnAdd.dataset.itemId;
        const quantity = document.querySelector(".quantity-" + String(id)).value;
        const unitMeasureId = document.querySelector(".unitMeasure-" + String(id)).value;
        const alertColor = document.querySelector(".alert-" + String(id)).dataset.alertColor;
        const alertText = document.querySelector(".alert-" + String(id)).dataset.alertText;


        // ON AFFICHE LES PLATS PRESELECTIONNES
        
        let params = new URLSearchParams({
            'rankMeal': btnAdd.dataset.rankMeal,
            'rankDish': btnAdd.dataset.rankDish,
            'quantity': quantity,
            'unitMeasure': unitMeasureId,
            'alertColor': alertColor,
            'alertText': alertText,
            'ajax': 1
        });

        console.log(`${urlPreselectItem}?${params.toString()}`);

        fetch(`${urlPreselectItem}?${params.toString()}`)
            .then((response) => response.text())
            .then((text) => {
                document.getElementById("listPreselectedItem").innerHTML += text;
                document.getElementById("itemPreselected-" + id).classList.replace('fade-enter-from', 'fade-enter-to');
                fetch(this.urlReloadEnergyLabelValue)
                    .then((response) => {return response.text()})
                    .then((text) => {
                        document.getElementById('sidebarTotalEnergy').innerHTML = text;
                    });
        });


        // ON CHANGE LE SELECT QUANTITY EN "NOMBRE PORTIONS/KG/G..."

        // const containerQuantity = document.getElementById("containerQuantity-" + id);
        // const urlUnitMeasureAlias = containerQuantity.dataset.urlGetUnitmeasureAlias;
        // params = new URLSearchParams({
        //     'id': unitMeasureId,
        // });
        // // console.log(`${url}?${params.toString()}`);
        // fetch(`${urlUnitMeasureAlias}?${params.toString()}`)
        //     .then((response) => response.text())
        //     .then((text) => {
        //         document.getElementById('wrapperQuantitySelected-' + id).innnerHTML = "<div>" + quantity + " " + text + "</div>";
        //         document.getElementById('wrapperQuantityForm-' + id).classList.add('hidden');
        //         document.getElementById('wrapperQuantitySelected-' + id).classList.remove('hidden');
        // });

    }

    updateItem(event) {
        const btnAdd = event.currentTarget;
        const id = btnAdd.dataset.itemId;
        const quantity = document.querySelector(".quantity-" + String(id)).value;

        if(quantity === '' || quantity === null || quantity === 'undefined' || quantity <= 0 || quantity === 'Aucune') {
            Swal.fire({
                title: "Ouch!",
                text: "Veuillez saisir une quantité valide",
                icon: "warning"
            })
    
            return;
        }

        const unitMeasureId = document.querySelector(".unitMeasure-" + String(id)).value;

        let params = new URLSearchParams({
            id: id,
            type: btnAdd.dataset.itemType,
            rankMeal: btnAdd.dataset.rankMeal,
            rankDish: btnAdd.dataset.rankDish,
            quantity: quantity,
            unitMeasure: unitMeasureId,
            ajax: 1
        });

        console.log("j'update l'element à la session");
        console.log(`${this.urlAddItemValue}?${params.toString()}`);

        fetch(`${this.urlAddItemValue}?${params.toString()}`)
            .then((response) => {
                this.toggleSlideover();
                this.reloadMeals();
            });
    }

    resetSelection(event) {

        // On remet les champs selection quantité à zéro
        const id = event.currentTarget.dataset.itemId;
        const typeItem = event.currentTarget.dataset.itemType;
        const selector = `.quantity-${id}`; // ou la classe/id réel
        const el = document.querySelector(selector);
        if (!el) return;

        // 1) set value
        if(typeItem == 'Food') {
            
            el.value = '';
        }else{
            el.value = 'Aucune';
        }

        // 2) dispatch input (best)
        const inputEvt = new InputEvent('input', { bubbles: true, cancelable: true });
        el.dispatchEvent(inputEvt);


        // On remet l'energie totale à sa valeur de départ
        // fetch(`${this.urlAddItemValue}?${params.toString()}`)
        //     .then((response) => {
        //         this.toggleSlideover();
        //         this.reloadMeals();
        //     });
    }

    async removeItem(event) {

        const btnRemove = event.currentTarget;
        const id = btnRemove.dataset.itemId;

        let params = new URLSearchParams({
            'rankMeal': btnRemove.dataset.rankMeal,
            'rankDish': btnRemove.dataset.rankDish,
            'ajax': 1
        });

        console.log("je supprime l'element à la session");
        console.log(`${this.urlRemoveItemValue}?${params.toString()}`);

        fetch(`${this.urlRemoveItemValue}?${params.toString()}`)
            .then((response) => {
                this.removePreselect(btnRemove);
            })
            .then((text) => {
                this.dispatch('async:remove-item');
            });

    }

    async removeAllItem() {

        let params = new URLSearchParams({
            'rankMeal': document.getElementById('slideOverRankMeal').value,
            'rankDish': document.getElementById('slideOverRankDish').value,
            'fromRankDishToTheEnd': true,
            'ajax': 1
        });

        console.log("je supprime tous les élements de la session");
        console.log(`${this.urlRemoveItemValue}?${params.toString()}`);

        fetch(`${this.urlRemoveItemValue}?${params.toString()}`)
            .then((response) => {
                this.removeAllPreselect();
            });
            

    }

    async removePreselect(btnRemove) {
  
        const url = btnRemove.dataset.urlRemovePreselectItem;
        const id = btnRemove.dataset.itemId;

        const params = new URLSearchParams({
            'rankMeal': btnRemove.dataset.rankMeal,
            'rankDish': btnRemove.dataset.rankDish,
        });
        console.log('je supprime un element de ma liste préslection');
        console.log(`${url}?${params.toString()}`);

        fetch(`${url}?${params.toString()}`)
            .then((response) => response.text())
            .then((text) => {
                document.getElementById("listPreselectedItem").innerHTML = text;
                fetch(this.urlReloadEnergyLabelValue)
                    .then((response) => {return response.text()})
                    .then((text) => {
                        document.getElementById('sidebarTotalEnergy').innerHTML = text;
                    });
             
        });
        
    }

    async removeAllPreselect() {
  
        console.log('je supprime tous les éléments de ma liste préslection');
        console.log(`${this.urlRemovePreselectedItemsValue}`);

        fetch(`${this.urlRemovePreselectedItemsValue}`)
            .then((response) => {
                document.getElementById("listPreselectedItem").innerHTML = '';
            })
            .then(
                fetch(this.urlReloadEnergyLabelValue)
                    .then((response) => {return response.text()})
                    .then((text) => {
                        console.log('on recalcule l\'energie');
                        console.log(text);
                        document.getElementById('sidebarTotalEnergy').innerHTML = text;
                    })
            );
           
    }

    async reloadMeals() {
        const params = new URLSearchParams({
            'ajax': 1
        });
        const url = `${this.urlReloadMealsValue}?${params.toString()}`;
        console.log('url reload meals');
        console.log(url);

        fetch(url)
            .then((response) => {
                return response.text();
            })
            .then((text) => {
                document.getElementById('meals-day').innerHTML = text;
                console.log('je vide la liste des items présélectionnés');
                fetch(this.urlRemovePreselectedItemsValue);
            });
    }

    toggleSlideover(event) {
        document.getElementById('slideover-container').classList.toggle('invisible');
        document.getElementById('slideover-bg').classList.toggle('opacity-0');
        document.getElementById('slideover-bg').classList.toggle('opacity-50');
        document.getElementById('slideover').classList.toggle('translate-y-full');
    }

}