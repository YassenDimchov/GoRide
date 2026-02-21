// Sofia default view
const map = L.map("map", {
  zoomControl: false
}).setView([42.6977, 23.3219], 13);

L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
  maxZoom: 19,
  attribution: '&copy; OpenStreetMap contributors'
}).addTo(map);

L.control.zoom({ position: "topright" }).addTo(map);

let pickMode = null;

window.addEventListener("map:pickMode", (e) => {
    pickMode = e.detail.mode;

    const mapEl = document.getElementById("map");
    if (mapEl) {
        mapEl.style.cursor = "crosshair";
    }
});

let pickupMarker = null;
let dropoffMarker = null;
let routeLine = null;

let lastRouteKey = "";
window.DEBUG_DIRECTIONS = false;

function addOrMoveMarker(kind, loc) {
  if (!loc || !Number.isFinite(Number(loc.lat)) || !Number.isFinite(Number(loc.lng))) return;

  const latlng = [Number(loc.lat), Number(loc.lng)];

  if (kind === "pickup") {
    if (pickupMarker) pickupMarker.setLatLng(latlng);
    else pickupMarker = L.marker(latlng).addTo(map);
  } else {
    if (dropoffMarker) dropoffMarker.setLatLng(latlng);
    else dropoffMarker = L.marker(latlng).addTo(map);
  }
}

function showEstimateFromTripEstimate() {
  const est = window.tripEstimate || null;

  const card = document.getElementById("estimateCard");
  const etaEl = document.getElementById("estEta");
  const distEl = document.getElementById("estDistance");
  const fareEl = document.getElementById("estFare");
  const noteEl = document.getElementById("estNote");

  if (!card) return;

  const distanceM = Number(est?.distance_m);
  const durationS = Number(est?.duration_s);

  if (!Number.isFinite(distanceM) || !Number.isFinite(durationS) || distanceM <= 0 || durationS <= 0) {
    card.style.display = "none";
    return;
  }

  const km = distanceM / 1000;
  const min = Math.max(1, Math.round(durationS / 60));
  const baseFare = 1.8;
  const includedKm = 2;
  const includedMin = 5;
  const perKmAfterIncluded = 0.85;
  const perMinAfterIncluded = 0.18;
  const minFare = 3.5;

  const billableKm = Math.max(0, km - includedKm);
  const billableMin = Math.max(0, min - includedMin);
  const fare = Math.max(minFare, baseFare + (billableKm * perKmAfterIncluded) + (billableMin * perMinAfterIncluded));

  card.style.display = "";
  if (etaEl) etaEl.textContent = `${min} min`;
  if (distEl) distEl.textContent = `${km.toFixed(km < 10 ? 1 : 0)} km`;
  if (fareEl) fareEl.textContent = `${fare.toFixed(2)} EUR`;

  if (noteEl) {
    noteEl.style.display = "";
    noteEl.textContent = "Estimate only (traffic, waiting and surcharges may vary).";
  }
}

function hideEstimate() {
  const card = document.getElementById("estimateCard");
  if (card) card.style.display = "none";
}

window.mapShowEstimate = () => showEstimateFromTripEstimate();

async function drawRouteIfPossible({ fit = true, force = false } = {}) {
  if (!window.pickupLoc || !window.dropoffLoc) return false;

  const a = window.pickupLoc;
  const b = window.dropoffLoc;

  const aLat = Number(a.lat), aLng = Number(a.lng);
  const bLat = Number(b.lat), bLng = Number(b.lng);

  if (![aLat, aLng, bLat, bLng].every(Number.isFinite)) return false;

  const routeKey = `${aLat},${aLng}|${bLat},${bLng}`;

  if (!force && routeLine && routeKey === lastRouteKey) {
    showEstimateFromTripEstimate();
    return true;
  }

  lastRouteKey = routeKey;

  if (routeLine) {
    map.removeLayer(routeLine);
    routeLine = null;
  }

  try {
    const url = new URL("/GoRide/frontend/api/directions.php", window.location.origin);
    url.searchParams.set("start_lat", String(aLat));
    url.searchParams.set("start_lng", String(aLng));
    url.searchParams.set("end_lat", String(bLat));
    url.searchParams.set("end_lng", String(bLng));

    const res = await fetch(url.toString(), { headers: { Accept: "application/json" } });
    if (!res.ok) throw new Error("directions failed");

    const geo = await res.json();
    if (window.DEBUG_DIRECTIONS) console.log("Directions geo:", geo);

    const coords = geo?.features?.[0]?.geometry?.coordinates;
    if (!Array.isArray(coords) || coords.length < 2) throw new Error("bad geometry");

    const seg = geo?.features?.[0]?.properties?.segments?.[0];
    if (seg) {
      const distanceM = Math.round(Number(seg.distance || 0));
      const durationS = Math.round(Number(seg.duration || 0));
      if (Number.isFinite(distanceM) && Number.isFinite(durationS) && distanceM > 0 && durationS > 0) {
        window.tripEstimate = { distance_m: distanceM, duration_s: durationS };
      }
    }

    showEstimateFromTripEstimate();

    const latlngs = coords.map(([lng, lat]) => [lat, lng]);
    routeLine = L.polyline(latlngs, { weight: 5, opacity: 0.85 }).addTo(map);

    if (fit) map.fitBounds(routeLine.getBounds(), { padding: [60, 60] });

    return true;
  } catch (e) {
    const hasEstimate =
      Number.isFinite(Number(window.tripEstimate?.distance_m)) &&
      Number.isFinite(Number(window.tripEstimate?.duration_s));

    if (!hasEstimate) hideEstimate();
    else showEstimateFromTripEstimate();

    routeLine = L.polyline(
      [
        [aLat, aLng],
        [bLat, bLng],
      ],
      { weight: 5, opacity: 0.6 }
    ).addTo(map);

    if (fit) map.fitBounds(routeLine.getBounds(), { padding: [60, 60] });

    return false;
  }
}

