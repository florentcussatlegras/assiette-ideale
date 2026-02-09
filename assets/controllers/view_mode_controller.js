// assets/controllers/view_mode_controller.js
import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["wrapper", "listBtn", "gridBtn"];

    connect() {
        // console.log("ViewMode controller connected");
    }

    list(event) {
        event.preventDefault();
        this.wrapperTarget.classList.add("list");
        this.gridBtnTarget.classList.remove("view-mode-switch__display--selected");
        this.listBtnTarget.classList.add("view-mode-switch__display--selected");
    }

    grid(event) {
        event.preventDefault();
        this.wrapperTarget.classList.remove("list");
        this.listBtnTarget.classList.remove("view-mode-switch__display--selected");
        this.gridBtnTarget.classList.add("view-mode-switch__display--selected");
    }
}
