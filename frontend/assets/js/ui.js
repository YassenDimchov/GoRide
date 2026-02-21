const pickupEl = document.getElementById("pickup");
const destinationEl = document.getElementById("destination");
const passengerCountEl = document.getElementById("passengerCount");

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
const liveEtaEl = document.getElementById("liveEta");
const liveEtaLabelEl = document.getElementById("liveEtaLabel");
const tripEtaLiveEl = document.getElementById("tripEtaLive");
const tripDistanceLeftEl = document.getElementById("tripDistanceLeft");
const driverProfileModalEl = document.getElementById("driverProfileModal");
const closeDriverProfileModalEl = document.getElementById("closeDriverProfileModal");
const selectedPaymentWarningEl = document.getElementById("selectedPaymentWarning");
const selectedPaymentMethodTextEl = document.getElementById("selectedPaymentMethodText");

const estimateCard = document.getElementById("estimateCard");

const LS_ACTIVE_RIDE_ID = "goride_user_active_ride_id";
let lastTripRestoreKey = "";

let isWaiting = false;
let isFound = false;

let currentRideId = null;
let pollTimer = null;

let driverLocationMarker = null;
let currentDriverForProfile = null;
let driverProfileOverlayEl = null;

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
  if (passengerCountEl) passengerCountEl.disabled = lock;
}

async function loadPreferredPaymentWarning() {
  if (!selectedPaymentWarningEl || !selectedPaymentMethodTextEl) return;

  try {
    const url = new URL("/GoRide/frontend/api/preferred_payment.php", window.location.origin);
    const res = await fetch(url.toString(), { headers: { Accept: "application/json" } });
    const json = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(json.message || "Failed to load payment method");

    const label = (json?.label || "Card").toString();
    selectedPaymentMethodTextEl.textContent = label;
    selectedPaymentWarningEl.style.display = "";
  } catch {
    selectedPaymentMethodTextEl.textContent = "Card";
    selectedPaymentWarningEl.style.display = "";
  }
}

function haversineKm(lat1, lng1, lat2, lng2) {
  const toRad = (v) => (v * Math.PI) / 180;
  const R = 6371;
  const dLat = toRad(lat2 - lat1);
  const dLng = toRad(lng2 - lng1);
  const a =
    Math.sin(dLat / 2) * Math.sin(dLat / 2) +
    Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) *
    Math.sin(dLng / 2) * Math.sin(dLng / 2);
  return 2 * R * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
}

