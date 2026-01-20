
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GoRide</title>

    <!-- Font Awesome -->
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
        integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA=="
        crossorigin="anonymous"
        referrerpolicy="no-referrer"
    />
    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/style.css"/>
    <link rel="stylesheet" href="assets/css/index.css"/>

    <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    />
</head>

<body>

    <?php include 'navbar.php'; ?>
    
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



            <div class="panel-hint">
                Enter a destination to request a ride
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="assets/js/map.js"></script>
</body>
</html>