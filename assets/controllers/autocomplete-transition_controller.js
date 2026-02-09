import { Controller } from '@hotwired/stimulus';
import { addFadeTransition } from '../util/add-transition';

export default class extends Controller {
    static targets = ['results', 'illustration'];

    connect(event) {
        addFadeTransition(this, this.resultsTarget);
    }
    
    toggle(event) {
        if (event.detail.action === 'open') {
            this.enter();
        } else {
            this.leave();
        }
    }

    enter() {
        this.illustrationTarget.classList.add('hidden-fade');
    }

    leave() {
        this.illustrationTarget.classList.remove('hidden-fade');
    }
}

