import flatpickr from "flatpickr";
import "flatpickr/dist/flatpickr.min.css";
import Swal from "sweetalert2";

// Récupère les éléments
const startInput = document.getElementById('startDate');
const endInput = document.getElementById('endDate');
const validateBtn = document.getElementById('validateDates');
const hiddenStart = document.getElementById('startFromWeekMenu');
const hiddenEnd = document.getElementById('endFromWeekMenu');

// Vérifie que tous les éléments existent avant d'initialiser
if (startInput) {

    // Initialise Flatpickr
    const startPicker = flatpickr(startInput, {
        dateFormat: "m/d/Y",
        onChange: function(selectedDates, dateStr) {
            if (hiddenStart) hiddenStart.value = dateStr;

            if (selectedDates[0] && endPicker.selectedDates[0] && selectedDates[0] > endPicker.selectedDates[0]) {
                endPicker.setDate(selectedDates[0], true);
            }
            endPicker.set('minDate', selectedDates[0]);
        }
    });

    const endPicker = flatpickr(endInput, {
        dateFormat: "m/d/Y",
        onChange: function(selectedDates, dateStr) {
            if (hiddenEnd) hiddenEnd.value = dateStr;

            if (selectedDates[0] && startPicker.selectedDates[0] && selectedDates[0] < startPicker.selectedDates[0]) {
                startPicker.setDate(selectedDates[0], true);
            }
            startPicker.set('maxDate', selectedDates[0]);
        }
    });

    // Bouton "Valider" avec validation et SweetAlert2
    validateBtn.addEventListener('click', () => {

        // Vérifie que les deux champs ne sont pas vides
        if (!startInput.value || !endInput.value) {
            Swal.fire({
                icon: 'warning',
                title: 'Champs vides',
                text: 'Veuillez saisir une date de début et une date de fin.',
                confirmButtonColor: '#0ea5e9'
            });
            return;
        }

        // Vérifie que la date de début < date de fin
        if (new Date(startInput.value) > new Date(endInput.value)) {
            Swal.fire({
                icon: 'error',
                title: 'Dates invalides',
                text: 'La date de début doit être inférieure ou égale à la date de fin.',
                confirmButtonColor: '#ef4444'
            });
            return;
        }

        // Met à jour les inputs cachés
        if (hiddenStart) hiddenStart.value = startInput.value;
        if (hiddenEnd) hiddenEnd.value = endInput.value;

        console.log("Dates choisies :", startInput.value, endInput.value);

        // Ici tu peux déclencher ton action Stimulus ou soumettre le formulaire
    });
}
