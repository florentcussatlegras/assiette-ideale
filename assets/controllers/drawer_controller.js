import { Controller } from "@hotwired/stimulus"

export default class extends Controller {
  static targets = ["menu", "overlay"]

  connect() {
    this.close() // ferme le menu au départ
  }

  toggle() {
    if (this.menuTarget.classList.contains("-translate-x-full")) {
      this.open()
    } else {
      this.close()
    }
  }

  open() {
    this.menuTarget.classList.remove("-translate-x-full")
    this.menuTarget.classList.add("translate-x-0")
    this.overlayTarget.classList.remove("invisible", "opacity-0")
    this.overlayTarget.classList.add("visible", "opacity-100")
  }

  close() {
    this.menuTarget.classList.add("-translate-x-full")
    this.menuTarget.classList.remove("translate-x-0")
    this.overlayTarget.classList.add("invisible", "opacity-0")
    this.overlayTarget.classList.remove("visible", "opacity-100")
  }
}
