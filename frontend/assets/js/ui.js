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
const tripPickup = document.getElementById("tripPickup");
const tripDropoff = document.getElementById("tripDropoff");

let isWaiting = false;
let isFound = false;

let currentRideId = null;
let pollTimer = null;

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

  waitingCard.style.display = "flex";

  pickupEl.disabled = true;
  destinationEl.disabled = true;
}

function goFoundUI(ride) {
  isWaiting = false;
  isFound = true;
  const driverAvatarEl = document.getElementById("driverAvatar");
  const driverNameEl   = document.getElementById("driverName");
  const carModelEl     = document.getElementById("carModel");
  const carPlateEl     = document.getElementById("carPlate");
  const callDriverBtn  = document.getElementById("callDriverBtn");

  waitingCard.style.display = "none";

  tripPickup.textContent = ride.start_address || normalize(pickupEl.value);
  tripDropoff.textContent = ride.end_address || normalize(destinationEl.value);

  foundWrap.style.display = "flex";

  const d = ride.driver || null;
  const du = d?.user || null;

  const driverName = du?.name || "Driver";
  const initials = driverName.trim().split(/\s+/).slice(0,2).map(s => s[0]?.toUpperCase() || "").join("") || "DR";

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

}

async function pollRideStatus() {
  if (!currentRideId) return;

  try {
    const url = new URL("/GoRide/frontend/api/rides_show.php", window.location.origin);
    url.searchParams.set("id", String(currentRideId));

    const res = await fetch(url.toString(), { headers: { "Accept": "application/json" } });
    if (!res.ok) return;

    const json = await res.json();
    const ride = json.data;

    if (!ride) return;

    if (ride.status === "accepted") {
      stopPolling();
      goFoundUI(ride);
    }

  } catch (_) {}
}

async function cancelBackendRideIfAny() {
  if (!currentRideId) return;

  try {
    const res = await fetch(`/GoRide/frontend/api/rides_cancel.php?id=${currentRideId}`, {
      method: "POST",
      headers: { "Accept": "application/json" },
    });

    const json = await res.json().catch(() => ({}));

    if (!res.ok) {
      console.log("Cancel failed:", res.status, json);
      alert(json.message || `Cancel failed (${res.status})`);
      return;
    }
  } catch (e) {
    console.log("Cancel error:", e);
  } finally {
    currentRideId = null;
  }
}


pickupEl.addEventListener("input", updateState);
destinationEl.addEventListener("input", updateState);

// State 3
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
      headers: { "Content-Type": "application/json", "Accept": "application/json" },
      body: JSON.stringify(payload),
    });

    if (!res.ok) {
      const err = await res.json().catch(() => ({}));
      throw new Error(err.message || "Failed to create ride.");
    }

    const json = await res.json();
    const ride = json.data;

    currentRideId = ride?.id ?? null;

    if (ride?.estimated_fare != null) {
      const fareEl = document.getElementById("estFare");
      if (fareEl) fareEl.textContent = `${Number(ride.estimated_fare).toFixed(2)} €`;
    }

    stopPolling();
    pollTimer = setInterval(pollRideStatus, 2000);
    pollRideStatus();
  } catch (e) {
    stopPolling();
    currentRideId = null;

    isWaiting = false;
    isFound = false;

    waitingCard.style.display = "none";
    foundWrap.style.display = "none";

    pickupEl.disabled = false;
    destinationEl.disabled = false;

    alert(e.message || "Request failed");
    updateState();
  }
});

function resetRideUI() {
  isWaiting = false;
  isFound = false;

  waitingCard.style.display = "none";
  foundWrap.style.display = "none";

  pickupEl.disabled = false;
  destinationEl.disabled = false;

  pickupEl.value = "";
  destinationEl.value = "";

  window.pickupLoc = null;
  window.dropoffLoc = null;

  updateState();
}

// Cancel State 3 
cancelBtn.addEventListener("click", async () => {
  stopPolling();
  await cancelBackendRideIfAny();

  isWaiting = false;
  isFound = false;

  waitingCard.style.display = "none";
  foundWrap.style.display = "none";

  pickupEl.disabled = false;
  destinationEl.disabled = false;

  updateState();
});



// Cancel ride in State 4 
cancelRideBtn.addEventListener("click", async () => {
  stopPolling();
  await cancelBackendRideIfAny();
  if (window.mapResetTrip) window.mapResetTrip();
  resetRideUI();
});


window.addEventListener("ride:locationChanged", updateState);

updateState();
