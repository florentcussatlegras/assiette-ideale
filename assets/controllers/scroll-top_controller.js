import { Controller } from "@hotwired/stimulus"

export default class extends Controller {

    static targets = ["button"]

    connect() {
        this.onScroll = this.onScroll.bind(this)
        window.addEventListener("scroll", this.onScroll)
    }

    disconnect() {
        window.removeEventListener("scroll", this.onScroll)
    }

    onScroll() {
        if (window.scrollY > 300) {
            this.buttonTarget.classList.remove("opacity-0", "pointer-events-none")
            this.buttonTarget.classList.add("opacity-100")
        } else {
            this.buttonTarget.classList.add("opacity-0", "pointer-events-none")
            this.buttonTarget.classList.remove("opacity-100")
        }
    }

    scrollTop() {
        window.scrollTo({
            top: 0,
            behavior: "smooth"
        })
    }

}