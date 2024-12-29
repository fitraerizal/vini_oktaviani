<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detect Fake Location and Device</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        .result {
            margin-top: 20px;
        }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <h1>Fake Location and Device Detector</h1>
    <button id="check-location">Check My Location</button>

    <div class="result">
        <h3>Results:</h3>
        <pre id="results"></pre>
    </div>

    <script>
        $(document).ready(function () {
            $('#check-location').click(async function () {
                const $results = $('#results');
                $results.text("Checking location and device...");

                // Lokasi kantor
                const officeLocation = {
                    latitude: -4.0009728, // Latitude lokasi kantor Anda
                    longitude: 122.5162752 // Longitude lokasi kantor Anda
                };

                // Deteksi jenis perangkat
                const deviceInfo = detectDevice();

                // Gunakan HTML5 Geolocation API untuk mendapatkan lokasi perangkat
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(
                        async function (position) {
                            const userLatitude = position.coords.latitude;
                            const userLongitude = position.coords.longitude;
                            const accuracy = position.coords.accuracy;

                            // Hitung jarak ke lokasi kantor
                            const distance = calculateDistance(
                                userLatitude, userLongitude,
                                officeLocation.latitude, officeLocation.longitude
                            );

                            // Dapatkan data lokasi berdasarkan IP
                            const ipLocation = await getIpLocation();

                            // Validasi data
                            const validationResults = validateLocation({
                                userLatitude,
                                userLongitude,
                                distance,
                                accuracy,
                                ipLocation
                            });

                            // Gabungkan hasil deteksi perangkat dan lokasi
                            const combinedResults = {
                                deviceInfo,
                                locationValidation: validationResults
                            };

                            $results.text(JSON.stringify(combinedResults, null, 2));
                        },
                        function (error) {
                            $results.text(`Error mendapatkan lokasi: ${error.message}`);
                        },
                        {
                            enableHighAccuracy: true,
                            timeout: 10000,
                            maximumAge: 0
                        }
                    );
                } else {
                    $results.text("Geolocation API tidak didukung di browser ini.");
                }
            });

            // Fungsi untuk menghitung jarak (Haversine Formula)
            function calculateDistance(lat1, lon1, lat2, lon2) {
                const R = 6371e3; // Radius bumi dalam meter
                const rad = Math.PI / 180;
                const φ1 = lat1 * rad;
                const φ2 = lat2 * rad;
                const Δφ = (lat2 - lat1) * rad;
                const Δλ = (lon2 - lon1) * rad;

                const a = Math.sin(Δφ / 2) * Math.sin(Δφ / 2) +
                          Math.cos(φ1) * Math.cos(φ2) *
                          Math.sin(Δλ / 2) * Math.sin(Δλ / 2);
                const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

                return R * c; // Jarak dalam meter
            }

            // Fungsi untuk mendapatkan lokasi berdasarkan IP
            async function getIpLocation() {
                try {
                    const response = await $.getJSON('https://ipinfo.io/json?token=33ce6ac45b9699');
                    const [ipLatitude, ipLongitude] = response.loc.split(',').map(Number);
                    return {
                        latitude: ipLatitude,
                        longitude: ipLongitude,
                        city: response.city,
                        country: response.country,
                        region: response.region
                    };
                } catch (error) {
                    return { error: "Gagal mendapatkan data lokasi dari IP." };
                }
            }

            // Validasi lokasi
            function validateLocation({ userLatitude, userLongitude, distance, accuracy, ipLocation }) {
                const results = {
                    userLocation: { latitude: userLatitude, longitude: userLongitude },
                    distanceToOffice: `${distance.toFixed(2)} meters`,
                    accuracy: `${accuracy.toFixed(2)} meters`,
                    ipLocation
                };

                // Validasi akurasi
                if (accuracy > 1000) {
                    results.warning = "Akurasi lokasi rendah. Lokasi mungkin tidak valid.";
                }

                // Validasi IP vs GPS
                if (ipLocation.latitude && ipLocation.longitude) {
                    const ipDistance = calculateDistance(
                        userLatitude, userLongitude,
                        ipLocation.latitude, ipLocation.longitude
                    );
                    results.ipDistance = `${ipDistance.toFixed(2)} meters`;

                    if (ipDistance > 50000) { // Jika jarak IP > 50km dari lokasi GPS
                        results.warning = "Perbedaan besar antara lokasi IP dan GPS. Lokasi mungkin palsu.";
                    }
                } else {
                    results.warning = "Tidak dapat memvalidasi lokasi berdasarkan IP.";
                }

                // Validasi jarak ke kantor
                if (distance > 50) { // Radius 50 meter
                    results.status = "Anda berada di luar lokasi kantor.";
                } else {
                    results.status = "Anda berada di lokasi kantor.";
                }

                return results;
            }

            // Fungsi untuk mendeteksi jenis perangkat
            function detectDevice() {
                const userAgent = navigator.userAgent;
                const platform = navigator.platform;

                let deviceType;
                if (/mobile/i.test(userAgent)) {
                    deviceType = "Mobile";
                } else if (/tablet/i.test(userAgent)) {
                    deviceType = "Tablet";
                } else {
                    deviceType = "Desktop";
                }

                return {
                    deviceType,
                    platform,
                    userAgent,
                    screenSize: {
                        width: window.innerWidth,
                        height: window.innerHeight
                    }
                };
            }
        });
    </script>
</body>
</html>
