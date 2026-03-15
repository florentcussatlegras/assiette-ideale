import { Controller } from "@hotwired/stimulus";
import flatpickr from "flatpickr";
import "flatpickr/dist/flatpickr.min.css"; // Styles de base

export default class extends Controller {
  static targets = ["input"];

  connect() {
    this.fp = flatpickr(this.inputTarget, {
      dateFormat: "d/m/Y",
      allowInput: true,
      allowInvalidPreload: false
    });
  }

  handleSubmit(event) {
    // si aucune date
    if (!this.inputTarget.value) {
      // empêche la soumission
      event.preventDefault();

      // ouvre le calendrier
      this.fp.open();
    }

    const regex = /^\d{1,2}\/\d{1,2}\/\d{4}$/;
    // si date invalide même chose
    if (this.fp.selectedDates.length === 0) {
        event.preventDefault();

        this.fp.open();
    }
  }
}
