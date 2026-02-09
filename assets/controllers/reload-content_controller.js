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

        this.foodGroupTargets.forEach((element) => {
            if(element.classList.contains('selected') == true) {
                this.foodGroupButtonSelected.push(element);
            }
        });

        this.foodGroupButtonSelected.forEach(element => {
            this.selectedFoodGroupId.push(element.dataset.foodGroupId);
        });
     
        if(this.lastResultsTargets.reverse()[0].value == 1) {
            this.loadMoreTarget.classList.add('hidden');
        }
    }

    onSearchInput(event) {
        this.pageValue = 0;
        this.refreshContent();
    }

    onSelectFoodGroup(event) {
        const btnSelected = event.currentTarget;

        const newFoodGroupId = btnSelected.dataset.foodGroupId;
        const indexFoodGroupId = this.selectedFoodGroupId.indexOf(newFoodGroupId);

        if(indexFoodGroupId == -1) {
            this.selectedFoodGroupId.push(newFoodGroupId);
            btnSelected.classList.add('selected');
            this.allFgUnchecked = false;
            if(this.selectedFoodGroupId.length == this.lengthFgpTotal) {
                this.updateBtnAllFgpCheckedClasses(true);
            }else{
                this.updateBtnAllFgpCheckedClasses(false);
            }
            this.updateBtnAllFgpUnCheckedClasses(false);
        }else{
            this.selectedFoodGroupId.splice(indexFoodGroupId, 1);
            btnSelected.classList.remove('selected');
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
            btn.classList.replace("text-gray-900", "text-white");
            btn.classList.remove("hover:text-gray-900");
            btn.classList.add("hover:text-white");
            btn.classList.replace("bg-gray-100", "bg-sky-600");
            btn.classList.remove("hover:bg-gray-900");
            btn.classList.add("hover:bg-sky-600");
        }else{
            btn.classList.remove('selected')
            btn.classList.replace("text-white", "text-gray-900");
            btn.classList.remove("hover:text-white");
            btn.classList.add("hover:text-gray-900");
            btn.classList.replace("bg-sky-600", "bg-gray-100");
            btn.classList.remove("hover:bg-sky-600");
            btn.classList.add("hover:bg-gray-900");
        }
    }

     updateBtnAllFgpUnCheckedClasses(active) {
        const btn = this.deselectAllFoodGroupTarget;

        if(active) {
            btn.classList.add('selected')
            btn.classList.replace("text-gray-900", "text-white");
            btn.classList.remove("hover:text-gray-900");
            btn.classList.add("hover:text-white");
            btn.classList.replace("bg-gray-100", "bg-sky-600");
            btn.classList.remove("hover:bg-gray-900");
            btn.classList.add("hover:bg-sky-600");
        }else{
            btn.classList.remove('selected');
            btn.classList.replace("text-white", "text-gray-900");
            btn.classList.remove("hover:text-white");
            btn.classList.add("hover:text-gray-900");
            btn.classList.replace("bg-sky-600", "bg-gray-100");
            btn.classList.remove("hover:bg-sky-600");
            btn.classList.add("hover:bg-gray-900");
        }
    }

    onSelectTypeDish(event) {

        this.typeDishValue = event.currentTarget.dataset.typeDish;

        this.typeDishTargets.forEach((element) => {
            if(element.dataset.typeDish == this.typeDishValue) {
                element.classList.replace("text-gray-900", "text-white");
                element.classList.remove("hover:text-gray-900");
                element.classList.add("hover:text-white");
                element.classList.replace("bg-gray-100", "bg-sky-600");
                element.classList.remove("hover:bg-gray-900");
                element.classList.add("hover:bg-sky-600");
            } else {
                element.classList.replace("text-white", "text-gray-900");
                element.classList.remove("hover:text-white");
                element.classList.add("hover:text-gray-900");
                element.classList.replace("bg-sky-600", "bg-gray-100");
                element.classList.remove("hover:bg-sky-600");
                element.classList.add("hover:bg-gray-900");
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

            element.classList.replace("text-white", "text-gray-900");
            element.classList.remove("hover:text-white");
            element.classList.add("hover:text-gray-900");
            element.classList.replace("bg-sky-600", "bg-gray-100");
            element.classList.remove("hover:bg-sky-600");
            element.classList.add("hover:bg-gray-200");

        } else {

            element.classList.add('selected');

            element.classList.replace("text-gray-900", "text-white");
            element.classList.remove("hover:text-gray-900");
            element.classList.add("hover:text-white");
            element.classList.replace("bg-gray-100", "bg-sky-600");
            element.classList.remove("hover:bg-gray-200");
            element.classList.add("hover:bg-sky-600");

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

        this.pageValue = 0;
        this.refreshContent();

    }

    onSelectGlutenFood(event) {

        const btnChoice = event.currentTarget;

        if(btnChoice.classList.contains('selected')) {
            btnChoice.classList.remove('selected')
        
            btnChoice.classList.replace("text-white", "text-gray-900");
            btnChoice.classList.remove("hover:text-white");
            btnChoice.classList.add("hover:text-gray-900");
            btnChoice.classList.replace("bg-sky-600", "bg-gray-100");
            btnChoice.classList.remove("hover:bg-sky-600");
            btnChoice.classList.add("hover:bg-gray-200");

            this.freeGluten = 0;
        }else{
            btnChoice.classList.add('selected');

            btnChoice.classList.replace("text-gray-900", "text-white");
            btnChoice.classList.remove("hover:text-gray-900");
            btnChoice.classList.add("hover:text-white");
            btnChoice.classList.replace("bg-gray-100", "bg-sky-600");
            btnChoice.classList.remove("hover:bg-gray-200");
            btnChoice.classList.add("hover:bg-sky-600");

            this.freeGluten = 1;
        }

        this.pageValue = 0;
        this.refreshContent();

    }

    onSelectLactoseFood(event) {

        const btnChoice = event.currentTarget;

        if(btnChoice.classList.contains('selected')) {
            btnChoice.classList.remove('selected')
          
            btnChoice.classList.replace("text-white", "text-gray-900");
            btnChoice.classList.remove("hover:text-white");
            btnChoice.classList.add("hover:text-gray-900");
            btnChoice.classList.replace("bg-sky-600", "bg-gray-100");
            btnChoice.classList.remove("hover:bg-sky-600");
            btnChoice.classList.add("hover:bg-gray-200");

            this.freeLactose = 0;
        }else{
            btnChoice.classList.add('selected');

            btnChoice.classList.replace("text-gray-900", "text-white");
            btnChoice.classList.remove("hover:text-gray-900");
            btnChoice.classList.add("hover:text-white");
            btnChoice.classList.replace("bg-gray-100", "bg-sky-600");
            btnChoice.classList.remove("hover:bg-gray-200");
            btnChoice.classList.add("hover:bg-sky-600");

            this.freeLactose = 1;
        }

        this.pageValue = 0;
        this.refreshContent();
        
    }

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

        }

    }

    onSelectAllFoodGroup(event) {

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

        this.updateBtnAllFgpCheckedClasses(false);
        this.updateBtnAllFgpUnCheckedClasses(true);

        this.selectedFoodGroupId = [];

        this.foodGroupTargets.forEach((element) => {
            element.classList.remove('selected');
            // this.foodGroupButtonSelected.remove(element);
        });
        
        this.pageValue = 0;
        this.refreshContent();
    }
}