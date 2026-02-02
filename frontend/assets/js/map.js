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
