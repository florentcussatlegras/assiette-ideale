import { Controller } from "@hotwired/stimulus"

/**
 * Stimulus controller chargé de gérer l'affichage des messages flash
 */
export default class extends Controller {

  connect() {
    // Écoute les événements personnalisés "flash" sur window
    window.addEventListener("flash", this.showFlash.bind(this))
  }

  // Méthode pour afficher un message flash
  showFlash(event) {
    // Récupère le message et le type depuis l'événement
    const { message, type } = event.detail

    // Crée un nouvel élément <div> pour le flash
    const flash = document.createElement("div")
    flash.textContent = message

    // Définition des classes CSS pour le style et l'animation
    flash.className = `
      fixed top-5 right-5 z-50 px-6 py-3 rounded-xl shadow-lg
      text-white transition-opacity duration-300
      ${type === "success" ? "bg-green-600" : "bg-red-600"}
    `

    // Ajoute le flash dans le body
    document.body.appendChild(flash)

    // Après 2,5 secondes, lance l'animation de disparition
    setTimeout(() => {
      flash.classList.add("opacity-0") // transition CSS pour s'effacer
      setTimeout(() => flash.remove(), 300) // supprime l'élément après l'animation
    }, 2500)
  }
  
}