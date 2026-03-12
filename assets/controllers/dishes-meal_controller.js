import { Controller } from '@hotwired/stimulus';
import Swal from 'sweetalert2';

/**
 * Stimulus controller chargé de gérer la suppression d'un plat depuis l'interface utilisateur.
 */
export default class extends Controller {

    /**
     * URLs injectées depuis Twig.
     * - urlRemoveItem : endpoint de suppression
     * - urlReload : endpoint renvoyant la liste mise à jour
     */
    static values = {
        urlRemoveItem: String,
        urlReload: String
    }

    /**
     * Action déclenchée lors du clic sur le bouton de suppression.
     * Affiche une modal de confirmation avant d'effectuer l'action.
     */
    onRemoveItem(event) {
        Swal.fire({
            title: 'Confirmation',
            text: 'Etes-vous sûr de vouloir supprimer ce plat?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Oui',

            // Affiche un loader dans le bouton pendant l'exécution
            showLoaderOnConfirm: true,

            /**
             * preConfirm permet d'exécuter une action asynchrone
             * avant de fermer la modal.
             */
            preConfirm: () => {
                return this.removeItem()
            }
        });
    }

    /**
     * Supprime l'élément côté serveur puis recharge
     * la liste des plats affichée dans l'interface.
     */
    async removeItem() {

        // Suppression côté backend
        await fetch(this.urlRemoveItemValue);

        /**
         * Rechargement du bloc contenant les plats.
         * Le backend Symfony renvoie un fragment HTML
         * qui remplace le contenu existant.
         */
        const response = await fetch(this.urlReloadValue);

        document.getElementById('meals-day').innerHTML = await response.text();
    }
}