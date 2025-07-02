<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aplikasi Onsite dengan Pemilihan Lokasi</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY&libraries=places&callback=initMap" async defer></script>
    <style>
        #map {
            height: 400px;
            width: 100%;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }
        .error-message {
            color: #ef4444;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-3xl mx-auto bg-white rounded-lg shadow-md overflow-hidden">
            <div class="bg-blue-600 px-6 py-4">
                <h1 class="text-2xl font-bold text-white">Form Tambah Data Onsite</h1>
            </div>
            
            <form id="onsiteForm" class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="nama" class="block text-sm font-medium text-gray-700 mb-1">Nama Project</label>
                        <input type="text" id="nama" name="nama" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
                    </div>
                    
                    <div>
                        <label for="tanggal" class="block text-sm font-medium text-gray-700 mb-1">Tanggal Onsite</label>
                        <input type="date" id="tanggal" name="tanggal" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
                    </div>
                    
                    <div>
                        <label for="alamat" class="block text-sm font-medium text-gray-700 mb-1">Alamat</label>
                        <textarea id="alamat" name="alamat" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"></textarea>
                    </div>
                    
                    <div>
                        <label for="keterangan" class="block text-sm font-medium text-gray-700 mb-1">Keterangan</label>
                        <textarea id="keterangan" name="keterangan" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"></textarea>
                    </div>
                </div>
                
                <div class="mt-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Pilih Lokasi di Peta</label>
                    <div class="relative">
                        <div id="map"></div>
                        <div class="flex justify-between mt-2 items-center">
                            <small class="text-gray-500">Geser marker untuk memilih lokasi</small>
                            <button type="button" id="lokasiSaatIni" class="text-sm bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded-md">Gunakan Lokasi Saat Ini</button>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4 mt-4">
                        <div>
                            <label for="latitude" class="block text-sm font-medium text-gray-700 mb-1">Latitude</label>
                            <input type="text" id="latitude" name="latitude" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm bg-gray-100" readonly>
                        </div>
                        <div>
                            <label for="longitude" class="block text-sm font-medium text-gray-700 mb-1">Longitude</label>
                            <input type="text" id="longitude" name="longitude" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm bg-gray-100" readonly>
                        </div>
                    </div>
                </div>
                
                <div class="mt-8 flex justify-end space-x-3">
                    <button type="reset" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">Reset</button>
                    <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let map;
        let marker;
        let geocoder;
        
        function initMap() {
            // Default location
            const defaultLocation = { lat: -6.1754, lng: 106.8272 }; // Jakarta
            
            // Initialize map
            map = new google.maps.Map(document.getElementById("map"), {
                center: defaultLocation,
                zoom: 12,
                mapTypeControl: true,
                streetViewControl: false,
                fullscreenControl: true
            });
            
            geocoder = new google.maps.Geocoder();
            
            // Add marker
            marker = new google.maps.Marker({
                position: defaultLocation,
                map: map,
                draggable: true,
                title: "Geser untuk memilih lokasi"
            });
            
            // Update coordinates when marker is moved
            marker.addListener("dragend", updateMarkerPosition);
            
            // Initial coordinates
            updateMarkerPosition();
            
            // Add click listener to map
            map.addListener("click", (e) => {
                placeMarker(e.latLng);
                reverseGeocode(e.latLng);
            });
            
            // Add location search box
            addSearchBox();
            
            // Handle current location button
            document.getElementById('lokasiSaatIni').addEventListener('click', getCurrentLocation);
        }
        
        function updateMarkerPosition() {
            const position = marker.getPosition();
            document.getElementById('latitude').value = position.lat();
            document.getElementById('longitude').value = position.lng();
            
            // Reverse geocode the position to get address
            reverseGeocode(position);
        }
        
        function placeMarker(location) {
            marker.setPosition(location);
            updateMarkerPosition();
        }
        
        function reverseGeocode(latlng) {
            geocoder.geocode({ 'location': latlng }, (results, status) => {
                if (status === "OK" && results[0]) {
                    document.getElementById('alamat').value = results[0].formatted_address;
                }
            });
        }
        
        function addSearchBox() {
            const input = document.createElement("input");
            input.type = "text";
            input.id = "search-input";
            input.placeholder = "Cari lokasi...";
            input.className = "w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500";
            
            const searchBoxContainer = document.createElement("div");
            searchBoxContainer.className = "absolute top-2 left-2 right-2 bg-white p-2 rounded-md shadow-md";
            searchBoxContainer.appendChild(input);
            
            document.getElementById("map").appendChild(searchBoxContainer);
            
            const searchBox = new google.maps.places.SearchBox(input);
            
            map.controls[google.maps.ControlPosition.TOP_CENTER].push(searchBoxContainer);
            
            searchBox.addListener("places_changed", () => {
                const places = searchBox.getPlaces();
                
                if (places.length === 0) {
                    return;
                }
                
                // For multiple places, just use the first one
                const place = places[0];
                
                if (!place.geometry) {
                    console.log("Returned place contains no geometry");
                    return;
                }
                
                // Move map and marker
                if (place.geometry.viewport) {
                    map.fitBounds(place.geometry.viewport);
                } else {
                    map.setCenter(place.geometry.location);
                    map.setZoom(17);
                }
                
                placeMarker(place.geometry.location);
                
                // Set address
                if (place.formatted_address) {
                    document.getElementById('alamat').value = place.formatted_address;
                }
            });
        }
        
        function getCurrentLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        const pos = {
                            lat: position.coords.latitude,
                            lng: position.coords.longitude
                        };
                        
                        marker.setPosition(pos);
                        map.setCenter(pos);
                        map.setZoom(15);
                        updateMarkerPosition();
                    },
                    (error) => {
                        alert("Tidak dapat mendapatkan lokasi saat ini. Pastikan GPS aktif atau izinkan akses lokasi.");
                        console.error("Error getting location:", error);
                    }
                );
            } else {
                alert("Browser tidak mendukung geolocation");
            }
        }
        
        // Form submission handler
        document.getElementById('onsiteForm').addEventListener('submit', (e) => {
            e.preventDefault();
            
            // Get form values
            const formData = {
                nama: document.getElementById('nama').value,
                tanggal: document.getElementById('tanggal').value,
                alamat: document.getElementById('alamat').value,
                keterangan: document.getElementById('keterangan').value,
                latitude: document.getElementById('latitude').value,
                longitude: document.getElementById('longitude').value
            };
            
            // Here you would typically send data to server
            console.log('Data yang akan dikirim:', formData);
            
            // Show success message
            alert('Data onsite berhasil disimpan!');
            
            // You can add AJAX call here to send data to your server
            // fetch('/api/onsite', {
            //     method: 'POST',
            //     headers: {
            //         'Content-Type': 'application/json',
            //     },
            //     body: JSON.stringify(formData)
            // })
        });
    </script>
</body>
</html>
