// assets/controllers/view_mode_controller.js
import { Controller } from "@hotwired/stimulus";

/**
 * Contrôleur Stimulus pour gérer le mode d'affichage
 * d'une liste d'éléments : mode "liste" ou "grille". Utilisé pour les cards des recettes.
 *
 * Fonctionnalités :
 * - Permet de basculer entre la vue liste et la vue grille
 * - Met à jour les classes CSS des boutons pour indiquer l'état actif
 */
export default class extends Controller {
    // Définition des targets pour accéder facilement aux éléments DOM
    static targets = ["wrapper", "listBtn", "gridBtn"];

    /**
     * Active le mode liste
     */
    list(event) {
        event.preventDefault(); // Empêche le comportement par défaut du clic

        this.wrapperTarget.classList.add("list");

        this.gridBtnTarget.classList.remove("view-mode-switch__display--selected");

        this.listBtnTarget.classList.add("view-mode-switch__display--selected");
    }

    /**
     * Active le mode grille
     */
    grid(event) {
        event.preventDefault(); // Empêche le comportement par défaut du clic

        this.wrapperTarget.classList.remove("list");

        this.listBtnTarget.classList.remove("view-mode-switch__display--selected");

        this.gridBtnTarget.classList.add("view-mode-switch__display--selected");
    }
}