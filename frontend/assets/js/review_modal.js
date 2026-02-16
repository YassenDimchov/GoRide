(() => {
  const MAX_LEN = 500;

  const LABELS = {
    1: "Poor",
    2: "Fair",
    3: "Good",
    4: "Great",
    5: "Excellent!",
  };

  function el(tag, cls, text) {
    const n = document.createElement(tag);
    if (cls) n.className = cls;
    if (text != null) n.textContent = text;
    return n;
  }

  function getDriverName(ride) {
    const du = ride?.driver?.user || null;
    return (du?.name || "").trim() || "your driver";
  }

  function ensureModal() {
    let overlay = document.getElementById("reviewOverlay");
    if (overlay) return overlay;

    overlay = el("div", "review-overlay");
    overlay.id = "reviewOverlay";
    overlay.style.display = "none";

    const modal = el("div", "review-modal");

    const h = el("div", "review-head");
    const title = el("div", "review-title", "Leave a Review");
    const sub = el("div", "review-sub");
    sub.id = "reviewDriverSub";
    h.append(title, sub);

    const rateLbl = el("div", "review-rate-label", "Rate your trip");

    const starsWrap = el("div", "review-stars");
    starsWrap.setAttribute("role", "radiogroup");
    starsWrap.setAttribute("aria-label", "Trip rating");

    const caption = el("div", "review-caption", "Fair");
    caption.id = "reviewCaption";

    const writeLbl = el("div", "review-write-label", "Write your review (optional)");
    const ta = el("textarea", "review-text");
    ta.id = "reviewText";
    ta.placeholder = "Tell us about your experience...";
    ta.maxLength = String(MAX_LEN);

    const count = el("div", "review-count", `0/${MAX_LEN} characters`);
    count.id = "reviewCount";

    const actions = el("div", "review-actions");
    const cancelBtn = el("button", "review-btn review-btn-ghost", "Cancel");
    cancelBtn.type = "button";
    cancelBtn.id = "reviewCancelBtn";

    const submitBtn = el("button", "review-btn review-btn-solid", "Submit Review");
    submitBtn.type = "button";
    submitBtn.id = "reviewSubmitBtn";
    submitBtn.disabled = true;

    actions.append(cancelBtn, submitBtn);

    modal.append(h, rateLbl, starsWrap, caption, writeLbl, ta, count, actions);
    overlay.append(modal);
    document.body.append(overlay);

    overlay.addEventListener("click", (e) => {
      if (e.target === overlay) close();
    });

    window.addEventListener("keydown", (e) => {
      if (e.key === "Escape" && overlay.style.display !== "none") close();
    });

    ta.addEventListener("input", () => {
      const v = ta.value || "";
      count.textContent = `${v.length}/${MAX_LEN} characters`;
    });

    overlay._state = {
      rideId: null,
      rating: 0,
      ride: null,
      onSubmitted: null,
    };

    for (let i = 1; i <= 5; i++) {
      const btn = el("button", "star-btn");
      btn.type = "button";
      btn.setAttribute("aria-label", `Rate ${i}`);
      btn.dataset.value = String(i);

      const ring = el("span", "star-ring");
      const star = el("span", "star-shape", "★");
      ring.append(star);
      btn.append(ring);

      btn.addEventListener("mouseenter", () => setHover(i));
      btn.addEventListener("focus", () => setHover(i));
      btn.addEventListener("mouseleave", () => setHover(0));
      btn.addEventListener("blur", () => setHover(0));
      btn.addEventListener("click", () => setRating(i));

      starsWrap.append(btn);
    }

    function setHover(v) {
      const st = overlay._state;
      const show = v || st.rating;
      paint(show);
      caption.textContent = LABELS[show] || "Rate";
    }

    function setRating(v) {
      const st = overlay._state;
      st.rating = v;
      paint(v);
      caption.textContent = LABELS[v] || "Rate";
      submitBtn.disabled = !(v >= 1 && v <= 5);
    }

    function paint(activeCount) {
      const buttons = starsWrap.querySelectorAll(".star-btn");
      buttons.forEach((b) => {
        const v = Number(b.dataset.value);
        b.classList.toggle("is-active", v <= activeCount);
      });
    }

    async function submit() {
      const st = overlay._state;
      const rating = Number(st.rating);
      if (!(rating >= 1 && rating <= 5)) return;

      submitBtn.disabled = true;
      submitBtn.textContent = "Submitting...";

      try {
        const url = new URL("/GoRide/frontend/api/rides_review_create.php", window.location.origin);
        url.searchParams.set("id", String(st.rideId));

        const res = await fetch(url.toString(), {
          method: "POST",
          headers: { "Content-Type": "application/json", Accept: "application/json" },
          body: JSON.stringify({
            rating,
            review_text: (ta.value || "").trim(),
          }),
        });

        const json = await res.json().catch(() => ({}));
        if (!res.ok) throw new Error(json.message || `Review failed (${res.status})`);

        if (typeof st.onSubmitted === "function") st.onSubmitted(json.data || json.review || json);

        close();
      } catch (e) {
        alert(e.message || "Review submit failed");
      } finally {
        submitBtn.textContent = "Submit Review";
        submitBtn.disabled = !(overlay._state.rating >= 1 && overlay._state.rating <= 5);
      }
    }

    submitBtn.addEventListener("click", submit);
    cancelBtn.addEventListener("click", close);

    return overlay;
  }

  function open({ rideId, ride, onSubmitted } = {}) {
    const overlay = ensureModal();
    const st = overlay._state;

    st.rideId = rideId ?? null;
    st.ride = ride || null;
    st.onSubmitted = onSubmitted || null;
    st.rating = 0;

    const sub = document.getElementById("reviewDriverSub");
    const caption = document.getElementById("reviewCaption");
    const ta = document.getElementById("reviewText");
    const count = document.getElementById("reviewCount");
    const submitBtn = document.getElementById("reviewSubmitBtn");

    const driverName = getDriverName(ride);
    if (sub) sub.textContent = `Share your experience with ${driverName}`;

    if (ta) ta.value = "";
    if (count) count.textContent = `0/${MAX_LEN} characters`;
    if (caption) caption.textContent = "Rate";
    if (submitBtn) submitBtn.disabled = true;

    overlay.querySelectorAll(".star-btn").forEach((b) => b.classList.remove("is-active"));

    overlay.style.display = "flex";
    document.body.classList.add("review-modal-open");
  }

  function close() {
    const overlay = document.getElementById("reviewOverlay");
    if (!overlay) return;
    overlay.style.display = "none";
    document.body.classList.remove("review-modal-open");
  }

  window.ReviewModal = { open, close };
})();
