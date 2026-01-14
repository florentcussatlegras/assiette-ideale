import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
  static targets = ["panel", "arrowDown", "arrowUp"];

  connect() {
    console.log("connect menu accordion");
  }

  toggle(event) {
    const clickedDay = event.currentTarget.dataset.day;

    this.panelTargets.forEach(panel => {
      const isCurrent = panel.dataset.dayPanel === clickedDay;

      if (isCurrent) {
        if (!panel.classList.contains("is-open")) {
          // ----------- OUVRIR le panel -------------
          panel.classList.add("is-open");
          panel.style.maxHeight = panel.scrollHeight + "px";
        } else {
          // ----------- FERMER le panel -------------
          panel.style.maxHeight = panel.scrollHeight + "px"; // force hauteur actuelle
          requestAnimationFrame(() => {
            panel.style.maxHeight = "0px";
          });
          panel.classList.remove("is-open");
        }
      } else {
        // ----------- FERME les autres panels -----------
        if (panel.classList.contains("is-open")) {
          panel.style.maxHeight = panel.scrollHeight + "px";
          requestAnimationFrame(() => {
            panel.style.maxHeight = "0px";
          });
          panel.classList.remove("is-open");
        }
      }
    });

    // Mettre à jour les flèches
    this.updateArrows(clickedDay);
  }

  updateArrows(clickedDay) {
    const openPanel = this.panelTargets.find(p =>
      p.classList.contains("is-open")
    );
    const openDay = openPanel ? openPanel.dataset.dayPanel : null;

    this.arrowDownTargets.forEach(down => {
      const day = down.dataset.dayArrow;
      if (day === openDay) down.classList.add("my-hidden");
      else down.classList.remove("my-hidden");
    });

    this.arrowUpTargets.forEach(up => {
      const day = up.dataset.dayArrow;
      if (day === openDay) up.classList.remove("my-hidden");
      else up.classList.add("my-hidden");
    });
  }
}
