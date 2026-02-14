(() => {
  const wrap = document.querySelector(".status-pills");
  const msg = document.getElementById("statusMsg");

  const pendingSection = document.getElementById("pendingSection");
  const ongoingSection = document.getElementById("ongoingSection");

  const pendingList = document.getElementById("pendingList");
  const pendingEmpty = document.getElementById("pendingEmpty");
  const pendingCount = document.getElementById("pendingCount");

  const ongoingList = document.getElementById("ongoingList");
  const ongoingEmpty = document.getElementById("ongoingEmpty");
  const ongoingCount = document.getElementById("ongoingCount");

  const tabsWrap = document.querySelector(".trips-tabs");

  if (!wrap) return;

  const POLL_MS = 5000; 
  const LOC_MS  = 15000;

  const URL_PENDING  = "driver_available_rides_action.php";
  const URL_ACCEPT   = "driver_accept_ride_action.php";
  const URL_STATUS   = "driver_status_action.php";
  const URL_LOC      = "driver_location_action.php";
  const URL_START    = "driver_start_ride_action.php";
  const URL_COMPLETE = "driver_complete_ride_action.php";
  const URL_ACTIVE = "driver_active_ride_action.php";

  const LS_ACTIVE_RIDE = "goride_driver_active_ride";
  const LS_ACTIVE_TAB  = "goride_driver_active_tab";

  let current = wrap.dataset.currentStatus || "offline";
  let pollTimer = null;
  let locTimer = null;

  //  helpers 
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

  function escapeHtml(str) {
    return String(str ?? "")
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  }

  function initials(name) {
    if (!name) return "U";
    return name.trim().split(/\s+/).slice(0,2).map(s => s[0]?.toUpperCase() || "").join("") || "U";
  }

  function fmtMoney(v) {
    const n = Number(v);
    if (!Number.isFinite(n)) return "";
    return `${n.toFixed(2)} €`;
  }

  function setTab(tab) {
    localStorage.setItem(LS_ACTIVE_TAB, tab);
    if (tabsWrap) {
      tabsWrap.querySelectorAll(".tab").forEach(b => {
        b.classList.toggle("is-active", b.dataset.tab === tab);
      });
    }
    if (tab === "pending") {
      pendingSection.style.display = "";
      ongoingSection.style.display = "none";
    } else {
      pendingSection.style.display = "none";
      ongoingSection.style.display = "";
    }
  }

  function getSavedRide() {
    try { return JSON.parse(localStorage.getItem(LS_ACTIVE_RIDE) || "null"); }
    catch { return null; }
  }

  function saveRide(ride) {
    if (!ride) localStorage.removeItem(LS_ACTIVE_RIDE);
    else localStorage.setItem(LS_ACTIVE_RIDE, JSON.stringify(ride));
  }

  async function fetchActiveRideFromServer() {
    try {
      const res = await fetch(URL_ACTIVE);
      const data = await res.json().catch(() => null);
      if (!res.ok || !data?.ok) return null;
      return data.data || null;
    } catch {
      return null;
    }
  }

  function getPos() {
    return new Promise((resolve, reject) => {
      if (!navigator.geolocation) return reject(new Error("Geolocation not supported"));
      navigator.geolocation.getCurrentPosition(
        (pos) => resolve(pos),
        (err) => reject(err),
        { enableHighAccuracy: true, timeout: 8000, maximumAge: 5000 }
      );
    });
  }

  function openGoogleNav({ originLat, originLng, destLat, destLng }) {
    const url = new URL("https://www.google.com/maps/dir/");
    url.searchParams.set("api", "1");
    url.searchParams.set("destination", `${destLat},${destLng}`);
    url.searchParams.set("travelmode", "driving");

    if (Number.isFinite(originLat) && Number.isFinite(originLng)) {
      url.searchParams.set("origin", `${originLat},${originLng}`);
    }

    window.open(url.toString(), "_blank", "noopener,noreferrer");
  }

  async function handleNavigateClick(ride) {
    if (!ride) return;

    const status = String(ride.status || "accepted");

    const dest =
      status === "accepted"
        ? { lat: Number(ride.start_lat), lng: Number(ride.start_lng) }
        : { lat: Number(ride.end_lat),   lng: Number(ride.end_lng) };

    if (!Number.isFinite(dest.lat) || !Number.isFinite(dest.lng)) {
      setMsg("Missing destination coordinates for navigation.");
      return;
    }

    try {
      const pos = await getPos();
      openGoogleNav({
        originLat: pos.coords.latitude,
        originLng: pos.coords.longitude,
        destLat: dest.lat,
        destLng: dest.lng,
      });
    } catch {
      openGoogleNav({
        originLat: NaN,
        originLng: NaN,
        destLat: dest.lat,
        destLng: dest.lng,
      });
    }
  }

  // location tracking
  function stopLocationTracking() {
    if (locTimer) clearInterval(locTimer);
    locTimer = null;
  }

  async function sendLocation(lat, lng) {
    try {
      await fetch(URL_LOC, {
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
        (pos) => sendLocation(pos.coords.latitude, pos.coords.longitude),
        () => setMsg("Enable location to receive requests."),
        { enableHighAccuracy: true, timeout: 8000, maximumAge: 5000 }
      );
    };
    stopLocationTracking();
    tick();
    locTimer = setInterval(tick, LOC_MS);
  }

  // render pending
  function renderPending(data) {
    pendingCount.textContent = String(data.length);

    if (!data.length) {
      pendingList.innerHTML = "";
      pendingEmpty.style.display = "block";
      return;
    }

    pendingEmpty.style.display = "none";
    pendingList.innerHTML = data.map(r => {
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
                <div class="req-sub">${escapeHtml(wait)}</div>
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
    if (current !== "available") {
      renderPending([]);
      pendingEmpty.style.display = "block";
      pendingEmpty.textContent =
        current === "offline"
          ? "You are offline. Switch to Online to see pending requests."
          : "You are busy right now.";
      return;
    }

    try {
      const res = await fetch(URL_PENDING);
      const data = await res.json().catch(() => null);

      if (!res.ok || !data?.ok) {
        if (data?.code === "AUTO_OFFLINE") {
          paint("offline");
          setMsg(data.message || "Offline due to inactivity.");
          return refreshPending();
        }
        setMsg(data?.message || "Failed to load rides");
        renderPending([]);
        return;
      }

      setMsg("");
      renderPending(Array.isArray(data.data) ? data.data : []);
    } catch {
      setMsg("Network error");
      renderPending([]);
    }
  }

  // render ongoing
  function renderOngoing(ride) {
    const hasRide = !!ride;
    ongoingCount.textContent = hasRide ? "1" : "0";

    if (!hasRide) {
      ongoingList.innerHTML = "";
      ongoingEmpty.style.display = "block";
      return;
    }

    ongoingEmpty.style.display = "none";

    const u = ride.user || {};
    const nm = u.name || "Passenger";
    const pickup = ride.start_address || `${ride.start_lat}, ${ride.start_lng}`;
    const drop = ride.end_address || `${ride.end_lat}, ${ride.end_lng}`;
    const phone = u.phone || "";
    const email = u.email || "";

    const est = ride.estimated_fare ?? ride.match?.estimated_fare ?? null;
    const status = String(ride.status || "accepted");

    const badge =
      status === "ongoing" ? "On trip" :
      status === "accepted" ? "En route to pickup" :
      status;

    const progress = status === "ongoing" ? 70 : 20;

    ongoingList.innerHTML = `
      <div class="ongoing-card" data-ride-id="${ride.id}">
        <div class="ongoing-top">
          <div class="ongoing-badge">${escapeHtml(badge)}</div>
          <div class="ongoing-price">${est != null ? fmtMoney(est) : ""}</div>
        </div>

        <div class="ongoing-progress">
          <div class="progress-label">Trip Progress</div>
          <div class="progress-bar">
            <div class="progress-fill" style="width:${progress}%"></div>
          </div>
        </div>

        <div class="ongoing-user">
          <div class="req-user">
            <div class="req-avatar">${initials(nm)}</div>
            <div>
              <div class="req-name">${escapeHtml(nm)}</div>
              <div class="req-sub"></div>
            </div>
          </div>

          <div class="ongoing-actions-small">
            <button class="btn-mini" type="button" data-action="call">Call</button>
            <button class="btn-mini" type="button" data-action="msg">Message</button>
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

        <div class="ongoing-bottom">
          <button class="btn-nav" type="button" data-action="navigate">Navigate</button>

          ${
            status === "accepted"
              ? `<button class="btn-complete" type="button" data-action="start">Start Trip</button>`
              : `<button class="btn-complete" type="button" data-action="complete">Complete Trip</button>`
          }
        </div>
      </div>
    `;
  }

  // accept / start / complete
  function disableCard(cardEl, disabled) {
    if (!cardEl) return;
    cardEl.querySelectorAll("button").forEach(b => b.disabled = disabled);
    cardEl.style.opacity = disabled ? "0.7" : "1";
  }

  async function acceptRide(rideId, cardEl) {
    setMsg("Accepting...");
    disableCard(cardEl, true);

    try {
      const res = await fetch(URL_ACCEPT, {
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

      const ride = data.data || null;
      saveRide(ride);

      paint("busy");
      setMsg("Ride accepted ✓");

      renderOngoing(ride);
      setTab("ongoing");

      renderPending([]);
    } catch {
      setMsg("Network error");
      disableCard(cardEl, false);
    }
  }

  async function startTrip(rideId, cardEl) {
    setMsg("Starting trip...");
    disableCard(cardEl, true);

    try {
      const res = await fetch(URL_START, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ ride_id: rideId })
      });

      const data = await res.json().catch(() => null);

      if (!res.ok || !data?.ok) {
        setMsg(data?.message || "Failed to start");
        disableCard(cardEl, false);
        return;
      }

      const ride = data.data || null;
      saveRide(ride);
      renderOngoing(ride);

      setMsg("Trip started ✓");
      setTimeout(() => setMsg(""), 900);
    } catch {
      setMsg("Network error");
      disableCard(cardEl, false);
    }
  }

  async function completeTrip(rideId, cardEl) {
    setMsg("Completing trip...");
    disableCard(cardEl, true);

    try {
      const res = await fetch(URL_COMPLETE, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ ride_id: rideId })
      });

      const data = await res.json().catch(() => null);

      if (!res.ok || !data?.ok) {
        setMsg(data?.message || "Failed to complete");
        disableCard(cardEl, false);
        return;
      }

      saveRide(null);
      renderOngoing(null);

      paint("available");
      setTab("pending");

      setMsg("Trip completed ✓");
      startLocationTracking();
      refreshPending();
    } catch {
      setMsg("Network error");
      disableCard(cardEl, false);
    }
  }

  async function updateStatus(status) {
    setMsg("Saving...");
    setDisabled(true);

    try {
      const res = await fetch(URL_STATUS, {
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

  function startPolling() {
    if (pollTimer) clearInterval(pollTimer);

    pollTimer = setInterval(async () => {
      const serverRide = await fetchActiveRideFromServer();

      if (serverRide) {
        saveRide(serverRide);
        paint("busy");
        renderOngoing(serverRide);
      } else {
        saveRide(null);
        renderOngoing(null);
        if (current !== "offline") paint("available");
      }

      refreshPending();
    }, POLL_MS);
  }


  wrap.addEventListener("click", (e) => {
    const btn = e.target.closest(".pill");
    if (!btn || btn.disabled) return;

    const next = btn.dataset.status;
    if (next === "busy") return;
    if (next === current) return;

    updateStatus(next);
  });

  if (tabsWrap) {
    tabsWrap.addEventListener("click", (e) => {
      const tabBtn = e.target.closest(".tab");
      if (!tabBtn) return;
      setTab(tabBtn.dataset.tab);
    });
  }

  document.addEventListener("click", (e) => {
    const reqCard = e.target.closest(".req-card");
    const btn = e.target.closest("button");
    if (reqCard && btn) {
      const rideId = Number(reqCard.dataset.rideId);
      if (!Number.isFinite(rideId)) return;
      if (btn.dataset.action === "accept") acceptRide(rideId, reqCard);
      return;
    }

    const ongoingCard = e.target.closest(".ongoing-card");
    if (ongoingCard && btn) {
      const rideId = Number(ongoingCard.dataset.rideId);
      if (!Number.isFinite(rideId)) return;

      const action = btn.dataset.action;
      if (action === "start") return startTrip(rideId, ongoingCard);
      if (action === "complete") return completeTrip(rideId, ongoingCard);
      if (action === "navigate") {
        const ride = getSavedRide();
        if (!ride) {
          setMsg("No active ride found.");
          return;
        }
        handleNavigateClick(ride);
        return;
      }
      if (action === "call") {
        const ride = getSavedRide();
        const phone = ride?.user?.phone;
        if (phone) window.location.href = `tel:${phone}`;
        else setMsg("No phone number for this user.");
        return;
      }

      if (action === "msg") {
        const ride = getSavedRide();
        const phone = ride?.user?.phone;
        if (phone) window.location.href = `sms:${phone}`;
        else setMsg("No phone number for this user.");
        return;
      }
      return;
    }
  });

  paint(current);

  const savedTab = localStorage.getItem(LS_ACTIVE_TAB) || "pending";
  setTab(savedTab);

  (async () => {
    const serverRide = await fetchActiveRideFromServer();

    if (serverRide) {
      saveRide(serverRide);
      paint("busy");
      renderOngoing(serverRide);
      setTab("ongoing");
    } else {
      saveRide(null);
      renderOngoing(null);
    }
  })();




  if (current === "available") startLocationTracking();
  refreshPending();
  startPolling();
})();
