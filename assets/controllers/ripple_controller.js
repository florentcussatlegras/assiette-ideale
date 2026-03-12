import { Controller } from "@hotwired/stimulus"

/**
 * Stimulus controller en charge de créer un effet "ripple" sur les boutons
 * + effet de scale (agrandissement temporaire) pour un retour visuel rapide
 */
export default class extends Controller {
  
  ripple(event) {
    const button = this.element // le bouton sur lequel on clique

    // Effet scale + changement temporaire de background
    // On ajoute un petit agrandissement et un fond temporaire
    button.classList.add("scale-105", "bg-gray-300", "transition", "duration-150")

    // Après 150ms, on retire les classes pour revenir à l'état initial
    setTimeout(() => {
      button.classList.remove("scale-105", "bg-gray-300")
    }, 150)

    // Création de l'effet ripple
    const circle = document.createElement("span") // élément cercle
    circle.classList.add(
      "absolute",        // position absolue par rapport au bouton
      "rounded-full",    // forme ronde
      "animate-ping",    // animation ping (taille + opacité)
      "bg-white/50",     // couleur blanche semi-transparente
      "pointer-events-none" // ne bloque pas les clics
    )

    // Récupération des dimensions du bouton
    const rect = button.getBoundingClientRect()
    const size = Math.max(rect.width, rect.height) // cercle carré, côté le plus grand

    // Position du cercle au point de clic
    circle.style.width = `${size}px`
    circle.style.height = `${size}px`
    circle.style.left = `${event.clientX - rect.left - size / 2}px` // centré horizontalement
    circle.style.top = `${event.clientY - rect.top - size / 2}px`   // centré verticalement

    // Ajout du cercle au bouton
    button.appendChild(circle)

    // Suppression du cercle après la fin de l'animation (600ms)
    setTimeout(() => {
      circle.remove()
    }, 600)
  }
}