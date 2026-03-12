import { Controller } from "@hotwired/stimulus";

/**
 * Stimulus controller pour gérer la sélection d'un repas
 * dans une modal ou une liste.
 *
 * Fonctionnalités principales :
 * - sélectionner / désélectionner un repas
 * - activer / désactiver le bouton "choisir"
 * - redirection ou ajout AJAX d'un repas
 * - gestion d'un accordéon dans la carte
 */
export default class extends Controller {

  /**
   * Valeurs injectées depuis Twig :
   * - urlAddModelMealToMealsDay : URL pour ajouter un repas au jour
   * - fromModal : indique si on est dans une modal
   */
  static values = {
    urlAddModelMealToMealsDay: String,
    fromModal: Boolean,
  }

  /**
   * Targets DOM utilisées :
   * - card : chaque carte de repas
   * - chooseButton : bouton pour confirmer la sélection
   * - typeName : nom du type de repas
   * - name : nom du repas
   */
  static targets = ["card", "chooseButton", "typeName", "name"];

  connect() {
    this.selectedCard = null;
  }

  /**
   * Toggle la sélection d'une carte.
   * - gère l'apparence de la carte et du texte
   * - active/désactive le bouton "choisir"
   * - ouvre l'accordéon si besoin
   */
  toggleSelect(event) {
    if (event.defaultPrevented || !this.fromModalValue) return;

    const clickedCard = event.currentTarget;
    let selection = !clickedCard.classList.contains("border-2");

    // Désélectionne toutes les cartes
    this.cardTargets.forEach(card => {
      card.classList.remove("border-2", "border-sky-600");
    });
    this.typeNameTargets.forEach(typeName => {
      typeName.classList.remove("text-sky-600");
      typeName.classList.add("text-neutral-500");
    });
    this.nameTargets.forEach(name => {
      name.classList.remove("text-sky-600");
      name.classList.add("text-neutral-500");
    });

    if (selection) {
      // Sélectionne la carte cliquée
      clickedCard.classList.add("border-2", "border-sky-600");
      this.selectedCard = clickedCard;

      const name = clickedCard.querySelector('[data-model-meal-select-target="name"]');
      const typeName = clickedCard.querySelector('[data-model-meal-select-target="typeName"]');

      if (typeName) typeName.classList.replace("text-neutral-500", "text-sky-600");
      if (name) name.classList.replace("text-neutral-500", "text-sky-600");

      this.enableButton();
    } else {
      this.selectedCard = null;
      this.disableButton();
    }

    // Ouvre l'accordéon si la carte possède un panel fermé
    const accordionButton = clickedCard.querySelector('[data-action*="menu-accordion#toggle"]');
    const panel = clickedCard.querySelector('[data-menu-accordion-target="panel"]');
    if (accordionButton && panel && panel.classList.contains("max-h-0")) {
      accordionButton.click();
    }
  }

  /**
   * Empêche le toggleSelect de se déclencher
   */
  ignore(event) {
    event.preventDefault();
  }

  /**
   * Redirige vers l'URL du bouton si présente
   */
  redirect(event) {
    const url = event.currentTarget.dataset.href;
    if (url) window.location.href = url;
  }

  /**
   * Active le bouton "choisir"
   */
  enableButton() {
    this.chooseButtonTarget.disabled = false;
    this.chooseButtonTarget.classList.remove("opacity-50", "cursor-not-allowed");
    this.chooseButtonTarget.classList.add("hover:bg-sky-700");
  }

  /**
   * Désactive le bouton "choisir"
   */
  disableButton() {
    this.chooseButtonTarget.disabled = true;
    this.chooseButtonTarget.classList.add("opacity-50", "cursor-not-allowed");
    this.chooseButtonTarget.classList.remove("hover:bg-sky-700");
  }

  /**
   * Ajoute le repas sélectionné via AJAX
   * - met à jour le front-end ou redirige si nécessaire
   * - déclenche un événement global "meal:added"
   */
  async choose() {
    if (!this.selectedCard) return;

    const mealId = this.selectedCard.dataset.mealId;
    const url = this.urlAddModelMealToMealsDayValue.replace(0, mealId);

    try {
      const response = await fetch(url, {
        headers: { "X-Requested-With": "XMLHttpRequest" }
      });

      if (!response.ok) throw new Error("Erreur serveur");

      const data = await response.json();
      console.log("Succès :", data);

      if (data.redirectUrl) window.location.href = data.redirectUrl;

      this.element.dispatchEvent(new CustomEvent("meal:added", {
        bubbles: true,
        detail: { mealId }
      }));

    } catch (error) {
      console.error("Erreur lors de l'ajout :", error);
    }
  }
}