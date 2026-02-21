(() => {
  const totalDriversEl = document.getElementById("mdTotalDrivers");
  const onlineNowEl = document.getElementById("mdOnlineNow");
  const avgRatingEl = document.getElementById("mdAvgRating");
  const tripsTodayEl = document.getElementById("mdTripsToday");

  const searchEl = document.getElementById("mdSearch");
  const cardsEl = document.getElementById("mdCards");
  const emptyEl = document.getElementById("mdEmpty");

  const modalEl = document.getElementById("driverProfileModal");
  const closeModalEl = document.getElementById("mdCloseDriverProfileModal");

  if (!cardsEl) return;

  let debounceTimer = null;
  let loading = false;
  let overlayEl = null;

  function esc(value) {
    return String(value ?? "")
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  }

  function initials(name) {
    return String(name || "DR")
      .trim()
      .split(/\s+/)
      .slice(0, 2)
      .map((x) => (x[0] || "").toUpperCase())
      .join("") || "DR";
  }

  function moneyEUR(v) {
    const n = Number(v);
    if (!Number.isFinite(n)) return "0.00 EUR";
    return `${n.toFixed(2)} EUR`;
  }

  function setStats(stats) {
    if (totalDriversEl) totalDriversEl.textContent = String(stats?.total_drivers ?? 0);
    if (onlineNowEl) onlineNowEl.textContent = String(stats?.online_now ?? 0);
    if (avgRatingEl) avgRatingEl.textContent = String(Number(stats?.avg_rating ?? 0).toFixed(2));
    if (tripsTodayEl) tripsTodayEl.textContent = String(stats?.total_trips_today ?? 0);
  }

  function openModal() {
    if (!modalEl) return;
    overlayEl = document.createElement("div");
    overlayEl.className = "modal-overlay";
    overlayEl.addEventListener("click", closeModal);
    document.body.appendChild(overlayEl);
    modalEl.style.display = "block";
    document.body.style.overflow = "hidden";
  }

  function closeModal() {
    if (modalEl) modalEl.style.display = "none";
    if (overlayEl) {
      overlayEl.remove();
      overlayEl = null;
    }
    document.body.style.overflow = "auto";
  }

  function setText(id, val) {
    const el = document.getElementById(id);
    if (el) el.textContent = val;
  }

  async function fetchDriverProfile(driverId) {
    const url = new URL("/GoRide/frontend/api/driver_profile.php", window.location.origin);
    url.searchParams.set("id", String(driverId));
    const res = await fetch(url.toString(), { headers: { Accept: "application/json" } });
    const json = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(json.message || "Failed to load driver profile.");
    return json?.driver || null;
  }

  function fillDriverModal(profile, phone, init) {
    if (!profile) return;

    setText("mdDriverProfileInitials", init || "DR");
    setText("mdDriverProfileName", profile.name || "Driver");
    setText("mdDriverProfileAverageRating", String(profile.average_review ?? "-"));
    setText("mdDriverProfileTotalTripsInfo", `• ${profile.total_trips ?? 0} trips`);
    setText("mdDriverProfileTotalTrips", String(profile.total_trips ?? 0));

    const years = Number(profile?.active_time?.years || 0);
    const months = Number(profile?.active_time?.months || 0);
    const days = Number(profile?.active_time?.days || 0);
    let activeText = `${days} days`;
    if (years > 0) activeText = `${years} years, ${months} months, ${days} days`;
    else if (months > 0) activeText = `${months} months, ${days} days`;
    setText("mdDriverProfileYearsActive", activeText);

    const avgResponseMin = Math.round(Number(profile?.average_response_time || 0) / 60);
    setText("mdDriverProfileResponseTime", `${avgResponseMin} minutes`);

    const breakdown = profile?.rating_breakdown || {};
    const totalReviews = Object.values(breakdown).reduce((acc, n) => acc + Number(n || 0), 0);
    for (let i = 1; i <= 5; i++) {
      const count = Number(breakdown[i] || 0);
      const percent = totalReviews > 0 ? Math.round((count / totalReviews) * 100) : 0;
      const bar = document.getElementById(`mdDriverProfileRatingBar${i}`);
      const txt = document.getElementById(`mdDriverProfileRatingCount${i}`);
      if (bar) bar.style.width = `${percent}%`;
      if (txt) txt.textContent = `${percent}%`;
    }

    const callBtn = document.getElementById("mdDriverProfileCallBtn");
    if (callBtn) {
      if (phone) callBtn.href = `tel:${phone}`;
      else callBtn.href = "javascript:void(0)";
    }
  }

  function cardHtml(driver) {
    const stillDriver = String(driver.user_role || "") === "driver";
    const st = stillDriver ? String(driver.status || "offline") : "offline";
    const statusClass = st === "available" || st === "busy" ? "md-status-online" : "md-status-offline";
    const statusText = st === "available" || st === "busy" ? "Online" : "Offline";

    const vehicle = [driver.vehicle_color, driver.vehicle_make, driver.vehicle_model]
      .filter(Boolean)
      .join(" ") || "Vehicle";
    const ratingValue = driver.average_rating != null ? Number(driver.average_rating) : null;
    const ratingHtml = ratingValue != null
      ? `<span class="md-rating-wrap"><img src="./assets/images/icons/star-filled.svg" alt="" class="md-rating-star"><span>${esc(ratingValue.toFixed(2))}</span></span>`
      : "-";

    return `
      <article class="md-card">
        <div class="md-top">
          <div class="md-user">
            <div class="md-avatar">${esc(initials(driver.name))}</div>
            <div>
              <div class="md-name">${esc(driver.name || "Driver")}</div>
              <div class="md-license">License: ${esc(driver.license_plate || "-")}</div>
            </div>
          </div>
          <span class="md-status ${statusClass}">${statusText}</span>
        </div>

        <div class="md-vehicle">
          <img src="./assets/images/icons/car.svg" alt="" class="icon16">
          <span>${esc(vehicle)}</span>
        </div>

        <div class="md-metrics">
          <div>
            <div class="md-metric-label">Rating</div>
            <div class="md-metric-value">${ratingHtml}</div>
          </div>
          <div>
            <div class="md-metric-label">Trips</div>
            <div class="md-metric-value">${esc(driver.rides_count ?? 0)}</div>
          </div>
          <div>
            <div class="md-metric-label">Earnings</div>
            <div class="md-metric-value md-earnings-value">${esc(moneyEUR(driver.earnings_total || 0))}</div>
          </div>
        </div>

        <div class="md-contact">
          <div>${esc(driver.email || "-")}</div>
          <div>${esc(driver.phone || "-")}</div>
          ${!stillDriver ? `<div class="md-warning">This driver is no longer a driver!</div>` : ""}
        </div>

        <div class="md-actions">
          <button type="button" class="md-view-btn" data-action="view-details" data-driver-id="${Number(driver.id || 0)}" data-driver-name="${esc(driver.name || "Driver")}" data-driver-phone="${esc(driver.phone || "")}">View Details</button>
        </div>
      </article>
    `;
  }

  async function loadDrivers(search = "") {
    if (loading) return;
    loading = true;
    cardsEl.innerHTML = "";
    if (emptyEl) {
      emptyEl.style.display = "";
      emptyEl.textContent = "Loading...";
    }

    try {
      const url = new URL("/GoRide/frontend/api/admin_drivers_list.php", window.location.origin);
      if (search.trim()) url.searchParams.set("search", search.trim());
      const res = await fetch(url.toString(), { headers: { Accept: "application/json" } });
      const json = await res.json().catch(() => ({}));
      if (!res.ok) throw new Error(json.message || `Failed (${res.status})`);

      const drivers = Array.isArray(json.drivers) ? json.drivers : [];
      setStats(json.stats || {});

      if (drivers.length === 0) {
        if (emptyEl) {
          emptyEl.style.display = "";
          emptyEl.textContent = "No drivers found.";
        }
        return;
      }

      if (emptyEl) emptyEl.style.display = "none";
      cardsEl.innerHTML = drivers.map(cardHtml).join("");
    } catch (e) {
      if (emptyEl) {
        emptyEl.style.display = "";
        emptyEl.textContent = e?.message || "Could not load drivers.";
      }
    } finally {
      loading = false;
    }
  }

  cardsEl.addEventListener("click", async (e) => {
    const btn = e.target.closest('button[data-action="view-details"]');
    if (!btn) return;

    const driverId = Number(btn.dataset.driverId || 0);
    if (!Number.isInteger(driverId) || driverId <= 0) return;
    const driverName = String(btn.dataset.driverName || "Driver");
    const phone = String(btn.dataset.driverPhone || "");

    btn.disabled = true;
    try {
      const profile = await fetchDriverProfile(driverId);
      fillDriverModal(profile, phone, initials(driverName));
      openModal();
    } catch (err) {
      alert(err?.message || "Could not open driver profile.");
    } finally {
      btn.disabled = false;
    }
  });

  if (closeModalEl) {
    closeModalEl.addEventListener("click", closeModal);
  }

  window.addEventListener("keydown", (e) => {
    if (e.key === "Escape" && modalEl && modalEl.style.display !== "none") {
      closeModal();
    }
  });

  if (searchEl) {
    searchEl.addEventListener("input", () => {
      if (debounceTimer) clearTimeout(debounceTimer);
      debounceTimer = setTimeout(() => loadDrivers(searchEl.value || ""), 250);
    });
  }

  loadDrivers("");
})();
