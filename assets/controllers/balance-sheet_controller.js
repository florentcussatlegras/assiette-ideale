import { Controller } from "@hotwired/stimulus";
import flatpickr from "flatpickr";
import "flatpickr/dist/flatpickr.min.css";
import Swal from "sweetalert2";

/**
 * Stimulus controller responsable du dashboard de statistiques nutritionnelles basé sur une plage de dates.
 */
export default class extends Controller {

  /**
   * Valeurs dynamiques injectées depuis Twig.
   * Utilisées pour construire les requêtes AJAX vers le backend.
   */
  static values = {
    start: String,
    end: String,
    urlLoadContent: String,
    urlLoadFavoriteDish: String,
    urlLoadFavoriteFood: String,
    urlLoadMostCaloricMeal: String,
  };

  /**
   * Références DOM utilisées par le controller.
   * Elles correspondent aux data-*-target déclarés dans le template Twig.
   */
  static targets = [
    "content",
    "contentFavorite",
    "loader",
    "loaderFavorite",
    "start",
    "end",
    "weightImcEnergy",
    "defaultDate",
    "favoriteDish",
    "favoriteFood",
    "showDetailsAlert",
    "hideDetailsAlert",
    "detailsAlert",
    "mostCaloricMeal",
  ];

  /**
   * Lifecycle Stimulus.
   * Initialise l'état du controller et déclenche le premier chargement.
   */
  connect() {
    this.initFlatpickr();
    this.initTabRangeStyle();
    this.initStartEndValues();

    // Synchronise l'onglet actif avec la plage de dates actuelle
    this.activateMatchingTab();

    // Chargement initial du dashboard
    this.loadContent();
  }

  // ------------------------------------------------------
  // 📌 1) Initialisation du sélecteur de dates
  // ------------------------------------------------------

  /**
   * Initialise les deux instances Flatpickr.
   *
   * Les pickers sont liés afin de garantir la cohérence
   * entre date de début et date de fin :
   * - start ne peut pas être > end
   * - end ne peut pas être < start
   */
  initFlatpickr() {
    if (!this.startTarget || !this.endTarget) return;

    this.startPicker = flatpickr(this.startTarget, {
      dateFormat: "Y-m-d",
      onChange: (selectedDates, dateStr) => {
        this.startValue = dateStr;

        // Si start dépasse end → on réaligne end automatiquement
        if (
          selectedDates[0] &&
          this.endPicker.selectedDates[0] &&
          selectedDates[0] > this.endPicker.selectedDates[0]
        ) {
          this.endPicker.setDate(selectedDates[0], true);
        }

        // Empêche de sélectionner une fin antérieure
        this.endPicker.set("minDate", selectedDates[0]);
      },
    });

    this.endPicker = flatpickr(this.endTarget, {
      dateFormat: "Y-m-d",
      onChange: (selectedDates, dateStr) => {
        this.endValue = dateStr;

        // Si end précède start → on réaligne start automatiquement
        if (
          selectedDates[0] &&
          this.startPicker.selectedDates[0] &&
          selectedDates[0] < this.startPicker.selectedDates[0]
        ) {
          this.startPicker.setDate(selectedDates[0], true);
        }

        // Empêche de sélectionner un début après la fin
        this.startPicker.set("maxDate", selectedDates[0]);
      },
    });
  }

  // ------------------------------------------------------
  // 📌 2) Gestion des interactions de sélection de dates
  // ------------------------------------------------------

  /**
   * Appliquée lorsqu'un utilisateur :
   * - clique sur un bouton de plage rapide
   * - valide une plage personnalisée
   *
   * Valide les données puis déclenche le rechargement
   * des statistiques pour la nouvelle période.
   */
  setDates(event) {
    const target = event.currentTarget;

    // Bouton rapide (ex: semaine, mois...)
    if (target.dataset.start && target.dataset.end) {
      this.startValue = target.dataset.start;
      this.endValue = target.dataset.end;

      this.startTarget.value = this.startValue;
      this.endTarget.value = this.endValue;
    } else {

      // Plage personnalisée
      this.startValue = this.startTarget.value;
      this.endValue = this.endTarget.value;

      // Validation simple côté UI
      if (!this.startValue || !this.endValue) {
        Swal.fire({
          icon: "warning",
          title: "Champs vides",
          text: "Veuillez saisir une date de début et une date de fin.",
        });
        return;
      }

      if (new Date(this.startValue) > new Date(this.endValue)) {
        Swal.fire({
          icon: "error",
          title: "Dates invalides",
          text: "La date de début doit être inférieure ou égale à la date de fin.",
        });
        return;
      }
    }

    // Synchronisation UI + rafraîchissement données
    this.activateMatchingTab();
    this.loadContent();
  }

