<?php
$psodate = "";
$clientname = "";
$mobileno = "";
$emailid = "";
$deliverydate = "";
$psoamount = "";
if (isset($_GET['dz'])) {
  require('../connection.php');
  require('../functions/function_master.php');
  include('functions/orders_functions.php');
  $key = $_GET['k'];
  $key_decoded = base64url_decode($key);
  $key_parts = explode("|", $key_decoded);
  $checkkey = base64url_decode($key_parts[count($key_parts) - 1]);
  $actkey = "";
  for ($i = 0; $i < count($key_parts) - 1; $i++) {
    $actkey = $actkey ? $actkey . "|" . $key_parts[$i] : $key_parts[$i];
  }
  if ($actkey != $checkkey) {
    echo "<div class=\"col-md-12 text-center black\"><h3>OOPS!!Something went wrong. Please Try after sometime.</h3></div>";
    die;
  }
  $loginname = "";
  $loginid = $key_parts[0];
  $loginroleid = $key_parts[1];
  $companyid = $key_parts[2];
  $subcompanyid = $key_parts[3];
  $projectid = $key_parts[4];
  $logintype = $key_parts[5];
  $newkey = "x|x|" . $key_parts[2] . "|" . $key_parts[3] . "|" . $key_parts[4] . "|x";
  $encodednewkey = base64url_encode($newkey);
  $keycompany = base64url_encode($newkey . "|" . $encodednewkey);
  $cmpinfo = getcompanydetails($mconn, "companyid", $companyid);
  $companyname = "";
  if (count($cmpinfo) > 0) {
    foreach ($cmpinfo as $cmphkey) {
      $companyname = $cmphkey['company_name'];
      $countryid = $cmphkey['countryid'];
    }
  }

  $subcmpinfo = getsubcompanydetails($mconn, $companyid, $subcompanyid, "subcompanyid", $subcompanyid);
  $subcompanyname = "";
  if (count($subcmpinfo) > 0) {
    foreach ($subcmpinfo as $subcmphkey) {
      $subcompanyname = $subcmphkey['subcompanyname'];
    }
  }

  //projectinfo
  $prj = getprojectdetails($conn, $companyid, $subcompanyid, "projectid", $projectid);
  // print_r($prj);
  $projectname = "";
  if (count($prj) > 0) {
    foreach ($prj as $pjkey) {
      $projectname = $pjkey['projectname'];
    }
  }
  $protocol = $_SERVER['PROTOCOL'] = isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) ? 'https' : 'http';
  $url = basename($_SERVER["PHP_SELF"]);
  $rooturl = $protocol . '://' . $_SERVER['HTTP_HOST'];
  $baseurl = $rooturl . str_replace($url, "", $_SERVER["PHP_SELF"]);
  $countryid = "";
  if ($_SERVER['HTTP_HOST'] == "localhost") {
    $prefix = $rooturl . "/krf_amanaccounting/";
  } else {
    $prefix = $rooturl . "/";
  }
  $rooturl = $prefix;
} else {
  require('../header.php');
  include('functions/orders_functions.php');
  $rooturl;
}
$mdid = "";
if (isset($_GET['mdid'])) {
  $mdid = $_GET['mdid'];
}
$dddr = loginmodulesdetails_frommaster($authconn, $companyid, $subcompanyid, $projectid, "moduleid", $mdid);
$groupid = "";
if (count($dddr) > 0) {
  $groupid = $dddr[0]['groupid'];
}

?>

