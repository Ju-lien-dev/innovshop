// assets/home.js
document.addEventListener("DOMContentLoaded", () => {
    const root = document.querySelector(".home-page");
    if (!root) return;

    const carouselEl = document.querySelector("#carouselHomePage");
    if (carouselEl && window.bootstrap?.Carousel) {
        new bootstrap.Carousel(carouselEl, {
            interval: 3000,
            ride: "carousel",
            touch: true,
        });
    }

    document.querySelectorAll(".home-page .card").forEach((card) => {
        card.addEventListener("click", () => {
            const id = card.getAttribute("data-id");
            if (id) window.location.href = `/innovshop/produit/${id}`;
        });
    });

    const cards = document.querySelectorAll(".home-page .card");
    if ("IntersectionObserver" in window) {
        const obs = new IntersectionObserver(
            (entries) => {
                entries.forEach((e) => {
                    if (e.isIntersecting) {
                        e.target.classList.add("visible");
                        obs.unobserve(e.target);
                    }
                });
            },
            { threshold: 0.2 }
        );
        cards.forEach((c) => obs.observe(c));
    } else {
        cards.forEach((c) => c.classList.add("visible"));
    }
});
