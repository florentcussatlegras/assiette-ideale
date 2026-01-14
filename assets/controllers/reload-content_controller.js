import { Controller } from '@hotwired/stimulus';
import { useDebounce } from 'stimulus-use';

export default class extends Controller {

    selectedFoodGroupId = [];
    foodGroupButtonSelected = [];
    typeItemSelected = [];
    lengthFgpTotal = 16;
    allFgUnchecked = false;
    updateDish = null;
    freeGluten = 0; 
    freeLactose = 0;

    static values = {
        url: String,
        urlRemoveItem: String,
        typeDish: String,
        page: Number,
        urlListRemoveFavoriteDish: String,
    }
    static targets = ['content', 'loader', 'loadMore', 'search', 'foodGroup', 'typeDish', 'typeItem', 'lastResults', 'selectAllFoodGroup', 'deselectAllFoodGroup'];
    static debounces = ['onSearchInput'];

    connect() {
        useDebounce(this);
        console.log(this.urlValue);
        // const foodGroupButtonSelected = this.foodGroupTargets.filter((element) => element.classList.contains('selected') == true);

        this.foodGroupTargets.forEach((element) => {
            if(element.classList.contains('selected') == true) {
                this.foodGroupButtonSelected.push(element);
            }
            // else{
            //     element.querySelector('.checkmark').classList.replace('flex', 'hidden');
            // }
        });

        this.foodGroupButtonSelected.forEach(element => {
            this.selectedFoodGroupId.push(element.dataset.foodGroupId);
        });
     
        if(this.lastResultsTargets.reverse()[0].value == 1) {
            this.loadMoreTarget.classList.add('hidden');
        }
    }

    onSearchInput(event) {

        // const params = new URLSearchParams({
        //     q: event.target.value,
        //     food_groups: this.selectedFoodGroupId.join(','),
        //     ajax: 1
        // })

        // const params = new URLSearchParams({
        //     q: this.searchTarget.value,
        //     food_groups: this.selectedFoodGroupId.join(','),
        //     type: this.typeDishValue,
        //     ajax: 1
        // });
        
        // this.url = `${this.urlValue}?${params.toString()}`;
        this.pageValue = 0;
        this.refreshContent();
    }

    onSelectFoodGroup(event) {
        const btnSelected = event.currentTarget;
        console.log('on select foodgroup');

        const newFoodGroupId = btnSelected.dataset.foodGroupId;
        const indexFoodGroupId = this.selectedFoodGroupId.indexOf(newFoodGroupId);

        if(indexFoodGroupId == -1) {
            console.log('je suis là 1');
            this.selectedFoodGroupId.push(newFoodGroupId);
            btnSelected.classList.add('selected');
            // btnSelected.querySelector('.checkmark').classList.replace('hidden', 'flex');
            this.allFgUnchecked = false;
            if(this.selectedFoodGroupId.length == this.lengthFgpTotal) {
                this.updateBtnAllFgpCheckedClasses(true);
            }else{
                this.updateBtnAllFgpCheckedClasses(false);
            }
            this.updateBtnAllFgpUnCheckedClasses(false);
        }else{
            console.log('je suis là 2');
            this.selectedFoodGroupId.splice(indexFoodGroupId, 1);
            btnSelected.classList.remove('selected');
            // btnSelected.querySelector('.checkmark').classList.replace('flex', 'hidden');
            if(this.selectedFoodGroupId.length == 0) {
                this.allFgUnchecked = true;
                this.updateBtnAllFgpUnCheckedClasses(true);
            }else{
                this.allFgUnchecked = false;
                this.updateBtnAllFgpUnCheckedClasses(false);
            }
            this.updateBtnAllFgpCheckedClasses(false);
        }

        this.pageValue = 0;
        this.refreshContent();
    }

    updateBtnAllFgpCheckedClasses(active) {
        const btn = this.selectAllFoodGroupTarget;

        if(active) {
            btn.classList.add('selected')
            btn.classList.replace('text-dark-blue', 'text-white');
            btn.classList.replace('bg-white', 'bg-light-blue');
        }else{
            btn.classList.remove('selected')
            btn.classList.replace('text-white', 'text-dark-blue');
            btn.classList.replace('bg-light-blue', 'bg-white');
        }
    }

