
let objNav = document.querySelector("nav")

let memoPositionNav = objNav.offsetTop

function sticky() {
    // position du curseur au scroll
    var posCurseur = this.pageYOffset

    console.log(memoPositionNav)
    console.log(posCurseur)

    // différence de distance entre le scroll et nav
    if(memoPositionNav-posCurseur < 1) {
        objNav.classList.replace("relative", "fixed")
        objNav.classList.add("top-0")
        objNav.style.zIndex = 40
    }
    if(posCurseur < 101) {
        objNav.classList.replace("fixed", "relative")
        objNav.classList.remove("top-0")
    }
}

window.addEventListener("scroll", sticky)