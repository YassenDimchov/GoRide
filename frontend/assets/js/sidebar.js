(() => {
  const toggle = document.getElementById("sidebarToggle");
  const overlay = document.getElementById("sidebarOverlay");
  const sidebar = document.getElementById("sidebar");

  if (!toggle) return;

  const close = () => document.body.classList.remove("sidebar-open");

  toggle.addEventListener("click", () => {
    document.body.classList.toggle("sidebar-open");
  });

  overlay?.addEventListener("click", close);

  sidebar?.addEventListener("click", (e) => {
    const a = e.target.closest("a");
    if (!a) return;

    if (a.dataset.closeSidebar === "1") {
      e.preventDefault();
      close();
      return;
    }

    close();
  });

  window.addEventListener("keydown", (e) => {
    if (e.key === "Escape") close();
  });
})();
