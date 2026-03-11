import { Controller } from "@hotwired/stimulus"

export default class extends Controller {

    static targets = ["content", "select"]
    static values = {
        url: String
    }

    async change() {

        const portion = this.selectTarget.value

        const url = this.urlValue.replace(0, portion)

        this.contentTarget.classList.add('opacity-50')

        const response = await fetch(url, {
            headers: {
                "X-Requested-With": "XMLHttpRequest"
            }
        })

        const html = await response.text()

        const parser = new DOMParser()
        const doc = parser.parseFromString(html, "text/html")

        const newContent = doc.querySelector('[data-dish-recommended-quantities-by-portion-target="content"]')

        this.contentTarget.innerHTML = newContent.innerHTML

        this.contentTarget.classList.remove('opacity-50')
    }
}
