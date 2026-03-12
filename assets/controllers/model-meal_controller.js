import { Controller } from "@hotwired/stimulus";
import Swal from "sweetalert2";

/**
 * Stimulus controller pour gérer la modale des repas préenregistrés de l'utilisateur.
 * 
 * Fonctionnalités principales :
 * - ouverture de la modale avec vérifications sur le dernier repas
 * - suppression d'un repas avec confirmation
 * - filtrage dynamique des repas (calories, recherche, types, tri)
 * - gestion du loader et du bouton "choisir"
 */
export default class extends Controller {

  /**
   * Valeurs injectées depuis Twig :
   * - urlList : URL pour récupérer la liste filtrée de repas
   * - urlListModal : URL pour charger le contenu de la modale
   * - urlRemove : URL pour supprimer un repas
   * - lastRankMeal : index/ID du dernier repas
   */
  static values = {
    urlList: String,
    urlListModal: String,
    urlRemove: String,
    lastRankMeal: String,
  };

  /**
   * Targets DOM utilisées :
   * - content : zone principale où s'affiche la liste de repas
   * - modalContent : contenu de la modale
   * - background : arrière-plan de la modale
   * - container : conteneur global
   * - search : input recherche
   * - types : filtres types de repas
   * - sort : select de tri
   * - loader : loader affiché lors des requêtes AJAX
   * - chooseButton : bouton "choisir" ou action principale
   */
  static targets = [
    "content", "modalContent", "background", "container",
    "search", "types", "sort", "loader", "chooseButton"
  ];

  /**
   * Lifecycle Stimulus.
   * Initialise les filtres de calories.
   */
  connect() {
    console.log("connect to meal controller");

    this.filters = {
      minCalories: null,
      maxCalories: null,
    };
  }

  /**
   * Affiche la modale.
   * Vérifie que le dernier repas existe et a un type sélectionné.
   * Charge le contenu via AJAX si nécessaire.
   */
  async show(event) {
    const lastMealElement = document.getElementById('meal-' + this.lastRankMealValue);
    const dishes = lastMealElement.getElementsByClassName('row-dish');

    // Vérifie le dernier repas et les plats
    const types = lastMealElement.getElementsByClassName('type-meal');
    const typeChecked = Array.from(types).some(el => el.checked);

    if (dishes.length === 0 || !typeChecked) {
      const message = !typeChecked
        ? "Vous n'avez pas précisé de type pour votre dernier repas"
        : "Vous n'avez pas saisis de plats pour votre dernier repas";

      Swal.fire({
        title: "Attention!",
        text: message,
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
      });

      // Empêche l'ouverture de la modale
      event.preventDefault();
      event.stopImmediatePropagation();
      return;
    }

    // Charge la modale via AJAX
    if (!this.hasModalContentTarget) return;
    const target = this.modalContentTarget;
    target.innerHTML = '<div class="loader"></div>';

    const response = await fetch(this.urlListModalValue, {
      headers: { "X-Requested-With": "XMLHttpRequest" }
    });
    target.innerHTML = await response.text();
  }

  /**
   * Supprime un repas après confirmation utilisateur.
   * Recharge ensuite la liste filtrée.
   */
  async onRemoveMeal(event) {
    event.preventDefault();

    const button = event.currentTarget;
    const mealId = button.dataset.modelMealId;
    const csrfToken = button.dataset.csrf;

    if (!mealId || !csrfToken) {
      console.error("ID ou CSRF manquant !");
      return;
    }

    const result = await Swal.fire({
      title: "Êtes-vous sûr ?",
      text: "Voulez-vous vraiment supprimer ce repas ?",
      icon: "warning",
      showCancelButton: true,
      confirmButtonText: "Oui, supprimer",
      cancelButtonText: "Annuler",
      confirmButtonColor: "#dc2626",
      cancelButtonColor: "#6b7280",
    });

    if (!result.isConfirmed) return;

    try {
      const response = await fetch(
        `${this.urlRemoveValue}/${mealId}?ajax=1&_token=${csrfToken}`,
        { headers: { "X-Requested-With": "XMLHttpRequest" } }
      );

      if (!response.ok) throw new Error("Erreur lors de la suppression du repas");

      await this.applyFilters();
    } catch (error) {
      alert("Impossible de supprimer le repas, veuillez réessayer.");
    }
  }

  /**
   * Met à jour les filtres de calories depuis le composant slider
   */
  onCaloriesChange(event) {
    this.filters.minCalories = event.detail.min;
    this.filters.maxCalories = event.detail.max;
    this.applyFilters();
  }

  /**
   * Applique tous les filtres (calories, recherche, types, tri)
   * et recharge la liste de repas via AJAX.
   */
  async applyFilters() {
    const params = new URLSearchParams();

    if (this.filters.minCalories !== null) params.append("minCalories", this.filters.minCalories);
    if (this.filters.maxCalories !== null) params.append("maxCalories", this.filters.maxCalories);

    if (this.hasSearchTarget && this.searchTarget.value.trim() !== "")
      params.append("search", this.searchTarget.value.trim());

    const selectedTypes = this.typesTargets.filter(input => input.checked).map(input => input.value);
    selectedTypes.forEach(type => params.append("types[]", type));

    if (this.hasSortTarget) params.append("sort", this.sortTarget.value);

    // Affiche le loader
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

  /**
   * Désactive le bouton "choisir" pour éviter les actions multiples
   */
  disableButton() {
    this.chooseButtonTarget.disabled = true;
    this.chooseButtonTarget.classList.add("opacity-50", "cursor-not-allowed");
    this.chooseButtonTarget.classList.remove("hover:bg-sky-700");
  }
}