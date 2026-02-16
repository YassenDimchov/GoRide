const pickupEl = document.getElementById("pickup");
const destinationEl = document.getElementById("destination");

const requestBtn = document.getElementById("requestRideBtn");
const readyCard = document.getElementById("readyCard");
const hint = document.getElementById("stateHint");

// State 3
const waitingCard = document.getElementById("waitingCard");
const cancelBtn = document.getElementById("cancelRequestBtn");

// State 4
const foundWrap = document.getElementById("foundWrap");
const cancelRideBtn = document.getElementById("cancelRideBtn");
const navigateBtn = document.getElementById("navigateBtn");
const tripPickup = document.getElementById("tripPickup");
const tripDropoff = document.getElementById("tripDropoff");

const estimateCard = document.getElementById("estimateCard");

const LS_ACTIVE_RIDE_ID = "goride_user_active_ride_id";
let lastTripRestoreKey = "";

let isWaiting = false;
let isFound = false;

let currentRideId = null;
let pollTimer = null;

let driverLocationMarker = null;

function saveRideId(id) {
  if (!id) localStorage.removeItem(LS_ACTIVE_RIDE_ID);
  else localStorage.setItem(LS_ACTIVE_RIDE_ID, String(id));
}

function loadRideId() {
  const v = localStorage.getItem(LS_ACTIVE_RIDE_ID);
  return v && /^\d+$/.test(v) ? Number(v) : null;
}

function stopPolling() {
  if (pollTimer) clearInterval(pollTimer);
  pollTimer = null;
}

function normalize(v) {
  return (v || "").trim();
}

function hasLoc(loc) {
  return loc && Number.isFinite(loc.lat) && Number.isFinite(loc.lng) && (loc.address || "").trim().length > 0;
}

function lockInputs(lock) {
  pickupEl.disabled = lock;
  destinationEl.disabled = lock;
}

async function restoreTripFromRide(ride) {
  if (!ride) return;

  const startLat = Number(ride.start_lat);
  const startLng = Number(ride.start_lng);
  const endLat = Number(ride.end_lat);
  const endLng = Number(ride.end_lng);

  const startAddr = (ride.start_address || "").trim();
  const endAddr = (ride.end_address || "").trim();

  const key = `${ride.start_lat},${ride.start_lng}|${ride.end_lat},${ride.end_lng}|${ride.status}`;
  if (key === lastTripRestoreKey) return;
  lastTripRestoreKey = key;

  if (!Number.isFinite(startLat) || !Number.isFinite(startLng) || !Number.isFinite(endLat) || !Number.isFinite(endLng)) {
    return;
  }

  pickupEl.value = startAddr || pickupEl.value;
  destinationEl.value = endAddr || destinationEl.value;

  window.pickupLoc = { lat: startLat, lng: startLng, address: startAddr || pickupEl.value };
  window.dropoffLoc = { lat: endLat, lng: endLng, address: endAddr || destinationEl.value };

  window.tripEstimate = {
    distance_m: ride.trip_distance_m != null ? Number(ride.trip_distance_m) : null,
    duration_s: ride.trip_duration_s != null ? Number(ride.trip_duration_s) : null,
  };

  if (estimateCard && window.tripEstimate?.distance_m && window.tripEstimate?.duration_s) {
    estimateCard.style.display = "block";
  }

  if (typeof window.mapRestoreTrip === "function") {
    await window.mapRestoreTrip(window.pickupLoc, window.dropoffLoc, window.tripEstimate);
  }
}

function toState1() {
  stopPolling();
  saveRideId(null);
  currentRideId = null;

  isWaiting = false;
  isFound = false;

  waitingCard.style.display = "none";
  foundWrap.style.display = "none";

  pickupEl.value = "";
  destinationEl.value = "";
  window.pickupLoc = null;
  window.dropoffLoc = null;
  window.tripEstimate = null;

  if (driverLocationMarker) {
    map.removeLayer(driverLocationMarker);
    driverLocationMarker = null;
  }
  if (window.mapResetTrip) window.mapResetTrip();
  if (window.mapHideEstimate) window.mapHideEstimate();

  lockInputs(false);

  hint.style.display = "block";
  requestBtn.style.display = "none";
  readyCard.style.display = "none";
}

