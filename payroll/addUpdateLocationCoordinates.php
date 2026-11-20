<?php
if(isset($_GET['vw'])==1){
    $key=$_GET['k'];
    $rooturl="";
}else{
  require('header.php');
}
?>
<style>
    .holiday-card {
      width: 100%;
      margin: 10px auto;
      border-radius: 15px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }
    .table thead th {
      background-color: #0d6efd;
      color: #fff;
      /*text-align: center;*/
    }
</style>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<div class="container mt-2">
    <div class="row mb-2">
        <div class="col-12">
          
        </div>
    </div>
     <div class="card holiday-card">
        <div class="card-header">
          <h4>Office Location Coordinates</h4>
        </div>
        <div class="card-body">
            <div class="row mt-2">
                <div class="col-md-12 mt-2">
                    <div id="viewlatlng" class="d-none">
                        <p><b>Latitude:</b> <span id="latlb"></span> <b>Longitude:</b> <span id="lnglb"></span></p>
                    </div>
                    <div class="d-flex justify-content-end mb-2">
                        <button class="btn btn-primary btn-sm m-2" id="openLocationModal"><i class="fa fa-plus-circle"></i> Add New Location</button>

                        <button id="refreshLocation" class="btn btn-primary btn-sm m-2"><i class="fa fa-sync"></i> Refresh Location</button>
                        
                        <button class="btn btn-outline-primary btn-sm m-2"  onclick="getMyLocation()">
                            📍 Get Current Location Coordinates
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-light" id="LocationMasterTable">
                            <thead>
                                <thead>
                                <tr>
                                  <th style="width:50px; text-align: center;">S.No</th>
                                  <th>Office Location Name</th>
                                  <th>Location Latitude</th>
                                  <th>Location Longitude</th>
                                  <th>Action</th>
                              </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="locationModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Your Location</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <p><b>Latitude:</b> <span id="lat"></span></p>
                <p><b>Longitude:</b> <span id="lng"></span></p>

                <div id="locationMap" style="height: 350px; width: 100%;"></div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="locationAddModal">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <div class="d-flex align-items-center">
                    <h4 class="modal-title me-2" id="">Attendance Location</h4><span></span>
                </div>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>
            <div class="modal-body pb-0 ">
                <div class="row">
                    <div class="col-md-12">
                        <label>Location Name</label>
                        <input type="text" class="form-control" name="locationName" id="locationName" required />
                    </div>
                    <div class="col-md-12">
                        <label>Location Short Name</label>
                        <input type="text" class="form-control" name="locationShortName" id="locationShortName" required />
                    </div>
                    <div class="col-md-12">
                        <label>Location Address</label>
                        <input type="text" class="form-control" name="locationAddress" id="locationAddress" />
                    </div>
                    <div class="col-md-12">
                        <label>Remark</label>
                        <textarea type="text" class="form-control" name="remark" id="remark" rows="2"></textarea>
                    </div>
                </div>
                <div class="row mt-4 mb-4">
                    <div class="col-md-12 text-center">
                        <!-- data-locationid="" → if empty = add new, if set = update -->
                        <button class="btn btn-outline-primary" data-locationid="" id="savetolocationbtn" onclick="saveLocationJs('locationName', 'locationAddress','remark', 'savetolocationbtn');">Save Location</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<?php
if(isset($_GET['vw'])==1){

}else{
  require('footer.php'); 
}
?>
<script type="text/javascript">
$("#openLocationModal").on("click", function() {
    $("#locationAddModal").modal("show");
});

