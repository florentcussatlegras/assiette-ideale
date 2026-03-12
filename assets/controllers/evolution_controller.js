import { Controller } from "@hotwired/stimulus";
import flatpickr from "flatpickr";
import "flatpickr/dist/flatpickr.min.css";

/**
 * Stimulus controller chargé de gérer l'affichage
 * des évolutions de poids, imc et consommations nutriments, fgp etc
 */
export default class extends Controller {
  // Catégorie par défaut pour le contenu chargé
  category = "Weight";

  // Déclaration des valeurs passées depuis le HTML
  static values = {
    start: String,        // Date de début
    end: String,          // Date de fin
    urlLoadContent: String, // URL pour charger le contenu via fetch
  };

  // Déclaration des targets HTML
  static targets = ["content", "loader", "start", "end", "defaultDate"];

  // Méthode exécutée au moment de la connexion du controller
  connect() {
    this.initFlatpickr();       // Initialisation des datepickers
    this.initStartEndValues();  // Lecture des valeurs par défaut des dates

    // 👉 Active l'onglet correspondant à la plage de dates actuelle
    this.activateMatchingTab();

    // Chargement initial du contenu
    this.loadContent();
  }

  // ------------------------------------------------------
  // 📌 1) Initialisation de Flatpickr pour les inputs start et end
  // ------------------------------------------------------
  initFlatpickr() {
    if (!this.startTarget || !this.endTarget) return;

    // Flatpickr pour la date de début
    this.startPicker = flatpickr(this.startTarget, {
      dateFormat: "Y-m-d",
      onChange: (selectedDates, dateStr) => {
        this.startValue = dateStr;

        // Empêche la date de début d'être après la date de fin
        if (
          selectedDates[0] &&
          this.endPicker.selectedDates[0] &&
          selectedDates[0] > this.endPicker.selectedDates[0]
        ) {
          this.endPicker.setDate(selectedDates[0], true);
        }

        // Met à jour la date minimale possible pour end
        this.endPicker.set("minDate", selectedDates[0]);
      },
    });

    // Flatpickr pour la date de fin
    this.endPicker = flatpickr(this.endTarget, {
      dateFormat: "Y-m-d",
      onChange: (selectedDates, dateStr) => {
        this.endValue = dateStr;

        // Empêche la date de fin d'être avant la date de début
        if (
          selectedDates[0] &&
          this.startPicker.selectedDates[0] &&
          selectedDates[0] < this.startPicker.selectedDates[0]
        ) {
          this.startPicker.setDate(selectedDates[0], true);
        }

        // Met à jour la date maximale possible pour start
        this.startPicker.set("maxDate", selectedDates[0]);
      },
    });
  }

  // ------------------------------------------------------
  // 📌 2) Changement de contenu en fonction de l'onglet sélectionné
  // ------------------------------------------------------
  changeContent(event) {
      const currentTab = event.currentTarget;

      // Récupère la catégorie associée à l'onglet
      this.category = currentTab.dataset.category;

      // Reset style de tous les onglets
      const tabs = document.querySelectorAll(".tabs-content-evolution_tab");
      tabs.forEach((tab) => {
        tab.classList.replace("text-white", "text-gray-900");
        tab.classList.remove("hover:text-white");
        tab.classList.add("hover:text-gray-900");
        tab.classList.replace("bg-sky-600", "bg-gray-100");
        tab.classList.remove("hover:bg-sky-600");
        tab.classList.add("hover:bg-gray-900");
      });

      // Applique le style actif à l'onglet sélectionné
      currentTab.classList.replace("text-gray-900", "text-white");
      currentTab.classList.remove("hover:text-gray-900");
      currentTab.classList.add("hover:text-white");
      currentTab.classList.replace("bg-gray-100", "bg-sky-600");
      currentTab.classList.remove("hover:bg-gray-900");
      currentTab.classList.add("hover:bg-sky-600");

      // Recharge le contenu correspondant
      this.loadContent();
  }

  // ------------------------------------------------------
  // 📌 3) Gestion des boutons de sélection rapide et validation des dates
  // ------------------------------------------------------
  setDates(event) {
    const target = event.currentTarget;
    
    // Si bouton rapide → utiliser les dates prédéfinies
    if (target.dataset.start && target.dataset.end) {
      this.startValue = target.dataset.start;
      this.endValue = target.dataset.end;

      this.startTarget.value = this.startValue;
      this.endTarget.value = this.endValue;
    } else {
      // Sinon on prend les valeurs saisies manuellement
      this.startValue = this.startTarget.value;
      this.endValue = this.endTarget.value;

      // 🔴 Vérification champs vides
      if (!this.startValue || !this.endValue) {
        Swal.fire({
          icon: "warning",
          title: "Champs vides",
          text: "Veuillez saisir une date de début et une date de fin.",
        });
        return;
      }

      // 🔴 Vérification que start <= end
      if (new Date(this.startValue) > new Date(this.endValue)) {
        Swal.fire({
          icon: "error",
          title: "Dates invalides",
          text: "La date de début doit être inférieure ou égale à la date de fin.",
        });
        return;
      }
    }

    // Active l'onglet correspondant aux dates choisies
    this.activateMatchingTab();

    // Recharge le contenu
    this.loadContent();
  }