  // ------------------------------------------------------
  // 📌 3) Initialisation des valeurs par défaut
  // ------------------------------------------------------

  /**
   * Détermine la plage de dates initiale :
   * - soit issue d'une navigation précédente
   * - soit définie par défaut dans le template
   */
  initStartEndValues() {
    const inputStart = document.getElementById("startFromWeekMenu");
    const inputEnd = document.getElementById("endFromWeekMenu");

    if (inputStart.value && inputEnd.value) {
      this.startValue = inputStart.value;
      this.endValue = inputEnd.value;

      this.startTarget.value = this.startValue;
      this.endTarget.value = this.endValue;
    } else {
      this.startValue = this.defaultDateTarget.dataset.start;
      this.endValue = this.defaultDateTarget.dataset.end;
    }
  }

  // ------------------------------------------------------
  // 📌 4) Gestion du style des onglets de période
  // ------------------------------------------------------

  /**
   * Gère le comportement visuel des onglets de plage rapide.
   * L'objectif est uniquement UI (pas de logique métier ici).
   */
  initTabRangeStyle() {
    const tabs = document.querySelectorAll(".tabs-date-range_tab");

    tabs.forEach((tab) => {
      tab.addEventListener("click", (e) => {

        // reset de tous les onglets
        tabs.forEach((t) => {
          t.classList.replace("text-white", "text-gray-900");
          t.classList.replace("bg-sky-600", "bg-gray-100");
        });

        // activation onglet sélectionné
        if (!tab.classList.contains("date-picker")) {
          tab.classList.replace("text-gray-900", "text-white");
          tab.classList.replace("bg-gray-100", "bg-sky-600");
        }
      });
    });
  }

  // ------------------------------------------------------
  // 📌 5) Chargement asynchrone des statistiques
  // ------------------------------------------------------

  /**
   * Charge les différents blocs statistiques via AJAX.
   *
   * Chaque bloc est indépendant afin de permettre
   * un rendu progressif et améliorer la perception
   * de performance côté utilisateur.
   */
  loadContent() {

    // état loading UI
    this.contentTarget.classList.add("hidden");
    this.contentFavoriteTarget.classList.add("hidden");
    this.loaderTarget.classList.remove("hidden");
    this.loaderFavoriteTarget.classList.remove("hidden");

    const params = new URLSearchParams({
      start: this.startValue,
      end: this.endValue,
      ajax: 1,
    });

    // bloc principal statistiques
    fetch(`${this.urlLoadContentValue}?${params}`)
      .then((r) => r.text())
      .then((html) => {
        this.contentTarget.innerHTML = html;
        this.contentTarget.classList.remove("hidden");
        this.loaderTarget.classList.add("hidden");

        // activation toggles des alert details
        this.showDetailsAlertTargets.forEach((btn, key) => {
          btn.addEventListener("click", () => {
            this.detailsAlertTargets[key].classList.toggle("hidden");
            btn.classList.toggle("open");
          });
        });
      });

    // statistiques secondaires
    fetch(`${this.urlLoadFavoriteDishValue}?${params}`)
      .then((r) => r.text())
      .then((html) => (this.favoriteDishTarget.innerHTML = html));

    fetch(`${this.urlLoadFavoriteFoodValue}?${params}`)
      .then((r) => r.text())
      .then((html) => (this.favoriteFoodTarget.innerHTML = html));

    fetch(`${this.urlLoadMostCaloricMealValue}?${params}`)
      .then((r) => r.text())
      .then((html) => {
        this.mostCaloricMealTarget.innerHTML = html;

        // affichage final du bloc favoris
        this.contentFavoriteTarget.classList.remove("hidden");
        this.loaderFavoriteTarget.classList.add("hidden");
      });
  }