// Sofia default view
const map = L.map("map", {
  zoomControl: false
}).setView([42.6977, 23.3219], 13);

L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
  maxZoom: 19,
  attribution: '&copy; OpenStreetMap contributors'
}).addTo(map);

L.control.zoom({ position: "topright" }).addTo(map);
