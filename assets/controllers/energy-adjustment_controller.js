import { Controller } from '@hotwired/stimulus';

export default class extends Controller {

    static values = {
        urlAcceptEnergyAdjustement: String
    }

    static targets = ["text", "container"];

    connect() {
        console.log('connect energy adjustment controller');
        this.icon = document.getElementById('weightGoalIcon');
    }

    toggleMessage() {
        // Affiche le message et cache l'icône
        console.log('show message');
        this.containerTarget.classList.remove('hidden');
        this.icon.classList.add('hidden');
    }

    hide() {
        console.log('hide message');
        // Cache le message et affiche l'icône
        this.containerTarget.classList.add('hidden');
        this.icon.classList.remove('hidden');
    }

    accept() {
        // Appel fetch pour accepter le réajustement
        fetch(this.urlAcceptEnergyAdjustementValue, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                // on change le texte et supprime le bouton Accepter
                this.textTarget.textContent = "Vous êtes en réevaluation calorique afin de réajuster votre IMC.";
                const buttons = this.element.querySelectorAll('button[data-action="click->weight-goal#accept"]');
                buttons.forEach(btn => btn.remove());
            }
        })
        .catch(err => console.error(err));
    }
}
