import { Controller } from "@hotwired/stimulus"

export default class extends Controller {

    static values = {
        url: String
    }

    toggle(event) {

        const value = event.target.checked

        fetch(this.urlValue, {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                value: value
            })
        })

    }

}