import { Controller } from '@hotwired/stimulus';

/**
 * Stimulus controller chargé d'afficher les détails des repas du jour depuis le tabelau des menus.
 */
export default class extends Controller {   

    // Stocke la date sélectionnée
    date = null;

    // Stocke le niveau d'alerte le plus élevé pour la journée
    highestAlertPerDay = null;

    // URL utilisée pour charger les repas via AJAX
    static values = {
        url: String
    }

    // Zone où le HTML retourné par la requête sera injecté
    static targets = ['content'];

    connect() {
        // Ajuste la hauteur des menus dropdown en fonction de leur parent
        const menuDropdowns = document.querySelectorAll('.menuDropdown');

        menuDropdowns.forEach((el) => {
            const dimensions = el.parentNode.getBoundingClientRect();
            el.height = dimensions.height;
        });
    }

    toggleSlideover() {
        // Ouvre ou ferme le panneau slideover
        document.getElementById('slideover-container').classList.toggle('invisible');
        document.getElementById('slideover-bg').classList.toggle('opacity-0');
        document.getElementById('slideover-bg').classList.toggle('opacity-50');
        document.getElementById('slideover').classList.toggle('translate-y-full');
    }

    setMeals(event) {
        // Récupère la date et le niveau d'alerte depuis l'élément cliqué
        this.date = event.currentTarget.dataset.date;
        this.highestAlertPerDay = event.currentTarget.dataset.highestAlertPerDay;

        // Charge les repas correspondants
        this.loadMeals();
    }

    async loadMeals() {
        // Prépare les paramètres envoyés au serveur
        const params = new URLSearchParams({
            'date': this.date,
            'highestAlertPerDay': this.highestAlertPerDay,
            'ajax': 1
        });

        // Requête AJAX pour récupérer les repas
        const response = await fetch(`${this.urlValue}?${params.toString()}`);

        // Injection du HTML retourné dans la zone cible
        this.contentTarget.innerHTML = await response.text();
    }

    openMenuDropdown(event) {
        // Empêche la propagation du clic
        event.stopPropagation();

        // Affiche le menu dropdown correspondant
        const menuDropdown = document.getElementById(event.currentTarget.dataset.menuDropdown);
        menuDropdown.classList.remove('hidden');
    }

    closeMenuDropdown(event) {
        // Cache le menu dropdown correspondant
        const menuDropdown = document.getElementById(event.currentTarget.dataset.menuDropdown);
        menuDropdown.classList.add('hidden');
    }

}