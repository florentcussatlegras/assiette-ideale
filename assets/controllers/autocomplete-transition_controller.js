import { Controller } from '@hotwired/stimulus'

export default class extends Controller {
    static targets = [
        'results',
        'input',
        'loadMoreButton',
        'loadMoreWrapper'
    ]
    static values = { url: String }

    connect() {
        this.page = 1
        this.loading = false
        this.lastPage = false
    }

    async loadMore() {

        alert('load more');
     
        if (this.loading || this.lastPage) return

        this.loading = true
        this.loadMoreButtonTarget.disabled = true
        this.loadMoreButtonTarget.textContent = "Chargement..."

        this.page++

        const url = new URL(this.urlValue, window.location.origin)
        url.searchParams.set('q', this.inputTarget?.value || '')
        url.searchParams.set('page', this.page)

        console.log(url);

        try {
            const response = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })

            const html = await response.text()
            const temp = document.createElement('div')
            temp.innerHTML = html

            const newItems = temp.children

            if (newItems.length === 0) {
                this.lastPage = true
                this.loadMoreWrapperTarget.classList.add('hidden')
                return
            }

            Array.from(newItems).forEach(el => {
                this.resultsTarget.appendChild(el)
            })

        } catch (e) {
            console.error(e)
        }

        this.loading = false
        this.loadMoreButtonTarget.disabled = false
        this.loadMoreButtonTarget.textContent = "Afficher plus de résultats"
    }
}