$("#refreshLocation").on("click", function() {
    AttendanceLocationMaster_callback("all", "all", function(res) {
        let html = "";    
        if (Array.isArray(res) && res.length > 0) {
            res.forEach((row, index) => {
                // Ensure null-safe values
                let LocationName = row.LocationName ?? "";
                let OfficeLat    = row.OfficeLat ?? "";
                let OfficeLng    = row.OfficeLng ?? "";
                html += `
                    <tr data-locationid="${row.LocationId}" data-dbsno="${row.sno}">
                        <td style="text-align:center;">${index + 1}</td>
                        <td>${LocationName}</td>
                        <td>
                            <input type="text" class="form-control form-control-sm LocationLat"
                                   placeholder="Enter Latitude" 
                                   name="LocationLat_${row.sno}" 
                                   value="${OfficeLat}" />
                        </td>
                        <td>
                            <input type="text" class="form-control form-control-sm LocationLng"
                                   placeholder="Enter Longitude" 
                                   name="LocationLng_${row.sno}" 
                                   value="${OfficeLng}" />
                        </td>
                        <td>
                            <button class="btn btn-sm btn-primary saveLocationBtn" data-id="${row.LocationId}">
                                Save
                            </button>
                        </td>
                    </tr>`;
            });

        } else {
            html = `
                <tr>
                    <td colspan="5" class="text-center">No locations found</td>
                </tr>`;
        }
        $("#LocationMasterTable tbody").html(html);
    });
});

AttendanceLocationMaster_callback("all", "all", function(res) {
    let html = "";    
    if (Array.isArray(res) && res.length > 0) {
        res.forEach((row, index) => {
            // Ensure null-safe values
            let LocationName = row.LocationName ?? "";
            let OfficeLat    = row.OfficeLat ?? "";
            let OfficeLng    = row.OfficeLng ?? "";
            html += `
                <tr data-locationid="${row.LocationId}" data-dbsno="${row.sno}">
                    <td style="text-align:center;">${index + 1}</td>
                    <td>${LocationName}</td>
                    <td>
                        <input type="text" class="form-control form-control-sm LocationLat"
                               placeholder="Enter Latitude" 
                               name="LocationLat_${row.sno}" 
                               value="${OfficeLat}" />
                    </td>
                    <td>
                        <input type="text" class="form-control form-control-sm LocationLng"
                               placeholder="Enter Longitude" 
                               name="LocationLng_${row.sno}" 
                               value="${OfficeLng}" />
                    </td>
                    <td>
                        <button class="btn btn-sm btn-primary saveLocationBtn" data-id="${row.LocationId}">
                            Save
                        </button>
                    </td>
                </tr>`;
        });

    } else {
        html = `
            <tr>
                <td colspan="5" class="text-center">No locations found</td>
            </tr>`;
    }

    $("#LocationMasterTable tbody").html(html);
});

$(document).on("click", ".saveLocationBtn", function () {
    let btn = this; // button reference
    let row = $(this).closest("tr");
    let locationId = row.data("locationid");
    let sno = row.data("dbsno");
    let lat = row.find(".LocationLat").val().trim();
    let lng = row.find(".LocationLng").val().trim();
    saveLocationlatlng(locationId, sno, lat, lng, btn);
});

let map, marker;

function getMyLocation() {
    $("#viewlatlng").removeClass('d-none');
    if (!navigator.geolocation) {
        alert("Your browser does not support location.");
        return;
    }

    navigator.geolocation.getCurrentPosition(
        function (pos) {
            let lat = pos.coords.latitude;
            let lng = pos.coords.longitude;

            document.getElementById("lat").innerText = lat;
            document.getElementById("lng").innerText = lng;

            document.getElementById("latlb").innerText = lat;
            document.getElementById("lnglb").innerText = lng;

            // Show Modal
            let modal = new bootstrap.Modal(document.getElementById('locationModal'));
            modal.show();

            setTimeout(() => loadMap(lat, lng), 300);
        },
        function () {
            alert("Unable to fetch location. Please enable GPS.");
        }
    );
}

function loadMap(lat, lng) {
    if (!map) {
        map = L.map('locationMap').setView([lat, lng], 15);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19
        }).addTo(map);

        // Draggable marker
        marker = L.marker([lat, lng], { draggable: true }).addTo(map);

        // Update coordinates on drag
        marker.on("dragend", function (e) {
            let newLat = e.target.getLatLng().lat;
            let newLng = e.target.getLatLng().lng;

            document.getElementById("lat").innerText = newLat;
            document.getElementById("lng").innerText = newLng;

            document.getElementById("latlb").innerText = newLat;
            document.getElementById("lnglb").innerText = newLng;
        });

    } else {
        map.setView([lat, lng], 15);
        marker.setLatLng([lat, lng]);
    }
}
</script>
