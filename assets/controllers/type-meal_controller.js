import { Controller } from '@hotwired/stimulus';

/**
 * Stimuls Ccontroller charg de gérer la sélection d'un type de repas sur la page de saisie/édition des repas.
 *
 * Fonctionnalités :
 * - Sélection d'un type de repas (ex : déjeuner, dîner)
 * - Affichage d'un loader pendant le chargement
 */
export default class extends Controller {

    static values = {
        url: String,       // URL pour appliquer le type de repas sélectionné
        urlReload: String, // URL pour recharger la liste complète des repas
        rankMeal: Number   // Rang du repas courant
    };

    static targets = ['typeMealOption', 'typeMealButton', 'btnOpenSlidebar'];

    /**
     * Gestion du clic sur un type de repas
     */
    async onSelectType(event) {
        const element = event.currentTarget;

        // Stocke le type de repas sélectionné
        this.typeMealValue = element.dataset.typeMeal;

        // Mesure l'espace du texte pour afficher un loader de même taille
        const rect = element.getBoundingClientRect();
        const spanTypeName = element.querySelector('.name-type');
        const loaderType = element.querySelector('.loader-type');

        spanTypeName.classList.replace('flex', 'hidden'); // cache le texte
        loaderType.style.width = `${rect.width}px`;
        loaderType.style.height = `${rect.height}px`;
        loaderType.classList.replace('hidden', 'flex'); // affiche le loader

        // Cherche l'option correspondante pour le type sélectionné
        const matchingOption = this.typeMealOptionTargets.find(opt => opt.value === this.typeMealValue);
        if (!matchingOption) return;

        try {
            // 1️⃣ Applique le type de repas sélectionné côté serveur
            const paramsApply = new URLSearchParams({
                rankMeal: this.rankMealValue,
                type: this.typeMealValue,
                ajax: 1
            });
            console.log(`${this.urlValue}?${paramsApply.toString()}`);
            await fetch(`${this.urlValue}?${paramsApply.toString()}`);

            // 2️⃣ Recharge la liste des repas
            const paramsReload = new URLSearchParams({ ajax: 1 });
            const response = await fetch(`${this.urlReloadValue}?${paramsReload.toString()}`);
            const html = await response.text();

            // 3️⃣ Met à jour le DOM avec les nouveaux repas
            document.getElementById('meals-day').innerHTML = html;

            // 4️⃣ Met à jour le bouton du slideover avec le type sélectionné
            this.btnOpenSlidebarTarget.dataset.typeDish = this.typeMealValue;
        } catch (error) {
            console.error('Erreur lors du chargement du type de repas:', error);
        } finally {
            // 5️⃣ Cache le loader et réaffiche le texte
            loaderType.classList.replace('flex', 'hidden');
            spanTypeName.classList.replace('hidden', 'flex');
        }
    }
}