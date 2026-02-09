import { Controller } from '@hotwired/stimulus';
import flatpickr from "flatpickr";
import "flatpickr/dist/flatpickr.min.css"; // Styles de base

export default class extends Controller {
    connect() {
        flatpickr('.datepicker-input', {
            dateFormat: "d/m/Y",       // Format de la date
            allowInput: true,           // Permet la saisie manuelle
        });
    }
}