function ensureCompletedButtons() {
  const actions = foundWrap?.querySelector(".found-actions");
  if (!actions) return { newRideBtn: null, reviewBtn: null };

  let newRideBtn = document.getElementById("newRideBtn");
  let reviewBtn = document.getElementById("leaveReviewBtn");

  if (!newRideBtn) {
    newRideBtn = document.createElement("button");
    newRideBtn.id = "newRideBtn";
    newRideBtn.type = "button";
    newRideBtn.className = "btn-outline";
    newRideBtn.textContent = "New Ride";
    actions.appendChild(newRideBtn);
  }

  if (!reviewBtn) {
    reviewBtn = document.createElement("button");
    reviewBtn.id = "leaveReviewBtn";
    reviewBtn.type = "button";
    reviewBtn.className = "btn-outline";
    reviewBtn.textContent = "Leave a Review";
    actions.appendChild(reviewBtn);
  }

  return { newRideBtn, reviewBtn };
}

function setFoundHeader(status) {
  const title = foundWrap?.querySelector(".found-title span:last-child");
  const sub = foundWrap?.querySelector(".found-sub");
  if (!title || !sub) return;

  if (status === "accepted") {
    title.textContent = "Driver Found!";
    sub.textContent = "Your driver is on the way";
  } else if (status === "ongoing") {
    title.textContent = "Ride Started!";
    sub.textContent = "You are on the trip";
  } else if (status === "completed") {
    title.textContent = "Trip Completed!";
    sub.textContent = "You’ve arrived";
  } else {
    title.textContent = "Driver Found!";
    sub.textContent = "Your driver is on the way";
  }
}

function clearDriverMarker() {
  if (driverLocationMarker) {
    map.removeLayer(driverLocationMarker);
    driverLocationMarker = null;
  }
}

function updateDriverMarkerPopup(status) {
  if (!driverLocationMarker) return;
  const txt = status === "ongoing" ? "Ride is ongoing" : "Your driver is on the way!";
  driverLocationMarker.bindPopup(txt);
}

function updateDriverLocation(lat, lng) {
  const la = Number(lat);
  const ln = Number(lng);
  if (!Number.isFinite(la) || !Number.isFinite(ln)) return;

  const customIcon = L.icon({
    iconUrl: "assets/images/Icons/car-icon-2.svg",
    iconSize: [32, 32],
    iconAnchor: [16, 32],
    popupAnchor: [0, -32],
  });

  if (driverLocationMarker) {
    driverLocationMarker.setLatLng([la, ln]);
  } else {
    driverLocationMarker = L.marker([la, ln], { icon: customIcon }).addTo(map);
  }
}

function applyActionButtons(ride) {
  const status = String(ride?.status || "accepted");

  const { newRideBtn, reviewBtn } = ensureCompletedButtons();
  if (newRideBtn) newRideBtn.style.display = "none";
  if (reviewBtn) reviewBtn.style.display = "none";

  const callDriverBtn = document.getElementById("callDriverBtn");

  if (status === "completed") {
    if (cancelRideBtn) cancelRideBtn.style.display = "none";
    if (callDriverBtn) callDriverBtn.style.display = "none";
    if (navigateBtn) navigateBtn.style.display = "none";

    if (newRideBtn) {
      newRideBtn.style.display = "";
      newRideBtn.onclick = () => toState1();
    }
    if (reviewBtn) {
      reviewBtn.style.display = "";
      reviewBtn.onclick = async () => {
          if (!window.ReviewModal || typeof window.ReviewModal.open !== "function") {
              alert("Review modal is not available.");
              return;
          }

          const latest = currentRideId ? await fetchRide(currentRideId) : ride;

          window.ReviewModal.open({
              rideId: latest?.id || currentRideId,
              ride: latest || ride,
              onSubmitted: () => {
                  if (reviewBtn) reviewBtn.style.display = "none";
              },
          });
      };
    }
    return;
  }

  if (cancelRideBtn) cancelRideBtn.style.display = status === "ongoing" ? "none" : "";
  if (navigateBtn) navigateBtn.style.display = status === "ongoing" ? "" : "none";

  if (navigateBtn) {
    const pickupLat = Number(ride.start_lat);
    const pickupLng = Number(ride.start_lng);
    const dropLat = Number(ride.end_lat);
    const dropLng = Number(ride.end_lng);

    navigateBtn.onclick = () => {
      const url =
        status === "ongoing"
          ? `https://www.google.com/maps/dir/?api=1&origin=${encodeURIComponent(
              pickupLat + "," + pickupLng
            )}&destination=${encodeURIComponent(dropLat + "," + dropLng)}&travelmode=driving`
          : `https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(
              pickupLat + "," + pickupLng
            )}&travelmode=driving`;

      window.open(url, "_blank", "noopener");
    };
  }
}

