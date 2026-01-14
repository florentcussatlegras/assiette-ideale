import { Controller } from "@hotwired/stimulus";
import Swal from "sweetalert2";

export default class extends Controller {
  static values = {
    url: String,
    urlRemove: String,
  };
  static targets = ["content", "background", "container"];

  connect() {
    console.log("connect to model meal controller");
  }

  async show(event) {
    this.url = event.currentTarget.dataset.url;
    console.log("je suis ici");
    console.log(this.url);
    this.urlRedirect = event.currentTarget.dataset.urlRedirect;

    // const rankMeal = event.currentTarget.dataset.rankMeal;

    // if(rankMeal !== "none") {

    //     const lastMealElement = document.getElementById('meal-' + rankMeal);

    //     // On vérifie que le dernier repas a bien un type
    //     const types = lastMealElement.getElementsByClassName('type-meal');
    //     let typeChecked = false;

    //     Array.from(types).forEach((element) => {
    //         if(element.checked == true) {
    //             typeChecked = true;
    //         }
    //     });

    //     if(typeChecked == false) {
    //         Swal.fire({
    //             title: "Ouch!",
    //             text: "Vous n'avez pas précisé de type pour votre dernier repas",
    //             icon: "warning"
    //         })

    //         return;
    //     }

    //     // On vérifie que le dernier repas contient bien des plats/aliments
    //     const dishes = lastMealElement.getElementsByClassName('row-dish');
    //     if (dishes.length === 0 ) {
    //         Swal.fire({
    //             title: "Ouch!",
    //             text: "Vous n'avez pas saisis de plats pour votre dernier repas",
    //             icon: "warning"
    //         })

    //         return;
    //     }
    // }

    const target = this.hasContentTarget ? this.contentTarget : this.element;

    // document.getElementById('model-meal').classList.replace('hidden', 'flex');

    target.style.opacity = 0.5;
    const response = await fetch(this.urlValue);
    target.innerHTML = await response.text();
    target.style.opacity = 1;
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

        const html = await response.text();

        if (this.hasContentTarget) {
          this.contentTarget.innerHTML = html;
        }
      } catch (error) {
        console.error(error);
        alert("Impossible de supprimer le repas, veuillez réessayer.");
      }
    });

  }

  // hideMeals() {
  //     document.getElementById('model-meal').classList.replace('flex', 'hidden');
  // }

  // async refreshContent(event) {
  //     const target = this.hasContentTarget ? this.contentTarget : this.element;

  //     target.style.opacity = .5;
  //     const response = await fetch(this.urlValue);
  //     target.innerHTML = await response.text();
  //     target.style.opacity = 1;
  // }
}
