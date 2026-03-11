import { Controller } from "@hotwired/stimulus"

export default class extends Controller {
  connect() {
    window.addEventListener("flash", this.showFlash.bind(this))
  }

  showFlash(event) {
    const { message, type } = event.detail

    const flash = document.createElement("div")
    flash.textContent = message

    flash.className = `
      fixed top-5 right-5 z-50 px-6 py-3 rounded-xl shadow-lg
      text-white transition-opacity duration-300
      ${type === "success" ? "bg-green-600" : "bg-red-600"}
    `

    document.body.appendChild(flash)

    setTimeout(() => {
      flash.classList.add("opacity-0")
      setTimeout(() => flash.remove(), 300)
    }, 2500)
  }
}