<div class="container">

  <!-- Header -->
  <div class="text-center mb-4">
    <h2 class="fw-bold">Seed Details</h2>
  </div>

  <div class="row <?php echo isset($_GET['dz']) ? 'd-none' : '' ?>">
    <div class="col-md-12">
      <button class="btn btn-sm btn-deepblue m-2 float-right" onclick="location.reload();">Refresh</a>
    </div>
  </div>
  <div class="container">
    <div class="card">
      <div class="card-body">
        <form id="seedForm" method="POST" enctype="multipart/form-data">
          <div class="mb-3">
            <label for="SeedCategory" class="form-label">Category of Vegitable</label>
            <select class="form-select form-control" id="SeedCategory" name="SeedCategory" required>
              <option value="" selected disabled>Select</option>
            </select>
          </div>
          <div class="mb-3">
            <label for="SubSeedCategory" class="form-label">Type of Vegitable (Crop Name)</label>
            <i class="fa fa-plus text-deepblue m-2 addnewsubcategory" style="cursor: pointer; font-size: 20px;"></i>
            <i class="fa fa-sync text-deepblue m-2"
              onclick="GetProductSubCategoryFrommaster_option('SubSeedCategory', 'SeedCategory', $('#SeedCategory').val(),'');"
              style="cursor: pointer; font-size: 20px;"></i>
            <select class="form-select form-control" id="SubSeedCategory" name="SubSeedCategory" required>
              <option value="" selected disabled>Select</option>
            </select>
          </div>
          <div class="mb-3">
            <label for="TypeOfSeed" class="form-label">Type of Seed</label>
            <input type="text" class="form-control" id="TypeOfSeed" name="TypeOfSeed" autocomplete="off"
              placeholder="Write type of seed." required>
          </div>
          <!-- Variety Name -->
          <div class="mb-3">
            <label for="VarietyName" class="form-label">Variety of Seed</label>
            <input type="text" class="form-control" id="VarietyName" name="VarietyName" autocomplete="off"
              placeholder="Write the variety of seed." required>
          </div>

          <!-- Seed Name -->
          <!--             <div class="mb-3 d-none">
                <label for="SeedName" class="form-label">Seed Name</label>
                <input type="text" class="form-control" id="SeedName" name="SeedName" autocomplete="off" placeholder="Write the name of seed">
            </div>
 -->
          <!-- Brand Name -->
          <div class="mb-3">
            <label for="BrandName" class="form-label">Brand Name of Seed</label>
            <input type="text" class="form-control" id="BrandName" name="BrandName" autocomplete="off"
              placeholder="Write the brand name of seed" required>
          </div>

          <!-- Company Name -->
          <div class="mb-3">
            <label for="CompanyName" class="form-label">Manufacturer / Company Name of Seed</label>
            <input type="text" class="form-control" id="CompanyName" name="CompanyName" autocomplete="off"
              placeholder="Write the Manufacturer company name of seed">
          </div>
          <!-- Vendor ID -->
          <div class="mb-3">
            <label for="VendorID" class="form-label">Supplier / Vendor Name of Seed</label>
            <i class="fa fa-plus text-deepblue m-2 creatvendormaster" style="cursor: pointer; font-size: 20px;"></i>
            <i class="fa fa-sync text-deepblue m-2" onclick="GetVendorDataOnlyOption('VendorID','');"
              style="cursor: pointer; font-size: 20px;"></i>
            <select class="form-select form-control" id="VendorID" name="VendorID" required></select>
          </div>

          <div class="mb-3">
            <label for="SeedWeightPerPacket" class="form-label">Seeds Weight Pet Packet (gm)</label>
            <input type="text" class="form-control" id="SeedWeightPerPacket" name="SeedWeightPerPacket"
              autocomplete="off" onkeypress="return isNumberKey(event)" placeholder="Enter the weight per packet" />
          </div>

          <div class="mb-3">
            <label for="SeedCountPerPacket" class="form-label">How Many Seeds in a Packet?</label>
            <input type="text" class="form-control" id="SeedCountPerPacket" name="SeedCountPerPacket" autocomplete="off"
              onkeypress="return isNumberKey(event)" placeholder="Write the no of seed in a packet" />
          </div>

          <div class="mb-3">
            <label for="growingdays" class="form-label">How Many Days Required to Grow?</label>
            <input type="text" class="form-control" id="growingdays" name="growingdays" autocomplete="off"
              onkeypress="return isNumberKey(event)" placeholder="Write growing days" />
          </div>

          <!-- Description -->
          <div class="mb-3">
            <label for="Description" class="form-label">Description of Seed</label>
            <textarea class="form-control" id="Description" name="Description" rows="3"></textarea>
          </div>

          <div class="mb-3">
            <label for="suggestedsku" class="form-label">Suggested SKU</label>
            <input type="text" class="form-control" id="suggestedsku" name="suggestedsku" />
          </div>

          <!-- Status -->
          <div class="mb-3 d-none">
            <label for="Status" class="form-label">Status</label>
            <select class="form-select form-control" id="Status" name="Status" required>
              <option value="Active" selected>Active</option>
              <option value="Inactive">Inactive</option>
            </select>
          </div>
          <div class="row mb-3">
            <div class="col-md-6">
              <label for="uploadMainPhoto" class="form-label">Upload Main Photo (Only 1 photo/image allowed)</label>
              <input type="file" class="form-control" id="uploadMainPhoto" name="uploadMainPhoto" accept="image/*" />
            </div>
            <div class="col-md-6">
              <label for="uploadOtherPhotos" class="form-label">Other Photos (Multiple photos/images allowed)</label>
              <input type="file" class="form-control" id="uploadOtherPhotos" name="uploadOtherPhotos[]" multiple
                accept="image/*" />
            </div>
          </div>

          <!-- Submit Button -->
          <div class="col-md-12 text-center">
            <input type="hidden" name="companyid" value="<?php echo $companyid; ?>" />
            <input type="hidden" name="subcompanyid" value="<?php echo $subcompanyid; ?>" />
            <input type="hidden" name="projectid" value="<?php echo $projectid; ?>" />
            <input type="hidden" name="loginid" value="<?php echo $loginid; ?>" />
            <input type="hidden" name="SeedID" value="" />
            <button type="submit" class="btn btn-primary text-center">Save Seed Details</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php
