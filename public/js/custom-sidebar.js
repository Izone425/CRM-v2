document.addEventListener("DOMContentLoaded", function () {
    // Handle dropdowns
    const dropdownTriggers = document.querySelectorAll(".dropdown-trigger");

    dropdownTriggers.forEach((trigger) => {
        trigger.addEventListener("click", function (e) {
            e.preventDefault();
            const dropdown = this.parentElement;
            dropdown.classList.toggle("open");

            // Close other open dropdowns
            dropdownTriggers.forEach((otherTrigger) => {
                if (
                    otherTrigger !== trigger &&
                    otherTrigger.parentElement.classList.contains("open")
                ) {
                    otherTrigger.parentElement.classList.remove("open");
                }
            });
        });
    });

    // Mobile sidebar toggle
    const mobileToggle = document.getElementById("mobile-sidebar-toggle");
    if (mobileToggle) {
        mobileToggle.addEventListener("click", function () {
            const sidebar = document.getElementById("custom-sidebar");
            sidebar.classList.toggle("open");
            document.body.classList.toggle("sidebar-open");
        });
    }

    // Close sidebar when clicking outside on mobile
    document.addEventListener("click", function (e) {
        const sidebar = document.getElementById("custom-sidebar");
        const toggle = document.getElementById("mobile-sidebar-toggle");

        if (
            window.innerWidth <= 768 &&
            sidebar &&
            sidebar.classList.contains("open") &&
            !sidebar.contains(e.target) &&
            toggle !== e.target &&
            !toggle.contains(e.target)
        ) {
            sidebar.classList.remove("open");
            document.body.classList.remove("sidebar-open");
        }
    });

    // Handle Livewire page changes
    document.addEventListener("livewire:navigated", function () {
        // Update active state based on current URL
        const currentPath = window.location.pathname;
        const sidebarItems = document.querySelectorAll(
            ".sidebar-item, .dropdown-item"
        );

        sidebarItems.forEach((item) => {
            if (
                item.getAttribute("href") &&
                currentPath.includes(item.getAttribute("href"))
            ) {
                item.classList.add("active");

                // If it's in a dropdown, open the dropdown
                const parentDropdown = item.closest(".sidebar-dropdown");
                if (parentDropdown) {
                    parentDropdown.classList.add("open");
                }
            } else {
                item.classList.remove("active");
            }
        });
    });
});