  // ------------------------------------------------------
  // 📌 4) Initialisation des valeurs par défaut au chargement
  // ------------------------------------------------------
  initStartEndValues() {
    const inputStart = document.getElementById("startFromWeekMenu");
    const inputEnd = document.getElementById("endFromWeekMenu");

    if (inputStart.value && inputEnd.value) {
      // Si des dates sont déjà présentes dans le DOM
      this.startValue = inputStart.value;
      this.endValue = inputEnd.value;

      this.startTarget.value = this.startValue;
      this.endTarget.value = this.endValue;
    } else {
      // Sinon on prend les valeurs par défaut du dataset
      this.startValue = this.defaultDateTarget.dataset.start;
      this.endValue = this.defaultDateTarget.dataset.end;
    }
  }

  // ------------------------------------------------------
  // 📌 5) Gestion du style des onglets de plage de dates
  // ------------------------------------------------------
  initTabRangeStyle() {
    const tabs = document.querySelectorAll(".tabs-date-range_tab");

    tabs.forEach((tab) => {
      tab.addEventListener("click", (e) => {
        // Reset style de tous les onglets
        tabs.forEach((t) => {
          t.classList.replace("text-white", "text-gray-900");
          t.classList.remove("hover:text-white");
          t.classList.add("hover:text-gray-900");
          t.classList.replace("bg-sky-600", "bg-gray-100");
          t.classList.remove("hover:bg-gray-600");
          t.classList.add("hover:bg-gray-900");
        });

        // Applique le style actif à l'onglet cliqué si ce n'est pas un date-picker
        if (!tab.classList.contains("date-picker")) {
          tab.classList.replace("text-gray-900", "text-white");
          tab.classList.remove("hover:text-gray-900");
          tab.classList.add("hover:text-white");
          tab.classList.replace("bg-gray-100", "bg-sky-600");
          tab.classList.remove("hover:bg-gray-900");
          tab.classList.add("hover:bg-sky-600");
        }
      });
    });
  }

  // ------------------------------------------------------
  // 📌 6) Chargement du contenu via AJAX
  // ------------------------------------------------------
  loadContent() {
    this.contentTarget.classList.add("hidden");   // Masque le contenu
    this.loaderTarget.classList.remove("hidden"); // Affiche le loader

    // Construction des paramètres pour la requête
    const params = new URLSearchParams({
      start: this.startValue,
      end: this.endValue,
      category: this.category,
      ajax: 1,
    });

    console.log(`${this.urlLoadContentValue}?${params.toString()}`);

    // Requête fetch pour charger le contenu
    fetch(`${this.urlLoadContentValue}?${params.toString()}`)
      .then((response) => {
        return response.text();
      })
      .then((text) => {
        // Affichage du contenu et masquage du loader
        this.contentTarget.classList.remove("hidden");
        this.contentTarget.innerHTML = text;
        this.loaderTarget.classList.add("hidden");
      });
  }

  // ------------------------------------------------------
  // 📌 7) Active l'onglet correspondant aux dates actuelles
  // ------------------------------------------------------
  activateMatchingTab() {
    const tabs = document.querySelectorAll(".tabs-date-range_tab");

    const currentStart = this.startValue;
    const currentEnd = this.endValue;

    let matched = false;

    tabs.forEach((tab) => {
        const tabStart = tab.dataset.start;
        const tabEnd = tab.dataset.end;

        // Reset style de tous les onglets
        tab.classList.replace("text-white", "text-gray-900");
        tab.classList.remove("hover:text-white");
        tab.classList.add("hover:text-gray-900");
        tab.classList.replace("bg-sky-600", "bg-gray-100");
        tab.classList.remove("hover:bg-sky-600");
        tab.classList.add("hover:bg-gray-900");

        // Si l'onglet correspond exactement aux dates → style actif
        if (tabStart === currentStart && tabEnd === currentEnd) {
            tab.classList.replace("text-gray-900", "text-white");
            tab.classList.remove("hover:text-gray-900");
            tab.classList.add("hover:text-white");
            tab.classList.replace("bg-gray-100", "bg-sky-600");
            tab.classList.remove("hover:bg-gray-900");
            tab.classList.add("hover:bg-sky-600");

            matched = true;
        }
    });
  }
}