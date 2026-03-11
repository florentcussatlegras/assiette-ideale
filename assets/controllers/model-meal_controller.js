import { Controller } from "@hotwired/stimulus";
import Swal from "sweetalert2";

export default class extends Controller {
  static values = {
    urlList: String,
    urlListModal: String,
    urlRemove: String,
    lastRankMeal: String,
  };
  static targets = ["content", "modalContent", "background", "container", "search", "types", "sort", "loader", "chooseButton"];

  connect() {
    console.log("connect to model meal controller");

    this.filters = {
      minCalories: null,
      maxCalories: null,
    };
  }

  async show(event) {

    const lastMealElement = document.getElementById('meal-' + this.lastRankMealValue);
    const dishes = lastMealElement.getElementsByClassName('row-dish');

    if (this.lastRankMealValue == 0) {

      if (dishes.length == 0) {

        if (!this.hasModalContentTarget) {
          return;
        }

        const target = this.modalContentTarget;

        target.innerHTML = '<div class="loader"></div>';

        const response = await fetch(this.urlListModalValue, {
          headers: { "X-Requested-With": "XMLHttpRequest" }
        });

        target.innerHTML = await response.text();

        return;

      }else{

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
                icon: "warning",
                customClass: {
                    confirmButton: `
                        text-white
                        bg-sky-600 
                        hover:bg-sky-700 
                        transition 
                        duration-300 
                        font-semibold
                        rounded-lg
                        px-4
                        py-2
                    `
                },
                buttonsStyling: false
            })

            // Empêche l'ouverture de la modal
            event.preventDefault();
            event.stopImmediatePropagation(); // empêche tout autre handler click
            return;
        }

        const target = this.modalContentTarget;

        target.innerHTML = '<div class="loader"></div>';

        const response = await fetch(this.urlListModalValue, {
          headers: { "X-Requested-With": "XMLHttpRequest" }
        });

        target.innerHTML = await response.text();

        return;

      }

    }

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
            icon: "warning",
            customClass: {
                confirmButton: `
                    text-white
                    bg-sky-600 
                    hover:bg-sky-700 
                    transition 
                    duration-300 
                    font-semibold
                    rounded-lg
                    px-4
                    py-2
                `
            },
            buttonsStyling: false
        })

        // Empêche l'ouverture de la modal
        event.preventDefault();
        event.stopImmediatePropagation(); // empêche tout autre handler click
        return;
    }

    // On vérifie que le dernier repas contient bien des plats/aliments
    if (dishes.length === 0 ) {
        Swal.fire({
            title: "Attention!",
            text: "Vous n'avez pas saisis de plats pour votre dernier repas",
            icon: "warning"
        })

        // Empêche l'ouverture de la modal
        event.preventDefault();
        event.stopImmediatePropagation(); // empêche tout autre handler click
        return;
    }

    if (!this.hasModalContentTarget) {
      return;
    }

    const target = this.modalContentTarget;

    target.innerHTML = '<div class="loader"></div>';

    const response = await fetch(this.urlListModalValue, {
      headers: { "X-Requested-With": "XMLHttpRequest" }
    });

    target.innerHTML = await response.text();
    
  }

  async onRemoveMeal(event) {
    event.preventDefault(); // Empêche tout comportement par défaut

    const button = event.currentTarget;
    const mealId = button.dataset.modelMealId;
    const csrfToken = button.dataset.csrf;

    if (!mealId || !csrfToken) {
      console.error("ID ou CSRF manquant !");
      return;
    }

    Swal.fire({
      title: "Êtes-vous sûr ?",
      text: "Voulez-vous vraiment supprimer ce repas ?",
      icon: "warning",
      showCancelButton: true,
      confirmButtonText: "Oui, supprimer",
      cancelButtonText: "Annuler",
      confirmButtonColor: "#dc2626", // rouge
      cancelButtonColor: "#6b7280", // gris
    }).then(async (result) => {
      if (!result.isConfirmed) {
        return;
      }

      try {
        const response = await fetch(
          `${this.urlRemoveValue}/${mealId}?ajax=1&_token=${csrfToken}`,
          {
            headers: {
              "X-Requested-With": "XMLHttpRequest",
            },
          }
        );

        if (!response.ok) {
          throw new Error("Erreur lors de la suppression du repas");
        }

        // const html = await response.text();

        // if (this.hasContentTarget) {
        //   this.contentTarget.innerHTML = html;
        // }
        await this.applyFilters();
      } catch (error) {
        alert("Impossible de supprimer le repas, veuillez réessayer.");
      } 
    });

  }

  onCaloriesChange(event) {
    this.filters.minCalories = event.detail.min;
    this.filters.maxCalories = event.detail.max;
    this.applyFilters();
  }

  async applyFilters() {
    const params = new URLSearchParams();

    // calories
    if (this.filters.minCalories !== null) {
      params.append("minCalories", this.filters.minCalories);
    }
    if (this.filters.maxCalories !== null) {
      params.append("maxCalories", this.filters.maxCalories);
    }

    // recherche texte
    if (this.hasSearchTarget && this.searchTarget.value.trim() !== "") {
      params.append("search", this.searchTarget.value.trim());
    }

    // types
    const selectedTypes = this.typesTargets
      .filter(input => input.checked)
      .map(input => input.value);

    selectedTypes.forEach(type =>
      params.append("types[]", type)
    );

    // tri
    if (this.hasSortTarget) {
      params.append("sort", this.sortTarget.value);
    }

    // 🔄 AJAX
    this.loaderTarget.classList.remove("hidden");
    this.contentTarget.classList.add("hidden");
    this.contentTarget.style.opacity = 0.5;

    try {

      this.disableButton();

      const response = await fetch(`${this.urlListValue}?${params.toString()}`, {
        headers: { "X-Requested-With": "XMLHttpRequest" },
      });
      const html = await response.text();
      this.contentTarget.innerHTML = html;

    } catch (e) {

      console.error(e);
      this.contentTarget.innerHTML = "<p>Erreur lors du filtrage.</p>";

    } finally {

      this.loaderTarget.classList.add("hidden");
      this.contentTarget.classList.remove("hidden");
      this.contentTarget.style.opacity = 1;

    }

  }

  disableButton() {
    this.chooseButtonTarget.disabled = true;
    this.chooseButtonTarget.classList.add("opacity-50", "cursor-not-allowed");
    this.chooseButtonTarget.classList.remove("hover:bg-sky-700");

    // this.removeButtonTarget.disabled = false;
    // this.removeButtonTarget.classList.add("opacity-30", "cursor-not-allowed");
    // this.removeButtonTarget.classList.remove("hover:bg-red-900");
  }
}
