import { Controller } from '@hotwired/stimulus';

export default class extends Controller {

    static values = {
        url: String,
        urlReload: String,
        // currentTypeMeal: String,
        rankMeal: Number
    };
    static targets = ['typeMealOption', 'typeMealButton', 'btnOpenSlidebar'];

    // connect() {
    //     this.typeMealOptionTargets.forEach((element) => {
    //         if(element.value == this.currentTypeMealValue && element.dataset.disabled == "0") {
    //             element.checked = true;
    //             element.nextElementSibling.classList.add('selected');
    //             document.getElementById('modalAddModelMeal-' + this.rankMealValue).querySelector('input.typeMeal').value = element.value;
    //             document.getElementById('typeModelMeal').value = element.value;
    //         }else{
    //             element.checked = false;
    //         }
    //     });

    // }

    async onSelectType(event) {
      
        const element = event.currentTarget;
        this.typeMealValue = element.dataset.typeMeal;

        const widthSpanTypeName = element.getBoundingClientRect().width;
        const heightSpanTypeName = element.getBoundingClientRect().height;

        const spanTypeName = element.querySelector('.name-type');
        spanTypeName.classList.replace('flex', 'hidden');

        const loaderType = element.querySelector('.loader-type');
        loaderType.style.width = widthSpanTypeName + "px";
        loaderType.style.height = heightSpanTypeName + "px";
        loaderType.classList.replace('hidden', 'flex');

        this.typeMealOptionTargets.forEach((element) => {
            if(element.value == this.typeMealValue) {
                const params = new URLSearchParams({
                    rankMeal: this.rankMealValue,
                    type: element.value,
                    ajax: 1
                });
                console.log(`${this.urlValue}?${params.toString()}`);
                fetch(`${this.urlValue}?${params.toString()}`)
                    .then((response) => {
                        fetch(this.urlReloadValue)
                            .then((response) => {
                                return response.text()
                            })
                            .then((text) => {
                                document.getElementById('meals-day').innerHTML = text;
                                this.btnOpenSlidebarTarget.dataset.typeDish = this.typeMealValue;
                                loaderType.classList.replace('flex', 'hidden');
                                spanTypeName.classList.replace('hidden', 'flex');
                            });
                });
            }
        });
    }

}