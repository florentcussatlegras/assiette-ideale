import { Controller } from "@hotwired/stimulus";
import flatpickr from "flatpickr";
import "flatpickr/dist/flatpickr.min.css";

export default class extends Controller {
  category = "Weight";

  static values = {
    start: String,
    end: String,
    urlLoadContent: String,
  };

  static targets = ["content", "loader", "start", "end", "defaultDate"];

  connect() {
    this.initFlatpickr();
    this.initStartEndValues();

    // 👉 Active le bon bouton par défaut
    this.activateMatchingTab();

    this.loadContent();
  }

  // ------------------------------------------------------
  // 📌 1) Initialisation de Flatpickr
  // ------------------------------------------------------
  initFlatpickr() {
    if (!this.startTarget || !this.endTarget) return;

    this.startPicker = flatpickr(this.startTarget, {
      dateFormat: "Y-m-d",
      onChange: (selectedDates, dateStr) => {
        this.startValue = dateStr;

        // Empêche start > end
        if (
          selectedDates[0] &&
          this.endPicker.selectedDates[0] &&
          selectedDates[0] > this.endPicker.selectedDates[0]
        ) {
          this.endPicker.setDate(selectedDates[0], true);
        }

        this.endPicker.set("minDate", selectedDates[0]);
      },
    });

    this.endPicker = flatpickr(this.endTarget, {
      dateFormat: "Y-m-d",
      onChange: (selectedDates, dateStr) => {
        this.endValue = dateStr;

        // Empêche end < start
        if (
          selectedDates[0] &&
          this.startPicker.selectedDates[0] &&
          selectedDates[0] < this.startPicker.selectedDates[0]
        ) {
          this.startPicker.setDate(selectedDates[0], true);
        }

        this.startPicker.set("maxDate", selectedDates[0]);
      },
    });
  }

  changeContent(event) {
      const currentTab = event.currentTarget;

      this.category = currentTab.dataset.category;

      const tabs = document.querySelectorAll(".tabs-content-evolution_tab");

      tabs.forEach((tab) => {
        tab.classList.replace("text-white", "text-gray-900");
        tab.classList.remove("hover:text-white");
        tab.classList.add("hover:text-gray-900");
        tab.classList.replace("bg-sky-600", "bg-gray-100");
        tab.classList.remove("hover:bg-sky-600");
        tab.classList.add("hover:bg-gray-900");
      });

      currentTab.classList.replace("text-gray-900", "text-white");
      currentTab.classList.remove("hover:text-gray-900");
      currentTab.classList.add("hover:text-white");
      currentTab.classList.replace("bg-gray-100", "bg-sky-600");
      currentTab.classList.remove("hover:bg-gray-900");
      currentTab.classList.add("hover:bg-sky-600");

      this.loadContent();
  }

  setDates(event) {
    const target = event.currentTarget;
    
        // Si bouton rapide → utilise ses dates prédéfinies
        if (target.dataset.start && target.dataset.end) {
          this.startValue = target.dataset.start;
          this.endValue = target.dataset.end;
    
          this.startTarget.value = this.startValue;
          this.endTarget.value = this.endValue;
        } else {
          // Sinon on prend les valeurs saisies
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
    
          // 🔴 Vérification start <= end
          if (new Date(this.startValue) > new Date(this.endValue)) {
            Swal.fire({
              icon: "error",
              title: "Dates invalides",
              text: "La date de début doit être inférieure ou égale à la date de fin.",
            });
            return;
          }
        }
    
        this.activateMatchingTab();
        this.loadContent();
  }

  // ------------------------------------------------------
  // 📌 3) Initialisation affichage et valeurs par défaut
  // ------------------------------------------------------
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
  // 📌 4) Gestion des onglets (style UI)
  // ------------------------------------------------------
  initTabRangeStyle() {
    const tabs = document.querySelectorAll(".tabs-date-range_tab");

    tabs.forEach((tab) => {
      tab.addEventListener("click", (e) => {
        tabs.forEach((t) => {
          t.classList.replace("text-white", "text-gray-900");
          t.classList.remove("hover:text-white");
          t.classList.add("hover:text-gray-900");
          t.classList.replace("bg-sky-600", "bg-gray-100");
          t.classList.remove("hover:bg-gray-600");
          t.classList.add("hover:bg-gray-900");
        });

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

  loadContent() {
    this.contentTarget.classList.add("hidden");
    this.loaderTarget.classList.remove("hidden");

    const params = new URLSearchParams({
      start: this.startValue,
      end: this.endValue,
      category: this.category,
      ajax: 1,
    });

    console.log(`${this.urlLoadContentValue}?${params.toString()}`);
    fetch(`${this.urlLoadContentValue}?${params.toString()}`)
      .then((response) => {
        return response.text();
      })
      .then((text) => {
        this.contentTarget.classList.remove("hidden");
        this.contentTarget.innerHTML = text;
        this.loaderTarget.classList.add("hidden");
      });
  }

  activateMatchingTab() {
    const tabs = document.querySelectorAll(".tabs-date-range_tab");

    const currentStart = this.startValue;
    const currentEnd = this.endValue;

    let matched = false;

    tabs.forEach((tab) => {
        const tabStart = tab.dataset.start;
        const tabEnd = tab.dataset.end;

        // reset styles
        tab.classList.replace("text-white", "text-gray-900");
        tab.classList.remove("hover:text-white");
        tab.classList.add("hover:text-gray-900");
        tab.classList.replace("bg-sky-600", "bg-gray-100");
        tab.classList.remove("hover:bg-sky-600");
        tab.classList.add("hover:bg-gray-900");

        // match exact dates
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
