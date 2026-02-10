import { Controller } from '@hotwired/stimulus';

export default class extends Controller {

    connect() {
        const choice = localStorage.getItem('cookie-consent');

        if (!choice) {
            this.element.classList.remove('hidden');
        } else if (choice === 'accepted') {
            this.loadTrackingScripts();
        }
    }

    accept() {
        localStorage.setItem('cookie-consent', 'accepted');
        this.element.classList.add('hidden');

        this.loadTrackingScripts();
    }

    reject() {
        localStorage.setItem('cookie-consent', 'rejected');
        this.element.classList.add('hidden');
    }

    loadTrackingScripts() {
        console.log("Cookies acceptés");
    }
    
}