function updateLiveTripMetrics(ride) {
  const status = String(ride?.status || "");
  if (status !== "accepted" && status !== "ongoing") {
    if (liveEtaEl) liveEtaEl.textContent = "- min";
    if (liveEtaLabelEl) liveEtaLabelEl.textContent = "away";
    if (tripEtaLiveEl) tripEtaLiveEl.textContent = "-";
    if (tripDistanceLeftEl) tripDistanceLeftEl.textContent = "-";
    return;
  }

  const driverLat = Number(ride?.driver?.current_lat);
  const driverLng = Number(ride?.driver?.current_lng);

  const targetLat = status === "accepted" ? Number(ride?.start_lat) : Number(ride?.end_lat);
  const targetLng = status === "accepted" ? Number(ride?.start_lng) : Number(ride?.end_lng);

  if (![driverLat, driverLng, targetLat, targetLng].every(Number.isFinite)) {
    if (liveEtaEl) liveEtaEl.textContent = "- min";
    if (tripEtaLiveEl) tripEtaLiveEl.textContent = "-";
    if (tripDistanceLeftEl) tripDistanceLeftEl.textContent = "-";
    return;
  }

  const kmLeft = haversineKm(driverLat, driverLng, targetLat, targetLng);
  const speedKmh = status === "ongoing" ? 30 : 25;
  const etaMin = Math.max(1, Math.round((kmLeft / speedKmh) * 60));
  const distanceText = `${kmLeft.toFixed(kmLeft >= 10 ? 0 : 1)} km`;

  if (liveEtaEl) liveEtaEl.textContent = `${etaMin} min`;
  if (liveEtaLabelEl) liveEtaLabelEl.textContent = status === "ongoing" ? "to dropoff" : "to pickup";
  if (tripEtaLiveEl) tripEtaLiveEl.textContent = `${etaMin} min`;
  if (tripDistanceLeftEl) tripDistanceLeftEl.textContent = distanceText;
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
  if (passengerCountEl) {
    passengerCountEl.value = String(Math.max(1, Number(ride.passenger_count || 1)));
  }

  window.tripEstimate = {
    distance_m: ride.trip_distance_m != null ? Number(ride.trip_distance_m) : null,
    duration_s: ride.trip_duration_s != null ? Number(ride.trip_duration_s) : null,
  };

  if (estimateCard && window.tripEstimate?.distance_m && window.tripEstimate?.duration_s) {
    estimateCard.style.display = "block";
  }

  if (typeof window.mapRestoreTrip === "function") {
    await window.mapRestoreTrip(window.pickupLoc, window.dropoffLoc, window.tripEstimate, { fit: false });
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
  if (passengerCountEl) passengerCountEl.value = "1";
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

async function fetchDriverProfile(driverId) {
  const url = new URL("/GoRide/frontend/api/driver_profile.php", window.location.origin);
  url.searchParams.set("id", String(driverId));

  const res = await fetch(url.toString(), { headers: { Accept: "application/json" } });
  const json = await res.json().catch(() => ({}));

  if (!res.ok) throw new Error(json.message || "Could not load driver profile.");
  return json?.driver || null;
}

function closeDriverProfileModal() {
  if (!driverProfileModalEl) return;
  driverProfileModalEl.style.display = "none";
  if (driverProfileOverlayEl) {
    driverProfileOverlayEl.remove();
    driverProfileOverlayEl = null;
  }
  document.body.style.overflow = "auto";
}

function openDriverProfileModal(profile, phone, initials) {
  if (!driverProfileModalEl || !profile) return;

  const setText = (id, text) => {
    const el = document.getElementById(id);
    if (el) el.textContent = text;
  };

  setText("driverProfileInitials", initials || "DR");
  setText("driverProfileName", profile.name || "Driver");
  setText("driverProfileAverageRating", String(profile.average_review ?? "-"));
  setText("driverProfileTotalTripsInfo", `• ${profile.total_trips ?? 0} trips`);
  setText("driverProfileTotalTrips", String(profile.total_trips ?? 0));

  const years = Number(profile?.active_time?.years || 0);
  const months = Number(profile?.active_time?.months || 0);
  const days = Number(profile?.active_time?.days || 0);
  let activeText = `${days} days`;
  if (years > 0) activeText = `${years} years, ${months} months, ${days} days`;
  else if (months > 0) activeText = `${months} months, ${days} days`;
  setText("driverProfileYearsActive", activeText);

  const avgResponseMin = Math.round(Number(profile?.average_response_time || 0) / 60);
  setText("driverProfileResponseTime", `${avgResponseMin} minutes`);

  const breakdown = profile?.rating_breakdown || {};
  const totalReviews = Object.values(breakdown).reduce((acc, n) => acc + Number(n || 0), 0);
  for (let i = 1; i <= 5; i++) {
    const count = Number(breakdown[i] || 0);
    const percent = totalReviews > 0 ? Math.round((count / totalReviews) * 100) : 0;
    const bar = document.getElementById(`driverProfileRatingBar${i}`);
    const txt = document.getElementById(`driverProfileRatingCount${i}`);
    if (bar) bar.style.width = `${percent}%`;
    if (txt) txt.textContent = `${percent}%`;
  }

  const callBtn = document.getElementById("driverProfileCallBtn");
  if (callBtn) {
    if (phone) {
      callBtn.href = `tel:${phone}`;
      callBtn.setAttribute("aria-disabled", "false");
    } else {
      callBtn.href = "javascript:void(0)";
      callBtn.setAttribute("aria-disabled", "true");
    }
  }

  driverProfileOverlayEl = document.createElement("div");
  driverProfileOverlayEl.className = "modal-overlay";
  driverProfileOverlayEl.addEventListener("click", closeDriverProfileModal);
  document.body.appendChild(driverProfileOverlayEl);

  driverProfileModalEl.style.display = "block";
  document.body.style.overflow = "hidden";
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
    Number.isInteger(Number(passengerCountEl?.value || 0)) &&
    Number(passengerCountEl?.value || 0) >= 1 &&
    Number(passengerCountEl?.value || 0) <= 8 &&
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
  currentDriverForProfile = d?.id ? { id: d.id, phone: du?.phone || "" } : null;

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
  const color = d?.vehicle_color || "";
  if (carModelEl) carModelEl.textContent = [color, make, model].filter(Boolean).join(" ") || "Vehicle";
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
  updateLiveTripMetrics(ride);

  if (ride.driver) {
    updateDriverLocation(ride.driver.current_lat, ride.driver.current_lng);
    updateDriverMarkerPopup(status);
  }

  const driverCardEl = foundWrap?.querySelector(".driver-card");
  if (driverCardEl) {
    driverCardEl.onclick = async () => {
      if (!currentDriverForProfile?.id) return;
      try {
        const profile = await fetchDriverProfile(currentDriverForProfile.id);
        openDriverProfileModal(profile, currentDriverForProfile.phone, initials);
      } catch (e) {
        alert(e?.message || "Could not open driver profile.");
      }
    };
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
if (passengerCountEl) passengerCountEl.addEventListener("input", updateState);

// Request ride
requestBtn.addEventListener("click", async () => {
  if (!window.pickupLoc || !window.dropoffLoc) {
    alert("Please select BOTH pickup and dropoff (from suggestions or by pin).");
    return;
  }
  const passengerCount = Number(passengerCountEl?.value || 0);
  if (!Number.isInteger(passengerCount) || passengerCount < 1 || passengerCount > 8) {
    alert("Passenger count must be between 1 and 8.");
    return;
  }

  const payload = {
    start_lat: window.pickupLoc.lat,
    start_lng: window.pickupLoc.lng,
    end_lat: window.dropoffLoc.lat,
    end_lng: window.dropoffLoc.lng,
    passenger_count: passengerCount,
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

if (closeDriverProfileModalEl) {
  closeDriverProfileModalEl.addEventListener("click", closeDriverProfileModal);
}

window.addEventListener("keydown", (e) => {
  if (e.key === "Escape" && driverProfileModalEl && driverProfileModalEl.style.display !== "none") {
    closeDriverProfileModal();
  }
});

(async () => {
  await loadPreferredPaymentWarning();

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
