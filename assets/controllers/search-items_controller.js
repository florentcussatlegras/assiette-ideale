import { Controller } from '@hotwired/stimulus';
import { useDebounce } from 'stimulus-use';

export default class extends Controller {
    static targets = ['input', 'results', 'loadMoreWrapper', 'illustration', 'clearButton']
    static values = { url: String };
    static debounces = ['search'];

    connect() {
        useDebounce(this);

        this.page = 0;
        this.query = '';

        this.loadMoreWrapperTarget.classList.add('hidden');
    }

    async search() {
        this.page = 0;
        this.query = this.inputTarget.value;

        this.toggleClear();

        if (this.query === '') {
            this.resultsTarget.innerHTML = '';
            this.loadMoreWrapperTarget.classList.add('hidden');
            this.showIllustration(true);
            return
        }

        this.showIllustration(false);

        const { html, lastResults } = await this.fetchResults();

        this.resultsTarget.innerHTML = html;

        const hasResults = this.resultsTarget.children.length > 0;

        this.updateLoadMoreVisibility(hasResults, lastResults);
    }

    async loadMore() {
        this.page++;
        this.showLoading(true);

        const { html, lastResults } = await this.fetchResults();

        this.resultsTarget.insertAdjacentHTML('beforeend', html);

        const hasResults = this.resultsTarget.children.length > 0;

        this.updateLoadMoreVisibility(hasResults, lastResults);
        this.showLoading(false);
    }

    async fetchResults() {
        const url = new URL(this.urlValue, window.location.origin);
        url.searchParams.set('q', this.query);
        url.searchParams.set('page', this.page);

        const response = await fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });

        const html = await response.text();
        const lastResults = response.headers.get('X-Last-Results') === '1';

        return { html, lastResults };
    }

    updateLoadMoreVisibility(hasResults, lastResults) {
        if (hasResults && !lastResults) {
            this.loadMoreWrapperTarget.classList.remove('hidden');
        } else {
            this.loadMoreWrapperTarget.classList.add('hidden');
        }
    }

    showLoading(isLoading) {
        if (!this.hasLoadMoreWrapperTarget) return;

        const button = this.loadMoreWrapperTarget.querySelector('button');
        const loader = this.loadMoreWrapperTarget.querySelector('.loader-type');

        if (isLoading) {
            button.hidden = true;
            loader.classList.remove('hidden');
        } else {
            button.hidden = false;
            loader.classList.add('hidden');
        }
    }

    showIllustration(show) {
        if (!this.hasIllustrationTarget) return
        this.illustrationTarget.style.display = show ? 'block' : 'none'
    }

    toggleClear() {
        if (this.inputTarget.value.length > 0) {
            this.clearButtonTarget.classList.remove("hidden");
        } else {
            this.clearButtonTarget.classList.add("hidden");
        }
    }

    clearSearch() {
        this.inputTarget.value = '';
        this.toggleClear();
        this.inputTarget.dispatchEvent(new Event('input'));
    }
}