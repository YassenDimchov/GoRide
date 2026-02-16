(() => {
    const tabs = Array.from(document.querySelectorAll(".dash-tab"));
    const totalEarningsEl = document.getElementById("ddTotalEarnings");
    const earningsSubEl = document.getElementById("ddEarningsSub");
    const totalTripsEl = document.getElementById("ddTotalTrips");
    const avgPerTripEl = document.getElementById("ddAvgPerTrip");
    const listEl = document.getElementById("ddTripsList");
    const emptyEl = document.getElementById("ddTripsEmpty");

    function money(n) {
        const x = Number(n);
        if (!Number.isFinite(x)) return "—";
        return `${x.toFixed(2)} €`;
    }

    function fmtDate(s) {
        if (!s) return "";
        const d = new Date(s);
        if (Number.isNaN(d.getTime())) return "";
        return d.toLocaleString(undefined, {
            year: "numeric",
            month: "2-digit",
            day: "2-digit",
            hour: "2-digit",
            minute: "2-digit"
        });
    }

    function setActiveTab(period) {
        tabs.forEach((btn) => {
            const on = btn.dataset.period === period;
            btn.classList.toggle("is-active", on);
            btn.setAttribute("aria-selected", on ? "true" : "false");
        });
    }

    function renderTrips(trips) {
        listEl.innerHTML = "";

        if (!Array.isArray(trips) || trips.length === 0) {
            emptyEl.style.display = "";
            return;
        }

        emptyEl.style.display = "none";

        for (const r of trips) {
            const when = fmtDate(r.completed_at || r.created_at);
            const from = (r.start_address || "").trim();
            const to = (r.end_address || "").trim();

            const amt = (r.payment && r.payment.amount != null) ? r.payment.amount : r.fare;
            const right = money(amt);

            const review = r.review;
            const reviewText = review ? review.review_text : "No review for this trip yet.";
            const userName = `Passenger - ${r.user.name}`;

            const rating = review ? review.rating : 0;
            const stars = generateStars(rating);

            const row = document.createElement("div");
            row.className = "dash-trip";

            row.innerHTML = `
                <div class="dash-trip-wrap">
                    <div class="dash-trip-left">
                        <div class="dash-trip-top">
                            <img src="./assets/images/Icons/calendar.svg" class="icon16" alt="">
                            <div class="dash-trip-when">${when || "—"}</div>
                        </div>
                        <div class="dash-trip-route">
                            <div class="dash-trip-from">${from || "—"}</div>
                            <div class="dash-trip-arrow">→</div>
                            <div class="dash-trip-to">${to || "—"}</div>
                        </div>
                    </div>
                    <div class="dash-trip-right">
                        <div class="dash-trip-price">${right}</div>
                        <button class="dash-trip-reviewToggle" type="button">
                            Review
                            <img class="dash-trip-reviewIcon" src="./assets/images/Icons/down-arrow.svg" alt="Toggle Review">
                        </button>
                    </div>
                </div>
            `;

            const reviewBox = document.createElement("div");
            reviewBox.className = "dash-trip-review";
            reviewBox.style.display = "none";

            reviewBox.innerHTML = `
                <div class="dash-trip-reviewName">${userName}</div>
                <div class="dash-trip-reviewRating">${stars}</div>
                <div class="dash-trip-reviewText">${reviewText}</div>
            `;

            row.appendChild(reviewBox);

            const toggleBtn = row.querySelector(".dash-trip-reviewToggle");
            const iconEl = row.querySelector(".dash-trip-reviewIcon");

            toggleBtn.addEventListener("click", () => {
                const open = reviewBox.style.display === "block";
                reviewBox.style.display = open ? "none" : "block";
                iconEl.src = open ? "./assets/images/Icons/down-arrow.svg" : "./assets/images/Icons/up-arrow.svg";
            });

            listEl.appendChild(row);
        }
    }

    function generateStars(rating) {
        const maxRating = 5;
        let stars = "";
        
        for (let i = 1; i <= maxRating; i++) {
            if (i <= rating) {
                stars += `<img src="./assets/images/Icons/star-filled.svg" class="star-icon" alt="star">`;
            } else {
                stars += `<img src="./assets/images/Icons/star-empty.svg" class="star-icon" alt="star">`;
            }
        }

        return stars;
    }


    async function load(period) {
        setActiveTab(period);

        totalEarningsEl.textContent = "—";
        totalTripsEl.textContent = "—";
        avgPerTripEl.textContent = "—";
        earningsSubEl.textContent = "";
        emptyEl.style.display = "none";
        listEl.innerHTML = "";

        try {
            const url = new URL("/GoRide/frontend/api/driver_dashboard.php", window.location.origin);
            url.searchParams.set("period", period);

            const res = await fetch(url.toString(), { headers: { "Accept": "application/json" } });
            const json = await res.json().catch(() => null);

            if (!res.ok || !json) {
                throw new Error(json?.message || `Dashboard failed (${res.status})`);
            }

            totalEarningsEl.textContent = money(json?.stats?.total_earnings);
            totalTripsEl.textContent = String(json?.stats?.total_trips ?? "—");
            avgPerTripEl.textContent = money(json?.stats?.avg_per_trip);

            if (json.from && json.to && json.from === json.to) {
                earningsSubEl.textContent = json.from;
            } else if (json.from && json.to) {
                earningsSubEl.textContent = `${json.from} → ${json.to}`;
            } else {
                earningsSubEl.textContent = "";
            }

            renderTrips(json.trips);
        } catch (e) {
            emptyEl.style.display = "";
            emptyEl.textContent = e.message || "Failed to load dashboard.";
        }
    }

    tabs.forEach((btn) => {
        btn.addEventListener("click", () => load(btn.dataset.period || "today"));
    });

    load("today");
})();
