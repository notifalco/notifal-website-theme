


/////////// Start - Notifal Mobile Menu
document.addEventListener("DOMContentLoaded", function () {
    const menu = document.querySelector('.mobile-menu');
    const openButtons = document.querySelectorAll('.open-menu-btn');
    const closeButton = document.querySelector('.close-menu-btn');

    if (!menu) return;

    openButtons.forEach(button => {
        button.addEventListener('click', () => {
            menu.classList.remove('menu-hidden');
            menu.classList.add('menu-visible');
        });
    });

    if (closeButton) {
        closeButton.addEventListener('click', () => {
            menu.classList.remove('menu-visible');
            menu.classList.add('menu-hidden');
        });
    }
});

/////////// End - Notifal Mobile Menu



////////// Start - Notifal Header Sticky
document.addEventListener("DOMContentLoaded", function () {
    const headers = document.querySelectorAll(".notifal-theme-scroll-header");
    const scrollTrigger = 500;

    if (!headers.length) return;

    const onScroll = () => {
        headers.forEach(header => {
            if (window.scrollY > scrollTrigger) {
                header.classList.add("is-visible");
            } else {
                header.classList.remove("is-visible");
            }
        });
    };

    window.addEventListener("scroll", onScroll);
    onScroll();
});

////////// End - Notifal Header Sticky