     updateBtnAllFgpUnCheckedClasses(active) {
        const btn = this.deselectAllFoodGroupTarget;

        if(active) {
            btn.classList.add('selected')
            btn.classList.replace('text-dark-blue', 'text-white');
            btn.classList.replace('bg-white', 'bg-light-blue');
        }else{
            btn.classList.remove('selected')
            btn.classList.replace('text-white', 'text-dark-blue');
            btn.classList.replace('bg-light-blue', 'bg-white');
        }
    }

    // setSelectedFoodGroup(newFoodGroupId) {

    //     const indexFoodGroupId = this.selectedFoodGroupId.indexOf(newFoodGroupId);
    //     const selectedFoodGroup = this.findSelectedFoodGroup(newFoodGroupId);

    //     if(indexFoodGroupId == -1) {
    //         this.selectedFoodGroupId.push(newFoodGroupId);
    //         selectedFoodGroup.classList.replace('opacity-50', 'shadow-lg');
    //         selectedFoodGroup.classList.replace('py-2', 'py-3');

    //         return;
    //     }

    //     this.selectedFoodGroupId.splice(indexFoodGroupId, 1);
    //     selectedFoodGroup.classList.replace('shadow-lg', 'opacity-50');
    //     selectedFoodGroup.classList.replace('py-3', 'py-2');
    // }

    // /**
    //  * @return {Element|null}
    //  */
    // findSelectedFoodGroup(newFoodGroupId) {
    //     return this.foodGroupTargets.find((element) => element.dataset.foodGroupId == newFoodGroupId);
    // }


    onSelectTypeDish(event) {

        // const clickedType = event.currentTarget.dataset.typeName;
        // this.clickedType =  clickedType == this.typeNamevalue ? null : clickedType;
        this.typeDishValue = event.currentTarget.dataset.typeDish;

        this.typeDishTargets.forEach((element) => {
            if(element.dataset.typeDish == this.typeDishValue) {
                element.classList.replace('text-dark-blue', 'text-white');
                element.classList.replace('bg-white', 'bg-light-blue');
                // element.classList.add('selected');
                // element.children[0].classList.replace('hidden', 'flex');
            } else {
                element.classList.replace('text-white', 'text-dark-blue');
                element.classList.replace('bg-light-blue', 'bg-white');
                // element.classList.remove('selected');
                // element.children[0].classList.replace('flex', 'hidden');
            }
        });

        this.pageValue = 0;
        this.refreshContent();

    }

    onSelectTypeItem(event) {

        var element = event.currentTarget;

        var allTypeItemDeselect = true;

        if(element.classList.contains('selected')) {
            element.classList.remove('selected');
        } else {
            element.classList.add('selected');
            allTypeItemDeselect = false;
        }

        this.typeItemSelected = [];

        this.typeItemTargets.forEach((element) => {
            if(element.classList.contains('selected') == true) {
                allTypeItemDeselect = false;
            }
        });

        if(allTypeItemDeselect == true) {
            this.typeItemTargets.forEach((element) => {
                element.classList.add('selected');
            });
        }

        this.typeItemTargets.forEach((element) => {
            if(element.classList.contains('selected') == true) {
                this.typeItemSelected.push(element.dataset.typeItem);
            }
        });

        console.log(this.typeItemSelected);

        this.pageValue = 0;
        this.refreshContent();

    }

    onSelectGlutenFood(event) {

        const btnChoice = event.currentTarget;

        if(btnChoice.classList.contains('selected')) {
            btnChoice.classList.remove('selected')
            btnChoice.classList.replace('text-white', 'text-dark-blue');
            btnChoice.classList.replace('bg-light-blue', 'bg-white');
            this.freeGluten = 0;
        }else{
            btnChoice.classList.add('selected')
            btnChoice.classList.replace('text-dark-blue', 'text-white');
            btnChoice.classList.replace('bg-white', 'bg-light-blue');
            this.freeGluten = 1;
        }

        this.pageValue = 0;
        this.refreshContent();

    }

    onSelectLactoseFood(event) {

        const btnChoice = event.currentTarget;

        if(btnChoice.classList.contains('selected')) {
            btnChoice.classList.remove('selected')
            btnChoice.classList.replace('text-white', 'text-dark-blue');
            btnChoice.classList.replace('bg-light-blue', 'bg-white');
            this.freeLactose = 0;
        }else{
            btnChoice.classList.add('selected')
            btnChoice.classList.replace('text-dark-blue', 'text-white');
            btnChoice.classList.replace('bg-white', 'bg-light-blue');
            this.freeLactose = 1;
        }

        this.pageValue = 0;
        this.refreshContent();
        
    }


