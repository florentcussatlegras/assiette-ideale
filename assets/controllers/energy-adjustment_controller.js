import { Controller } from '@hotwired/stimulus';

/**
 * Stimulus controller chargé de gérer du message de réajustement calorique.
 */
export default class extends Controller {

    /**
     * Valeur injectée depuis Twig :
     * URL pour accepter le réajustement énergétique via AJAX.
     */
    static values = {
        urlAcceptEnergyAdjustement: String
    }

    /**
     * Targets DOM utilisées par le controller :
     * - text : conteneur texte à mettre à jour
     * - container : conteneur du message
     */
    static targets = ["text", "container"];

    /**
     * Lifecycle Stimulus.
     * Récupère l'icône représentant le statut poids / objectif.
     */
    connect() {
        this.icon = document.getElementById('weightGoalIcon');
    }

    /**
     * Affiche le message d'alerte et cache l'icône.
     * Utilisé lorsqu'on veut notifier l'utilisateur d'un réajustement.
     */
    toggleMessage() {
        console.log('show message');
        this.containerTarget.classList.remove('hidden');
        this.icon.classList.add('hidden');
    }

    /**
     * Cache le message d'alerte et affiche à nouveau l'icône.
     */
    hide() {
        console.log('hide message');
        this.containerTarget.classList.add('hidden');
        this.icon.classList.remove('hidden');
    }

    /**
     * Accepte le réajustement calorique.
     * - Envoie un POST au backend via fetch
     * - Inclut le token CSRF pour sécurité
     * - Met à jour dynamiquement le texte et supprime le bouton Accepter
     */
    accept() {
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
            if (data.success) {
                // Mise à jour du message et suppression du bouton
                this.textTarget.textContent = "Vous êtes en réevaluation calorique afin de réajuster votre IMC.";
                const buttons = this.element.querySelectorAll('button[data-action="click->weight-goal#accept"]');
                buttons.forEach(btn => btn.remove());
            }
        })
        .catch(err => console.error(err));
    }
}