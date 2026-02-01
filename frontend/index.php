<?php require_once __DIR__ . '/includes/guard.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GoRide</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/style.css"/>
    <link rel="stylesheet" href="assets/css/index.css"/>

    <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    />
</head>

<body>
    <?php include __DIR__ . '/components/navbar.php'; ?>
    <?php include __DIR__ . '/components/sidebar.php'; ?>
    
    <div class="app-shell">
        <div class="map-area">
            <div id="map"></div>
        </div>

        <div class="panel">
            <div class="panel-card">
                <!-- Pickup row -->
                <div class="panel-row">
                    <div class="panel-icon">
                        <span class="dot"></span>
                    </div>

                    <div class="panel-input">
                        <input id="pickup" type="text" placeholder="Where from?">
                    </div>
                </div>

                <!-- Destination row -->
                <div class="panel-row">
                    <div class="panel-icon">
                        <img  src="./assets/images/Icons/location.svg" alt="" class="icon20">
                    </div>

                    <div class="panel-input">
                        <input id="destination" type="text" placeholder="Where to?">
                    </div>
                </div>
            </div>

            <!-- State 1 -->
            <div class="panel-hint" id="stateHint">
                Enter a destination to request a ride
            </div>

            <button class="btn-primary" id="requestRideBtn" style="display:none;">
                Request Ride
            </button>

            <!-- State 2 -->
            <div class="info-card" id="readyCard" style="display:none;">
                <div class="info-icon">
                    <img src="assets/images/Icons/car.svg" alt="" class="icon20">
                </div>
                <div class="info-text">
                    <div class="info-title">Reqdy to ride?</div>
                    <div class="info-sub">Click "Request Ride" to find a nearby driver</div>
                </div>
            </div>

            <!-- State 3 -->
            <div class="waiting-card" id="waitingCard" style="display:none;">
                <div class="spinner"></div>

                <div class="waiting-title">
                    Finding your driver...
                </div>

                <div class="waiting-sub">
                    Please wait while we match you with a nearby driver
                </div>

                <button class="btn-secondary" id="cancelRequestBtn">
                    Cancel Request
                </button>
            </div>

            <!-- State 4 -->
             <div class="found-wrap" id="foundWrap" style="display:none;">
                <div class="found-card">
                    <div class="found-header">
                        <div class="found-title">
                            <span class="found-check">✓</span>
                            <span>Driver Found!</span>
                        </div>
                        <div class="found-sub">Your driver is on the way</div>
                    </div>

                    <div class="driver-card">
                        <div class="driver-top">
                            <div class="driver-left">
                                <div class="driver-avatar">MC</div>
                                <div class="driver-meta">
                                    <div class="driver-name">Mike Chen</div>
                                    <div class="driver-rating">
                                        <span class="star">★</span>
                                        <span>4.9</span>
                                    </div>
                                </div>
                            </div>

                            <div class="driver-eta">
                                <div class="eta-min">3 mins</div>
                                <div class="eta-away">away</div>
                            </div>
                        </div>

                        <div class="driver-divider"></div>

                        <div class="driver-bottom">
                            <img src="assets/images/Icons/car.svg" alt="" class="icon20">
                            <span class="car-model">Toyota Camry</span>
                            <span class="dot-sep">•</span>
                            <span class="car-plate">ABC-1234</span>
                        </div>
                    </div>

                    <div class="found-actions">
                        <button class="btn-outline" id="callDriverBtn">Call Driver</button>
                        <button class="btn-outline" id="cancelRideBtn">Cancel Ride</button>
                    </div>
                </div>

                <div class="trip-card">
                    <div class="trip-title">Trip Details</div>

                    <div class="trip-row">
                        <span class="trip-dot pickup"></span>
                        <div class="trip-text">
                            <div class="trip-label">PICKUP</div>
                            <div class="trip-value" id="tripPickup">123 Main Street</div>
                        </div>
                    </div>

                    <div class="trip-row">
                        <span class="trip-dot dropoff"></span>
                        <div class="trip-text">
                            <div class="trip-label">DROPOFF</div>
                            <div class="trip-value" id="tripDropoff">Sofia City</div>
                        </div>
                    </div>
                </div>

             </div>
        </div>
    </div>

    <?php require_once __DIR__ . '/components/footer.php'; ?>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="assets/js/map.js"></script>
    <script src="assets/js/ui.js"></script>
</body>
</html>