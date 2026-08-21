window.toggleMobileMenu = function (shouldOpen) {
    const open = typeof shouldOpen === "boolean" ? shouldOpen : true;
    const body = document.body;
    const sidebar = document.querySelector(".mobile-view .sidebar-nav");
    const menuTrigger = document.querySelector(".mobile-header .menu-trigger");
    const reopenBtn = document.querySelector(".mobile-header .sidebar-reopen-btn");

    if (!body.classList.contains("mobile-view")) {
        return;
    }

    body.classList.toggle("mobile-menu-open", open);
    body.classList.toggle("sidebar-collapsed", !open);

    if (sidebar) {
        sidebar.style.display = open ? "flex" : "none";
        sidebar.setAttribute("aria-hidden", String(!open));
    }

    if (menuTrigger) {
        menuTrigger.style.display = open ? "none" : "flex";
        menuTrigger.setAttribute("aria-hidden", String(!open));
    }

    if (reopenBtn) {
        reopenBtn.style.display = open ? "none" : "inline-flex";
        reopenBtn.setAttribute("aria-hidden", String(open));
    }
};

window.closeMobileMenu = function () {
    window.toggleMobileMenu(false);
};

document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll(".sidebar-close").forEach(function (btn) {
        btn.addEventListener("click", function (event) {
            event.preventDefault();
            event.stopPropagation();
            window.toggleMobileMenu(false);
        });
    });

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
    const reopenBtn = document.querySelector(".mobile-header .sidebar-reopen-btn");

    if (menuTrigger) {
        menuTrigger.addEventListener("click", function (event) {
            event.stopPropagation();
            const isOpen = document.body.classList.contains("mobile-menu-open");
            window.toggleMobileMenu(!isOpen);
        });
    }

    if (reopenBtn) {
        reopenBtn.addEventListener("click", function (event) {
            event.stopPropagation();
            window.toggleMobileMenu(true);
        });
    }

    document.addEventListener("click", function (event) {
        if (!document.body.classList.contains("mobile-menu-open")) return;
        if (!event.target.closest(".sidebar-nav") && !event.target.closest(".menu-trigger") && !event.target.closest(".sidebar-close") && !event.target.closest(".sidebar-reopen-btn")) {
            window.toggleMobileMenu(false);
        }
    });

    window.toggleMobileMenu(false);
});
