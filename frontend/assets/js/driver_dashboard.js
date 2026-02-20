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
        if (!Number.isFinite(x)) return "-";
        return `${x.toFixed(2)} EUR`;
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

    function getActivePeriod() {
        return tabs.find((btn) => btn.classList.contains("is-active"))?.dataset.period || "today";
    }

    function createEl(tag, className, text) {
        const node = document.createElement(tag);
        if (className) node.className = className;
        if (text != null) node.textContent = text;
        return node;
    }

    function ensurePaymentModal() {
        let overlay = document.getElementById("paymentActionOverlay");
        if (overlay) return overlay;

        overlay = createEl("div", "dash-pay-overlay");
        overlay.id = "paymentActionOverlay";
        overlay.style.display = "none";

        const modal = createEl("div", "dash-pay-modal");

        const title = createEl("h3", "dash-pay-modal-title");
        title.id = "dashPayModalTitle";

        const text = createEl("p", "dash-pay-modal-text");
        text.id = "dashPayModalText";

        const form = createEl("div", "dash-pay-form");
        form.id = "dashPayModalForm";


        const noteLabel = createEl("label", "dash-pay-label", "What happened?");
        noteLabel.setAttribute("for", "dashPayNote");
        const noteInput = createEl("textarea", "dash-pay-textarea");
        noteInput.id = "dashPayNote";
        noteInput.placeholder = "Describe why payment was not received...";
        noteInput.maxLength = 2000;

        const errEl = createEl("div", "dash-pay-error");
        errEl.id = "dashPayError";

        form.append(noteLabel, noteInput, errEl);

        const actions = createEl("div", "dash-pay-actions");
        const cancelBtn = createEl("button", "dash-pay-btn-secondary", "Cancel");
        cancelBtn.type = "button";
        cancelBtn.id = "dashPayCancel";

        const submitBtn = createEl("button", "dash-pay-btn-primary", "Confirm");
        submitBtn.type = "button";
        submitBtn.id = "dashPaySubmit";

        actions.append(cancelBtn, submitBtn);

        modal.append(title, text, form, actions);
        overlay.append(modal);
        document.body.append(overlay);

        overlay._state = {
            mode: "confirm",
            submit: null,
        };

        function close() {
            overlay.style.display = "none";
            document.body.classList.remove("dash-pay-modal-open");
            errEl.textContent = "";
            submitBtn.disabled = false;
            cancelBtn.disabled = false;
        }

        cancelBtn.addEventListener("click", close);
        overlay.addEventListener("click", (e) => {
            if (e.target === overlay) close();
        });
        window.addEventListener("keydown", (e) => {
            if (e.key === "Escape" && overlay.style.display !== "none") close();
        });

        submitBtn.addEventListener("click", async () => {
            if (typeof overlay._state.submit !== "function") return;

            errEl.textContent = "";
            submitBtn.disabled = true;
            cancelBtn.disabled = true;

            try {
                await overlay._state.submit({
                    note: noteInput.value.trim(),
                    setError: (msg) => {
                        errEl.textContent = msg || "";
                    },
                });
                close();
            } catch (e) {
                errEl.textContent = e?.message || "Action failed.";
                submitBtn.disabled = false;
                cancelBtn.disabled = false;
            }
        });

        overlay._open = ({ mode, titleText, bodyText, submitText, submit }) => {
            overlay._state.mode = mode;
            overlay._state.submit = submit;

            title.textContent = titleText;
            text.textContent = bodyText;
            submitBtn.textContent = submitText;

            form.style.display = mode === "report" ? "flex" : "none";
            if (mode !== "report") {
                noteInput.value = "";
            }

            overlay.style.display = "flex";
            document.body.classList.add("dash-pay-modal-open");
        };

        return overlay;
    }

    async function openConfirmCashModal(passengerName, onConfirm) {
        const overlay = ensurePaymentModal();
        const safePassenger = (passengerName || "Passenger").trim() || "Passenger";

        overlay._open({
            mode: "confirm",
            titleText: "Confirm Cash Payment",
            bodyText: `Are you sure you want to confirm ${safePassenger} has paid in CASH?`,
            submitText: "Yes, Confirm",
            submit: async () => {
                await onConfirm();
            },
        });
    }

    async function openReportUnpaidModal(passengerName, onReport) {
        const overlay = ensurePaymentModal();
        const safePassenger = (passengerName || "Passenger").trim() || "Passenger";

        overlay._open({
            mode: "report",
            titleText: "Report Unpaid Cash Ride",
            bodyText: `Report that ${safePassenger} has not paid in cash. This will email admin at yasen.s.dimchov.2021@elsys-bg.org with ride context.`,
            submitText: "Send Report",
            submit: async ({ note }) => {
                await onReport({ note });
            },
        });
    }

    async function confirmPayment(paymentId) {
        const url = new URL("/GoRide/frontend/api/payments_confirm.php", window.location.origin);
        url.searchParams.set("id", String(paymentId));

        const res = await fetch(url.toString(), {
            method: "POST",
            headers: { Accept: "application/json" },
        });

        const json = await res.json().catch(() => ({}));
        if (!res.ok) {
            throw new Error(json.message || `Payment confirm failed (${res.status})`);
        }
    }

    async function reportUnpaid(paymentId, note) {
        const url = new URL("/GoRide/frontend/api/payments_report_unpaid.php", window.location.origin);
        url.searchParams.set("id", String(paymentId));

        const res = await fetch(url.toString(), {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                Accept: "application/json",
            },
            body: JSON.stringify({
                note,
            }),
        });

        const json = await res.json().catch(() => ({}));
        if (!res.ok) {
            throw new Error(json.message || `Unpaid report failed (${res.status})`);
        }

        return json;
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
            let reviewText = review ? review.review_text : "No review for this trip yet.";
            if (reviewText == null) {
                reviewText = "No description.";
            }

            const passengerName = (r.user && r.user.name) ? r.user.name : "Passenger";
            const userName = `Passenger - ${passengerName}`;

            const rating = review ? review.rating : 0;
            const stars = generateStars(rating);

            const canHandleCashPayment = r.payment && r.payment.method === "cash" && r.payment.status === "pending";

            const row = document.createElement("div");
            row.className = "dash-trip";

            row.innerHTML = `
                <div class="dash-trip-wrap">
                    <div class="dash-trip-left">
                        <div class="dash-trip-top">
                            <img src="./assets/images/Icons/calendar.svg" class="icon16" alt="">
                            <div class="dash-trip-when">${when || "-"}</div>
                        </div>
                        <div class="dash-trip-route">
                            <div class="dash-trip-from">${from || "-"}</div>
                            <div class="dash-trip-arrow">&rarr;</div>
                            <div class="dash-trip-to">${to || "-"}</div>
                        </div>
                    </div>
                    <div class="dash-trip-right">
                        <div class="dash-trip-price">${right}</div>
                        <button class="dash-trip-reviewToggle" type="button">
                            Review
                            <img class="dash-trip-reviewIcon" src="./assets/images/Icons/down-arrow.svg" alt="Toggle Review">
                        </button>
                        ${canHandleCashPayment ? `
                        <div class="dash-pay-row">
                            <button class="dash-pay-action-btn dash-pay-action-confirm" data-payment-id="${r.payment.id}">Confirm Payment</button>
                            <button class="dash-pay-action-btn dash-pay-action-report" data-payment-id="${r.payment.id}">Report Unpaid</button>
                        </div>
                        ` : ""}
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

            const confirmPaymentBtn = row.querySelector(".dash-pay-action-confirm");
            if (confirmPaymentBtn) {
                confirmPaymentBtn.addEventListener("click", async () => {
                    const paymentId = Number(confirmPaymentBtn.dataset.paymentId || 0);
                    if (!Number.isInteger(paymentId) || paymentId <= 0) {
                        alert("Invalid payment id.");
                        return;
                    }

                    await openConfirmCashModal(passengerName, async () => {
                        await confirmPayment(paymentId);
                        await load(getActivePeriod());
                    });
                });
            }

            const reportUnpaidBtn = row.querySelector(".dash-pay-action-report");
            if (reportUnpaidBtn) {
                reportUnpaidBtn.addEventListener("click", async () => {
                    const paymentId = Number(reportUnpaidBtn.dataset.paymentId || 0);
                    if (!Number.isInteger(paymentId) || paymentId <= 0) {
                        alert("Invalid payment id.");
                        return;
                    }

                    await openReportUnpaidModal(passengerName, async ({ note }) => {
                        const response = await reportUnpaid(paymentId, note);
                        alert(response.message || "Report sent successfully.");
                    });
                });
            }

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

        totalEarningsEl.textContent = "-";
        totalTripsEl.textContent = "-";
        avgPerTripEl.textContent = "-";
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
            totalTripsEl.textContent = String(json?.stats?.total_trips ?? "-");
            avgPerTripEl.textContent = money(json?.stats?.avg_per_trip);

            if (json.from && json.to && json.from === json.to) {
                earningsSubEl.textContent = json.from;
            } else if (json.from && json.to) {
                earningsSubEl.textContent = `${json.from} -> ${json.to}`;
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








