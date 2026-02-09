document.addEventListener("DOMContentLoaded", () => {

    const modal   = document.getElementById("welcome-modal");
    const loader  = document.getElementById("welcome-loader");
    const content = document.getElementById("welcome-content");
    const closeBtn = document.getElementById("close-modal");

    if (!modal) return;

    // ouverture modale
    setTimeout(() => {
        modal.classList.remove("opacity-0", "scale-95");
        modal.classList.add("opacity-100", "scale-100");
    }, 100);

    // faux calcul
    setTimeout(() => {

        /* ==========================
           1️⃣ fade out loader
        ========================== */
        loader.classList.add("opacity-0");

        /* ==========================
           2️⃣ préparer le contenu
        ========================== */
        content.classList.remove("hidden");

        // forcer le navigateur à recalculer
        content.offsetHeight;

        /* ==========================
           3️⃣ fade in contenu
        ========================== */
        content.classList.remove("opacity-0");
        content.classList.add("opacity-100");

        /* ==========================
           4️⃣ supprimer loader
        ========================== */
        setTimeout(() => {
            loader.remove();
        }, 700);

    }, 7000);

    // fermeture
    closeBtn?.addEventListener("click", () => {
        modal.classList.add("opacity-0", "scale-95");
        setTimeout(() => modal.remove(), 300);
    });

});
