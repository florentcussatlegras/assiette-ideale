import { Controller } from '@hotwired/stimulus';
import { useDebounce } from 'stimulus-use';

/**
 * Stimulus controller chargé de gérer une recherche AJAX avec pagination des aliments/plats.
 * 
 * Fonctionnalités :
 * - recherche dynamique avec debounce
 * - affichage des résultats
 * - pagination via bouton "load more"
 * - illustration affichée quand aucun résultat n'est recherché
 * - bouton pour vider rapidement la recherche
 */
export default class extends Controller {

    // Targets utilisées dans le DOM
    // input : champ de recherche
    // results : conteneur qui reçoit les résultats HTML
    // loadMoreWrapper : conteneur du bouton "load more"
    // illustration : illustration affichée quand la recherche est vide
    // clearButton : bouton pour vider la recherche
    static targets = ['input', 'results', 'loadMoreWrapper', 'illustration', 'clearButton']

    // Valeur passée depuis le HTML contenant l’URL de recherche
    static values = { url: String };

    // Active un debounce sur la méthode search
    // Cela évite d'envoyer une requête à chaque frappe trop rapidement
    static debounces = ['search'];

    connect() {
        // Active la fonctionnalité debounce du package stimulus-use
        useDebounce(this);

        // Initialisation de la pagination
        this.page = 0;

        // Texte de recherche actuel
        this.query = '';

        // Au chargement, on cache le bouton "load more"
        this.loadMoreWrapperTarget.classList.add('hidden');
    }

    /**
     * Méthode déclenchée lors de la saisie dans le champ de recherche.
     * Elle relance une recherche AJAX à partir de la première page.
     */
    async search() {
        this.page = 0;
        this.query = this.inputTarget.value;

        // Met à jour l'affichage du bouton "clear"
        this.toggleClear();

        // Si la recherche est vide
        if (this.query === '') {

            // On vide les résultats
            this.resultsTarget.innerHTML = '';

            // On cache le bouton "load more"
            this.loadMoreWrapperTarget.classList.add('hidden');

            // On affiche l’illustration
            this.showIllustration(true);

            return
        }

        // Si une recherche est saisie, on cache l’illustration
        this.showIllustration(false);

        // Récupération des résultats depuis le serveur
        const { html, lastResults } = await this.fetchResults();

        // Injection du HTML retourné dans le conteneur
        this.resultsTarget.innerHTML = html;

        // Vérifie si des résultats existent
        const hasResults = this.resultsTarget.children.length > 0;

        // Met à jour la visibilité du bouton "load more"
        this.updateLoadMoreVisibility(hasResults, lastResults);
    }

    /**
     * Charge la page suivante de résultats.
     */
    async loadMore() {
        // Incrémente la page
        this.page++;

        // Affiche le loader dans le bouton
        this.showLoading(true);

        // Récupère les résultats suivants
        const { html, lastResults } = await this.fetchResults();

        // Ajoute les nouveaux résultats à la fin de la liste
        this.resultsTarget.insertAdjacentHTML('beforeend', html);

        // Vérifie si des résultats existent
        const hasResults = this.resultsTarget.children.length > 0;

        // Met à jour la visibilité du bouton "load more"
        this.updateLoadMoreVisibility(hasResults, lastResults);

        // Cache le loader
        this.showLoading(false);
    }

    /**
     * Effectue la requête AJAX vers le serveur pour récupérer les résultats.
     */
    async fetchResults() {

        // Construction de l'URL avec les paramètres de recherche
        const url = new URL(this.urlValue, window.location.origin);
        url.searchParams.set('q', this.query);
        url.searchParams.set('page', this.page);

        // Requête fetch avec header AJAX
        const response = await fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });

        // Le serveur retourne directement du HTML
        const html = await response.text();

        /**
         * Le serveur envoie un header personnalisé indiquant
         * si on a atteint la dernière page de résultats.
         */
        const lastResults = response.headers.get('X-Last-Results') === '1';

        return { html, lastResults };
    }

    /**
     * Gère l'affichage du bouton "load more".
     * 
     * Le bouton est visible seulement si :
     * - il existe des résultats
     * - on n'est pas sur la dernière page
     */
    updateLoadMoreVisibility(hasResults, lastResults) {
        if (hasResults && !lastResults) {
            this.loadMoreWrapperTarget.classList.remove('hidden');
        } else {
            this.loadMoreWrapperTarget.classList.add('hidden');
        }
    }

    /**
     * Affiche ou cache le loader dans le bouton "load more".
     */
    showLoading(isLoading) {

        // Sécurité si la target n'existe pas
        if (!this.hasLoadMoreWrapperTarget) return;

        const button = this.loadMoreWrapperTarget.querySelector('button');
        const loader = this.loadMoreWrapperTarget.querySelector('.loader-type');

        if (isLoading) {
            // Cache le bouton et affiche le loader
            button.hidden = true;
            loader.classList.remove('hidden');
        } else {
            // Réaffiche le bouton et cache le loader
            button.hidden = false;
            loader.classList.add('hidden');
        }
    }

    /**
     * Affiche ou cache l’illustration lorsque la recherche est vide.
     */
    showIllustration(show) {
        if (!this.hasIllustrationTarget) return
        this.illustrationTarget.style.display = show ? 'block' : 'none'
    }

    /**
     * Affiche ou cache le bouton permettant de vider la recherche.
     */
    toggleClear() {
        if (this.inputTarget.value.length > 0) {
            this.clearButtonTarget.classList.remove("hidden");
        } else {
            this.clearButtonTarget.classList.add("hidden");
        }
    }

    /**
     * Vide le champ de recherche et relance automatiquement la recherche.
     */
    clearSearch() {
        this.inputTarget.value = '';
        this.toggleClear();

        // Déclenche l'événement input pour relancer la logique de recherche
        this.inputTarget.dispatchEvent(new Event('input'));
    }
}