function updateState() {
  if (isWaiting || isFound) return;

  const pickup = normalize(pickupEl.value);
  const dest = normalize(destinationEl.value);

  const canRequest =
    pickup.length > 0 &&
    dest.length > 0 &&
    hasLoc(window.pickupLoc) &&
    hasLoc(window.dropoffLoc);

  // Hide states 3,4
  waitingCard.style.display = "none";
  foundWrap.style.display = "none";

  // State 2
  requestBtn.style.display = canRequest ? "block" : "none";
  readyCard.style.display = canRequest ? "flex" : "none";

  // State 1
  hint.style.display = canRequest ? "none" : "block";
}

function goWaitingUI() {
  isWaiting = true;
  isFound = false;

  hint.style.display = "none";
  requestBtn.style.display = "none";
  readyCard.style.display = "none";
  foundWrap.style.display = "none";

  if (estimateCard && window.tripEstimate?.distance_m && window.tripEstimate?.duration_s) {
    estimateCard.style.display = "block";
  }

  waitingCard.style.display = "flex";
  lockInputs(true);
}

function goFoundUI(ride) {
  isWaiting = false;
  isFound = true;

  waitingCard.style.display = "none";
  foundWrap.style.display = "flex";
  lockInputs(true);

  if (estimateCard && window.tripEstimate?.distance_m && window.tripEstimate?.duration_s) {
    estimateCard.style.display = "block";
  }

  tripPickup.textContent = ride.start_address || normalize(pickupEl.value);
  tripDropoff.textContent = ride.end_address || normalize(destinationEl.value);

  const driverAvatarEl = document.getElementById("driverAvatar");
  const driverNameEl = document.getElementById("driverName");
  const carModelEl = document.getElementById("carModel");
  const carPlateEl = document.getElementById("carPlate");
  const callDriverBtn = document.getElementById("callDriverBtn");

  const d = ride.driver || null;
  const du = d?.user || null;

  const driverName = du?.name || "Driver";
  const initials =
    driverName
      .trim()
      .split(/\s+/)
      .slice(0, 2)
      .map((s) => (s[0] || "").toUpperCase())
      .join("") || "DR";

  if (driverAvatarEl) driverAvatarEl.textContent = initials;
  if (driverNameEl) driverNameEl.textContent = driverName;

  const make = d?.vehicle_make || "";
  const model = d?.vehicle_model || "";
  if (carModelEl) carModelEl.textContent = [make, model].filter(Boolean).join(" ") || "Vehicle";
  if (carPlateEl) carPlateEl.textContent = d?.license_plate || "—";

  if (callDriverBtn) {
    const phone = du?.phone || "";
    callDriverBtn.disabled = !phone;
    callDriverBtn.onclick = () => {
      if (!phone) return;
      window.location.href = `tel:${phone}`;
    };
  }

  const status = String(ride.status || "accepted");
  setFoundHeader(status);
  applyActionButtons(ride);

  if (ride.driver) {
    updateDriverLocation(ride.driver.current_lat, ride.driver.current_lng);
    updateDriverMarkerPopup(status);
  }
}

async function fetchRide(id) {
  const url = new URL("/GoRide/frontend/api/rides_show.php", window.location.origin);
  url.searchParams.set("id", String(id));
  const res = await fetch(url.toString(), { headers: { Accept: "application/json" } });
  if (!res.ok) return null;
  const json = await res.json().catch(() => null);
  return json?.data || null;
}