function clearRouteAndPins() {
  if (routeLine) { map.removeLayer(routeLine); routeLine = null; }
  if (pickupMarker) { map.removeLayer(pickupMarker); pickupMarker = null; }
  if (dropoffMarker) { map.removeLayer(dropoffMarker); dropoffMarker = null; }

  window.pickupLoc = null;
  window.dropoffLoc = null;
  window.tripEstimate = null;

  lastRouteKey = "";
  hideEstimate();
}

window.mapResetTrip = clearRouteAndPins;
window.mapHideEstimate = hideEstimate;

map.on("click", async (e) => {
  if (!pickMode) return;

  const lat = e.latlng.lat;
  const lng = e.latlng.lng;

  const loc = {
    address: `Pin: ${lat.toFixed(5)}, ${lng.toFixed(5)}`,
    lat,
    lng,
    layer: "pin",
  };

  try {
    const url = new URL("/GoRide/frontend/api/geocode_reverse.php", window.location.origin);
    url.searchParams.set("lat", String(lat));
    url.searchParams.set("lng", String(lng));

    const res = await fetch(url.toString(), { method: "GET", headers: { Accept: "application/json" } });
    if (res.ok) {
      const json = await res.json();
      if (json && json.label) loc.address = json.label;
    }
  } catch (_) {}

  if (pickMode === "pickup") {
    window.pickupLoc = loc;
    const input = document.getElementById("pickup");
    if (input) input.value = loc.address;
    window.dispatchEvent(new CustomEvent("map:setPickup", { detail: loc }));
  }

  if (pickMode === "dropoff") {
    window.dropoffLoc = loc;
    const input = document.getElementById("destination");
    if (input) input.value = loc.address;
    window.dispatchEvent(new CustomEvent("map:setDropoff", { detail: loc }));
  }

  pickMode = null;
  const mapEl = document.getElementById("map");
  if (mapEl) mapEl.style.cursor = "";

  window.dispatchEvent(new Event("ride:locationChanged"));
});

window.addEventListener("map:setPickup", async (e) => {
  addOrMoveMarker("pickup", e.detail);
  await drawRouteIfPossible({ fit: true, force: true });
});

window.addEventListener("map:setDropoff", async (e) => {
  addOrMoveMarker("dropoff", e.detail);
  await drawRouteIfPossible({ fit: true, force: true });
});

window.mapRestoreTrip = async function (pickupLoc, dropoffLoc, estimate, options = {}) {
  if (!pickupLoc || !dropoffLoc) return;

  window.pickupLoc = {
    lat: Number(pickupLoc.lat),
    lng: Number(pickupLoc.lng),
    address: (pickupLoc.address || "").trim(),
  };
  window.dropoffLoc = {
    lat: Number(dropoffLoc.lat),
    lng: Number(dropoffLoc.lng),
    address: (dropoffLoc.address || "").trim(),
  };

  const dist = Number(estimate?.distance_m);
  const dur = Number(estimate?.duration_s);
  if (Number.isFinite(dist) && Number.isFinite(dur) && dist > 0 && dur > 0) {
    window.tripEstimate = { distance_m: dist, duration_s: dur };
    showEstimateFromTripEstimate();
  }

  addOrMoveMarker("pickup", window.pickupLoc);
  addOrMoveMarker("dropoff", window.dropoffLoc);

  await drawRouteIfPossible({ fit: options.fit !== false, force: false });
};

window.map = map;
