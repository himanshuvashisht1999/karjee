<?php
if (file_exists(__DIR__ . '/../connection.php')) {
    require_once __DIR__ . '/../connection.php';
} else if (file_exists(__DIR__ . '/../../connection.php')) {
    require_once __DIR__ . '/../../connection.php';
}

$clientid = $_GET['clid'] ?? ""; // New client ID parameter
$dest_lat = $_GET['lat'] ?? ""; // Passed destination latitude
$dest_lon = $_GET['lon'] ?? ""; // Passed destination longitude
$orderid = $_GET['orderid'] ?? ""; // Added orderid from GET
$office_lat = "23.0488";
$office_lon = "83.1904";
$bindbtnid = $_GET['bindbtnid'] ?? $_POST['bindbtnid'] ?? ""; // Handle both GET and POST
$isComponent = isset($_GET['component']) && $_GET['component'] == '1';

// If latitude/longitude not in GET, check database for existing coordinates in krf_deliveryaddress_location
if ((empty($dest_lat) || empty($dest_lon)) && isset($krfconn) && (!empty($orderid) || !empty($clientid))) {
    $escOrderId = mysqli_real_escape_string($krfconn, $orderid);
    $escClientId = mysqli_real_escape_string($krfconn, $clientid);
    $locQ = "SELECT dest_latitude, dest_longitude, deliveryaddress, dest_state, dest_district, dest_city, dest_pincode 
             FROM krf_deliveryaddress_location 
             WHERE (psono = '$escOrderId' AND '$escOrderId' != '') 
                OR (sono = '$escOrderId' AND '$escOrderId' != '') 
                OR (clientid = '$escClientId' AND '$escClientId' != '' AND (dest_latitude IS NOT NULL AND dest_latitude != ''))
             ORDER BY (CASE WHEN (psono = '$escOrderId' AND '$escOrderId' != '') OR (sono = '$escOrderId' AND '$escOrderId' != '') THEN 1 ELSE 2 END), id DESC LIMIT 1";
    $locRes = mysqli_query($krfconn, $locQ);
    if ($locRes && $locRow = mysqli_fetch_assoc($locRes)) {
        if (empty($dest_lat)) $dest_lat = $locRow['dest_latitude'] ?? '';
        if (empty($dest_lon)) $dest_lon = $locRow['dest_longitude'] ?? '';
    }
}

