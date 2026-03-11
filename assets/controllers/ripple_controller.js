import { Controller } from "@hotwired/stimulus"

export default class extends Controller {
  ripple(event) {
    const button = this.element

    // 🔹 Effet scale + background temporaire
    button.classList.add("scale-105", "bg-gray-300", "transition", "duration-150")

    setTimeout(() => {
      button.classList.remove("scale-105", "bg-gray-300")
    }, 150)

    // 🔹 Ripple
    const circle = document.createElement("span")
    circle.classList.add(
      "absolute",
      "rounded-full",
      "animate-ping",
      "bg-white/50",
      "pointer-events-none"
    )

    const rect = button.getBoundingClientRect()
    const size = Math.max(rect.width, rect.height)

    circle.style.width = `${size}px`
    circle.style.height = `${size}px`
    circle.style.left = `${event.clientX - rect.left - size / 2}px`
    circle.style.top = `${event.clientY - rect.top - size / 2}px`

    button.appendChild(circle)

    setTimeout(() => {
      circle.remove()
    }, 600)
  }
}