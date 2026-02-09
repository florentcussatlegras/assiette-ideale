const wrapper = document.getElementById("wrapper");
const btnModeList = document.getElementById("view-mode-list");
const btnModeGrid = document.getElementById("view-mode-grid");
const cards = document.querySelectorAll(".card");

if(btnModeList && btnModeGrid) {

    btnModeList.addEventListener("click", function (event) {

        console.log('view mode list');
        // List view
        event.preventDefault();
        wrapper.classList.add("list");
        btnModeGrid.classList.remove("view-mode-switch__display--selected")
        this.classList.add("view-mode-switch__display--selected");
        cards.forEach((el) => {
            el.classList.add("card-wrapper-list");
        });
    });

    btnModeGrid.addEventListener("click", function (event) {
        // Grid view
        event.preventDefault();
        wrapper.classList.remove("list");
        btnModeList.classList.remove("view-mode-switch__display--selected");
        this.classList.add("view-mode-switch__display--selected");
        cards.forEach((el) => {
            el.classList.remove("card-wrapper-list");
        });
    });

}