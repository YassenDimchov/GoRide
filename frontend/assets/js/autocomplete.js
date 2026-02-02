(() => {
    const pickup = document.getElementById("pickup");
    const dest = document.getElementById("destination");

    const pickupSug = document.getElementById("pickupSug");
    const destSug = document.getElementById("destSug");

    const pickPickupBtn = document.getElementById("pickPickupOnMap");
    const pickDropoffBtn = document.getElementById("pickDropoffOnMap");

    const cache = new Map();

    if (!pickup || !dest || !pickupSug || !destSug) {
        console.warn("Autocomplete: missing DOM elements");
        return;
    }

    window.pickupLoc = null;
    window.dropoffLoc = null;

    let pickupResults = [];
    let destResults = [];

    function esc(s) {
        return String(s)
            .replaceAll("&", "&amp;")
            .replaceAll("<", "&lt;")
            .replaceAll(">", "&gt;")
            .replaceAll('"', "&quot;")
            .replaceAll("'", "&#039;");
    }

    async function apiAutocomplete(q) {
        const key = q.toLowerCase();

        if (cache.has(key)) {
            return cache.get(key);
        }

        const url = new URL("/GoRide/frontend/api/geocode_autocomplete.php", window.location.origin);
        url.searchParams.set("q", q);
        url.searchParams.set("lat", "42.6977");
        url.searchParams.set("lng", "23.3219");

        const res = await fetch(url.toString(), {
            method: "GET",
            headers: { "Accept": "application/json" }
        });

        if (!res.ok) return [];

        const json = await res.json();
        const data = json.data || [];

        cache.set(key, data);
        return data;
    }

    function render(box, items, onPick) {
        if (!items.length) {
            box.style.display = "none";
            box.innerHTML = "";
            return;
        }

        box.innerHTML = items.map((p, i) => `
            <div class="suggestion-item" data-i="${i}">
                <div>${esc(p.label)}</div>
                <div class="suggestion-muted">${esc(p.layer || "place")}</div>
            </div>
        `).join("");

        box.style.display = "block";

        box.querySelectorAll(".suggestion-item").forEach((el) => {
            el.addEventListener("click", () => {
                const idx = Number(el.dataset.i);
                onPick(items[idx]);
                box.style.display = "none";
            });
        });
    }

    function clear(which) {
        if (which === "pickup") window.pickupLoc = null;
        if (which === "dropoff") window.dropoffLoc = null;

        window.dispatchEvent(new Event("ride:locationChanged"));
    }

    function pickPickup(place) {
        const typed = pickup.value.trim();
        const hasNumber = /\d/.test(typed);
        const layer = (place.layer || "").toLowerCase();

        const loc = {
            address: (hasNumber && layer === "street") ? typed : place.label,
            lat: Number(place.lat),
            lng: Number(place.lng),
            layer: place.layer || null
        };

        window.pickupLoc = loc;
        pickup.value = loc.address;
        pickupSug.style.display = "none";

        window.dispatchEvent(new CustomEvent("map:setPickup", { detail: loc }));
        window.dispatchEvent(new Event("ride:locationChanged"));
    }

    function pickDropoff(place) {
        const typed = dest.value.trim();
        const hasNumber = /\d/.test(typed);
        const layer = (place.layer || "").toLowerCase();

        const loc = {
            address: (hasNumber && layer === "street") ? typed : place.label,
            lat: Number(place.lat),
            lng: Number(place.lng),
            layer: place.layer || null
        };

        window.dropoffLoc = loc;
        dest.value = loc.address;
        destSug.style.display = "none";

        window.dispatchEvent(new CustomEvent("map:setDropoff", { detail: loc }));
        window.dispatchEvent(new Event("ride:locationChanged"));
    }

    async function doSearchPickup() {
        const q = pickup.value.trim();
        clear("pickup");

        if (q.length < 3) {
            pickupResults = [];
            return render(pickupSug, [], () => {});
        }

        pickupResults = await apiAutocomplete(q);

        render(pickupSug, pickupResults, (place) => {
            pickPickup(place);
        });
    }

    async function doSearchDropoff() {
        const q = dest.value.trim();
        clear("dropoff");

        if (q.length < 3) {
            destResults = [];
            return render(destSug, [], () => {});
        }

        destResults = await apiAutocomplete(q);

        render(destSug, destResults, (place) => {
            pickDropoff(place);
        });
    }

    pickup.addEventListener("keydown", async (e) => {
        if (e.key !== "Enter") return;
        e.preventDefault();

        await doSearchPickup();

        if (pickupResults.length) {
            pickupSug.style.display = "block";
        }
    });

    dest.addEventListener("keydown", async (e) => {
        if (e.key !== "Enter") return;
        e.preventDefault();

        await doSearchDropoff();

        if (destResults.length) {
            destSug.style.display = "block";
        }
    });

    document.addEventListener("click", (e) => {
        if (!pickupSug.contains(e.target) && e.target !== pickup) pickupSug.style.display = "none";
        if (!destSug.contains(e.target) && e.target !== dest) destSug.style.display = "none";
    });

    if (pickPickupBtn) {
        pickPickupBtn.addEventListener("click", () => {
            pickupSug.style.display = "none";
            destSug.style.display = "none";
            window.dispatchEvent(new CustomEvent("map:pickMode", { detail: { mode: "pickup" } }));
        });
    }

    if (pickDropoffBtn) {
        pickDropoffBtn.addEventListener("click", () => {
            pickupSug.style.display = "none";
            destSug.style.display = "none";
            window.dispatchEvent(new CustomEvent("map:pickMode", { detail: { mode: "dropoff" } }));
        });
    }

    window.addEventListener("ride:locationChanged", () => {
        console.log("pickupLoc UPDATED:", window.pickupLoc);
        console.log("dropoffLoc UPDATED:", window.dropoffLoc);
    });
    console.log("Autocomplete: ready");
})();
