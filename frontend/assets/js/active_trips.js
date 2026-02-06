(() => {
  const wrap = document.querySelector(".status-pills");
  const msg = document.getElementById("statusMsg");

  if (!wrap) return;
  
  let current = wrap.dataset.currentStatus || "offline";
  paint(current);

  function setMsg(t) { if (msg) msg.textContent = t || ""; }

  function paint(status) {
    wrap.querySelectorAll(".pill").forEach(btn => {
      btn.classList.toggle("is-active", btn.dataset.status === status);
    });
    wrap.dataset.currentStatus = status;
    current = status;
  }

  function setDisabled(disabled) {
    wrap.querySelectorAll(".pill").forEach(btn => {
      if (btn.dataset.status === "busy") return;
      btn.disabled = disabled;
    });
  }

  async function updateStatus(status) {
    setMsg("Saving...");
    setDisabled(true);

    try {
      const res = await fetch("driver_status_action.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ status })
      });

      const data = await res.json().catch(() => null);

      if (!res.ok || !data?.ok) {
        setMsg(data?.message || "Failed to update");
        paint(current);
        return;
      }

      paint(data.status);
      setMsg("Updated ✓");
      setTimeout(() => setMsg(""), 1200);
    } catch {
      setMsg("Network error");
      paint(current);
    } finally {
      setDisabled(false);
    }
  }

  wrap.addEventListener("click", (e) => {
    const btn = e.target.closest(".pill");
    if (!btn || btn.disabled) return;

    const next = btn.dataset.status;
    if (next === "busy") return;
    if (next === current) return;

    updateStatus(next);
  });
})();
