(() => {
  const totalUsersEl = document.getElementById("muTotalUsers");
  const activeUsersEl = document.getElementById("muActiveUsers");
  const driversEl = document.getElementById("muDrivers");
  const suspendedEl = document.getElementById("muSuspended");

  const searchEl = document.getElementById("muSearch");
  const rowsEl = document.getElementById("muRows");
  const emptyEl = document.getElementById("muEmpty");

  if (!rowsEl) return;

  let debounceTimer = null;
  let loading = false;
  let confirmState = null;

  function esc(value) {
    return String(value ?? "")
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  }

  function initials(name) {
    return String(name || "U")
      .trim()
      .split(/\s+/)
      .slice(0, 2)
      .map((x) => (x[0] || "").toUpperCase())
      .join("") || "U";
  }

  function fmtDate(iso) {
    if (!iso) return "-";
    const d = new Date(iso);
    if (Number.isNaN(d.getTime())) return "-";
    return d.toLocaleDateString("en-GB");
  }

  function setStats(stats) {
    if (totalUsersEl) totalUsersEl.textContent = String(stats?.total_users ?? 0);
    if (activeUsersEl) activeUsersEl.textContent = String(stats?.active_users ?? 0);
    if (driversEl) driversEl.textContent = String(stats?.drivers ?? 0);
    if (suspendedEl) suspendedEl.textContent = String(stats?.suspended ?? 0);
  }

  function roleClass(role) {
    if (role === "driver") return "admin-pill-role-driver";
    if (role === "admin") return "admin-pill-role-admin";
    return "admin-pill-role-user";
  }

  function rowHtml(user) {
    const statusClass = user.suspended ? "admin-pill-status-suspended" : "admin-pill-status-active";
    const statusText = user.suspended ? "suspended" : "active";
    const actionText = user.suspended ? "Unsuspend" : "Suspend";
    const actionClass = user.suspended ? "admin-btn-success" : "admin-btn-danger";
    const actionDisabled = user.is_current_admin ? "disabled" : "";
    const roleActionText = user.role === "driver" ? "Make User" : "Make Driver";
    const roleActionNext = user.role === "driver" ? "user" : "driver";
    const roleActionDisabled = user.is_current_admin || user.role === "admin" ? "disabled" : "";
    const roleActionIconHtml = user.role === "driver"
      ? `
          <span class="admin-btn-icon-pair">
            <img src="./assets/images/icons/car.svg" alt="" class="admin-btn-icon">
            <img src="./assets/images/icons/demote-arrow.svg" alt="" class="admin-btn-icon">
          </span>
        `
      : `
          <span class="admin-btn-icon-pair">
            <img src="./assets/images/icons/user.svg" alt="" class="admin-btn-icon">
            <img src="./assets/images/icons/promote-arrow.svg" alt="" class="admin-btn-icon">
          </span>
        `;

    const actionsHtml = user.role === "admin"
      ? `<span class="admin-actions-muted">—</span>`
      : `
          <button class="admin-btn admin-btn-icon-only" title="${roleActionText}" aria-label="${roleActionText}" data-action="toggle-role" data-user-id="${Number(user.id || 0)}" data-user-name="${esc(user.name || "User")}" data-next-role="${roleActionNext}" ${roleActionDisabled}>
            ${roleActionIconHtml}
          </button>
          <button class="admin-btn admin-btn-icon-only ${actionClass}" title="${actionText}" aria-label="${actionText}" data-action="toggle-suspend" data-user-id="${Number(user.id || 0)}" data-user-name="${esc(user.name || "User")}" data-next="${user.suspended ? "0" : "1"}" ${actionDisabled}>
            <img src="./assets/images/icons/ban.svg" alt="" class="admin-btn-icon">
          </button>
        `;

    return `
      <div class="admin-row">
        <div class="admin-user">
          <div class="admin-avatar">${esc(initials(user.name))}</div>
          <div>
            <div class="admin-user-name">${esc(user.name || "Unknown")}</div>
            <div class="admin-user-id">ID: ${esc(user.id)}</div>
          </div>
        </div>
        <div>
          <div class="admin-user-email">${esc(user.email || "-")}</div>
          <div class="admin-user-id">${esc(user.phone || "-")}</div>
        </div>
        <div>
          <span class="admin-pill ${roleClass(user.role)}">${esc(user.role || "user")}</span>
        </div>
        <div>${esc(user.rides_count ?? 0)}</div>
        <div>${esc(fmtDate(user.created_at))}</div>
        <div>
          <span class="admin-pill ${statusClass}">${statusText}</span>
        </div>
        <div class="admin-actions">
          ${actionsHtml}
        </div>
      </div>
    `;
  }

  function ensureConfirmModal() {
    let overlay = document.getElementById("muConfirmOverlay");
    if (overlay) return overlay;

    overlay = document.createElement("div");
    overlay.id = "muConfirmOverlay";
    overlay.className = "mu-confirm-overlay";
    overlay.style.display = "none";

    overlay.innerHTML = `
      <div class="mu-confirm-modal" role="dialog" aria-modal="true" aria-labelledby="muConfirmTitle">
        <div class="mu-confirm-title" id="muConfirmTitle">Confirm Action</div>
        <div class="mu-confirm-text" id="muConfirmText"></div>
        <div class="mu-confirm-actions">
          <button type="button" class="admin-btn" id="muConfirmCancel">Cancel</button>
          <button type="button" class="admin-btn admin-btn-danger" id="muConfirmOk">Confirm</button>
        </div>
      </div>
    `;

    document.body.appendChild(overlay);

    const close = () => {
      overlay.style.display = "none";
      document.body.classList.remove("mu-confirm-open");
      confirmState = null;
    };

    overlay.addEventListener("click", (e) => {
      if (e.target === overlay) close();
    });

    const cancelBtn = overlay.querySelector("#muConfirmCancel");
    const okBtn = overlay.querySelector("#muConfirmOk");
    if (cancelBtn) cancelBtn.addEventListener("click", close);

    if (okBtn) {
      okBtn.addEventListener("click", async () => {
        if (!confirmState?.onConfirm) return;
        okBtn.disabled = true;
        try {
          await confirmState.onConfirm();
          close();
        } finally {
          okBtn.disabled = false;
        }
      });
    }

    window.addEventListener("keydown", (e) => {
      if (e.key === "Escape" && overlay.style.display !== "none") close();
    });

    return overlay;
  }

  function openConfirmModal(text, onConfirm) {
    const overlay = ensureConfirmModal();
    const textEl = overlay.querySelector("#muConfirmText");
    const okBtn = overlay.querySelector("#muConfirmOk");
    if (textEl) textEl.textContent = text;
    if (okBtn) okBtn.textContent = "Confirm";
    confirmState = { onConfirm };
    overlay.style.display = "flex";
    document.body.classList.add("mu-confirm-open");
  }

  async function loadUsers(search = "") {
    if (loading) return;
    loading = true;
    rowsEl.innerHTML = "";
    if (emptyEl) {
      emptyEl.style.display = "";
      emptyEl.textContent = "Loading...";
    }

    try {
      const url = new URL("/GoRide/frontend/api/admin_users_list.php", window.location.origin);
      if (search.trim()) url.searchParams.set("search", search.trim());

      const res = await fetch(url.toString(), { headers: { Accept: "application/json" } });
      const json = await res.json().catch(() => ({}));
      if (!res.ok) throw new Error(json.message || `Failed (${res.status})`);

      const users = Array.isArray(json.users) ? json.users : [];
      setStats(json.stats || {});

      if (users.length === 0) {
        if (emptyEl) {
          emptyEl.style.display = "";
          emptyEl.textContent = "No users found.";
        }
        return;
      }

      if (emptyEl) emptyEl.style.display = "none";
      rowsEl.innerHTML = users.map(rowHtml).join("");
    } catch (e) {
      if (emptyEl) {
        emptyEl.style.display = "";
        emptyEl.textContent = e?.message || "Could not load users.";
      }
    } finally {
      loading = false;
    }
  }

  async function toggleSuspend(userId, suspended) {
    const url = new URL("/GoRide/frontend/api/admin_user_suspend.php", window.location.origin);
    url.searchParams.set("id", String(userId));

    const res = await fetch(url.toString(), {
      method: "POST",
      headers: { "Content-Type": "application/json", Accept: "application/json" },
      body: JSON.stringify({ suspended }),
    });

    const json = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(json.message || `Failed (${res.status})`);
  }

  async function toggleRole(userId, role) {
    const url = new URL("/GoRide/frontend/api/admin_user_role.php", window.location.origin);
    url.searchParams.set("id", String(userId));

    const res = await fetch(url.toString(), {
      method: "POST",
      headers: { "Content-Type": "application/json", Accept: "application/json" },
      body: JSON.stringify({ role }),
    });

    const json = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(json.message || `Failed (${res.status})`);
  }

  rowsEl.addEventListener("click", async (e) => {
    const btn = e.target.closest('button[data-action="toggle-suspend"]');
    const roleBtn = e.target.closest('button[data-action="toggle-role"]');

    if (roleBtn && !roleBtn.disabled) {
      const userId = Number(roleBtn.dataset.userId || 0);
      const userName = String(roleBtn.dataset.userName || "User");
      const nextRole = String(roleBtn.dataset.nextRole || "").toLowerCase();
      if (!Number.isInteger(userId) || userId <= 0) return;
      if (!["user", "driver"].includes(nextRole)) return;
      const roleLabel = nextRole === "driver" ? "driver" : "user";

      openConfirmModal(`Are you sure you want to make ${userName} a ${roleLabel}?`, async () => {
        roleBtn.disabled = true;
        try {
          await toggleRole(userId, nextRole);
          await loadUsers(searchEl?.value || "");
        } catch (err) {
          alert(err?.message || "Could not update user role.");
          roleBtn.disabled = false;
        }
      });
      return;
    }

    if (!btn || btn.disabled) return;

    const userId = Number(btn.dataset.userId || 0);
    const userName = String(btn.dataset.userName || "User");
    const next = btn.dataset.next === "1";
    if (!Number.isInteger(userId) || userId <= 0) return;
    openConfirmModal(`Are you sure you want to ${next ? "suspend" : "unsuspend"} ${userName}?`, async () => {
      btn.disabled = true;
      try {
        await toggleSuspend(userId, next);
        await loadUsers(searchEl?.value || "");
      } catch (err) {
        alert(err?.message || "Could not update user status.");
        btn.disabled = false;
      }
    });
  });

  if (searchEl) {
    searchEl.addEventListener("input", () => {
      if (debounceTimer) clearTimeout(debounceTimer);
      debounceTimer = setTimeout(() => loadUsers(searchEl.value || ""), 250);
    });
  }

  loadUsers("");
})();
