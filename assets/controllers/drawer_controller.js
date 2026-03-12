import { Controller } from "@hotwired/stimulus"

/**
 * Stimulus controller chargé de gérer le menu latéral en mode mobile/device (sidebar / off-canvas menu) et de son overlay.
 */
export default class extends Controller {

  /**
   * Targets DOM utilisées par le controller :
   * - menu : le conteneur du menu latéral
   * - overlay : couche semi-transparente derrière le menu
   */
  static targets = ["menu", "overlay"]

  /**
   * Lifecycle Stimulus.
   * Ferme le menu au chargement initial de la page.
   */
  connect() {
    this.close() // menu fermé par défaut
  }

  /**
   * Basculer l'état du menu.
   * Si le menu est fermé, il s'ouvre.
   * Si le menu est ouvert, il se ferme.
   */
  toggle() {
    if (this.menuTarget.classList.contains("-translate-x-full")) {
      this.open()
    } else {
      this.close()
    }
  }

  /**
   * Ouvre le menu et affiche l'overlay.
   * - enlève la classe qui déplace le menu hors écran
   * - ajoute la classe qui le fait apparaître
   * - rend l'overlay visible et opaque
   */
  open() {
    this.menuTarget.classList.remove("-translate-x-full")
    this.menuTarget.classList.add("translate-x-0")

    this.overlayTarget.classList.remove("invisible", "opacity-0")
    this.overlayTarget.classList.add("visible", "opacity-100")
  }

  /**
   * Ferme le menu et masque l'overlay.
   * - remet le menu hors écran
   * - rend l'overlay invisible et transparent
   */
  close() {
    this.menuTarget.classList.add("-translate-x-full")
    this.menuTarget.classList.remove("translate-x-0")

    this.overlayTarget.classList.add("invisible", "opacity-0")
    this.overlayTarget.classList.remove("visible", "opacity-100")
  }
}