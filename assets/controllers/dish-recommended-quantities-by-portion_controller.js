import { Controller } from "@hotwired/stimulus"

/**
 * Stimulus controller chargé de gérer la mise à jour dynamique des quantités nutritionnelles en fonction du nombre de portions d'une recette.
 */
export default class extends Controller {

    /**
     * Références vers les éléments DOM utilisés par le controller.
     * - content : conteneur dont le contenu est remplacé après chargement AJAX
     * - select  : sélecteur du nombre de portions
     */
    static targets = ["content", "select"]

    /**
     * URL de chargement injectée depuis Twig.
     * Contient un placeholder pour la portion qui sera remplacé dynamiquement.
     */
    static values = {
        url: String
    }

    /**
     * Déclenchée lors du changement de valeur du sélecteur.
     * Recharge les quantités nutritionnelles correspondant
     * au nombre de portions sélectionné.
     */
    async change() {

        // Nombre de portions sélectionné par l'utilisateur
        const portion = this.selectTarget.value

        // Construction de l'URL en remplaçant la portion dans la route
        const url = this.urlValue.replace(0, portion)

        // Indication visuelle de chargement
        this.contentTarget.classList.add('opacity-50')

        // Requête AJAX vers le backend Symfony
        const response = await fetch(url, {
            headers: {
                "X-Requested-With": "XMLHttpRequest"
            }
        })

        // Récupération du fragment HTML renvoyé
        const html = await response.text()

        /**
         * Parsing du HTML pour extraire uniquement
         * la partie du DOM correspondant à la zone "content".
         *
         * Cela permet d'éviter de remplacer l'ensemble du composant.
         */
        const parser = new DOMParser()
        const doc = parser.parseFromString(html, "text/html")

        const newContent = doc.querySelector('[data-dish-recommended-quantities-by-portion-target="content"]')

        // Mise à jour du contenu avec les nouvelles valeurs
        this.contentTarget.innerHTML = newContent.innerHTML

        // Fin de l'état de chargement
        this.contentTarget.classList.remove('opacity-50')
    }
}