if (!$isComponent):
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Karjee Farm | Geo-Location Service</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php endif; ?>
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --glass-bg: rgba(255, 255, 255, 0.95);
            --accent-color: #667eea;
        }

        body {
            background: #f4f7fa;
            font-family: 'Inter', sans-serif;
            color: #2d3748;
            margin: 0;
            padding: 5px;
            overflow: hidden;
        }

        .main-container {
            width: 100%;
            max-width: 100%;
            margin: 0 auto;
        }

        .distance-ruler {
            background: #ffffff;
            border-radius: 12px;
            padding: 6px 15px !important;
            margin-bottom: 8px !important;
            border: 1px solid #edf2f7;
            box-shadow: 0 2px 8px rgba(0,0,0,0.03);
            display: flex !important;
            flex-direction: row !important;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .ruler-point {
            background: #ffffff;
            border: 1.5px solid #e2e8f0;
            padding: 3px 12px !important;
            border-radius: 50px;
            font-weight: 700;
            font-size: 0.78rem !important;
            color: #4a5568;
            min-width: 110px !important;
            text-align: center;
            box-shadow: 0 2px 6px rgba(0,0,0,0.03);
        }

        .ruler-point.origin {
            border-color: #4a5568;
        }

        .ruler-point.destination {
            border-color: #667eea;
            color: #667eea;
        }

        .ruler-line {
            flex-grow: 1;
            height: 2px;
            background: repeating-linear-gradient(90deg, #cbd5e0, #cbd5e0 4px, transparent 4px, transparent 8px);
            margin: 0 10px;
            display: block !important;
        }

        .ruler-center {
            display: flex;
            align-items: center;
            background: #ffffff;
            padding: 0 10px;
        }

        .distance-stats-container {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .distance-label {
            font-size: 0.68rem;
            font-weight: 800;
            color: #a0aec0;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .distance-count {
            font-size: 1.2rem;
            font-weight: 900;
            color: #2d3748;
            line-height: 1;
        }

        .distance-unit {
            font-size: 0.75rem;
            color: #718096;
            margin-left: 2px;
        }

        .truck-icon {
            font-size: 1rem;
            color: #667eea;
        }

        /* --- 50-50 Mapper Section --- */
        .location-card {
            background: var(--glass-bg);
            border-radius: 16px;
            border: 1px solid #edf2f7;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            display: flex;
            flex-direction: row;
            height: 540px;
        }

        .map-section {
            flex: 0 0 50% !important;
            width: 50% !important;
            max-width: 50% !important;
            position: relative;
            background: #e2e8f0;
            height: 100%;
            min-height: 100%;
        }

        .form-section {
            flex: 0 0 50% !important;
            width: 50% !important;
            max-width: 50% !important;
            padding: 10px 14px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow-y: auto;
        }

        #map {
            height: 100%;
            width: 100%;
        }

        .header-section {
            margin-bottom: 6px;
        }

        .header-section h4 {
            font-size: 1.05rem;
            font-weight: 800;
            color: #1a202c;
            margin-bottom: 2px;
        }

        .field-group {
            position: relative;
            margin-bottom: 5px;
        }

        .field-group label {
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            color: #718096;
            margin-bottom: 2px;
            display: block;
        }

        .form-control {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 5px 10px;
            font-size: 0.82rem;
            transition: all 0.2s;
            background: #fff;
        }

        .form-control:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            outline: none;
        }

        .btn-locate {
            position: relative;
            background: linear-gradient(135deg, #ee5a5a 0%, #d32f2f 100%);
            border: none;
            border-radius: 8px;
            padding: 8px;
            font-weight: 700;
            color: white;
            width: 100%;
            margin-bottom: 8px;
            font-size: 0.85rem;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-confirm {
            background: var(--primary-gradient);
            border: none;
            border-radius: 10px;
            padding: 10px;
            font-weight: 700;
            color: white;
            width: 100%;
            margin-top: 4px;
            font-size: 0.9rem;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
            transition: all 0.3s;
        }

        .readonly-bg {
            background-color: #f8fafc !important;
            cursor: not-allowed;
        }

        .instruction-badge {
            position: absolute;
            top: 15px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1000;
            background: rgba(0, 0, 0, 0.75);
            color: white;
            padding: 6px 14px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            pointer-events: none;
            backdrop-filter: blur(4px);
        }

        .spinner-border {
            width: 1rem;
            height: 1rem;
            display: none;
        }

        @media (max-width: 767.98px) {
            body {
                overflow-y: auto;
            }
            .location-card {
                flex-direction: column;
                height: auto;
            }
            .map-section {
                flex: 0 0 100% !important;
                width: 100% !important;
                max-width: 100% !important;
                height: 220px !important;
                min-height: 220px !important;
            }
            .form-section {
                flex: 0 0 100% !important;
                width: 100% !important;
                max-width: 100% !important;
                padding: 10px;
                overflow: visible;
            }
            .distance-ruler {
                padding: 5px 8px !important;
            }
            .ruler-point {
                min-width: 80px !important;
                font-size: 0.7rem !important;
                padding: 2px 6px !important;
            }
        }
    </style>
<?php if (!$isComponent): ?>
</head>
<body>
<?php endif; ?>
<div class="main-container">

    <div class="distance-ruler">
        <div class="ruler-point origin">
            <i class="fa-solid fa-farm me-2"></i> Karjee Farm
        </div>
        
        <div class="ruler-line"></div>
        
        <div class="ruler-center">
            <div class="distance-stats-container">
                <i class="fa-solid fa-truck truck-icon"></i>
                <span class="distance-label">Estimated Road Distance</span>
                <div class="distance-count">
                    <span id="computedDistance">--</span>
                    <span class="distance-unit">KM</span>
                </div>
            </div>
        </div>
        
        <div class="ruler-line"></div>
        
        <div class="ruler-point destination" id="destCityName">
            Destination Point
        </div>
    </div>
    <div class="location-card">
        <div class="map-section">
            <div class="instruction-badge">
                <i class="fa-solid fa-hand-pointer me-2"></i>Drag pin or click map to correct location
            </div>
            <div id="map"></div>
        </div>

        <div class="form-section">
            <div class="header-section text-center">
                <h4>Confirm Delivery Address</h4>
                <p class="text-muted small">Select the exact point for delivery on the map</p>
                <div class="btn-group mt-2" role="group" aria-label="Language Options">
                    <input type="radio" class="btn-check" name="langRadio" id="langEn" value="en" autocomplete="off" onchange="changeLanguage()">
                    <label class="btn btn-outline-primary btn-sm px-3" for="langEn">English</label>
                    <input type="radio" class="btn-check" name="langRadio" id="langHi" value="hi" autocomplete="off" checked onchange="changeLanguage()">
                    <label class="btn btn-outline-primary btn-sm px-3" for="langHi">हिंदी</label>
                </div>
            </div>

            <button class="btn btn-locate" id="locateBtn" onclick="getLocation()">
 
                <i class="fa-solid fa-crosshairs text-white"></i>
                <span id="btnText">Detect My Current Position</span>
                <div class="spinner-border text-white" id="loader" role="status"></div>
             </button>

            <div class="row g-3">
                <div class="col-6">
                    <div class="field-group">
                        <label>Lat</label>
                        <input type="text" id="latitude" class="form-control readonly-bg" readonly>
                    </div>
                </div>
                <div class="col-6">
                    <div class="field-group">
                        <label>Lon</label>
                        <input type="text" id="longitude" class="form-control readonly-bg" readonly>
                    </div>
                </div>

                <div class="col-6">
                    <div class="field-group">
                        <label>State</label>
                        <input type="text" id="state" class="form-control">
                    </div>
                </div>
                <div class="col-6">
                    <div class="field-group">
                        <label>District</label>
                        <input type="text" id="district" class="form-control">
                    </div>
                </div>
                <div class="col-6">
                    <div class="field-group">
                        <label>City / Town</label>
                        <input type="text" id="city" class="form-control">
                    </div>
                </div>
                <div class="col-6">
                    <div class="field-group">
                        <label>Pincode</label>
                        <input type="text" id="pincode" class="form-control">
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="field-group">
                        <label>Full Delivery Address</label>
                        <textarea id="address" class="form-control" rows="2" style="max-height: 55px;"></textarea>
                    </div>
                </div>
            </div>

            <button class="btn btn-confirm d-none mt-2" id="saveBtn" onclick="saveDeliveryLocation()">
                <i class="fa-solid fa-check-circle me-2"></i>
                Confirm Delivery Location
            </button>
            <button class="btn btn-confirm mt-2" id="addressappendtblbtn" onclick="collectAndSendToParent();">
                <i class="fa-solid fa-check-circle me-2"></i>
                Confirm Delivery Location
            </button>
            
        </div>
    </div>
</div>

<?php if (!$isComponent): ?>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<?php endif; ?>
<script>
    const ORDER_ID = "<?php echo $orderid; ?>";
 
    const CLIENT_ID = "<?php echo $clientid; ?>";
     const OFFICE_LAT = <?php echo $office_lat; ?>;
    const OFFICE_LON = <?php echo $office_lon; ?>;
    
    let map = L.map('map', { zoomControl: false }).setView([20.5937, 78.9629], 5);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);
    L.control.zoom({ position: 'bottomright' }).addTo(map);

    let marker;
    let officeMarker = L.marker([OFFICE_LAT, OFFICE_LON], {
        icon: L.icon({
            iconUrl: 'https://cdn-icons-png.flaticon.com/512/25/25694.png',
            iconSize: [25, 25],
            iconAnchor: [12, 12]
        })
    }).addTo(map).bindPopup("<b>Karjee Farm</b><br>Origin Point");

    const DEST_LAT = "<?php echo $dest_lat; ?>";
    const DEST_LON = "<?php echo $dest_lon; ?>";
    
    if (DEST_LAT && DEST_LON) {
        let dLat = parseFloat(DEST_LAT);
        let dLon = parseFloat(DEST_LON);
        map.setView([dLat, dLon], 17);
        setTimeout(() => {
            setMarker(dLat, dLon);
            updateFieldsFromCoords(dLat, dLon);
        }, 500);
    } else {
        // Automatically attempt to fetch the user's current location if no coordinates are passed
        getLocation();
    }

     // Calculates straight-line distance (fallback)
    function getHaversineDistance(lat1, lon1, lat2, lon2) {
        let R = 6371; // km
        let dLat = (lat2 - lat1) * Math.PI / 180;
        let dLon = (lon2 - lon1) * Math.PI / 180;
        let a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                Math.sin(dLon / 2) * Math.sin(dLon / 2);
        let c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        return (R * c).toFixed(2);
    }

    // Calculates road distance using OSRM API
    async function getRoadDistance(lat1, lon1, lat2, lon2) {
        try {
            // OSRM format: lon,lat;lon,lat
            const url = `https://router.project-osrm.org/route/v1/driving/${lon1},${lat1};${lon2},${lat2}?overview=false`;
            const resp = await fetch(url);
            const data = await resp.json();
            
            if (data.code === 'Ok' && data.routes && data.routes[0]) {
                // distance is in meters, convert to km
                return (data.routes[0].distance / 1000).toFixed(2);
            }
            throw new Error("Route not found");
        } catch (e) {
            console.error("OSRM Error:", e);
            return getHaversineDistance(lat1, lon1, lat2, lon2); // Fallback to air distance
        }
    }

    function resetLoader() {
        document.getElementById("locateBtn").disabled = false;
        document.getElementById("loader").style.display = "none";
        document.getElementById("btnText").innerText = "Detect My Current Position";
    }

    function getLocation() {
        document.getElementById("locateBtn").disabled = true;
        document.getElementById("loader").style.display = "block";
        document.getElementById("btnText").innerText = "Locating...";

        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(showPosition, (e) => {
                resetLoader();
                alert("Location detection failed. Please click on the map to select manually.");
            }, { enableHighAccuracy: true });
        } else {
            resetLoader();
            alert("Geolocation not supported.");
        }
    }

    let currentLang = 'hi';

    function changeLanguage() {
        currentLang = document.getElementById('langHi').checked ? 'hi' : 'en';
        let lat = parseFloat(document.getElementById("latitude").value);
        let lon = parseFloat(document.getElementById("longitude").value);
        if (!isNaN(lat) && !isNaN(lon)) {
            document.getElementById("address").value = "Translating...";
            updateFieldsFromCoords(lat, lon, true);
        }
    }

    async function updateFieldsFromCoords(lat, lon, skipDistance = false) {
        let latStr = (typeof lat === 'number') ? lat.toFixed(6) : String(lat);
        let lonStr = (typeof lon === 'number') ? lon.toFixed(6) : String(lon);
        document.getElementById("latitude").value = latStr;
        document.getElementById("longitude").value = lonStr;
        
        if (window.parent && window.parent !== window) {
            try {
                window.parent.postMessage({
                    type: 'coords_updated',
                    lat: latStr,
                    lon: lonStr
                }, '*');
            } catch(e) {}
        }
        
        // Show Swal Loading
        Swal.fire({
            title: 'Fetching Address...',
            html: 'Please wait while we retrieve the location details.',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Fetch distance in parallel (don't await it yet)
        let distancePromise = Promise.resolve("--");
        if (!skipDistance) {
            document.getElementById("computedDistance").innerText = "...";
            distancePromise = getRoadDistance(OFFICE_LAT, OFFICE_LON, lat, lon);
        }

        // Fetch address from Nominatim
        fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lon}&addressdetails=1&accept-language=${currentLang},en`)
            .then(res => res.json())
            .then(async data => {
                const a = data.address || {};
                
                // State & District
                document.getElementById("state").value = a.state || a.region || a.province || '';
                document.getElementById("district").value = a.state_district || a.county || a.city_district || a.municipality || a.subdistrict || '';
                
                // City / Town Fallbacks - Added Tahsil and Subdistrict for rural areas
                const city = a.city || a.town || a.village || a.tahsil || a.subdistrict || a.suburb || a.hamlet || a.municipality || a.neighbourhood || a.county || 'Detected Area';
                document.getElementById("city").value = city;
                document.getElementById("destCityName").innerText = city;

                // Pincode Fallbacks
                let pincode = a.postcode || a.postal_code || a.postalcode || '';
                // Fallback: try to extract 6-digit number from display name if still empty
                if (!pincode && data.display_name) {
                    const match = data.display_name.match(/\b\d{6}\b/);
                    if (match) pincode = match[0];
                }
                document.getElementById("pincode").value = pincode;
                
                // Full Address
                document.getElementById("address").value = data.display_name || '';
                
                // Close loading indicator early so user can see data
                Swal.close();
                resetLoader();

                // Update distance when ready (non-blocking)
                const roadDist = await distancePromise;
                document.getElementById("computedDistance").innerText = roadDist;
            }).catch(e => {
                console.error("Address Fetch Error:", e);
                Swal.close();
                resetLoader();
            });
    }

    function setMarker(lat, lon) {
        if (marker) {
            marker.setLatLng([lat, lon]);
        } else {
            marker = L.marker([lat, lon], { draggable: true }).addTo(map);
            marker.on('dragend', () => {
                const p = marker.getLatLng();
                updateFieldsFromCoords(p.lat, p.lng);
            });
        }
    }

    function showPosition(pos) {
        const lat = pos.coords.latitude;
        const lon = pos.coords.longitude;
        map.setView([lat, lon], 17);
        setMarker(lat, lon);
        updateFieldsFromCoords(lat, lon);
    }

    map.on('click', (e) => {
        setMarker(e.latlng.lat, e.latlng.lng);
        updateFieldsFromCoords(e.latlng.lat, e.latlng.lng);
    });

    async function saveDeliveryLocation() {
        const addr = document.getElementById("address").value;
 
        if (!addr) {
            Swal.fire('Incomplete Data', 'Please ensure location is selected and Client ID is provided.', 'warning');
             return;
        }

        const data = {
            orderid: ORDER_ID,
            clientid: CLIENT_ID,
            lat: document.getElementById("latitude").value,
            lon: document.getElementById("longitude").value,
            state: document.getElementById("state").value,
            district: document.getElementById("district").value,
            city: document.getElementById("city").value,
            pincode: document.getElementById("pincode").value,
            full_address: addr
        };

        const btn = document.getElementById("saveBtn");
        btn.disabled = true;
         btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Saving to Database...';

        try {
            const resp = await fetch('modal/updatedeliveryaddress.php', {
                 method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await resp.json();
            
            if (result.status === 'success') {
                 Swal.fire({
                    title: 'Location Confirmed!',
                    text: 'The delivery location has been successfully mapped to your order.',
                    icon: 'success',
                    confirmButtonColor: '#667eea'
                }).then(() => window.close());
            } else if (result.status === 'info') {
                 Swal.fire('No Updates', result.message, 'info');
            } else {
                Swal.fire('Error', result.message || 'We encountered an error while saving.', 'error');
            }
        } catch (e) {
            Swal.fire('Network Error', 'Connection to server failed. Check your internet.', 'error');
         } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-check-circle me-2"></i> Confirm Delivery Location';
        }
    }

    function collectAndSendToParent() {
        const elLat = document.getElementById("latitude");
        const elLon = document.getElementById("longitude");
        const elState = document.getElementById("state");
        const elDist = document.getElementById("district");
        const elCity = document.getElementById("city");
        const elPin = document.getElementById("pincode");
        const elAddr = document.getElementById("address");

        if (!elLat || !elLon || !elAddr) {
            Swal.fire('Error', 'Required form elements are missing.', 'error');
            return;
        }

        const latVal = elLat.value;
        const lonVal = elLon.value;
        const addrVal = elAddr.value;

        if (!latVal || !lonVal) {
            Swal.fire('Incomplete Data', 'Missing location coordinates.', 'warning');
            return;
        }
        if (!addrVal) {
            Swal.fire('Incomplete Data', 'Missing full address.', 'warning');
            return;
        }

        const distText = document.getElementById("computedDistance").innerText;
        const distVal = (distText === "--" || distText === "...") ? 0 : distText;

        const data = {
            lat: latVal,
            lon: lonVal,
            state: elState ? elState.value : '',
            district: elDist ? elDist.value : '',
            city: elCity ? elCity.value : '',
            pincode: elPin ? elPin.value : '',
            full_address: addrVal,
            calc_km: distVal
        };

        if (window.parent && typeof window.parent.appendAddressRow === 'function') {
            window.parent.appendAddressRow(data);
        } else {
            console.error("Parent function appendAddressRow not found.");
            Swal.fire('Success', 'Address collected, but parent window not found.', 'info');
        }
    } 

    window.resetForm = function() {
        // Clear all inputs
        const inputs = ['latitude', 'longitude', 'state', 'district', 'city', 'pincode', 'address'];
        inputs.forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = '';
        });

        // Reset distance display
        const distEl = document.getElementById("computedDistance");
        if (distEl) distEl.innerText = '--';
        
        // Clear search input if exists
        const searchEl = document.getElementById("latlonginput");
        if (searchEl) searchEl.value = '';

        // Reset map if needed (optional, depends on implementation)
        if (typeof marker !== 'undefined' && map) {
            map.removeLayer(marker);
        }
    }
</script>
</body>
</html>