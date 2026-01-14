
const mobileIcon = document.querySelector('.icon-navbar-mobile');

if (mobileIcon) {
    mobileIcon.addEventListener('click', () => {
        var menu = document.getElementById("menuMobile");
        if (menu.style.display === "flex") {
            menu.style.display = "none";
        } else {
            menu.style.display = "flex";
        }
    });
}