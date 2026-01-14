(function (cash) {
    "use strict";

    cash(".top-bar, .top-bar-boxed")
        .find(".search")
        .find("input")
        .each(function () {
            cash(this).on("focus", function () {
                cash(".top-bar, .top-bar-boxed")
                    .find(".search-result")
                    .addClass("show");
            });

            cash(this).on("focusout", function () {
                cash(".top-bar, .top-bar-boxed")
                    .find(".search-result")
                    .removeClass("show");
            });
        });
})(cash);

// const search = document.querySelector('.search')
// const btn = document.querySelector('.btn-search')
// const input = document.querySelector('.input')

// btn.addEventListener('click', () => {
//     search.classList.toggle('active')
//     input.focus()
// })