    // typeDishValueChanged() {
    //     alert('3');
    //     this.typeDishTargets.forEach((element) => {
    //         if(element.dataset.typeDish == this.typeDishValue) {
    //             element.classList.add('selected');
    //         } else {
    //             element.classList.remove('selected');
    //         }
    //     });

    //     // const params = new URLSearchParams({
    //     //     q: this.searchTarget.value,
    //     //     food_groups: this.selectedFoodGroupId.join(','),
    //     //     type: this.typeDishValue,
    //     //     ajax: 1
    //     // });
        
    //     // this.url = `${this.urlValue}?${params.toString()}`;

    //     this.refreshContent();
    // }


    // loadMore(event) {
    //     event.preventDefault();
    //     console.log('load more');
    //     this.page++;
    //     this.refreshContent();
    // }

    onAddItem() {
        this.rankDishValue++;
        // this.loadMore = false;
        // this.reloadFromStart = 1;
        document.getElementById('btnLoadMore').dataset.rankDish = this.rankDishValue;
        
        this.pageValue = 0;
        this.refreshContent();
    }

    onRemoveItem() {
        this.rankDishValue--;
        // this.loadMore = false;
        // this.reloadFromStart = 1;
        document.getElementById('btnLoadMore').dataset.rankDish = this.rankDishValue;
        
        this.pageValue = 0;
        this.refreshContent();
    }
   
    onLoadMore() {
        this.pageValue++;
        this.loadMore = true;
        // this.reloadFromStart = 0;
        
        this.refreshContent();
    }

    openSidebarList(event) {
        document.getElementById('slideOverRankMeal').value = event.currentTarget.dataset.rankMeal;
        document.getElementById('slideOverRankDish').value = event.currentTarget.dataset.rankDish;
        document.getElementById('slideOverUpdateDish').value = event.currentTarget.dataset.updateDish;
        // console.log('type de plat');
        // console.log(event.currentTarget.dataset.typeDish);
        if(event.currentTarget.dataset.typeDish !== 'undefined' && event.currentTarget.dataset.typeDish !== '') {
            document.getElementById('slideOverTypeDish').classList.replace('hidden', 'inline');
            document.getElementById('slideOverTypeDish').innerHTML = event.currentTarget.dataset.typeDish;
        }

        this.pageValue = 0;
        this.refreshContent();
    }

    async refreshContent() {

        if (this.hasLoaderTarget) {
            this.loaderTarget.classList.remove('hidden');
        }
        if (this.hasLoadMoreTarget) {
            this.loadMoreTarget.classList.add('hidden');
        }

        const target = this.hasContentTarget ? this.contentTarget : this.element;
        const fg = this.allFgUnchecked == true ? "none" : this.selectedFoodGroupId;
        const typeItem = this.typeItemSelected;
   
        const params = new URLSearchParams({
            q: this.searchTarget.value,
            fg: fg,
            type: this.typeDishValue,
            rankMeal: null !== document.getElementById('slideOverRankMeal') ? document.getElementById('slideOverRankMeal').value : null,
            // rankDish: this.rankDishValue,
            page: this.pageValue,
            updateDish: null !== document.getElementById('slideOverUpdateDish') ? document.getElementById('slideOverUpdateDish').value : null, 
            // reloadFromStart: this.reloadFromStart,
            typeItem: this.typeItemSelected,
            freeGluten: this.freeGluten,
            freeLactose: this.freeLactose,
            ajax: 1
        });
        
        this.url = `${this.urlValue}?${params.toString()}`;
        console.log('url pour rafraichir le contenu');
        console.log(this.url);
        // console.log(this.pageValue);

        if(this.pageValue > 0) {

            const response = await fetch(this.url);
            const newContent = await response.text();

            fetch(this.url)
                .then((response) => {
                    return response.text()
                })
                .then((newContent) => {
                    target.innerHTML = target.innerHTML + newContent;
                    this.loaderTarget.classList.add('hidden');
                    target.classList.remove('hidden');
                    // console.log(this.lastResultsTargets);
                    if(this.lastResultsTargets.reverse()[0].value != 1) {
                        this.loadMoreTarget.classList.remove('hidden');
                    }
            });

        }else{

            target.classList.add('hidden');

            const response = await fetch(this.url);
            target.innerHTML = await response.text();

            fetch(this.url)
                .then((response) => {
                    return response.text()
                })
                .then((newContent) => {
                    target.innerHTML = newContent;
                    this.loaderTarget.classList.add('hidden');
                    target.classList.remove('hidden');
                    // console.log(this.lastResultsTargets);
                    if(this.lastResultsTargets.reverse()[0].value != 1) {
                        this.loadMoreTarget.classList.remove('hidden');
                    }
            });

            // this.loaderTarget.classList.add('hidden');
            // target.classList.remove('hidden');
            // console.log(this.lastResultsTargets);
            // alert(this.lastResultsTargets.reverse()[0].value);
            // if(this.lastResultsTargets.reverse()[0].value != 1) {
            //     this.loadMoreTarget.classList.remove('hidden');
            // }

        }

    }

