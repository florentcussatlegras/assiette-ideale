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
        tab.classList.replace("text-white", "text-dark-blue");
        tab.classList.replace("bg-light-blue", "bg-white");
      });

      currentTab.classList.replace("text-dark-blue", "text-white");
      currentTab.classList.replace("bg-white", "bg-light-blue");

      this.loadContent();
  }

  setDates(event) {
    // const target = event.currentTarget;

    // if (target.hasAttribute("data-start") && target.hasAttribute("data-end")) {
    //   this.startValue = target.dataset.start;
    //   this.endValue = target.dataset.end;
    //   this.startTarget.value = this.startValue;
    //   this.endTarget.value = this.endValue;
    // } else {
    //   this.startValue = this.startTarget.value;
    //   this.endValue = this.endTarget.value;
    // }

    // this.loadContent();
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

  // startValueChanged() {
  //     this.loadContent();
  // }

  // endValueChanged() {
  //     this.loadContent();
  // }

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
          t.classList.replace("text-white", "text-dark-blue");
          t.classList.replace("bg-light-blue", "bg-white");
        });

        if (!tab.classList.contains("date-picker")) {
          tab.classList.replace("text-dark-blue", "text-white");
          tab.classList.replace("bg-white", "bg-light-blue");
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
        // this.titleDateRangeTarget.innerHTML = `Mes bilans pour la période du ${this.startValue} au ${this.endValue}`;
        this.contentTarget.classList.remove("hidden");
        this.contentTarget.innerHTML = text;
        this.loaderTarget.classList.add("hidden");
      });

    // fetch(`${this.urlLoadDailyNutrientValue}?${params.toString()}`)
    //     .then((response) => {
    //         return response.text()
    //     })
    //     .then((text) => {
    //         this.averageNutrientTarget.innerHTML = text;
    //     });

    // fetch(`${this.urlLoadDailyFgpValue}?${params.toString()}`)
    //     .then((response) => {
    //         return response.text()
    //     })
    //     .then((text) => {
    //         this.averageFgpTarget.innerHTML = text;
    //     });
  }

  
  activateMatchingTab() {
    const tabs = document.querySelectorAll(".tabs-date-range_tab");

    const currentStart = this.startValue;
    const currentEnd = this.endValue;

    console.log(currentStart);
    console.log(currentEnd);

    let matched = false;

    tabs.forEach((tab) => {
        const tabStart = tab.dataset.start;
        const tabEnd = tab.dataset.end;
        console.log(tabStart);
        console.log(tabEnd);

        // reset styles
        tab.classList.replace("text-white", "text-dark-blue");
        tab.classList.replace("bg-light-blue", "bg-white");

        // match exact dates
        if (tabStart === currentStart && tabEnd === currentEnd) {
            console.log('ça match');
            tab.classList.replace("text-dark-blue", "text-white");
            tab.classList.replace("bg-white", "bg-light-blue");
            matched = true;
        }
    });
  }

}
