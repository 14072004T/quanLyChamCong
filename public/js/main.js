window.toggleMobileMenu = function (shouldOpen) {
    const open = typeof shouldOpen === "boolean" ? shouldOpen : true;
    document.body.classList.toggle("mobile-menu-open", open);

    const sidebar = document.querySelector(".mobile-view .sidebar-nav");
    if (sidebar) {
        sidebar.setAttribute("aria-hidden", String(!open));
    }

    if (!open) {
        document.removeEventListener("click", handleMobileMenuOutsideClick);
    }
};

function handleMobileMenuOutsideClick(event) {
    const sidebar = document.querySelector(".mobile-view .sidebar-nav");
    const trigger = document.querySelector(".mobile-header .menu-trigger");

    if (!sidebar || !trigger) return;

    const clickedInsideMenu = sidebar.contains(event.target);
    const clickedTrigger = trigger.contains(event.target);

    if (!clickedInsideMenu && !clickedTrigger) {
        window.toggleMobileMenu(false);
    }
}

document.addEventListener("DOMContentLoaded", function () {
    const sidebarList = document.getElementById("sidebarList");
    if (sidebarList) {
        const currentPage = new URLSearchParams(window.location.search).get("page") || "home";
        const items = sidebarList.querySelectorAll(".menu-item-btn");
        items.forEach((item) => {
            const page = item.getAttribute("data-page");
            if (page === currentPage) {
                item.classList.add("active");
            } else {
                item.classList.remove("active");
            }
        });

        sidebarList.querySelectorAll("a.menu-item").forEach((link) => {
            link.addEventListener("click", function () {
                window.toggleMobileMenu(false);
            });
        });
    }

    const bell = document.getElementById("notifBellBtn");
    const panel = document.getElementById("notifPanel");
    const wrapper = document.getElementById("notifWrapper");

    if (bell && panel && wrapper) {
        const closePanel = () => {
            wrapper.classList.remove("open");
            panel.hidden = true;
            panel.setAttribute("aria-hidden", "true");
        };

        const openPanel = () => {
            wrapper.classList.add("open");
            panel.hidden = false;
            panel.setAttribute("aria-hidden", "false");
        };

        bell.addEventListener("click", function (e) {
            e.preventDefault();
            e.stopPropagation();
            if (wrapper.classList.contains("open")) {
                closePanel();
            } else {
                openPanel();
            }
        });

        panel.addEventListener("click", function (e) {
            e.stopPropagation();
        });

        document.addEventListener("click", function () {
            closePanel();
        });

        document.addEventListener("keydown", function (e) {
            if (e.key === "Escape") {
                closePanel();
                window.toggleMobileMenu(false);
            }
        });

        closePanel();
    }

    const menuTrigger = document.querySelector(".mobile-header .menu-trigger");
    if (menuTrigger) {
        menuTrigger.addEventListener("click", function () {
            const isOpen = document.body.classList.contains("mobile-menu-open");
            window.toggleMobileMenu(!isOpen);
            if (!isOpen) {
                document.addEventListener("click", handleMobileMenuOutsideClick);
            }
        });
    }
});
