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

function makeDot(lat, lng, kind) {
  return L.circleMarker([lat, lng], {
    radius: 8,
    weight: 2,
    opacity: 1,
    fillOpacity: 1,
    color: kind === "pickup" ? "#16a34a" : "#dc2626",
    fillColor: kind === "pickup" ? "#22c55e" : "#ef4444"
  });
}

function addOrMoveMarker(kind, loc) {
  if (!loc || !Number.isFinite(loc.lat) || !Number.isFinite(loc.lng)) return;

  const latlng = [loc.lat, loc.lng];

  const marker = L.marker(latlng);

  if (kind === "pickup") {
    if (pickupMarker) map.removeLayer(pickupMarker);
    // pickupMarker = marker.addTo(map).bindPopup("Pickup").openPopup();
        pickupMarker = marker.addTo(map);
  } else {
    if (dropoffMarker) map.removeLayer(dropoffMarker);
    // dropoffMarker = marker.addTo(map).bindPopup("Dropoff").openPopup();
        dropoffMarker = marker.addTo(map);
  }
}

async function drawRouteIfPossible() {
  if (routeLine) { map.removeLayer(routeLine); routeLine = null; }

  if (!window.pickupLoc || !window.dropoffLoc) return;

  const a = window.pickupLoc;
  const b = window.dropoffLoc;

  if (![a.lat,a.lng,b.lat,b.lng].every(Number.isFinite)) return;

  try {
    const url = new URL("/GoRide/frontend/api/directions.php", window.location.origin);
    url.searchParams.set("start_lat", a.lat);
    url.searchParams.set("start_lng", a.lng);
    url.searchParams.set("end_lat", b.lat);
    url.searchParams.set("end_lng", b.lng);

    const res = await fetch(url.toString(), { headers: { "Accept": "application/json" } });
    if (!res.ok) throw new Error("directions failed");
    console.log("Directions status:", res.status);
    const txt = await res.text();
    console.log("Directions raw:", txt);
    const geo = JSON.parse(txt);

    const coords = geo?.features?.[0]?.geometry?.coordinates;
    if (!Array.isArray(coords) || coords.length < 2) throw new Error("bad geometry");
    
    window.tripEstimate = null;

    const seg = geo?.features?.[0]?.properties?.segments?.[0];

    if (seg) {
        const distanceM = Math.round(Number(seg.distance || 0));
        const durationS = Math.round(Number(seg.duration || 0));

        window.tripEstimate = { distance_m: distanceM, duration_s: durationS };

        const km = distanceM / 1000;
        const min = Math.max(1, Math.round(durationS / 60));

        const base = 2.50;
        const perKm = 1.20;
        const perMin = 0.25;
        const minimum = 5.00;

        let fare = base + km * perKm + min * perMin;
        fare = Math.max(minimum, fare);
        fare = Math.round(fare * 100) / 100;

        const card = document.getElementById("estimateCard");
        const etaEl = document.getElementById("estEta");
        const distEl = document.getElementById("estDistance");
        const fareEl = document.getElementById("estFare");
        const noteEl = document.getElementById("estNote");

        if (card && etaEl && distEl && fareEl) {
            etaEl.textContent = `${min} min`;
            distEl.textContent = `${km.toFixed(km < 10 ? 1 : 0)} km`;
            fareEl.textContent = "-";
            card.style.display = "";

            if (noteEl) {
            noteEl.style.display = "";
            noteEl.textContent = "Estimate only (traffic, waiting and surcharges may vary).";
            }
        }
    } else {
        const card = document.getElementById("estimateCard");
        if (card) card.style.display = "none";
    }




    const latlngs = coords.map(([lng, lat]) => [lat, lng]);
    routeLine = L.polyline(latlngs, { weight: 5, opacity: 0.85 }).addTo(map);

    map.fitBounds(routeLine.getBounds(), { padding: [60, 60] });
  } catch (e) {
    window.tripEstimate = null;
    const latlngs = [[a.lat, a.lng], [b.lat, b.lng]];
    routeLine = L.polyline(latlngs, { weight: 5, opacity: 0.6 }).addTo(map);
    map.fitBounds(routeLine.getBounds(), { padding: [60, 60] });
  }
}

function hideEstimate() {
  const card = document.getElementById("estimateCard");
  if (card) card.style.display = "none";
}

function clearRouteAndPins() {
  if (routeLine) { map.removeLayer(routeLine); routeLine = null; }
  if (pickupMarker) { map.removeLayer(pickupMarker); pickupMarker = null; }
  if (dropoffMarker) { map.removeLayer(dropoffMarker); dropoffMarker = null; }

  window.pickupLoc = null;
  window.dropoffLoc = null;
  window.tripEstimate = null;

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
        lat: lat,
        lng: lng,
        layer: "pin"
    };

    try {
        const url = new URL("/GoRide/frontend/api/geocode_reverse.php", window.location.origin);
        url.searchParams.set("lat", String(lat));
        url.searchParams.set("lng", String(lng));

        const res = await fetch(url.toString(), {
            method: "GET",
            headers: { "Accept": "application/json" }
        });

        if (res.ok) {
            const json = await res.json();
            if (json && json.label) {
                loc.address = json.label;
            }
        }
    } catch (_) { }

    if (pickMode === "pickup") {
        window.pickupLoc = loc;

        const input = document.getElementById("pickup");
        if (input) {
            input.value = loc.address;
        }

        window.dispatchEvent(new CustomEvent("map:setPickup", { detail: loc }));
    }

    if (pickMode === "dropoff") {
        window.dropoffLoc = loc;

        const input = document.getElementById("destination");
        if (input) {
            input.value = loc.address;
        }

        window.dispatchEvent(new CustomEvent("map:setDropoff", { detail: loc }));
    }

    pickMode = null;

    const mapEl = document.getElementById("map");
    if (mapEl) {
        mapEl.style.cursor = "";
    }

    window.dispatchEvent(new Event("ride:locationChanged"));
});

window.addEventListener("map:setPickup", (e) => {
  const loc = e.detail;
  addOrMoveMarker("pickup", loc);
  drawRouteIfPossible();
});

window.addEventListener("map:setDropoff", (e) => {
  const loc = e.detail;
  addOrMoveMarker("dropoff", loc);
  drawRouteIfPossible();
});