    onSelectAllFoodGroup(event) {

        // const btnChoice = event.currentTarget;

        // if(btnChoice.classList.contains('selected') == false) {
        //     btnChoice.classList.add('selected')
        //     btnChoice.classList.replace('text-dark-blue', 'text-white');
        //     btnChoice.classList.replace('bg-white', 'bg-light-blue');
        // }

        // const btnDeselectedAllFgp = this.deselectAllFoodGroupTarget;

        // if(btnDeselectedAllFgp.classList.contains('selected')) {
        //     btnDeselectedAllFgp.classList.remove('selected')
        //     btnDeselectedAllFgp.classList.replace('text-white', 'text-dark-blue');
        //     btnDeselectedAllFgp.classList.replace('bg-light-blue', 'bg-white');
        // }
        this.updateBtnAllFgpCheckedClasses(true);
        this.updateBtnAllFgpUnCheckedClasses(false);

        this.foodGroupButtonSelected = [];
        this.selectedFoodGroupId = [];

        this.foodGroupTargets.forEach((element) => {
            element.classList.add('selected');
            this.foodGroupButtonSelected.push(element);
        });

        this.foodGroupButtonSelected.forEach(element => {
            this.selectedFoodGroupId.push(element.dataset.foodGroupId);
        });

        this.pageValue = 0;
        this.refreshContent();
    }

    onDeselectAllFoodGroup(event) {

        // const btnChoice = event.currentTarget;

        // if(btnChoice.classList.contains('selected') == false) {
        //     btnChoice.classList.add('selected')
        //     btnChoice.classList.replace('text-dark-blue', 'text-white');
        //     btnChoice.classList.replace('bg-white', 'bg-light-blue');
        // }

        // const btnSelectedAllFgp = this.selectAllFoodGroupTarget;

        // if(btnSelectedAllFgp.classList.contains('selected')) {
        //     btnSelectedAllFgp.classList.remove('selected')
        //     btnSelectedAllFgp.classList.replace('text-white', 'text-dark-blue');
        //     btnSelectedAllFgp.classList.replace('bg-light-blue', 'bg-white');
        // }
        this.updateBtnAllFgpCheckedClasses(false);
        this.updateBtnAllFgpUnCheckedClasses(true);

        this.selectedFoodGroupId = [];

        this.foodGroupTargets.forEach((element) => {
            console.log(element);
            element.classList.remove('selected');
            // this.foodGroupButtonSelected.remove(element);
        });
        
        this.pageValue = 0;
        this.refreshContent();
    }

    // async removeFavoriteDish() {
    //     console.log('remove favorite dish');
    //     console.log(this.urlListRemoveFavoriteDishValue);

    //     const btn = event.currentTarget;
    //     const dishId = btn.dataset.dishId;

    //     const params = new URLSearchParams({
    //         dish_id: dishId
    //     });

    //     console.log(`${this.urlListRemoveFavoriteDishValue}?${params.toString()}`);
    //     const response = await fetch(`${this.urlListRemoveFavoriteDishValue}?${params.toString()}`);

    //     document.getElementById('alert-ajax').innerHTML = await response.text();

    //     this.pageValue = 0;
    //     this.refreshContent();
    // }
}