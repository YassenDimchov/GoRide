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
let foundTimer = null;

function normalize(v) {
  return (v || "").trim();
}

function updateState() {
  if (isWaiting || isFound) return;

  const pickup = normalize(pickupEl.value);
  const dest = normalize(destinationEl.value);

  const canRequest = pickup.length > 0 && dest.length > 0;

  // Hide states 3,4
  waitingCard.style.display = "none";
  foundWrap.style.display = "none";

  // State 2
  requestBtn.style.display = canRequest ? "block" : "none";
  readyCard.style.display = canRequest ? "flex" : "none";

  // State 1
  hint.style.display = canRequest ? "none" : "block";
}

pickupEl.addEventListener("input", updateState);
destinationEl.addEventListener("input", updateState);

// State 3
requestBtn.addEventListener("click", () => {
  isWaiting = true;
  isFound = false;

  // Hide states 1,2
  hint.style.display = "none";
  requestBtn.style.display = "none";
  readyCard.style.display = "none";

  foundWrap.style.display = "none";

  // State 3
  waitingCard.style.display = "flex";

  pickupEl.disabled = true;
  destinationEl.disabled = true;

  // Simulate driver found
  if (foundTimer) clearTimeout(foundTimer);
  foundTimer = setTimeout(() => {
    isWaiting = false;
    isFound = true;

    waitingCard.style.display = "none";

    tripPickup.textContent = normalize(pickupEl.value);
    tripDropoff.textContent = normalize(destinationEl.value);

    foundWrap.style.display = "flex";
  }, 2500);
});

// Cancel State 3 
cancelBtn.addEventListener("click", () => {
  if (foundTimer) clearTimeout(foundTimer);

  isWaiting = false;
  isFound = false;

  waitingCard.style.display = "none";
  foundWrap.style.display = "none";

  pickupEl.disabled = false;
  destinationEl.disabled = false;

  updateState();
});

// Cancel ride in State 4 
cancelRideBtn.addEventListener("click", () => {
  if (foundTimer) clearTimeout(foundTimer);

  isWaiting = false;
  isFound = false;

  foundWrap.style.display = "none";
  waitingCard.style.display = "none";

  pickupEl.disabled = false;
  destinationEl.disabled = false;

  updateState();
});

updateState();
