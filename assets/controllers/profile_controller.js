import { Controller } from '@hotwired/stimulus';
// import { CustomSelect } from '../profile';

class CustomSelect {

    constructor(originalSelect) {
        this.originalSelect = originalSelect

        // Supprime un ancien custom div si présent
        const existingCustom = originalSelect.nextElementSibling;
        if (existingCustom) {
            existingCustom.remove();
        }

        this.customSelect = document.createElement("div")
        this.customSelect.classList.add("w-full");

        this.originalSelect.querySelectorAll("option").forEach(optionElement => {

            const itemElement = document.createElement("div")

            itemElement.classList.add("select_profile__item")
            itemElement.classList.add("bg-sky-600")
            itemElement.classList.add("hover:bg-sky-800")
            itemElement.classList.add("w-full")
            itemElement.classList.add("text-white")
            itemElement.classList.add("p-4")
            itemElement.classList.add("cursor-pointer")
            itemElement.classList.add("mb-6")
            itemElement.classList.add("rounded-md")
            itemElement.classList.add("font-bold")
            itemElement.textContent = optionElement.textContent
            this.customSelect.appendChild(itemElement)

            if(optionElement.hasAttribute('selected')) {
                this._select(itemElement)
            }

            itemElement.addEventListener("click", () => {
                if(
                    this.originalSelect.multiple
                    && itemElement.classList.contains("select_profile__item--selected")
                ) {
                    this._deselect(itemElement)
                }else{
                    this._select(itemElement)
                }
                // Dispatch un CustomEvent pour éviter que Symfony / Turbo fasse un fetch 422
                const event = new CustomEvent("customSelectChanged", {
                    detail: { value: this.originalSelect.value },
                    bubbles: false
                });
                this.originalSelect.dispatchEvent(event);
            })
        })

        this.originalSelect.insertAdjacentElement("afterend", this.customSelect)
        this.originalSelect.style.display = "none"
    }

    _select(itemElement) {
        const index = Array.from(this.customSelect.children).indexOf(itemElement)

        if(!this.originalSelect.multiple) {
            this.customSelect.querySelectorAll(".select_profile__item").forEach(el => {
                el.classList.remove("select_profile__item--selected")
                el.classList.replace("bg-sky-800", "bg-sky-600")
            })
        }

        let opt = this.originalSelect.querySelectorAll("option")[index]
        opt.selected = true

        if(opt.getAttribute('id') == 'energy_calculator_auto') {
            document.getElementById('user_profile_energy').disabled = true
            document.querySelector('.input-energy').classList.add('hidden')
            document.querySelector('.unitmeasure-energy').classList.add('hidden')
        } else if(opt.getAttribute('id') == 'energy_calculator_perso') {
            document.getElementById('user_profile_energy').disabled = false
            document.querySelector('.input-energy').classList.remove('hidden')
            document.querySelector('.unitmeasure-energy').classList.remove('hidden')
        }

        itemElement.classList.add("select_profile__item--selected")
        itemElement.classList.replace("bg-sky-600", "bg-sky-800")
        // itemElement.classList.replace("text-dark-blue", "text-white")
    }

    _deselect(itemElement) {
        const index = Array.from(this.customSelect.children).indexOf(itemElement)

        this.originalSelect.querySelectorAll("option")[index].selected = false
        itemElement.classList.remove("select_profile__item--selected")
        itemElement.classList.replace("bg-sky-800", "bg-sky-600")
        // itemElement.classList.replace("text-white", "text-dark-blue")
    }

}

export default class extends Controller {

    connect() 
    {
        document.querySelectorAll(".custom-select-profiles").forEach(selectElement => {
            new CustomSelect(selectElement)
        })
                
        // toggle input energy fields
        
        const selectCalculEnergy = document.getElementById('user_profile_automaticCalculateEnergy');
        
        if(selectCalculEnergy.value == "1") {
            // toggleInputEnergy('none')
            document.querySelectorAll('.energy').forEach((element) => {
                element.style.display = "none";
            });
        }
        
        selectCalculEnergy.addEventListener("change", (event) => {
            if(event.target.value == "0") {
                document.querySelectorAll('.energy').forEach((element) => {
                    element.style.display = "flex";
                });
            }else{
                document.querySelectorAll('.energy').forEach((element) => {
                    element.style.display = "none";
                });
            }
        })
    }
}