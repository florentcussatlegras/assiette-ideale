import { Controller } from '@hotwired/stimulus';

// controllers/dropdown_controller.js
export default class extends Controller {
    static targets = ["menu"]

    connect() {
        console.log('connect dropdown controller');
    }

    toggle() {
        this.menuTarget.classList.toggle("opacity-100")
        this.menuTarget.classList.toggle("visible")
        this.menuTarget.classList.toggle("opacity-0")
        this.menuTarget.classList.toggle("invisible")
        this.menuTarget.classList.toggle("translate-y-0")
        this.menuTarget.classList.toggle("translate-y-2")
    }
}
