import { Controller } from "@hotwired/stimulus";

/**
 * Stimulus controller chargé de gérer un accordéon de panels (par jour)
 * avec ouverture/fermeture animée et synchronisation des icônes flèches.
 */
export default class extends Controller {
  static targets = ["panel", "arrowDown", "arrowUp"];

  // Gère l'ouverture/fermeture d'un panel au clic
  toggle(event) {
    const clickedDay = event.currentTarget.dataset.day;

    this.panelTargets.forEach(panel => {
      const isCurrent = panel.dataset.dayPanel === clickedDay;

      if (isCurrent) {
        if (!panel.classList.contains("is-open")) {
          // Ouvre le panel sélectionné
          panel.classList.add("is-open");
          panel.style.maxHeight = panel.scrollHeight + "px";
        } else {
          // Ferme le panel si déjà ouvert
          panel.style.maxHeight = panel.scrollHeight + "px"; // force la hauteur actuelle
          requestAnimationFrame(() => {
            panel.style.maxHeight = "0px";
          });
          panel.classList.remove("is-open");
        }
      } else {
        // Ferme les autres panels ouverts
        if (panel.classList.contains("is-open")) {
          panel.style.maxHeight = panel.scrollHeight + "px";
          requestAnimationFrame(() => {
            panel.style.maxHeight = "0px";
          });
          panel.classList.remove("is-open");
        }
      }
    });

    // Met à jour l'état des flèches
    this.updateArrows(clickedDay);
  }

  // Met à jour l'affichage des flèches selon le panel ouvert
  updateArrows(clickedDay) {
    const openPanel = this.panelTargets.find(p =>
      p.classList.contains("is-open")
    );

    const openDay = openPanel ? openPanel.dataset.dayPanel : null;

    // Affiche la flèche vers le bas pour les panels fermés
    this.arrowDownTargets.forEach(down => {
      const day = down.dataset.dayArrow;
      if (day === openDay) down.classList.add("my-hidden");
      else down.classList.remove("my-hidden");
    });

    // Affiche la flèche vers le haut pour le panel ouvert
    this.arrowUpTargets.forEach(up => {
      const day = up.dataset.dayArrow;
      if (day === openDay) up.classList.remove("my-hidden");
      else up.classList.add("my-hidden");
    });
  }
}