import { Controller } from "@hotwired/stimulus";

export default class extends Controller {

  static values = {
    urlAddModelMealToMealsDay: String,
    fromModal: Boolean,
  }

  static targets = ["card", "chooseButton"];

  connect() {
    this.selectedCard = null;
  }

  toggleSelect(event) {
    // Si on a cliqué sur une zone ignorée, on sort
    if (event.defaultPrevented) return;

    if (!this.fromModalValue) return;

    const clickedCard = event.currentTarget;
    let selection = true;

    if(clickedCard.classList.contains("border-2") == true) {
        selection = false;
    }

    // Déselectionner tous les repas
    this.cardTargets.forEach(card => {
        card.classList.remove("border-2");
        card.classList.remove("border-sky-600");
    });
   
    if(selection == true) {
        // Sélectionner le repas
        clickedCard.classList.add("border-2");
        clickedCard.classList.add("border-sky-600");

        this.selectedCard = clickedCard;

        // Activer les boutons
        this.enableButton();
    }else{
        this.selectedCard = null;

        // Désactiver les boutons
        this.disableButton();
    }
  }

  ignore(event) {
    // Empêche le toggleSelect de se déclencher
    event.preventDefault();
  }

  redirect(event) {
      const button = event.currentTarget;
      const url = button.dataset.href;

      if(url) {
          window.location.href = url;
      }
  }

  enableButton() {
    this.chooseButtonTarget.disabled = false;
    this.chooseButtonTarget.classList.remove("opacity-50", "cursor-not-allowed");
    this.chooseButtonTarget.classList.add("hover:bg-sky-700");

    // this.removeButtonTarget.disabled = false;
    // this.removeButtonTarget.classList.remove("opacity-30", "cursor-not-allowed");
    // this.removeButtonTarget.classList.add("hover:bg-red-900");
  }

  disableButton() {
    this.chooseButtonTarget.disabled = true;
    this.chooseButtonTarget.classList.add("opacity-50", "cursor-not-allowed");
    this.chooseButtonTarget.classList.remove("hover:bg-sky-700");

    // this.removeButtonTarget.disabled = false;
    // this.removeButtonTarget.classList.add("opacity-30", "cursor-not-allowed");
    // this.removeButtonTarget.classList.remove("hover:bg-red-900");
  }

  async choose() {
  
    if (!this.selectedCard) return;

    const mealId = this.selectedCard.dataset.mealId;

    // Construction de l’URL finale
    const url = this.urlAddModelMealToMealsDayValue.replace(0, mealId);
    console.log(url);

    try {
      const response = await fetch(url, {
        headers: {
          "X-Requested-With": "XMLHttpRequest"
        }
      });

      if (!response.ok) {
        throw new Error("Erreur serveur");
      }

      const data = await response.json();

      console.log("Succès :", data);

      if (data.redirectUrl) {
        window.location.href = data.redirectUrl;
      }

      // Exemple : tu peux déclencher un event global
      this.element.dispatchEvent(new CustomEvent("meal:added", {
        bubbles: true,
        detail: { mealId }
      }));

    } catch (error) {
      console.error("Erreur lors de l'ajout :", error);
    }
  }
}
