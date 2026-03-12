import { Controller } from '@hotwired/stimulus'

/**
 * Stimulus controller Stimulus gérant le chargement progressif des résultats (pagination dynamique / bouton "Afficher plus").
 */
export default class extends Controller {

    // Déclaration des éléments HTML utilisés dans le controller
    // Ils correspondent aux data-*-target dans le template Twig
    static targets = [
        'results',
        'input',
        'loadMoreButton',
        'loadMoreWrapper'
    ]

    // URL de récupération des résultats (passée via data-value dans le HTML)
    static values = { url: String }

    /**
     * Méthode appelée automatiquement à l'initialisation du controller.
     * Initialise les variables utilisées pour la pagination.
     */
    connect() {
        this.page = 1          // page actuelle
        this.loading = false  // évite plusieurs requêtes simultanées
        this.lastPage = false // indique si toutes les pages ont été chargées
    }

    /**
     * Charge la page suivante de résultats et les ajoute à la liste existante.
     */
    async loadMore() {

        // Empêche un nouveau chargement si une requête est déjà en cours
        // ou si la dernière page a déjà été atteinte
        if (this.loading || this.lastPage) return

        this.loading = true

        // Désactive le bouton pendant le chargement
        this.loadMoreButtonTarget.disabled = true
        this.loadMoreButtonTarget.textContent = "Chargement..."

        // Passage à la page suivante
        this.page++

        // Construction de l'URL avec les paramètres de recherche
        const url = new URL(this.urlValue, window.location.origin)
        url.searchParams.set('q', this.inputTarget?.value || '')
        url.searchParams.set('page', this.page)

        try {
            // Requête AJAX vers le backend Symfony
            const response = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })

            // Récupération du HTML renvoyé
            const html = await response.text()

            // Création d'un conteneur temporaire pour parser le HTML
            const temp = document.createElement('div')
            temp.innerHTML = html

            const newItems = temp.children

            // Si aucun résultat n'est retourné, on masque le bouton
            if (newItems.length === 0) {
                this.lastPage = true
                this.loadMoreWrapperTarget.classList.add('hidden')
                return
            }

            // Ajout des nouveaux éléments dans la liste existante
            Array.from(newItems).forEach(el => {
                this.resultsTarget.appendChild(el)
            })

        } catch (e) {
            // Log de l'erreur pour faciliter le debug
            console.error(e)
        }

        // Réactivation du bouton après chargement
        this.loading = false
        this.loadMoreButtonTarget.disabled = false
        this.loadMoreButtonTarget.textContent = "Afficher plus de résultats"
    }
}