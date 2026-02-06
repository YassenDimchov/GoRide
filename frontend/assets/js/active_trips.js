(() => {
  const wrap = document.querySelector(".status-pills");
  const msg = document.getElementById("statusMsg");
  const list = document.getElementById("pendingList");
  const empty = document.getElementById("pendingEmpty");
  const count = document.getElementById("pendingCount");

  let locTimer = null;

  function stopLocationTracking() {
    if (locTimer) clearInterval(locTimer);
    locTimer = null;
  }

  async function sendLocation(lat, lng) {
    try {
      await fetch("driver_location_action.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ lat, lng })
      });
    } catch {}
  }

  function startLocationTracking() {
    if (!navigator.geolocation) {
      setMsg("Geolocation not supported.");
      return;
    }

    const tick = () => {
      navigator.geolocation.getCurrentPosition(
        (pos) => {
          const { latitude, longitude } = pos.coords;
          sendLocation(latitude, longitude);
        },
        () => {
          setMsg("Enable location to receive requests.");
        },
        { enableHighAccuracy: true, timeout: 8000, maximumAge: 5000 }
      );
    };

    stopLocationTracking();
    tick();
    locTimer = setInterval(tick, 15000);
  }

  if (!wrap) return;

  let current = wrap.dataset.currentStatus || "offline";
  paint(current);
  if (current === "available") startLocationTracking();
  refreshPending();

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

  function initials(name) {
    if (!name) return "U";
    return name.trim().split(/\s+/).slice(0,2).map(s => s[0]?.toUpperCase() || "").join("") || "U";
  }

  function fmtMoney(v) {
    if (v == null) return "";
    const n = Number(v);
    if (!Number.isFinite(n)) return String(v);
    return `${n.toFixed(2)} €`;
  }

  function fmtKm(v) {
    const n = Number(v);
    if (!Number.isFinite(n)) return "";
    return `${n.toFixed(1)} km`;
  }

  function setList(data) {
    if (!list || !empty || !count) return;

    count.textContent = String(data.length);

    if (data.length === 0) {
      list.innerHTML = "";
      empty.style.display = "block";
      return;
    }

    empty.style.display = "none";

    list.innerHTML = data.map(r => {
      const u = r.user || {};
      const nm = u.name || "Passenger";
      const wait = r.match?.wait_min != null ? `${r.match.wait_min} min ago` : "";

      const pickup = r.start_address || `${r.start_lat}, ${r.start_lng}`;
      const drop = r.end_address || `${r.end_lat}, ${r.end_lng}`;

      const money = r.match?.estimated_fare ?? null;
      const dist = r.match?.distance_km != null ? `${r.match.distance_km.toFixed(1)} km` : "";
      const tripKm = r.match?.trip_km != null ? `${r.match.trip_km.toFixed(1)} km trip` : "";

      return `
        <div class="req-card" data-ride-id="${r.id}">
          <div class="req-top">
            <div class="req-user">
              <div class="req-avatar">${initials(nm)}</div>
              <div>
                <div class="req-name">${escapeHtml(nm)}</div>
                <div class="req-sub">⭐ 4.8 <span>•</span> ${escapeHtml(wait)}</div>
              </div>
            </div>
            <div class="req-price">
              <div class="money">${money != null ? fmtMoney(money) : ""}</div>
              <div class="km">
                ${dist ? `${escapeHtml(dist)} away` : ""}
                ${tripKm ? ` • ${escapeHtml(tripKm)}` : ""}
              </div>
            </div>
          </div>

          <div class="req-route">
            <div class="route-row">
              <div class="route-dot pick"></div>
              <div>
                <div class="route-label">Pickup</div>
                <div class="route-text">${escapeHtml(pickup)}</div>
              </div>
            </div>
            <div style="height:12px;"></div>
            <div class="route-row">
              <div class="route-dot drop"></div>
              <div>
                <div class="route-label">Dropoff</div>
                <div class="route-text">${escapeHtml(drop)}</div>
              </div>
            </div>
          </div>

          <div class="req-actions">
            <button class="btn-accept" type="button" data-action="accept">Accept Ride</button>
          </div>
        </div>
      `;
    }).join("");
  }

  async function refreshPending() {
    if (!list || !empty || !count) return;

    if (current !== "available") {
      setList([]);
      empty.style.display = "block";
      empty.textContent = current === "offline"
        ? "You are offline. Switch to Online to see pending requests."
        : "You are busy right now.";
      return;
    }

    setMsg("Loading pending requests...");
    try {
      const res = await fetch("driver_available_rides_action.php", { method: "GET" });
      const data = await res.json().catch(() => null);

      if (!res.ok || !data?.ok) {
        if (data?.code === "AUTO_OFFLINE") {
          paint("offline");
          setMsg(data.message || "Offline due to inactivity.");
          return refreshPending();
        }

        setMsg(data?.message || "Failed to load rides");
        setList([]);
        return;
      }

      setList(Array.isArray(data.data) ? data.data : []);
      setMsg("");
    } catch {
      setMsg("Network error");
      setList([]);
    }
  }

  async function acceptRide(rideId, cardEl) {
    setMsg("Accepting...");
    disableCard(cardEl, true);

    try {
      const res = await fetch("driver_accept_ride_action.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ ride_id: rideId })
      });

      const data = await res.json().catch(() => null);

      if (!res.ok || !data?.ok) {
        setMsg(data?.message || "Failed to accept");
        disableCard(cardEl, false);
        return;
      }

      paint("busy");
      setMsg("Ride accepted ✓");
      cardEl?.remove();
      refreshPending();
    } catch {
      setMsg("Network error");
      disableCard(cardEl, false);
    }
  }

  function disableCard(cardEl, disabled) {
    if (!cardEl) return;
    cardEl.querySelectorAll("button").forEach(b => b.disabled = disabled);
    cardEl.style.opacity = disabled ? "0.7" : "1";
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
      if (data.status === "available") startLocationTracking();
      else stopLocationTracking();

      refreshPending();
      setMsg("Updated ✓");
      setTimeout(() => setMsg(""), 800);
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

  document.addEventListener("click", (e) => {
    const card = e.target.closest(".req-card");
    const btn = e.target.closest("button");
    if (!card || !btn) return;

    const rideId = Number(card.dataset.rideId);
    if (!Number.isFinite(rideId)) return;

    const action = btn.dataset.action;

    if (action === "accept") {
      acceptRide(rideId, card);
    }
  });

  function escapeHtml(str) {
    return String(str ?? "")
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  }
})();
