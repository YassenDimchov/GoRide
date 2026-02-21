(() => {
  const totalRevenueEl = document.getElementById("atTotalRevenue");
  const totalTripsEl = document.getElementById("atTotalTrips");
  const activeUsersEl = document.getElementById("atActiveUsers");
  const avgTripValueEl = document.getElementById("atAvgTripValue");

  const rowsEl = document.getElementById("atRows");
  const emptyEl = document.getElementById("atEmpty");
  const searchEl = document.getElementById("atSearch");
  const pagerEl = document.getElementById("atPager");
  const pageNumbersEl = document.getElementById("atPageNumbers");
  const prevBtn = document.getElementById("atPrevBtn");
  const nextBtn = document.getElementById("atNextBtn");

  if (!rowsEl) return;

  let loading = false;
  let currentPage = 1;
  const perPage = 8;
  let lastPage = 1;
  let searchText = "";
  let debounceTimer = null;

  function esc(value) {
    return String(value ?? "")
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  }

  function moneyEUR(v) {
    const n = Number(v);
    if (!Number.isFinite(n)) return "0.00 EUR";
    return `${n.toFixed(2)} EUR`;
  }

  function fmtDateTime(iso) {
    if (!iso) return "-";
    const d = new Date(iso);
    if (Number.isNaN(d.getTime())) return "-";
    return d.toLocaleString("en-GB", {
      day: "2-digit",
      month: "2-digit",
      year: "numeric",
      hour: "2-digit",
      minute: "2-digit",
      hour12: false,
    });
  }

  function statusMeta(status) {
    const normalized = String(status || "").toLowerCase();
    if (normalized === "completed") return { label: "Completed", className: "at-pill-completed" };
    if (normalized === "ongoing" || normalized === "accepted") {
      return { label: "In Progress", className: "at-pill-in-progress" };
    }
    if (normalized === "cancelled") return { label: "Cancelled", className: "at-pill-cancelled" };
    return { label: "Pending", className: "at-pill-pending" };
  }

  function setStats(stats) {
    if (totalRevenueEl) totalRevenueEl.textContent = moneyEUR(stats?.total_revenue ?? 0);
    if (totalTripsEl) totalTripsEl.textContent = String(stats?.total_trips ?? 0);
    if (activeUsersEl) activeUsersEl.textContent = String(stats?.active_users ?? 0);
    if (avgTripValueEl) avgTripValueEl.textContent = moneyEUR(stats?.avg_trip_value ?? 0);
  }

  function tripCardHtml(trip) {
    const s = statusMeta(trip?.status);
    const dateValue = trip?.completed_at || trip?.created_at;
    const fare = moneyEUR(trip?.fare ?? 0);
    const distanceKm = Number(trip?.trip_distance_m) > 0 ? `${(Number(trip.trip_distance_m) / 1000).toFixed(2)} km` : "- km";
    const minutes = Number(trip?.trip_duration_s) > 0 ? `${Math.max(1, Math.round(Number(trip.trip_duration_s) / 60))} min` : "- min";
    const passengers = Math.max(1, Number(trip?.passenger_count || 1));
    const passengerName = trip?.user?.name || "Unknown Passenger";
    const passengerContact = trip?.user?.email || trip?.user?.phone || "-";
    const driverName = trip?.driver?.name || "No Driver";
    const driverContact = trip?.driver?.email || trip?.driver?.phone || "-";
    const reviewRating = Number(trip?.review?.rating || 0);
    const hasReview = Number.isFinite(reviewRating) && reviewRating > 0;
    const reviewText = String(trip?.review?.review_text || "").trim();
    const starsHtml = hasReview
      ? Array.from({ length: 5 })
          .map((_, i) => {
            const filled = i < reviewRating;
            const icon = filled ? "star-filled.svg" : "star-empty.svg";
            return `<img src="./assets/images/icons/${icon}" alt="" class="icon16">`;
          })
          .join("")
      : "";

    return `
      <article class="at-card">
        <div class="at-card-top">
          <div class="at-trip-main-info">
            <div class="at-id-wrap">
              <div class="at-trip-id">TR-${Number(trip?.id || 0)}</div>
              <span class="at-pill ${s.className}">${s.label}</span>
            </div>
            <div class="at-meta">
              <img src="./assets/images/icons/calendar.svg" alt="" class="icon16">
              <span>${esc(fmtDateTime(dateValue))}</span>
            </div>
            <div class="at-passengers">${esc(String(passengers))} passenger${passengers === 1 ? "" : "s"}</div>
          </div>
          <div class="at-right">
            <div class="at-fare">${esc(fare)}</div>
            <div class="at-sub">${esc(distanceKm)} • ${esc(minutes)}</div>
          </div>
        </div>

        <div class="at-grid">
          <div>
            <div class="at-person-label">Passenger</div>
            <div class="at-person-name">${esc(passengerName)}</div>
            <div class="at-person-contact">${esc(passengerContact)}</div>
          </div>
          <div>
            <div class="at-person-label">Driver</div>
            <div class="at-person-name">${esc(driverName)}</div>
            <div class="at-person-contact">${esc(driverContact)}</div>
          </div>
        </div>

        <div class="at-route">
          <div class="at-route-item">
            <span class="at-route-dot at-route-dot-green"></span>
            <div>
              <div class="at-route-label">Pickup</div>
              <div class="at-route-text">${esc(trip?.start_address || "-")}</div>
            </div>
          </div>
          <div class="at-route-item">
            <span class="at-route-dot at-route-dot-red"></span>
            <div>
              <div class="at-route-label">Dropoff</div>
              <div class="at-route-text">${esc(trip?.end_address || "-")}</div>
            </div>
          </div>
        </div>

        ${
          hasReview
            ? `
              <div class="at-review">
                <div class="at-review-stars">${starsHtml}</div>
                <div class="at-review-text">${esc(reviewText || "No description")}</div>
              </div>
            `
            : ""
        }
      </article>
    `;
  }

  function renderPager() {
    if (!pagerEl || !pageNumbersEl || !prevBtn || !nextBtn) return;
    if (lastPage <= 1) {
      pagerEl.style.display = "none";
      return;
    }

    pagerEl.style.display = "flex";
    prevBtn.disabled = currentPage <= 1;
    nextBtn.disabled = currentPage >= lastPage;

    const spread = 2;
    const start = Math.max(1, currentPage - spread);
    const end = Math.min(lastPage, currentPage + spread);
    const pages = [];
    for (let p = start; p <= end; p += 1) pages.push(p);

    pageNumbersEl.innerHTML = pages
      .map((p) => `<button type="button" class="at-page-num ${p === currentPage ? "active" : ""}" data-page="${p}">${p}</button>`)
      .join("");
  }

  async function loadTrips(page = 1) {
    if (loading) return;
    loading = true;
    currentPage = Math.max(1, page);
    rowsEl.innerHTML = "";
    if (emptyEl) {
      emptyEl.style.display = "";
      emptyEl.textContent = "Loading...";
    }

    try {
      const url = new URL("/GoRide/frontend/api/admin_trips_list.php", window.location.origin);
      url.searchParams.set("page", String(currentPage));
      url.searchParams.set("per_page", String(perPage));
      if (searchText.trim()) url.searchParams.set("search", searchText.trim());
      const res = await fetch(url.toString(), { headers: { Accept: "application/json" } });
      const json = await res.json().catch(() => ({}));
      if (!res.ok) throw new Error(json.message || `Failed (${res.status})`);

      setStats(json?.stats || {});
      const trips = Array.isArray(json?.trips) ? json.trips : [];
      const meta = json?.meta || {};
      lastPage = Math.max(1, Number(meta?.last_page || 1));
      if (currentPage > lastPage) currentPage = lastPage;

      if (trips.length === 0) {
        rowsEl.innerHTML = "";
        if (emptyEl) {
          emptyEl.style.display = "";
          emptyEl.textContent = "No trips found.";
        }
        renderPager();
        return;
      }

      if (emptyEl) emptyEl.style.display = "none";
      rowsEl.innerHTML = trips.map(tripCardHtml).join("");
      renderPager();
    } catch (e) {
      if (emptyEl) {
        emptyEl.style.display = "";
        emptyEl.textContent = e?.message || "Could not load trips.";
      }
      if (pagerEl) pagerEl.style.display = "none";
    } finally {
      loading = false;
    }
  }

  if (pageNumbersEl) {
    pageNumbersEl.addEventListener("click", (e) => {
      const btn = e.target.closest("button[data-page]");
      if (!btn) return;
      const page = Number(btn.dataset.page || 1);
      if (!Number.isFinite(page) || page < 1 || page === currentPage) return;
      loadTrips(page);
    });
  }

  if (prevBtn) {
    prevBtn.addEventListener("click", () => {
      if (currentPage > 1) loadTrips(currentPage - 1);
    });
  }

  if (nextBtn) {
    nextBtn.addEventListener("click", () => {
      if (currentPage < lastPage) loadTrips(currentPage + 1);
    });
  }

  if (searchEl) {
    searchEl.addEventListener("input", () => {
      if (debounceTimer) clearTimeout(debounceTimer);
      debounceTimer = setTimeout(() => {
        searchText = searchEl.value || "";
        loadTrips(1);
      }, 250);
    });
  }

  loadTrips(1);
})();