if (isset($_GET['dz'])) {
} else {
  include("../footer.php");
}
?>
<script type="text/javascript" src="<?php echo $prefix; ?>krf-orderandsales/js/pjs_function.js"></script>
<script>
  var dz = "<?php echo $_GET['dz']; ?>";

  GetProductCategoryFrommaster_option("SeedCategory", "VEG10");
  $(document).on("change", "#SeedCategory", function () {
    GetProductSubCategoryFrommaster_option('SubSeedCategory', "SeedCategory", $("#SeedCategory").val(), "");
  });

  $(document).on("input change", "#VarietyName, #TypeOfSeed, #BrandName, #SubSeedCategory", function () {
    GetSuggestedSKUSeed($('#SeedCategory').val(), $('#SubSeedCategory').val(), $('#VarietyName').val(), $('#TypeOfSeed').val(), $('#BrandName').val(), 'suggestedsku');
  });



  // subcategory master list or new save the data
  $(".addnewsubcategory").on("click", function () {
    var subcatelbl = $("#SeedCategory").val();
    var subcatelbltxt = $("#SeedCategory option:selected").text();
    $("#NewSubcategoryModalheader").html("New Type of Vegitable for " + subcatelbltxt);
    GetProductCategoryFrommaster_option("nsbcCategoryID", subcatelbl);
    $("#NewSubcategoryModal").modal("show");
  });


  GetVendorDataOnlyOption('VendorID', '');
  // creatvendormaster
  // $("#seedForm").submit(function(e) {
  //     e.preventDefault();
  //     var formData = new FormData(this); // Automatically includes all form fields + files
  //     $.ajax({
  //         url: "modal/seed_modal.php?action=saveupdateseeddetails",
  //         type: "POST",
  //         data: formData,
  //         dataType: "json",
  //         contentType: false, // Important for file uploads
  //         processData: false, // Important for file uploads
  //         success: function(response) {
  //             if (response.status === 'success') {
  //                 Swal.fire({
  //                     title: response.message,
  //                     text: "SKU No is " + response.sku,
  //                     showDenyButton: false,
  //                     showCancelButton: false,
  //                     confirmButtonText: "Refresh",
  //                 }).then((result) => {
  //                     if (result.isConfirmed) {
  //                         location.reload();
  //                     }
  //                 });
  //             } else {
  //                 alertmessage("error", "Error: " + response.message);
  //             }
  //         }
  //     });
  // });

  $("#seedForm").on("submit", function (e) {
    e.preventDefault();
    var formData = new FormData(this);
    // 🔄 SHOW WAITING / LOADING MESSAGE
    Swal.fire({
      title: "Please wait...",
      text: "Saving seed details",
      allowOutsideClick: false,
      allowEscapeKey: false,
      didOpen: () => {
        Swal.showLoading();
      }
    });
    $.ajax({
      url: "modal/seed_modal.php?action=saveupdateseeddetails",
      type: "POST",
      data: formData,
      dataType: "json",
      contentType: false,
      processData: false,
      success: function (response) {
        Swal.close(); // ✅ CLOSE LOADER
        if (response.status === "success") {
          Swal.fire({
            icon: "success",
            title: response.message,
            html: `<b>SKU No:</b> ${response.sku}`,
            confirmButtonText: "Refresh"
          }).then((result) => {
            if (result.isConfirmed) {
              location.reload();
            }
          });
        } else {
          Swal.fire({
            icon: "error",
            title: "Error",
            text: response.message
          });
        }
      },
      error: function (xhr, status, error) {

        Swal.close(); // ✅ ENSURE LOADER CLOSES

        Swal.fire({
          icon: "error",
          title: "Server Error",
          text: "Something went wrong. Please try again."
        });

        console.error("AJAX Error:", status, error);
      }
    });
  });


  // $(document).on("click", ".creatvendormaster", function() {
  //     let $body = $("body");
  //     let routurl = $body.data("routurl");
  //     let key = $body.data("key");
  //     let categoryid = $("#SeedCategory").val();
  //     let mdid = "<?php echo $mdid ?? ""; ?>"; // Only works if this JS is inside PHP page
  //     if(dz){
  //       window.open(routurl + "krf-orderandsales/addNewVendor.php?" + $.param({k: key, mdid: mdid}));
  //     }else{
  //       $("#ansdotherModalContent").empty();
  //       $("#ansdotherModalContent").load(routurl + "krf-orderandsales/addNewVendor.php?" + 
  //           $.param({k: key, mdid: mdid, pctid: categoryid, dz: 11}),
  //           function(response, status, xhr){
  //               if(status === "error"){
  //                   alert("Error loading content: " + xhr.status + " " + xhr.statusText);
  //                   return;
  //               }
  //               $("#ansdotherModalheader").html("New Vendor/Supplyer Details");
  //               $("#ansdotherModal").modal("show");
  //           }
  //       );
  //     }
  // });

  $(document).on("click", ".creatvendormaster", function () {
    const $body = $("body");
    const routurl = $body.data("routurl");
    const key = $body.data("key");
    const categoryid = $("#SeedCategory").val() || "";
    const dz = $(this).data("dz") || 0; // ✅ FIXED
    const mdid = typeof MDID !== "undefined" ? MDID : ""; // safer than PHP echo
    if (!routurl || !key) {
      console.error("Missing route URL or key");
      return;
    }
    // 🔹 OPEN IN NEW TAB
    if (dz == 1) {

      window.open(
        routurl + "krf-orderandsales/addNewVendor.php?" +
        $.param({ k: key, mdid: mdid }),
        "_blank"
      );

    }
    // 🔹 LOAD INSIDE MODAL
    else {
      const $modal = $("#ansdotherModal");
      const $content = $("#ansdotherModalContent");
      $content.empty().html('<div class="text-center p-3">Loading...</div>');
      $content.load(
        routurl + "krf-orderandsales/addNewVendor.php?" +
        $.param({ k: key, mdid: mdid, pctid: categoryid, dz: 11 }),
        function (response, status, xhr) {
          if (status === "error") {
            Swal.fire({
              icon: "error",
              title: "Load Failed",
              text: xhr.status + " " + xhr.statusText
            });
            return;
          }
          $("#ansdotherModalheader").text("New Vendor / Supplier Details");
          $modal.modal("show");
        }
      );
    }
  });

</script>