async function pollActiveRide() {
  if (!currentRideId) return;

  const ride = await fetchRide(currentRideId);
  if (!ride) return;

  const status = String(ride.status || "");

  if (status === "pending" || status === "accepted" || status === "ongoing") {
    await restoreTripFromRide(ride);
  }

  if (status === "pending") {
    goWaitingUI();
    return;
  }

  if (status === "accepted" || status === "ongoing") {
    goFoundUI(ride);
    return;
  }

  if (status === "completed") {
    stopPolling();
    saveRideId(null);
    currentRideId = null;

    goFoundUI({ ...ride, status: "completed" });

    pickupEl.value = "";
    destinationEl.value = "";
    window.pickupLoc = null;
    window.dropoffLoc = null;

    lockInputs(false);

    clearDriverMarker();
    if (window.mapResetTrip) window.mapResetTrip();
    if (window.mapHideEstimate) window.mapHideEstimate();

    return;
  }

  if (status === "cancelled") {
    stopPolling();
    saveRideId(null);
    currentRideId = null;
    toState1();
  }
}

hint.style.display = "none";
requestBtn.style.display = "none";
readyCard.style.display = "none";

function startPolling() {
  stopPolling();
  pollTimer = setInterval(pollActiveRide, 2000);
  pollActiveRide();
}

async function cancelBackendRideIfAny() {
  if (!currentRideId) return;

  try {
    const res = await fetch(`/GoRide/frontend/api/rides_cancel.php?id=${currentRideId}`, {
      method: "POST",
      headers: { Accept: "application/json" },
    });

    const json = await res.json().catch(() => ({}));
    if (!res.ok) {
      alert(json.message || `Cancel failed (${res.status})`);
      return false;
    }
    return true;
  } catch {
    return false;
  } finally {
    saveRideId(null);
    currentRideId = null;
  }
}

pickupEl.addEventListener("input", updateState);
destinationEl.addEventListener("input", updateState);
window.addEventListener("ride:locationChanged", updateState);

// Request ride
requestBtn.addEventListener("click", async () => {
  if (!window.pickupLoc || !window.dropoffLoc) {
    alert("Please select BOTH pickup and dropoff (from suggestions or by pin).");
    return;
  }

  const payload = {
    start_lat: window.pickupLoc.lat,
    start_lng: window.pickupLoc.lng,
    end_lat: window.dropoffLoc.lat,
    end_lng: window.dropoffLoc.lng,
    start_address: window.pickupLoc.address || normalize(pickupEl.value),
    end_address: window.dropoffLoc.address || normalize(destinationEl.value),
    trip_distance_m: window.tripEstimate?.distance_m ?? null,
    trip_duration_s: window.tripEstimate?.duration_s ?? null,
  };

  goWaitingUI();

  try {
    const url = new URL("/GoRide/frontend/api/rides_create.php", window.location.origin);

    const res = await fetch(url.toString(), {
      method: "POST",
      headers: { "Content-Type": "application/json", Accept: "application/json" },
      body: JSON.stringify(payload),
    });

    if (!res.ok) {
      const err = await res.json().catch(() => ({}));
      throw new Error(err.message || "Failed to create ride.");
    }

    const json = await res.json();
    const ride = json.data;

    currentRideId = ride?.id ?? null;
    saveRideId(currentRideId);

    startPolling();
  } catch (e) {
    stopPolling();
    saveRideId(null);
    currentRideId = null;

    alert(e.message || "Request failed");
    toState1();
  }
});

cancelBtn.addEventListener("click", async () => {
  stopPolling();
  await cancelBackendRideIfAny();
  toState1();
});

cancelRideBtn.addEventListener("click", async () => {
  const ride = currentRideId ? await fetchRide(currentRideId) : null;
  if (ride && String(ride.status) === "ongoing") {
    alert("You can't cancel once the ride has started.");
    return;
  }

  stopPolling();
  await cancelBackendRideIfAny();
  toState1();
});

(async () => {
  const saved = loadRideId();
  if (saved) {
    currentRideId = saved;
    startPolling();
    return;
  }

  try {
    const url = new URL("/GoRide/frontend/api/rides_active.php", window.location.origin);
    const res = await fetch(url.toString(), { headers: { Accept: "application/json" } });
    const json = await res.json().catch(() => null);

    const ride = json?.data || null;
    if (ride?.id) {
      currentRideId = Number(ride.id);
      saveRideId(currentRideId);
      startPolling();
      return;
    }
  } catch (_) {}

  updateState();
})();
