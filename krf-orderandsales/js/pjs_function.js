
window.changeDateFormatYmd_dMY = function(dateStr) {
    if (dateStr === null || dateStr === undefined) return "";
    if (typeof dateStr !== "string") dateStr = String(dateStr);
    let cleanStr = dateStr.trim();
    if (!cleanStr) return "";
    if (cleanStr.includes(" ")) cleanStr = cleanStr.split(" ")[0];
    if (cleanStr.includes("T")) cleanStr = cleanStr.split("T")[0];
    if (!cleanStr.includes("-") && !cleanStr.includes("/")) return cleanStr;
    const separator = cleanStr.includes("-") ? "-" : "/";
    const dateParts = cleanStr.split(separator);
    if (dateParts.length < 3) return cleanStr;
    let year, month, day;
    if (dateParts[0].length === 4) {
        year = dateParts[0]; month = dateParts[1]; day = dateParts[2];
    } else if (dateParts[2].length === 4) {
        day = dateParts[0]; month = dateParts[1]; year = dateParts[2];
    } else {
        return cleanStr;
    }
    const monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
    let monthIndex = parseInt(month, 10) - 1;
    let monthName = isNaN(monthIndex) ? month : (monthNames[monthIndex] || month);
    if (day && day.length === 1) day = "0" + day;
    return `${day}-${monthName}-${year}`;
};

if (typeof window.changeDateFormatYmd_dMY_returndate !== 'function') {
    window.changeDateFormatYmd_dMY_returndate = function(dateStr) {
        return window.changeDateFormatYmd_dMY(dateStr);
    };
}


window.changeDateFormatYmd_dmY = function(dateStr) {
    if (dateStr === null || dateStr === undefined) return "";
    if (typeof dateStr !== "string") dateStr = String(dateStr);
    let cleanStr = dateStr.trim();
    if (!cleanStr) return "";
    if (cleanStr.includes(" ")) cleanStr = cleanStr.split(" ")[0];
    if (cleanStr.includes("T")) cleanStr = cleanStr.split("T")[0];
    if (!cleanStr.includes("-") && !cleanStr.includes("/")) return cleanStr;
    const separator = cleanStr.includes("-") ? "-" : "/";
    const dateParts = cleanStr.split(separator);
    if (dateParts.length < 3) return cleanStr;
    let year, month, day;
    if (dateParts[0].length === 4) {
        year = dateParts[0]; month = dateParts[1]; day = dateParts[2];
    } else if (dateParts[2].length === 4) {
        day = dateParts[0]; month = dateParts[1]; year = dateParts[2];
    } else {
        return cleanStr;
    }
    if (day && day.length === 1) day = "0" + day;
    if (month && month.length === 1) month = "0" + month;
    return `${day}-${month}-${year}`;
};

if (typeof window.formatDateDDMMYYYY !== "function") {
    window.formatDateDDMMYYYY = function(dateStr) {
        return window.changeDateFormatYmd_dmY(dateStr);
    };
}

function loadMasterStates(selectedState = '', selectedDistrict = '') {
    var routurl = $("body").data("routurl") || "../";
    var $stateSelect = $("#statename");
    if (!$stateSelect.length || !$stateSelect.is("select")) return;

    // If state is empty but district is provided, lookup state by district first
    if (!selectedState && selectedDistrict) {
        $.ajax({
            url: routurl + "krf-orderandsales/modal/client_modal.php?action=getStateByDistrict",
            type: "POST",
            dataType: "json",
            data: { district: selectedDistrict },
            success: function(info) {
                if (info && info.statename) {
                    loadMasterStates(info.statename, selectedDistrict);
                } else {
                    loadMasterStatesInternal(selectedState, selectedDistrict);
                }
            },
            error: function() {
                loadMasterStatesInternal(selectedState, selectedDistrict);
            }
        });
        return;
    }

    loadMasterStatesInternal(selectedState, selectedDistrict);
}

function loadMasterStatesInternal(selectedState, selectedDistrict) {
    var routurl = $("body").data("routurl") || "../";
    var $stateSelect = $("#statename");

    $.ajax({
        url: routurl + "krf-orderandsales/modal/client_modal.php?action=getStatesFromMaster",
        type: "POST",
        dataType: "json",
        success: function (states) {
            $stateSelect.empty().append('<option value="">Select State</option>');
            var matchedOptionValue = "";
            var matchedStateId = "";

            if (states && states.length > 0) {
                states.forEach(function (st) {
                    var isMatch = selectedState && (
                        st.statename.toLowerCase().trim() === String(selectedState).toLowerCase().trim() ||
                        String(st.stateid).trim() === String(selectedState).trim()
                    );
                    if (isMatch && !matchedOptionValue) {
                        matchedOptionValue = st.statename;
                        matchedStateId = st.stateid;
                    }
                    $stateSelect.append(`<option value="${st.statename}" data-stateid="${st.stateid}">${st.statename}</option>`);
                });
            }

            if (selectedState && !matchedOptionValue) {
                matchedOptionValue = selectedState;
                $stateSelect.append(new Option(selectedState, selectedState, true, true));
            }

            if ($.fn.select2) {
                $stateSelect.select2({ width: '100%', placeholder: 'Select State' });
            }

            if (matchedOptionValue) {
                $stateSelect.val(matchedOptionValue).trigger('change.select2');
                $("#stateid").val(matchedStateId);
                var stateId = matchedStateId || matchedOptionValue;
                loadMasterDistricts(stateId, selectedDistrict);
            } else {
                $("#stateid").val("");
                loadMasterDistricts('', selectedDistrict);
            }
        },
        error: function () {
            console.error("Failed to load states from master.");
        }
    });
}

function loadMasterDistricts(stateIdOrName, selectedDistrict = '') {
    var routurl = $("body").data("routurl") || "../";
    var $distSelect = $("#districtname");
    if (!$distSelect.length || !$distSelect.is("select")) return;

    if (!stateIdOrName) {
        $distSelect.empty().append('<option value="">Select District</option>');
        if ($.fn.select2) {
            $distSelect.select2({ width: '100%', placeholder: 'Select District' });
        }
        $("#districtid").val("");
        return;
    }

    $.ajax({
        url: routurl + "krf-orderandsales/modal/client_modal.php?action=getDistrictsFromMaster",
        type: "POST",
        dataType: "json",
        data: { state: stateIdOrName },
        success: function (districts) {
            $distSelect.empty().append('<option value="">Select District</option>');
            var matchedDistValue = "";
            var matchedDistrictId = "";

            if (districts && districts.length > 0) {
                districts.forEach(function (dist) {
                    var isMatch = selectedDistrict && (
                        dist.districtname.toLowerCase().trim() === String(selectedDistrict).toLowerCase().trim() ||
                        String(dist.districtid).trim() === String(selectedDistrict).trim()
                    );
                    if (isMatch && !matchedDistValue) {
                        matchedDistValue = dist.districtname;
                        matchedDistrictId = dist.districtid;
                    }
                    $distSelect.append(`<option value="${dist.districtname}" data-districtid="${dist.districtid}">${dist.districtname}</option>`);
                });
            }

            if (selectedDistrict && !matchedDistValue) {
                matchedDistValue = selectedDistrict;
                $distSelect.append(new Option(selectedDistrict, selectedDistrict, true, true));
            }

            if ($.fn.select2) {
                $distSelect.select2({ width: '100%', placeholder: 'Select District' });
            }

            if (matchedDistValue) {
                $distSelect.val(matchedDistValue).trigger('change.select2');
                $("#districtid").val(matchedDistrictId);
            } else {
                $("#districtid").val("");
            }
            updateAddressForNewClient();
        },
        error: function () {
            console.error("Failed to load districts from master.");
        }
    });
}

// Auto-focus search input when Select2 dropdown opens
$(document).on('select2:open', function (e) {
    var searchField = document.querySelector('.select2-container--open .select2-search__field');
    if (searchField) {
        searchField.focus();
    }
    setTimeout(function() {
        var input = document.querySelector('.select2-container--open .select2-search__field');
        if (input) {
            input.focus();
        }
    }, 50);
});

function getGeoSuffix() {
    var city = ($("#cityname").val() || "").trim();
    var tahseel = ($("#tahseel").val() || "").trim();
    var district = ($("#districtname").val() || "").trim();
    var state = ($("#statename").val() || "").trim();

    var parts = [];
    if (city) parts.push(city);
    if (tahseel && tahseel !== city) parts.push(tahseel);
    if (district && district !== city && district !== tahseel) parts.push(district);
    if (state && state !== district) parts.push(state);

    return parts.join(", ");
}

function updateAddressForNewClient() {
    var clientId = $("#clientid").val();
    if (clientId && clientId !== "none") {
        return; // Not applicable for already existing client
    }

    var $addr = $("#delivery_address");
    if (!$addr.length) return;

    var suffix = getGeoSuffix();
    var currentVal = $addr.val() || "";

    // Extract user portion (line 1 before newline)
    var userText = currentVal;
    if (userText.includes("\n")) {
        userText = userText.split("\n")[0];
    } else {
        var oldSuffix = $addr.data("geo-suffix") || "";
        if (oldSuffix && userText.endsWith(oldSuffix)) {
            userText = userText.slice(0, -oldSuffix.length).trim();
        } else if (suffix && userText.trim() === suffix.trim()) {
            userText = "";
        }
    }

    $addr.data("geo-suffix", suffix);

    if (suffix) {
        $addr.val(userText + "\n" + suffix);
    } else if (userText) {
        $addr.val(userText);
    }
}

// Update address on city, district, state change for new client
$(document).on('input change', '#cityname, #tahseel', function () {
    updateAddressForNewClient();
});

// Update stateid on state selection & sync address suffix
$(document).on('change', '#statename', function () {
    var stateVal = $(this).val();
    var stateId = $(this).find('option:selected').data('stateid') || '';
    $("#stateid").val(stateId);
    updateAddressForNewClient();
});

// Update districtid on district selection & sync address suffix
$(document).on('change', '#districtname', function () {
    var distVal = $(this).val();
    var distId = $(this).find('option:selected').data('districtid') || '';
    $("#districtid").val(distId);
    updateAddressForNewClient();
});

// Protect suffix on address textarea for new clients
$(document).on('keydown', '#delivery_address', function (e) {
    var clientId = $("#clientid").val();
    if (clientId && clientId !== "none") return;

    var suffix = getGeoSuffix();
    if (!suffix) return;

    var val = $(this).val() || "";
    var suffixWithNewline = "\n" + suffix;
    var userTextLength = val.indexOf(suffixWithNewline);
    if (userTextLength === -1) userTextLength = val.indexOf("\n");
    if (userTextLength === -1) userTextLength = Math.max(0, val.length - suffix.length);

    // Prevent Enter key so user only types on Line 1
    if (e.key === "Enter") {
        e.preventDefault();
        return;
    }

    // If cursor is on line 2 (suffix), move to line 1
    if (this.selectionStart > userTextLength) {
        this.setSelectionRange(userTextLength, userTextLength);
    }
});

$(document).on('input', '#delivery_address', function () {
    var clientId = $("#clientid").val();
    if (clientId && clientId !== "none") return;

    var suffix = getGeoSuffix();
    if (!suffix) return;

    var val = $(this).val() || "";
    var userText = val.includes("\n") ? val.split("\n")[0] : val;

    // Check if user accidentally erased the newline or suffix
    var expectedVal = userText + "\n" + suffix;
    if (val !== expectedVal) {
        var cursorPos = this.selectionStart;
        $(this).val(expectedVal);
        var newCursorPos = Math.min(cursorPos, userText.length);
        this.setSelectionRange(newCursorPos, newCursorPos);
    }
});

$(document).on('click focus', '#delivery_address', function () {
    var clientId = $("#clientid").val();
    if (clientId && clientId !== "none") return;

    var suffix = getGeoSuffix();
    if (!suffix) return;

    var val = $(this).val() || "";
    var userText = val.includes("\n") ? val.split("\n")[0] : "";
    if (this.selectionStart > userText.length) {
        this.setSelectionRange(userText.length, userText.length);
    }
});

function handleExistingClientSelection(element) { 
    const clientId = $(element).data("clientid");
    // -----------------------------
    // Reset client fields
    // -----------------------------
    $("#clientid, #clientname, #mobileno, #emailid, #cityname, #tahseel, #delivery_address, #stateid, #districtid")
        .val("")
        .prop("readonly", false);

    if ($("#statename").is("select")) {
        $("#statename").val("").trigger("change.select2");
    } else {
        $("#statename").val("");
    }
    if ($("#districtname").is("select")) {
        $("#districtname").val("").trigger("change.select2");
    } else {
        $("#districtname").val("");
    }

    $("#deliveryaddresstbl tbody").empty();
    // $("#deliveryaddressdiv").hide();

    if (!clientId || clientId === "none") return;

    // -----------------------------
    // Populate client data
    // -----------------------------
    $("#clientid").val(clientId);
    $("#clientname").val($(element).data("clientname") || "");
    $("#mobileno").val($(element).data("mobileno") || "");
    $("#emailid").val($(element).data("emailaddress") || "");
    $("#cityname").val($(element).data("cityname") || "");
    $("#tahseel").val($(element).data("tahseel") || "");
    $("#delivery_address").val($(element).data("address") || "");
    
    const clientState = $(element).data("statename") || $(element).data("stateid") || "";
    const clientDistrict = $(element).data("districtname") || $(element).data("districtid") || "";
    const clientStateId = $(element).data("stateid") || "";
    const clientDistrictId = $(element).data("districtid") || "";

    $("#stateid").val(clientStateId);
    $("#districtid").val(clientDistrictId);

    if ($("#statename").is("select")) {
        loadMasterStates(clientState, clientDistrict);
    } else {
        $("#statename").val(clientState);
        $("#districtname").val(clientDistrict);
    }

    const isDealer = $(element).data("isdealer") == 1 || $(element).data("customertype") === "Dealer";
    if ($("#customer_type").length === 0) {
        $("form").first().append('<input type="hidden" id="customer_type" name="customer_type" value="' + (isDealer ? 'Dealer' : 'Farmer') + '" />');
    } else {
        $("#customer_type").val(isDealer ? "Dealer" : "Farmer");
    }

    if (typeof window.validateMobileNumber === 'function') {
        window.validateMobileNumber();
    }
    if (typeof window.validateClientName === 'function') {
        window.validateClientName();
    }
    if (typeof window.updateStep1NextButtonState === 'function') {
        window.updateStep1NextButtonState();
    }

    // -----------------------------
    // Fetch delivery addresses (Disabled auto-append, user picks via modal)
    // -----------------------------
}



// ===============================
// Apply Checked Client (Loop)
// ===============================
function applyCheckedExistingClient() { 
    $("input[name='existclient']").each(function () {
        if ($(this).is(":checked")) {
            handleExistingClientSelection(this);
            return false; // stop after first checked
        }
    });
}

// _____________________end of delivery addredd get___________________________________

function GetAccountdetailsFrommaster_option(dropdownid, alreadyexistval = "", parentdivid = null) {
    var routurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    var subcompanyid = $("body").data("subcompanyid");
    var projectid = $("body").data("projectid");
    var loginid = $("body").data("loginid");
    const $dropdown = $("#" + dropdownid);
    const $parentDiv = parentdivid ? $("#" + parentdivid) : null;

    const form_data = new FormData();
    form_data.append("companyid", companyid);
    form_data.append("subcompanyid", subcompanyid);
    form_data.append("projectid", projectid);
    $.ajax({
        type: "POST",
        url: `${routurl}krf-orderandsales/modal/paymentadvice_modal.php?action=GetAccountdetailsFrommaster_option`,
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType: "JSON",
        success: function (res) {
            $dropdown.empty();
            if (Array.isArray(res) && res.length > 0) {
                $dropdown.append(`<option value="" selected disabled>Select Bank Account Name</option>`);

                res.forEach(item => {
                    $dropdown.append(`<option value="${item.accountid}">${item.bankname} [${item.accountno}]</option>`);
                });

                if (res.length === 1) {
                    $dropdown.val(res[0].accountid).trigger("change");
                    if ($parentDiv) $parentDiv.hide();
                }

                if (alreadyexistval) {
                    $dropdown.val(alreadyexistval).trigger("change");
                }
            }
        },
        error: function (xhr, status, error) {
            console.error("Error fetching account details:", error);
        }
    });
}

function GetClientDetailsFrommaster_Callback(sortby, sortbyvalue, callback) {
    var routurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    var subcompanyid = $("body").data("subcompanyid");
    var projectid = $("body").data("projectid");
    var form_data = new FormData();
    form_data.append("companyid", companyid);
    form_data.append("subcompanyid", subcompanyid);
    form_data.append("projectid", projectid);
    form_data.append("sortby", sortby);
    form_data.append("sortbyvalue", sortbyvalue);
    $.ajax({
        type: "POST",
        url: routurl + "krf-orderandsales/modal/client_modal.php?action=clientDetailsFrommaster",
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType: "JSON",
        success: function (res) {
            // console.log(res);
            callback(res);
        }
    });
}

function GetclientDetailsFrommaster(sortby, sortbyvalue, dropdownid, alreadyexistval = null, callback = null) {
    var routurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    var subcompanyid = $("body").data("subcompanyid");
    var projectid = $("body").data("projectid");
    var $dropdown = $("#" + dropdownid);

    var form_data = new FormData();
    form_data.append("companyid", companyid);
    form_data.append("subcompanyid", subcompanyid);
    form_data.append("projectid", projectid);
    form_data.append("sortby", sortby);
    form_data.append("sortbyvalue", sortbyvalue);
    $.ajax({
        type: "POST",
        url: routurl + "krf-orderandsales/modal/client_modal.php?action=clientDetailsFrommaster",
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType: "JSON",
        success: function (res) {
            // console.log(res);
            $dropdown.empty();
            var existclid = "";
            if (res && res.length > 0) {
                $dropdown.append("<option value='' selected>Select client name</option>");
                res.forEach(function (client) {
                    if (existclid == "") {
                        existclid = client.client_name == alreadyexistval ? client.client_id : '';
                    }
                    $dropdown.append(
                        "<option value='" + client.client_id +
                        "' data-inv_add='" + client.invoicing_address +
                        "' data-phones='" + client.contact_forpo +
                        "' data-email='" + client.email_forpo +
                        "' data-ctcperson='" + client.name_forpo +
                        "' data-currency='" + client.currency +
                        "' data-clientemail='" + client.client_email +
                        "' data-clientphoneno='" + client.phones +
                        "'>" + client.client_name + "</option>"
                    );
                });

                if (res.length === 1) {
                    $dropdown.val(res[0].client_id).trigger("change");
                }

                if (existclid) {
                    $dropdown.val(existclid).trigger("change");
                }
            } else {
                $dropdown.append("<option value='' selected>Client Not Available</option>");
            }

            if (callback && typeof callback === 'function') {
                callback(res);
            }
        },
        error: function (xhr, status, error) {
            // console.error("Error fetching client details:", error);
            $dropdown.empty().append("<option value='' selected>Error loading clients</option>");
        }
    });
}

function GetProductListFrommaster_option(dropdownid, alreadyexistval = "") {
    var routurl = $("body").data("routurl");
    var projectid = $("body").data("projectid");
    const $dropdown = $("#" + dropdownid);
    const form_data = new FormData();
    form_data.append("projectid", projectid);
    $.ajax({
        type: "POST",
        url: `${routurl}krf-orderandsales/modal/product_modal.php?action=ProductListFrommaster`,
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType: "JSON",
        success: function (res) {
            // console.log(res);
            $dropdown.empty();
            if (Array.isArray(res) && res.length > 0) {
                // Add default option if more than one item
                if (res.length > 1) {
                    $dropdown.append(`<option value="" selected disabled>Select product</option>`);
                }

                res.forEach(item => {
                    var rawName = item.ProductName || '';
                    var displayName = rawName.replace(/^(NonGrafted|Non-Grafted)\s*[-:]?\s*/i, '');
                    $dropdown.append(`<option value="${item.ProductID}">${displayName}</option>`);
                });

                // Auto-select if only one item
                if (res.length === 1) {
                    $dropdown.val(res[0].ProductID).trigger("change");
                }

                // Override with already existing value if provided
                if (alreadyexistval) {
                    $dropdown.val(alreadyexistval).trigger("change");
                }
            }
        },
        error: function (xhr, status, error) {
            console.error("Error fetching product categories:", error);
        }
    });
}

function GetProductListFrommaster_callback(callback) {
    var routurl = $("body").data("routurl");
    var projectid = $("body").data("projectid");
    const form_data = new FormData();
    form_data.append("projectid", projectid);
    $.ajax({
        type: "POST",
        url: `${routurl}krf-orderandsales/modal/product_modal.php?action=ProductListFrommaster`,
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType: "JSON",
        success: function (res) {
            callback(res);
        }
    });
}
function GetFullProductListFrommaster_callback(shortby, shortbyvalue, callback) {
    var routurl = $("body").data("routurl");
    var projectid = $("body").data("projectid");
    const form_data = new FormData();
    form_data.append("projectid", projectid);
    form_data.append("shortby", shortby);
    form_data.append("shortbyvalue", shortbyvalue);
    $.ajax({
        type: "POST",
        url: `${routurl}krf-orderandsales/modal/product_modal.php?action=GetFullProductListFrommaster`,
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType: "JSON",
        success: function (res) {
            callback(res);
        }
    });
}

function GetGrowthstages_callback(callback) {
    var routurl = $("body").data("routurl");
    var projectid = $("body").data("projectid");
    const form_data = new FormData();
    form_data.append("projectid", projectid);
    $.ajax({
        type: "POST",
        url: `${routurl}krf-orderandsales/modal/product_modal.php?action=GetGrowthstages`,
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType: "JSON",
        success: function (res) {
            callback(res);
        }
    });
}

function AddUpdateProductionGrowthhtml(productid, productgrowthid) {
    let companyid = $("body").data("companyid");
    let subcompanyid = $("body").data("subcompanyid");
    let projectid = $("body").data("projectid");
    let routurl = $("body").data("routurl");
    let key = $("body").data("key");
    window.location.href = routurl + "krf-orderandsales/addNewGrowthData.php?k=" + key + "&productid=" + productid + "&productgrowthid=" + productgrowthid + "&iframe=true";
    // $("#ProductGrowthModalViewContent").load(routurl+"krf-orderandsales/addNewGrowthData.php?k="+key+"&productid="+productid+"&productgrowthid="+productgrowthid+"&dz=11");
    // $("#ProductGrowthModalView").modal("show");
}

function RemoveProductMaster(productid, btn) {

    if (!productid || productid == "" || productid == 0) {
        Swal.fire({
            icon: "error",
            title: "Invalid Product",
            text: "Product ID is missing. Please try again."
        });
        return;
    }

    // 🔐 Confirmation Dialog
    Swal.fire({
        title: "Are you sure?",
        text: "This product will be permanently removed!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#3085d6",
        confirmButtonText: "Yes, delete it!"
    }).then((result) => {

        if (!result.isConfirmed) return;

        let routurl = $("body").data("routurl");
        let companyid = $("body").data("companyid");
        let subcompanyid = $("body").data("subcompanyid");
        let projectid = $("body").data("projectid");
        let loginid = $("body").data("loginid");
        let loginname = $("body").data("loginname");

        const form_data = new FormData();
        form_data.append("companyid", companyid);
        form_data.append("subcompanyid", subcompanyid);
        form_data.append("projectid", projectid);
        form_data.append("loginid", loginid);
        form_data.append("loginname", loginname);
        form_data.append("productid", productid);

        // ⏳ Loading State
        Swal.fire({
            title: "Please wait...",
            text: "Deleting product...",
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        $.ajax({
            type: "POST",
            url: `${routurl}krf-orderandsales/modal/product_modal.php?action=RemoveProductMaster`,
            data: form_data,
            contentType: false,
            processData: false,
            cache: false,
            dataType: "JSON",
            success: function (res) {

                if (res.status === "success") {

                    // ♻️ Remove Row Smoothly
                    let row = $(btn).closest("tr");
                    row.fadeOut(300, function () {
                        $(this).remove();
                    });

                    Swal.fire({
                        icon: "success",
                        title: "Deleted!",
                        text: res.message || "Product removed successfully.",
                        timer: 1500,
                        showConfirmButton: false
                    });

                } else {

                    Swal.fire({
                        icon: "error",
                        title: "Error",
                        text: res.message || "Something went wrong."
                    });

                }
            },
            error: function () {
                Swal.fire({
                    icon: "error",
                    title: "Server Error",
                    text: "Unable to process request. Please try again."
                });
            }
        });

    });
}





function GetProductListForGrafting_callback(callback) {
    var routurl = $("body").data("routurl");
    var projectid = $("body").data("projectid");
    const form_data = new FormData();
    form_data.append("projectid", projectid);
    $.ajax({
        type: "POST",
        url: `${routurl}krf-orderandsales/modal/grafting_modal.php?action=GetProductListForGrafting`,
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType: "JSON",
        success: function (res) {
            callback(res);
        }
    });
}

function GetProductCategoryFrommaster_option(dropdownid, alreadyexistval = "") {
    var routurl = $("body").data("routurl");
    var projectid = $("body").data("projectid");
    const $dropdown = $("#" + dropdownid);
    const form_data = new FormData();
    form_data.append("projectid", projectid);
    // alert(routurl);
    $.ajax({
        type: "POST",
        url: `${routurl}krf-orderandsales/modal/product_modal.php?action=ProductCategoryFrommaster`,
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType: "JSON",
        success: function (res) {
            // console.log(res);
            $dropdown.empty();
            if (Array.isArray(res) && res.length > 0) {
                // Add default option if more than one item
                if (res.length > 1) {
                    $dropdown.append(`<option value="" selected disabled>Select</option>`);
                }

                res.forEach(item => {
                    // Wrap data-hsncode in quotes
                    $dropdown.append(`<option value="${item.CategoryID}" data-hsncode="${item.HSNCode}">${item.CategoryName}</option>`);
                });

                // Auto-select if only one item
                if (res.length === 1) {
                    $dropdown.val(res[0].CategoryID).trigger("change");
                }

                // Override with already existing value if provided
                if (alreadyexistval) {
                    $dropdown.val(alreadyexistval).trigger("change");
                }
            } else {
                $dropdown.empty();
                $dropdown.append(`<option value="" disabled selected>No data available for seed</option>`);
                $dropdown.trigger("change");
            }
        }
    });
}

function GetProductSubCategoryFrommaster_option(dropdownid, shortby, Shortbyvalue, alreadyexistval = "") {
    var routurl = $("body").data("routurl");
    var projectid = $("body").data("projectid");
    const $dropdown = $("#" + dropdownid);
    const form_data = new FormData();
    form_data.append("projectid", projectid);
    form_data.append("shortby", shortby);
    form_data.append("Shortbyvalue", Shortbyvalue);

    // alert(routurl);
    $.ajax({
        type: "POST",
        url: `${routurl}krf-orderandsales/modal/product_modal.php?action=ProductSubCategoryFrommaster`,
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType: "JSON",
        success: function (res) {
            // console.log(res);
            $dropdown.empty();
            if (Array.isArray(res) && res.length > 0) {
                // Add default option if more than one item
                if (res.length > 1) {
                    $dropdown.append(`<option value="" selected disabled>Select</option>`);
                }
                res.forEach(item => {
                    // Wrap data-hsncode in quotes
                    $dropdown.append(`<option value="${item.SubCategoryID}" data-skucode="${item.sku}">${item.SubCategoryName}</option>`);
                });

                // Auto-select if only one item
                if (res.length === 1) {
                    $dropdown.val(res[0].SubCategoryID).trigger("change");
                }

                // Override with already existing value if provided
                if (alreadyexistval) {
                    $dropdown.val(alreadyexistval).trigger("change");
                }
            } else {
                $dropdown.empty();
                $dropdown.append(`<option value="" disabled selected>No data available for seed</option>`);
                $dropdown.trigger("change");
            }
        }
    });
}

function GetSeedDataOnlyOption(dropdownid, shortby, shortbyvalue, alreadyexistval = "") {
    var routurl = $("body").data("routurl");
    var projectid = $("body").data("projectid");
    const $dropdown = $("#" + dropdownid);
    const form_data = new FormData();
    form_data.append("projectid", projectid);
    form_data.append("shortby", shortby);
    form_data.append("shortbyvalue", shortbyvalue);
    $dropdown.empty();
    // alert(alreadyexistval);
    // alert(projectid+'=='+shortby+'=='+shortbyvalue);
    $.ajax({
        type: "POST",
        url: `${routurl}krf-orderandsales/modal/seed_modal.php?action=SeedDataOnlyOption`,
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType: "JSON",
        success: function (res) {
            // console.log(res);
            $dropdown.empty();
            if (Array.isArray(res) && res.length > 0) {
                // Add default option if more than one item
                if (res.length > 1) {
                    $dropdown.append(`<option value="" selected disabled>Select seed</option>`);
                }

                res.forEach(item => {
                    // Wrap data-hsncode in quotes
                    // $dropdown.append(`<option value="${item.SeedID}" data-seedname="${item.SeedName}" data-categoryid="${item.CategoryID}" data-subcategoryid="${item.SubCategoryID}" data-varietyname="${item.VarietyofSeed}" data-vendorid="${item.VendorID}" data-sku="${item.sku}">${item.sku}</option>`);
                    $dropdown.append(`<option value="${item.SeedID}" data-seedname="${item.SeedName}" data-categoryid="${item.CategoryID}" data-subcategoryid="${item.SubCategoryID}" data-varietyname="${item.VarietyofSeed}" data-vendorid="${item.VendorID}" data-sku="${item.sku}">${item.SeedName + " - " + item.VarietyofSeed + ", (" + item.CompanyName + ")"}</option>`);
                });

                // Auto-select if only one item
                if (res.length === 1) {
                    $dropdown.val(res[0].CategoryID).trigger("change");
                }

                // Override with already existing value if provided
                if (alreadyexistval) {
                    $dropdown.val(alreadyexistval).trigger("change");
                }
            } else {
                $dropdown.empty();
                $dropdown.append(`<option value="" disabled selected>No data available for seed</option>`);
                $dropdown.trigger("change");
            }
        }
    });
}

function GetVendorDataOnlyOption(dropdownid, vendorid) {
    var routurl = $("body").data("routurl");
    var projectid = $("body").data("projectid");
    const $dropdown = $("#" + dropdownid);
    const form_data = new FormData();
    form_data.append("projectid", projectid);
    form_data.append("vendorid", vendorid);
    $.ajax({
        type: "POST",
        url: `${routurl}krf-orderandsales/modal/vendorModal.php?action=VendorDataOnlyOption`,
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType: "JSON",
        success: function (res) {
            // console.log(res);
            $dropdown.empty();
            if (Array.isArray(res) && res.length > 0) {
                // Add default option if more than one item
                if (res.length > 1) {
                    $dropdown.append(`<option value="" selected disabled>Select Vendor</option>`);
                }

                res.forEach(item => {
                    // Wrap data-hsncode in quotes
                    $dropdown.append(`<option value="${item.VendorID}">${item.VendorName}</option>`);
                });

                // Auto-select if only one item
                if (res.length === 1) {
                    $dropdown.val(res[0].VendorID).trigger("change");
                }

                // Override with already existing value if provided
                if (vendorid) {
                    $dropdown.val(vendorid).trigger("change");
                }
            }
        }
    });
}

function GetSuggestedSKUSeed(CategoryID, SubCategoryID, VarietyName, TypeOfSeed, BrandName = '', Inputid, SeedID = '') {
    var routurl = $("body").data("routurl");
    var projectid = $("body").data("projectid");
    const form_data = new FormData();
    form_data.append("projectid", projectid);
    form_data.append("CategoryID", CategoryID);
    form_data.append("SubCategoryID", SubCategoryID);
    form_data.append("VarietyName", VarietyName);
    form_data.append("TypeOfSeed", TypeOfSeed);
    form_data.append("BrandName", BrandName);
    if (SeedID) {
        form_data.append("SeedID", SeedID);
    }
    $.ajax({
        type: "POST",
        url: `${routurl}krf-orderandsales/modal/seed_modal.php?action=GetSuggestedSKUSeed`,
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType: "JSON",
        success: function (res) {
            // console.log(res);
            if (res && res.status == "success") {
                $("#" + Inputid).val(res.sku);
            } else if (res && res.message) {
                console.warn("SKU generation note:", res.message);
            }
        },
        error: function(xhr, status, error) {
            console.error("SKU generation request failed:", error);
        }
    });
}

function GetSuggestedProductSKU(SeedID, IsGraftedstatus, Inputid) {
    var routurl = $("body").data("routurl");
    var projectid = $("body").data("projectid");
    const form_data = new FormData();
    form_data.append("projectid", projectid);
    form_data.append("SeedID", SeedID);
    form_data.append("IsGraftedstatus", IsGraftedstatus);
    $.ajax({
        type: "POST",
        url: `${routurl}krf-orderandsales/modal/product_modal.php?action=GetSuggestedProductSKU`,
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType: "JSON",
        success: function (res) {
            console.log(res);
            $("#" + Inputid).val(res);
        }
    });
}

function GetProvisionaOrderLikebyUserName(clientName, callback) {
    var routurl = $('body').data('routurl');
    var companyid = $('body').data('companyid');
    var subcompanyid = $('body').data('subcompanyid');
    var projectid = $('body').data('projectid');

    const formData = new FormData();
    formData.append('companyid', companyid);
    formData.append('subcompanyid', subcompanyid);
    formData.append('projectid', projectid);
    formData.append('clientName', clientName);

    $.ajax({
        type: 'POST',
        url: `${routurl}krf-orderandsales/modal/paymentadvice_modal.php?action=ProvisionaOrderLikebyUserName`,
        data: formData,
        contentType: false,
        processData: false,
        cache: false,
        dataType: 'JSON',
        success: function (res) {
            callback(res);
        }
    });
}

function GetProvisionaOrderData(shortby, shortbyvalue, callback) {
    var routurl = $('body').data('routurl');
    var companyid = $('body').data('companyid');
    var subcompanyid = $('body').data('subcompanyid');
    var projectid = $('body').data('projectid');

    const formData = new FormData();
    formData.append('companyid', companyid);
    formData.append('subcompanyid', subcompanyid);
    formData.append('projectid', projectid);
    formData.append('shortby', shortby);
    formData.append('shortbyvalue', shortbyvalue);
    $.ajax({
        type: 'POST',
        url: `${routurl}krf-orderandsales/modal/paymentadvice_modal.php?action=GetProvisionaOrderData`,
        data: formData,
        contentType: false,
        processData: false,
        cache: false,
        dataType: 'JSON',
        success: function (res) {
            callback(res);
        }
    });
}

function UnallocatedAmounttoAllocat(unallocatedbsno, toallocatedbsno) {
    var routurl = $('body').data('routurl');
    var companyid = $('body').data('companyid');
    var subcompanyid = $('body').data('subcompanyid');
    var projectid = $('body').data('projectid');
    var loginid = $('body').data('loginid');

    const formData = new FormData();
    formData.append('companyid', companyid);
    formData.append('subcompanyid', subcompanyid);
    formData.append('projectid', projectid);
    formData.append('loginid', loginid);
    formData.append('unallocatedbsno', unallocatedbsno);
    formData.append('toallocatedbsno', toallocatedbsno);

    $.ajax({
        type: 'POST',
        url: `${routurl}krf-orderandsales/modal/paymentadvice_modal.php?action=UnallocatedAmounttoAllocat`,
        data: formData,
        contentType: false,
        processData: false,
        cache: false,
        dataType: 'JSON',
        success: function (res) {
            console.log(res);
            // callback(res);
            if (res.status == "success") {
                Swal.fire({
                    text: res.message,
                    icon: "success",
                    confirmButtonColor: "#3085d6",
                    confirmButtonText: "Refresh",
                    allowOutsideClick: false, // prevent closing by clicking outside
                    allowEscapeKey: false     // optional: prevent closing with ESC key
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.reload();
                    }
                });
            } else {
                Swal.fire({
                    text: res.message || "Failed to allocate advance payment.",
                    icon: "error",
                    confirmButtonColor: "#d33"
                });
            }
        },
        error: function (xhr, status, error) {
            Swal.fire({
                text: "An error occurred while processing: " + error,
                icon: "error",
                confirmButtonColor: "#d33"
            });
        }
    });
}

function submitOldOrderAdvancePayment() {
    let psono = $("#oop_psono").val();
    let clientid = $("#oop_clientid").val();
    let clientname = $("#oop_clientname").val();
    let clientmobileno = $("#oop_clientmobileno").val();
    let paymentdate = $("#oop_paymentdate").val();
    let amount = parseFloat($("#oop_amount").val() || 0);
    let bookingno = $("#oop_bookingno").val() ? $("#oop_bookingno").val().trim() : '';
    let sourceofpayment = $("#oop_sourceofpayment").val();
    let receivedtoaccountid = $("#oop_receivedtoaccountid").val();
    let utrnumber = $("#oop_utrnumber").val() ? $("#oop_utrnumber").val().trim() : '';
    let casgreceivedby = $("#oop_casgreceivedby").val() ? $("#oop_casgreceivedby").val().trim() : '';
    let casgreceuvelocation = $("#oop_casgreceuvelocation").val() ? $("#oop_casgreceuvelocation").val().trim() : '';
    let purposeofamount = $("#oop_purpose").val() ? $("#oop_purpose").val().trim() : 'Advance Payment';
    let remarks = $("#oop_remarks").val() ? $("#oop_remarks").val().trim() : '';
    let fileInput = document.getElementById("oop_paymentreceipt");
    let bookingReceiptInput = document.getElementById("oop_bookingreceipt");

    if (!psono) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({ icon: 'error', title: 'Error', text: 'Provisional Order number missing.' });
        } else {
            alert('Provisional Order number missing.');
        }
        return;
    }
    if (!paymentdate) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({ icon: 'warning', title: 'Payment Date Required', text: 'Please select the payment date.' });
        } else {
            alert('Please select the payment date.');
        }
        return;
    }
    if (!amount || amount <= 0) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({ icon: 'warning', title: 'Invalid Amount', text: 'Please enter a valid payment amount greater than 0.' });
        } else {
            alert('Please enter a valid payment amount greater than 0.');
        }
        return;
    }

    let companyid = $("body").data("companyid") || "";
    let subcompanyid = $("body").data("subcompanyid") || "";
    let projectid = $("body").data("projectid") || "";
    let loginid = $("#hidden_loginid").val() || $("body").data("loginid") || "";
    let loginname = $("body").data("loginname") || "";
    let routurl = $("body").data("routurl") || "";

    let formData = new FormData();
    formData.append("companyid", companyid);
    formData.append("subcompanyid", subcompanyid);
    formData.append("projectid", projectid);
    formData.append("loginid", loginid);
    formData.append("loginname", loginname);
    formData.append("invoiceno", psono);
    formData.append("psono", psono);
    formData.append("clientid", clientid);
    formData.append("clientname", clientname);
    formData.append("clientmobileno", clientmobileno);
    formData.append("amount", amount);
    formData.append("paymentdate", paymentdate);
    formData.append("bookingno", bookingno);
    formData.append("sourceofpayment", sourceofpayment);
    formData.append("receivedtoaccountid", receivedtoaccountid);
    formData.append("utrnumber", utrnumber);
    formData.append("casgreceivedby", casgreceivedby);
    formData.append("casgreceuvelocation", casgreceuvelocation);
    formData.append("purposeofamount", purposeofamount);
    formData.append("remarks", remarks);

    if (fileInput && fileInput.files && fileInput.files.length > 0) {
        formData.append("paymentreceipt", fileInput.files[0]);
    }
    if (bookingReceiptInput && bookingReceiptInput.files && bookingReceiptInput.files.length > 0) {
        formData.append("bookingreceipt", bookingReceiptInput.files[0]);
    }

    let $btn = $("#btnSubmitOldOrderPayment");
    $btn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm me-1"></span> Processing...');

    $.ajax({
        url: `${routurl}krf-orderandsales/modal/paymentadvice_modal.php?action=oldorderadvacepayment`,
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        cache: false,
        dataType: "json",
        success: function (res) {
            $btn.prop("disabled", false).html('<i class="fa fa-check-circle me-1"></i> Submit Payment Update');
            if (res && res.status === "success") {
                $("#oldOrderPaymentModal").modal("hide");
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: "success",
                        title: "Payment Updated!",
                        text: res.message || "Advance payment updated successfully.",
                        confirmButtonColor: "#10b981",
                        confirmButtonText: "OK"
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    alert(res.message || "Advance payment updated successfully.");
                    window.location.reload();
                }
            } else {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: "error",
                        title: "Failed to Update Payment",
                        text: (res && res.message) ? res.message : "An unexpected error occurred."
                    });
                } else {
                    alert((res && res.message) ? res.message : "An unexpected error occurred.");
                }
            }
        },
        error: function (xhr, status, error) {
            $btn.prop("disabled", false).html('<i class="fa fa-check-circle me-1"></i> Submit Payment Update');
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: "error",
                    title: "Request Error",
                    text: "Could not connect to server: " + error
                });
            } else {
                alert("Could not connect to server: " + error);
            }
        }
    });
}

function CheckProductNameExist(productname) {
    var routurl = $('body').data('routurl');
    var companyid = $('body').data('companyid');
    var subcompanyid = $('body').data('subcompanyid');
    var projectid = $('body').data('projectid');
    var loginid = $('body').data('loginid');


    const formData = new FormData();
    formData.append('companyid', companyid);
    formData.append('subcompanyid', subcompanyid);
    formData.append('projectid', projectid);
    formData.append('productname', productname);

    $.ajax({
        type: 'POST',
        url: `${routurl}krf-orderandsales/modal/product_modal.php?action=CheckProductNameExist`,
        data: formData,
        contentType: false,
        processData: false,
        cache: false,
        dataType: 'JSON',
        success: function (res) {
            // console.log(res);
            // callback(res);
            if (res.status == "success") {
                Swal.fire({
                    text: res.message,
                    icon: "success",
                    showDenyButton: true,
                    confirmButtonColor: "#3085d6",
                    confirmButtonText: "Refresh",
                    allowOutsideClick: true, // prevent closing by clicking outside
                    allowEscapeKey: true     // optional: prevent closing with ESC key
                }).then((result) => {
                    if (result.isConfirmed) {
                        // window.location.reload();
                    }
                });
            }
        }
    });
}

function getsowingstockOrderdatawithdates(datefrom, dateto, callback) {
    var routurl = $('body').data('routurl');
    var companyid = $('body').data('companyid');
    var subcompanyid = $('body').data('subcompanyid');
    var projectid = $('body').data('projectid');
    var loginid = $('body').data('loginid');

    const formData = new FormData();
    formData.append('companyid', companyid);
    formData.append('subcompanyid', subcompanyid);
    formData.append('projectid', projectid);
    formData.append('datefrom', datefrom);
    formData.append('dateto', dateto);

    // Show loading indicator
    Swal.fire({
        title: 'Loading Sowing Plan...',
        html: 'Please wait while data is being fetched.',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: function () {
            Swal.showLoading();
        }
    });

    $.ajax({
        type: 'POST',
        url: `${routurl}krf-orderandsales/modal/product_modal.php?action=getsowingstockOrderdatawithdates`,
        data: formData,
        contentType: false,
        processData: false,
        cache: false,
        dataType: 'JSON',
        success: function (res) {
            Swal.close();
            callback(res);
        },
        error: function (xhr) {
            if (xhr.status === 200 && xhr.responseText) {
                try {
                    var parsed = JSON.parse(xhr.responseText.trim().replace(/^\uFEFF/, ''));
                    Swal.close();
                    callback(parsed);
                    return;
                } catch (e) {}
            }
            Swal.fire({
                icon: 'error',
                title: 'Failed to Load',
                text: 'Unable to fetch sowing plan data. Please try again.',
                timer: 3000,
                showConfirmButton: false
            });
            console.error('getsowingstockOrderdatawithdates error:', xhr.responseText);
        }
    });
}

function getsowingschedulewithdates(datefrom, dateto, callback) {
    var routurl = $('body').data('routurl');
    var companyid = $('body').data('companyid');
    var subcompanyid = $('body').data('subcompanyid');
    var projectid = $('body').data('projectid');
    var loginid = $('body').data('loginid');

    const formData = new FormData();
    formData.append('companyid', companyid);
    formData.append('subcompanyid', subcompanyid);
    formData.append('projectid', projectid);
    formData.append('datefrom', datefrom);
    formData.append('dateto', dateto);

    // Show loading indicator
    Swal.fire({
        title: 'Loading Sowing Plan...',
        html: 'Please wait while data is being fetched.',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: function () {
            Swal.showLoading();
        }
    });

    $.ajax({
        type: 'POST',
        url: `${routurl}krf-orderandsales/modal/sowing_modal.php?action=GetProductionStockOrderDataWithDate`,
        data: formData,
        contentType: false,
        processData: false,
        cache: false,
        dataType: 'JSON',
        success: function (res) {
            Swal.close();
            callback(res);
        },
        error: function (xhr) {
            if (xhr.status === 200 && xhr.responseText) {
                try {
                    var parsed = JSON.parse(xhr.responseText.trim().replace(/^\uFEFF/, ''));
                    Swal.close();
                    callback(parsed);
                    return;
                } catch (e) {}
            }
            Swal.fire({
                icon: 'error',
                title: 'Failed to Load',
                text: 'Unable to fetch sowing plan data. Please try again.',
                timer: 3000,
                showConfirmButton: false
            });
            console.error('GetProductionStockOrderDataWithDate error:', xhr.responseText);
        }
    });
}

function GetProductionStockOrderDataWithDate(datefrom, dateto, callback) {
    // // debugger;
    var routurl = $('body').data('routurl');
    var companyid = $('body').data('companyid');
    var subcompanyid = $('body').data('subcompanyid');
    var projectid = $('body').data('projectid');
    var loginid = $('body').data('loginid');

    const formData = new FormData();
    formData.append('companyid', companyid);
    formData.append('subcompanyid', subcompanyid);
    formData.append('projectid', projectid);
    formData.append('datefrom', datefrom);
    formData.append('dateto', dateto);

    // Show loading indicator
    Swal.fire({
        title: 'Loading Sowing Plan...',
        html: 'Please wait while data is being fetched.',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: function () {
            Swal.showLoading();
        }
    });

    $.ajax({
        type: 'POST',
        url: `${routurl}krf-orderandsales/modal/sowing_modal.php?action=GetProductionStockOrderDataWithDate`,
        data: formData,
        contentType: false,
        processData: false,
        cache: false,
        dataType: 'JSON',
        success: function (res) {
            // console.log(res);
            Swal.close();
            callback(res);
        },
        error: function (xhr) {
            if (xhr.status === 200 && xhr.responseText) {
                try {
                    var parsed = JSON.parse(xhr.responseText.trim().replace(/^\uFEFF/, ''));
                    Swal.close();
                    callback(parsed);
                    return;
                } catch (e) {}
            }
            Swal.fire({
                icon: 'error',
                title: 'Failed to Load',
                text: 'Unable to fetch sowing plan data. Please try again.',
                timer: 3000,
                showConfirmButton: false
            });
            console.error('GetProductionStockOrderDataWithDate error:', xhr.responseText);
        }
    });
}


function GetPendingAllocationGBoxesDataWithDate(datefrom, dateto, callback) {
    var routurl = $('body').data('routurl') || '';
    var companyid = (typeof currentCompanyId !== 'undefined' && currentCompanyId) ? currentCompanyId : ($('body').data('companyid') || $('#hidden_companyid').val() || 0);
    var subcompanyid = (typeof currentSubCompanyId !== 'undefined' && currentSubCompanyId) ? currentSubCompanyId : ($('body').data('subcompanyid') || $('#hidden_subcompanyid').val() || 0);
    var projectid = (typeof currentProjectId !== 'undefined' && currentProjectId) ? currentProjectId : ($('body').data('projectid') || $('#hidden_projectid').val() || 0);
    var loginid = $('body').data('loginid');

    const formData = new FormData();
    formData.append('companyid', companyid);
    formData.append('subcompanyid', subcompanyid);
    formData.append('projectid', projectid);
    formData.append('datefrom', datefrom || 'all');
    formData.append('dateto', dateto || 'all');

    var apiUrl = 'modal/product_modal.php?action=GetPendingAllocationGBoxesDataWithDate';
    if (routurl && window.location.pathname.indexOf('krf-orderandsales') === -1) {
        apiUrl = (routurl.endsWith('/') ? routurl : routurl + '/') + 'krf-orderandsales/modal/product_modal.php?action=GetPendingAllocationGBoxesDataWithDate';
    }

    $.ajax({
        type: 'POST',
        url: apiUrl,
        data: formData,
        contentType: false,
        processData: false,
        cache: false,
        dataType: 'JSON',
        success: function (res) {
            callback(res);
        },
        error: function(xhr, status, error) {
            console.error("GetPendingAllocationGBoxesDataWithDate failed:", status, error);
            callback([]);
        }
    });
}

function PolyhouseTrayUsageDetailsFromPolyhousePlant(polyhouseid, callback) {
    var routurl = $('body').data('routurl');
    var companyid = $('body').data('companyid');
    var subcompanyid = $('body').data('subcompanyid');
    var projectid = $('body').data('projectid');
    var loginid = $('body').data('loginid');

    const formData = new FormData();
    formData.append('companyid', companyid);
    formData.append('subcompanyid', subcompanyid);
    formData.append('projectid', projectid);
    formData.append('polyhouseid', polyhouseid);
    $.ajax({
        type: 'POST',
        url: `${routurl}krf-orderandsales/modal/polihouse_modal.php?action=PolyhouseTrayUsageDetailsFromPolyhousePlant`,
        data: formData,
        contentType: false,
        processData: false,
        cache: false,
        dataType: 'JSON',
        success: function (res) {
            // console.log(res);
            callback(res);
        }
    });
}

function PolyhouseCapacitydetailswithLOCPOL(locationid, polyhouseid = '', callback) {
    var routurl = $('body').data('routurl');
    var companyid = $('body').data('companyid');
    var subcompanyid = $('body').data('subcompanyid');
    var projectid = $('body').data('projectid');
    var loginid = $('body').data('loginid');

    const formData = new FormData();
    formData.append('companyid', companyid);
    formData.append('subcompanyid', subcompanyid);
    formData.append('projectid', projectid);
    formData.append('locationid', locationid);
    formData.append('polyhouseid', polyhouseid);
    $.ajax({
        type: 'POST',
        url: `${routurl}krf-orderandsales/modal/polihouse_modal.php?action=PolyhouseCapacitydetails`,
        data: formData,
        contentType: false,
        processData: false,
        cache: false,
        dataType: 'JSON',
        success: function (res) {
            // console.log(res);
            callback(res);
        }
    });
}

function renderPolyhouseCapacityDetails(data, displaydivid) {
    const html = `
        <div class="polyhouse-capacity-card">
            <div class="ph-header">
                <h4>Polyhouse Capacity Overview</h4>
                <span class="ph-structure">${data.structure_type}</span>
            </div>

            <div class="ph-stats">
                <div class="ph-box total">
                    <span>Total Tray Capacity</span>
                    <strong>${data.total_tray_capacity}</strong>
                </div>

                <div class="ph-box used">
                    <span>Used Trays (Received)</span>
                    <strong>${data.total_tray_exists}</strong>
                </div>

                <div class="ph-box allocated">
                    <span>Allocated (Not Received)</span>
                    <strong>${data.allocated_not_received || 0}</strong>
                </div>

                <div class="ph-box remaining">
                    <span>Remaining Tray Capacity</span>
                    <strong>${data.remaining_total_tray}</strong>
                </div>
            </div>
        </div>
    `;
    $("#" + displaydivid).html(html);
}


function GetPendingTrayAllocationofGBoxes(callback) {
    var routurl = $('body').data('routurl');
    var companyid = $('body').data('companyid');
    var subcompanyid = $('body').data('subcompanyid');
    var projectid = $('body').data('projectid');
    var loginid = $('body').data('loginid');

    const formData = new FormData();
    formData.append('companyid', companyid);
    formData.append('subcompanyid', subcompanyid);
    formData.append('projectid', projectid);
    $.ajax({
        type: 'POST',
        url: `${routurl}krf-orderandsales/modal/product_modal.php?action=GetPendingTrayAllocationofGBoxes`,
        data: formData,
        contentType: false,
        processData: false,
        cache: false,
        dataType: 'JSON',
        success: function (res) {
            // console.log(res);
            callback(res);
        }
    });
}

function generateSeedLotCardsByStockOrder(stockOrderNo, roottype = "Root") {

    if (!stockOrderNo) {
        alertmessage("warning", "Stock Order No missing");
        return;
    }
    $("#sowingQRCodeModal").modal("show").data("stockorderno", stockOrderNo).data("roottype", roottype);
    $("#seedlotdetailsdiv").html("");
    const routurl = $("body").data("routurl");
    const projectid = $("body").data("projectid");
    $.ajax({
        url: `${routurl}krf-orderandsales/modal/sowing_modal.php?action=getsowinglotdetailswithstockno`,
        type: "POST",
        dataType: "JSON",
        data: { projectid, stockOrderNo, roottype },
        success: function (res) {
            // console.log(res);
            if (!Array.isArray(res) || res.length === 0) {
                $("#seedlotdetailsdiv").html(
                    `<div class="alert alert-warning">No sowing lots found</div>`
                );
                return;
            }
            let currentRoleId = 0;
            let rawRole1 = $('body').data('loginroleid') ?? $('body').attr('data-loginroleid') ?? $('#hidden_loginroleid').val() ?? (typeof loginroleid !== 'undefined' ? loginroleid : undefined) ?? (typeof window.loginroleid !== 'undefined' ? window.loginroleid : undefined);
            if (rawRole1 !== undefined && rawRole1 !== null && rawRole1 !== '') {
                let parsed = parseInt(rawRole1, 10);
                if (!isNaN(parsed)) currentRoleId = parsed;
            }
            const canManageDamageAndBatch = (currentRoleId < 3);

            let html = "";
            res.forEach(item => {
                const bgClass = getNextBGClass();
                const traycount = parseInt(item.tray_count) || 0;
                const traycavity = parseInt(item.traycavity) || 0;
                const ttlseed = traycount * traycavity;
                let headerLabels = "";
                if (item.sowingbatchno) {
                    headerLabels += `<span class="badge bg-info text-dark me-2">Batch: ${item.sowingbatchno}</span>`;
                }
                if (item.sowingdateinternal) {
                    const formattedDate = changeDateFormatYmd_dMY(item.sowingdateinternal);
                    headerLabels += `<span class="badge bg-success text-white">SW Date: ${formattedDate}</span>`;
                }
                html += `
                <div class="seedLotCard card shadow-sm p-3 mb-3 ${bgClass}">
                    <div class="row g-3">
                        <div class="col-md-12">${headerLabels}</div>
                        <div class="col-md-3">
                            <label><b>Seed Lot Number</b></label>
                            <input type="text" class="form-control"
                                value="${item.lotno}" readonly>
                        </div>

                        <div class="col-md-2">
                            <label><b>Seed Qty</b></label>
                            <input type="text" class="form-control"
                                value="${ttlseed}" readonly>
                        </div>

                        <div class="col-md-2">
                            <label><b>QR Code Qty</b></label>
                            <input type="text" class="form-control QRCodeQtyofLotno"
                                value="${traycount}" readonly>
                        </div>

                        <div class="col-md-5 d-flex align-items-end gap-2 flex-wrap">

                            <button class="btn btn-primary SowingPrintViewQrBtn d-none"
                                data-qrcodedata='${JSON.stringify(item.tray_details)}'
                                data-vtraycount="${traycount}"
                                data-lotno="${item.lotno}">
                                View / Remove QR
                            </button>

                            ${canManageDamageAndBatch ? `
                            <button class="btn btn-primary me-2 ConfirmSowingQrcodeBtn" data-qrcodedata='${JSON.stringify(item.tray_details || [])}'
                                    data-vtraycount="${traycount}" data-lotno="${item.lotno}" title="Remove Damaged / Destroyed Trays">
                                    Remove Damage QR
                            </button>
                            ` : ''}

                            <button class="btn btn-success PrintSavedQrBtn"
                                data-lotno="${item.lotno}"
                                data-sowingtraycount="${item.sowingtraycount}"
                                data-productname="${item.product_name}"
                                data-seedid="${item.seedid}"
                                data-seedname="${item.seedname}"
                                data-vtraycount="${traycount}"
                                data-stockorderno="${item.stockorder_no}"
                                data-qrcodedata='${JSON.stringify(item.tray_details)}'
                                data-sowindateinternal="${item.sowingdateinternal}"
                                data-dispatchdateinternal="${item.dispatchdateinternal}"
                                data-deliverydate="${item.expected_delivery_date}"
                                data-sowingdate="${item.sowing_date}"
                                data-sowinglotno_internal="${item.sowinglotno_internal}">
                                Print QR
                            </button>

                            ${canManageDamageAndBatch ? `
                            <button type="button" class="btn btn-danger DeleteSowingBatchBtn"
                                data-stockorderno="${item.stockorder_no}"
                                data-sowingbatchno="${item.sowingbatchno || ''}"
                                data-lotno="${item.lotno}"
                                data-roottype="${item.roottype || roottype}"
                                data-traycount="${traycount}"
                                title="Remove this batch and all its generated tray QR codes">
                                <i class="fa fa-trash-alt me-1"></i> Remove Whole Batch Tray
                            </button>
                            ` : ''}
                        </div>

                    </div>
                </div>`;
            });
            $("#seedlotdetailsdiv").html(html);
            calculateRemainingQRCode();

            // Refresh parent table if it exists
            if (typeof loadSowingScheduleStarting === "function") {
                loadSowingScheduleStarting($("#sowingweekSelector").val() || "current");
            }
        },

        error: function () {
            $("#seedlotdetailsdiv").html(
                `<div class="alert alert-danger">Server error while loading lots</div>`
            );
        }
    });
}

// Handler for removing a generated Sowing Batch / Lot Box
$(document).on("click", ".DeleteSowingBatchBtn", function () {
    const $btn = $(this);
    const stockorderno = $btn.data("stockorderno") || $("#sowingQRCodeModal").data("stockorderno") || "";
    const sowingbatchno = $btn.data("sowingbatchno") || "";
    const lotno = $btn.data("lotno") || "";
    const roottype = $btn.data("roottype") || $("#sowingQRCodeModal").data("roottype") || "Root";
    const traycount = $btn.data("traycount") || 0;
    const routurl = $("body").data("routurl") || "";
    const projectid = $("body").data("projectid") || 0;

    let currentRoleId = 0;
    let rawRole2 = $('body').data('loginroleid') ?? $('body').attr('data-loginroleid') ?? $('#hidden_loginroleid').val() ?? (typeof loginroleid !== 'undefined' ? loginroleid : undefined) ?? (typeof window.loginroleid !== 'undefined' ? window.loginroleid : undefined);
    if (rawRole2 !== undefined && rawRole2 !== null && rawRole2 !== '') {
        let parsed = parseInt(rawRole2, 10);
        if (!isNaN(parsed)) currentRoleId = parsed;
    }

    if (currentRoleId >= 3) {
        Swal.fire({
            icon: "error",
            title: "Access Denied",
            text: "Only authorized managers (Role < 3) can remove a whole batch tray."
        });
        return;
    }

    let batchDisplay = sowingbatchno ? `Batch #${sowingbatchno}` : `Lot #${lotno}`;

    Swal.fire({
        title: "Remove Whole Batch Tray?",
        html: `Are you sure you want to remove <b>${batchDisplay}</b> (Lot: <b>${lotno}</b>)?<br><br><span class="text-danger fw-bold">All ${traycount} generated tray QR records will be removed from Sowing.</span>`,
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#dc3545",
        cancelButtonColor: "#6c757d",
        confirmButtonText: '<i class="fa fa-trash-alt me-1"></i> Yes, Remove Whole Batch Tray',
        cancelButtonText: "Cancel",
        allowOutsideClick: false
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: "Removing Batch...",
                text: "Please wait while we update records.",
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: `${routurl}krf-orderandsales/modal/sowing_modal.php?action=RemoveSowingBatchLot`,
                type: "POST",
                dataType: "JSON",
                data: {
                    projectid: projectid,
                    stockorderno: stockorderno,
                    sowingbatchno: sowingbatchno,
                    lotno: lotno,
                    roottype: roottype,
                    loginroleid: currentRoleId,
                    loginid: $("body").data("loginid") || ""
                },
                success: function (res) {
                    Swal.close();
                    if (res && res.status === "success") {
                        Swal.fire({
                            icon: "success",
                            title: "Batch Removed",
                            text: res.message || "Batch removed successfully!",
                            timer: 2000,
                            showConfirmButton: false
                        });

                        // Re-render the remaining lot cards
                        generateSeedLotCardsByStockOrder(stockorderno, roottype);

                        // Refresh parent table if it exists
                        if (typeof loadSowingScheduleStarting === "function") {
                            loadSowingScheduleStarting($("#sowingweekSelector").val() || "current");
                        }
                    } else {
                        Swal.fire("Cannot Remove", (res && res.message) ? res.message : "Failed to remove batch.", "error");
                    }
                },
                error: function (xhr, status, error) {
                    Swal.close();
                    Swal.fire("Error", "Server error while removing batch: " + error, "error");
                }
            });
        }
    });
});

function sowingtrysofgerminationbox(stockorderno, roottype = "Root", displaydivid) {

    if (!stockorderno) {
        alertmessage("warning", "Stock Order No missing");
        return;
    }
    $("#germpackingQRCodeModal").data("stockorderno", stockorderno).data("roottype", roottype);
    const routurl = $("body").data("routurl");
    const projectid = $("body").data("projectid");
    $.ajax({
        url: `${routurl}krf-orderandsales/modal/sowing_modal.php?action=sowingtrysofgerminationbox`,
        type: "POST",
        dataType: "JSON",
        data: { projectid, stockorderno, roottype },
        success: function (res) {
            console.log("sowingtrysofgerminationbox response:", res);
            if (!Array.isArray(res) || res.length === 0) {
                $("#" + displaydivid).html(
                    `<div class="alert alert-warning border-0 shadow-sm text-center py-4" style="background: #fff9db; color: #856404; border-radius: 12px;">
                        <i class="fa fa-exclamation-triangle fa-2x mb-2"></i>
                        <p class="mb-0 fw-semibold">No germinated box batches found for this order.</p>
                     </div>`
                );
                return;
            }

            let bgCounter = 1;
            function getNextBGClass() {
                let cls = `lot-bg-${bgCounter}`;
                bgCounter = (bgCounter % 3) + 1;
                return cls;
            }

            let html = "";
            let boxno = 1;
            res.forEach(item => {
                const bgClass = getNextBGClass();
                const totalBoxes = item.boxes ? item.boxes.length : 0;
                let headerLabels = "";
                if (item.boxbatchno) {
                    headerLabels += `<span class="badge bg-primary text-white px-3 py-2 me-2 shadow-sm" style="font-size: 0.9rem; font-weight: 600;"><i class="fa fa-folder-open me-2"></i>Batch ${boxno}: ${item.boxbatchno}</span>`;
                }
                if (item.packingdate) {
                    const boxpdate = changeDateFormatYmd_dMY(item.packingdate);
                    headerLabels += `<span class="badge bg-warning text-dark px-3 py-2 me-2 shadow-sm" style="font-size: 0.9rem;"><i class="fa fa-calendar me-2"></i>Box PK Date: ${boxpdate}</span>`;
                }
                if (item.openingdate) {
                    const boxopeningdate = changeDateFormatYmd_dMY(item.openingdate);
                    headerLabels += `<span class="badge bg-warning text-dark px-3 py-2 me-2 shadow-sm" style="font-size: 0.9rem;"><i class="fa fa-calendar me-2"></i>Box OP Date: ${boxopeningdate}</span>`;
                }
                // packingdate
                // openingdate

                // Get current product name from modal header to use in printing
                const SeedName = item.SeedName;
                const safeSeedName = (SeedName || '').replace(/"/g, '&quot;');
                const batchKey = item.boxbatchno ? String(item.boxbatchno).replace(/[^a-zA-Z0-9_-]/g, '_') : ('batch_' + boxno);

                if (totalBoxes > 0) {
                    headerLabels += `<button type="button" class="btn btn-sm btn-success text-white px-3 py-2 shadow-sm btn-toggle-batch-boxes" 
                        data-batchtarget="batchBoxesCollapse_${batchKey}"
                        style="font-size: 0.9rem; font-weight: 600; border-radius: 8px; border: none; cursor: pointer; transition: all 0.2s ease; display: inline-flex; align-items: center;"
                        title="Click to view and print individual boxes">
                        <i class="fa fa-box me-2"></i><span>${totalBoxes} Boxes</span>
                        <i class="fa fa-chevron-down ms-2 toggle-icon" style="font-size: 0.8rem; transition: transform 0.2s;"></i>
                    </button>`;
                } else {
                    headerLabels += `<span class="badge bg-secondary text-white px-3 py-2 shadow-sm" style="font-size: 0.9rem;"><i class="fa fa-box me-2"></i>0 Boxes</span>`;
                }

                if (item.batch_status) {
                    let stBadgeBg = "bg-info text-dark";
                    const bStLow = String(item.batch_status).toLowerCase();
                    if (bStLow.includes("polyhouse") || bStLow.includes("allocated")) {
                        stBadgeBg = "bg-secondary text-white";
                    } else if (bStLow.includes("receivedaftersowing")) {
                        stBadgeBg = "bg-primary text-white";
                    }
                    headerLabels += `<span class="badge ${stBadgeBg} px-3 py-2 ms-2 shadow-sm" style="font-size: 0.9rem;"><i class="fa fa-tag me-1"></i>${item.batch_status}</span>`;
                }

                // Determine if removal is allowed
                const statusStr = String(item.batch_status || item.status || "").toLowerCase().replace(/[\s_-]/g, "");
                const isRemovable = Boolean(item.can_remove) || 
                                    statusStr.includes("receivedaftersowing") || 
                                    statusStr.includes("pendingallocation");

                let removeBtnHtml = "";
                if (item.boxbatchno && item.boxbatchno !== "unbatched") {
                    if (isRemovable) {
                        removeBtnHtml = `
                            <button type="button" class="btn btn-danger remove-germbox-batch-btn shadow-sm px-3 py-2 ms-2"
                                style="background: linear-gradient(135deg, #ef4444, #dc2626); border: none; font-weight: 600; border-radius: 10px;"
                                data-boxbatchno="${item.boxbatchno}"
                                data-stockorderno="${stockorderno}"
                                data-roottype="${item.roottype || roottype}"
                                title="Remove Germination Box Batch">
                                <i class="fa fa-trash me-1"></i> Remove
                            </button>`;
                    } else {
                        removeBtnHtml = `
                            <button type="button" class="btn btn-secondary shadow-sm px-3 py-2 ms-2 disabled"
                                style="background: #94a3b8; border: none; font-weight: 600; border-radius: 10px; cursor: not-allowed; opacity: 0.75;"
                                title="Cannot remove: Batch status is '${item.batch_status || 'Processed'}'. Only boxes in 'ReceivedAfterSowing' or 'PendingAllocation' can be removed.">
                                <i class="fa fa-lock me-1"></i> ${item.batch_status || 'Locked'}
                            </button>`;
                    }
                }

                let boxesTableHtml = "";
                if (item.boxes && item.boxes.length > 0) {
                    let rowsHtml = "";
                    item.boxes.forEach((b, idx) => {
                        const pkDateStr = b.boxpackingdate ? changeDateFormatYmd_dMY(b.boxpackingdate) : (item.packingdate ? changeDateFormatYmd_dMY(item.packingdate) : '-');
                        const opDateStr = b.boxopeningdate ? changeDateFormatYmd_dMY(b.boxopeningdate) : (item.openingdate ? changeDateFormatYmd_dMY(item.openingdate) : '-');
                        const bStatus = b.status || item.batch_status || 'ReceivedAfterSowing';
                        let bStBadge = "bg-info text-dark";
                        const bStLow = String(bStatus).toLowerCase();
                        if (bStLow.includes("polyhouse") || bStLow.includes("allocated")) {
                            bStBadge = "bg-secondary text-white";
                        } else if (bStLow.includes("receivedaftersowing")) {
                            bStBadge = "bg-primary text-white";
                        }

                        const singleBoxData = [{
                            boxid: b.boxid,
                            qrcode: b.qrcode,
                            boxpackingdate: b.boxpackingdate || item.packingdate,
                            boxopeningdate: b.boxopeningdate || item.openingdate,
                            traycountinbox: b.traycountinbox
                        }];

                        rowsHtml += `
                        <tr class="align-middle" style="background: #ffffff;">
                            <td class="text-center">
                                <input type="checkbox" class="form-check-input single-box-checkbox" 
                                    data-batchtarget="batchBoxesCollapse_${batchKey}"
                                    data-boxid="${b.boxid || ''}"
                                    data-qrcode="${b.qrcode}"
                                    data-packingdate="${b.boxpackingdate || item.packingdate || ''}"
                                    data-openingdate="${b.boxopeningdate || item.openingdate || ''}"
                                    data-traycount="${b.traycountinbox || ''}"
                                    style="cursor: pointer; width: 1.15em; height: 1.15em;">
                            </td>
                            <td class="text-center fw-semibold text-muted">${idx + 1}</td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border px-2 py-1 font-monospace" style="font-size: 0.88rem; letter-spacing: 0.5px;">
                                    <i class="fa fa-qrcode text-primary me-1"></i>${b.qrcode}
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-primary border border-primary px-2 py-1 fw-bold">${b.traycountinbox || '0'} Trays</span>
                            </td>
                            <td class="text-center text-nowrap">${pkDateStr}</td>
                            <td class="text-center text-nowrap fw-semibold text-success">${opDateStr}</td>
                            <td class="text-center">
                                <span class="badge ${bStBadge} px-2 py-1" style="font-size: 0.82rem;">${bStatus}</span>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-outline-success btn-sm print-single-box-btn py-1 px-2 shadow-sm"
                                    data-boxdata="${encodeURIComponent(JSON.stringify(singleBoxData))}"
                                    data-productionorderno="${stockorderno}"
                                    data-seedname="${safeSeedName}"
                                    title="Print single QR label for ${b.qrcode}">
                                    <i class="fa fa-print me-1"></i>Print
                                </button>
                            </td>
                        </tr>`;
                    });

                    boxesTableHtml = `
                    <div class="batch-boxes-collapse mt-3 pt-3 border-top" id="batchBoxesCollapse_${batchKey}" style="display: none;">
                        <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                            <div class="d-flex align-items-center gap-2">
                                <span class="fw-bold text-dark" style="font-size: 0.92rem;">
                                    <i class="fa fa-boxes text-success me-1"></i> Individual Boxes in Batch ${item.boxbatchno || boxno}
                                </span>
                                <span class="badge bg-light text-muted border px-2 py-1 batch-selection-status" style="font-size: 0.82rem;">
                                    0 of ${totalBoxes} selected
                                </span>
                            </div>
                            <div>
                                <button type="button" class="btn btn-primary btn-sm print-selected-boxes-btn shadow-sm px-3 py-1"
                                    data-batchtarget="batchBoxesCollapse_${batchKey}"
                                    data-productionorderno="${stockorderno}"
                                    data-seedname="${safeSeedName}"
                                    style="display: none; background: linear-gradient(135deg, #4e73df, #2e59d9); border: none; font-weight: 600; border-radius: 8px;">
                                    <i class="fa fa-print me-1"></i> Print Selected QR (<span class="selected-num">0</span>)
                                </button>
                            </div>
                        </div>
                        <div class="table-responsive rounded border shadow-sm bg-white">
                            <table class="table table-hover table-striped table-sm mb-0">
                                <thead class="table-light text-secondary text-uppercase" style="font-size: 0.78rem;">
                                    <tr>
                                        <th class="text-center" style="width: 45px;">
                                            <input type="checkbox" class="form-check-input select-all-batch-boxes" 
                                                data-batchtarget="batchBoxesCollapse_${batchKey}" 
                                                title="Select / Deselect All Boxes"
                                                style="cursor: pointer; width: 1.15em; height: 1.15em;">
                                        </th>
                                        <th class="text-center" style="width: 50px;">#</th>
                                        <th class="text-center">Box QR Code</th>
                                        <th class="text-center">Trays In Box</th>
                                        <th class="text-center">Packing Date</th>
                                        <th class="text-center">Opening Date</th>
                                        <th class="text-center">Status</th>
                                        <th class="text-center" style="width: 100px;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${rowsHtml}
                                </tbody>
                            </table>
                        </div>
                    </div>`;
                }

                html += `
                <div class="genratedboxqrcode card shadow-sm p-4 mb-4 ${bgClass}" style="border-radius: 16px; border: none; border-left: 6px solid #4e73df;">
                    <div class="row align-items-center g-3">
                        <div class="col-md-7 d-flex flex-wrap align-items-center gap-2">${headerLabels}</div>
                        <div class="col-md-5 text-md-end text-start d-flex justify-content-md-end align-items-center flex-wrap gap-2">
                            <button class="btn btn-success readytoprintgBoxbtntd shadow-sm px-4 py-2" 
                                style="background: linear-gradient(135deg, #1cc88a, #17a673); border: none; font-weight: 600; border-radius: 10px;"
                                data-gboxdata="${encodeURIComponent(JSON.stringify(item.boxes))}"
                                data-productionorderno="${stockorderno}"
                                data-seedname="${safeSeedName}">
                                <i class="fa fa-print me-2"></i> Print Batch QR
                            </button>
                            ${removeBtnHtml}
                        </div>
                    </div>
                    ${boxesTableHtml}
                </div>`;
                boxno++;
            });
            $("#" + displaydivid).html(html);
        },

        error: function () {
            $("#" + displaydivid).html(
                `<div class="alert alert-danger border-0 shadow-sm text-center py-4" style="background: #f8d7da; color: #721c24; border-radius: 12px;">
                    <i class="fa fa-exclamation-circle fa-2x mb-2"></i>
                    <p class="mb-0 fw-semibold">Server error while loading germinated box batches.</p>
                 </div>`
            );
        }
    });
}

function pendingsowingtrysofgerminationbox(stockorderno, roottype = "Root") {

    if (!stockorderno) {
        alertmessage("warning", "Stock Order No missing");
        return;
    }
    const routurl = $("body").data("routurl");
    const projectid = $("body").data("projectid");
    $.ajax({
        url: `${routurl}krf-orderandsales/modal/sowing_modal.php?action=pendingsowingtrysofgerminationbox`,
        type: "POST",
        dataType: "JSON",
        data: { projectid, stockorderno, roottype },
        success: function (res) {
            // console.log(res);
            // alert(res.length);
            // newboxqrcodediv
            if (res.length > 0) {
                $("#newboxqrcodediv").show();
                $("#boxqrcodemsg").html('');
                $("#pendingsowingTrayQtypck").html(res.length);
                if ($("#traycountinbox").val()) {
                    $("#traycountinbox").trigger("input");
                }
            } else {
                $("#newboxqrcodediv").hide();
                $("#boxqrcodemsg").html(`<div class="alert alert-danger">There are not any single tray for germination box</div>`);
            }
        },

        error: function () {
            $("#boxqrcodemsg").html(`<div class="alert alert-danger">Server error while loading lots</div>`);
        }
    });
}


function calculateRemainingQRCode() {
    const totalTrays = parseInt($("#TraysRequired").val(), 10) || 0;
    const trayCavity = parseInt(
        $("#sowingtraytype option:selected").data("cavitycount"),
        10
    ) || 0;

    let usedTrays = 0;

    $(".QRCodeQtyofLotno").each(function () {
        const v = parseInt($(this).val(), 10);
        if (!isNaN(v) && v > 0) {
            usedTrays += v;
        }
    });

    // Raw remaining (can be negative)
    const rawRemainingTrays = totalTrays - usedTrays;

    // Display-safe remaining
    const displayRemainingTrays = Math.max(0, rawRemainingTrays);
    const remainingQty = displayRemainingTrays * trayCavity;

    const $qrLbl = $("#resttoprintQrCode");
    const $qtyLbl = $("#pendingSowingqtylbl");
    const $btn = $(".SaveQRCodeDetailsBtn");
    $("#AddLotNoRow").show();
    if (displayRemainingTrays <= 0) {
        $("#AddLotNoRow").hide();
    }
    // Update UI text
    $qrLbl.text(displayRemainingTrays);
    $qtyLbl.text(remainingQty);

    // Reset classes
    $qrLbl.removeClass("text-success text-warning text-danger");
    $qtyLbl.removeClass("text-success text-warning text-danger");

    // Decision logic
    if (rawRemainingTrays < 0) {
        // ❌ Over limit — block saving
        $qrLbl.addClass("text-danger");
        $qtyLbl.addClass("text-danger");
        $btn.prop("disabled", true);
    } else if (rawRemainingTrays === 0) {
        // ✅ Perfect match
        $qrLbl.addClass("text-success");
        $qtyLbl.addClass("text-success");
        $btn.prop("disabled", false);
    } else {
        // ⚠ Still remaining
        $qrLbl.addClass("text-warning");
        $qtyLbl.addClass("text-warning");
        $btn.prop("disabled", false);
    }
}


var bgIndex = 1;  // 1 → 2 → 3 → repeat
function getNextBGClass() {
    bgIndex++;
    if (bgIndex > 3) bgIndex = 1;
    return "lot-bg-" + bgIndex;
}

function GetActveSowingdates(callback) {
    var routurl = $('body').data('routurl');
    var companyid = $('body').data('companyid');
    var subcompanyid = $('body').data('subcompanyid');
    var projectid = $('body').data('projectid');
    var loginid = $('body').data('loginid');

    const formData = new FormData();
    formData.append('companyid', companyid);
    formData.append('subcompanyid', subcompanyid);
    formData.append('projectid', projectid);
    $.ajax({
        type: 'POST',
        url: `${routurl}krf-orderandsales/modal/product_modal.php?action=GetActveSowingdates`,
        data: formData,
        contentType: false,
        processData: false,
        cache: false,
        dataType: 'JSON',
        success: function (res) {
            // console.log(res);
            callback(res);
        }
    });
}

function getExpectedDelivertDateandSowingDate(productid, callback) {
    let projectid = $("body").data("projectid");
    // 🔴 Validation
    if (!productid) {
        Swal.fire({
            icon: "warning",
            title: "Product Not Selected",
            text: "Please select a product to fetch sowing and delivery dates."
        });
        return;
    }

    let formData = new FormData();
    formData.append("projectid", projectid);
    formData.append("productid", productid);
    $.ajax({
        url: "modal/stockverification.php?action=fecthgrowingdate",
        type: "POST",
        data: formData,
        dataType: "json",
        contentType: false,
        processData: false,
        cache: false,

        success: function (res) {
            if (res && Object.keys(res).length > 0) {
                callback(res);
            } else {
                Swal.fire({
                    icon: "info",
                    title: "No Data Found",
                    text: "Growing date information is not available for this product."
                });
            }
        },
        error: function () {
            Swal.fire({
                icon: "error",
                title: "Request Failed",
                text: "Unable to fetch growing date details. Please try again."
            });
        }
    });
}



function GetPendingAllocationGerminationdates(callback) {
    var routurl = $('body').data('routurl');
    var companyid = $('body').data('companyid');
    var subcompanyid = $('body').data('subcompanyid');
    var projectid = $('body').data('projectid');
    var loginid = $('body').data('loginid');

    const formData = new FormData();
    formData.append('companyid', companyid);
    formData.append('subcompanyid', subcompanyid);
    formData.append('projectid', projectid);
    $.ajax({
        type: 'POST',
        url: `${routurl}krf-orderandsales/modal/product_modal.php?action=GetPendingAllocationGerminationdates`,
        data: formData,
        contentType: false,
        processData: false,
        cache: false,
        dataType: 'JSON',
        success: function (res) {
            // console.log(res);
            callback(res);
        }
    });
}

function RemoveQRCodeofsowingTray(stockorderno, qrcode, el = null) {
    const routurl = $("body").data("routurl");
    const companyid = $("body").data("companyid");
    const subcompanyid = $("body").data("subcompanyid");
    const projectid = $("body").data("projectid");
    const loginid = $("body").data("loginid");

    const formData = new FormData();
    formData.append("companyid", companyid);
    formData.append("subcompanyid", subcompanyid);
    formData.append("projectid", projectid);
    formData.append("loginid", loginid);
    formData.append("stockorderno", stockorderno);
    formData.append("qrcode", qrcode);

    $.ajax({
        type: "POST",
        url: `${routurl}krf-orderandsales/modal/sowing_modal.php?action=RemoveQRCodeofsowingTray`,
        data: formData,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (res) {
            if (res.status === "success") {
                Swal.fire({
                    icon: "success",
                    title: "Removed",
                    text: res.message,
                    timer: 1500,
                    showConfirmButton: false
                });
                // ✅ Remove row from UI
                if (el) {
                    $(el).closest("tr").fadeOut(300, function () {
                        $(this).remove();
                    });
                }
            } else if (res.status === "warning") {
                Swal.fire({
                    icon: "warning",
                    title: "Warning",
                    text: res.message
                });
            } else {
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: res.message || "Something went wrong"
                });
            }
        },

        error: function (xhr) {
            Swal.fire(
                "Server Error",
                xhr.responseText || "Request failed",
                "error"
            );
        }
    });
}


function SaveAndUpdateSowingDataForQRCodeGenrate(pstockorderno, productid, orderqty, failurebufferpecent, sowingquantity, trayid, seedid, seedlotno, traycount, roottype, callback) {
    var routurl = $('body').data('routurl');
    var companyid = $('body').data('companyid');
    var subcompanyid = $('body').data('subcompanyid');
    var projectid = $('body').data('projectid');
    var loginid = $('body').data('loginid');

    const formData = new FormData();
    formData.append('companyid', companyid);
    formData.append('subcompanyid', subcompanyid);
    formData.append('projectid', projectid);
    formData.append('loginid', loginid);
    formData.append('pstockorderno', pstockorderno);
    formData.append('productid', productid);
    formData.append('orderqty', orderqty);
    formData.append('failurebufferpecent', failurebufferpecent);
    formData.append('sowingquantity', sowingquantity);
    formData.append('trayid', trayid);
    formData.append('seedid', seedid);
    formData.append('seedlotno', seedlotno);
    formData.append('traycount', traycount);
    formData.append('roottype', roottype);
    // alert(roottype);
    $.ajax({
        type: 'POST',
        url: `${routurl}krf-orderandsales/modal/sowing_modal.php?action=SaveAndUpdateSowingDataForQRCodeGenrate`,
        data: formData,
        contentType: false,
        processData: false,
        cache: false,
        dataType: 'JSON',
        success: function (res) {
            // console.log(res);
            callback(res);
        },
        error: function (xhr, status, error) {
            var msg = "Server error or connection failed.";
            if (xhr.responseJSON && xhr.responseJSON.message) {
                msg = xhr.responseJSON.message;
            } else if (xhr.responseText) {
                try {
                    var parsed = JSON.parse(xhr.responseText);
                    if (parsed.message) msg = parsed.message;
                } catch (e) {
                    msg = "Server returned status: " + xhr.status + " " + (error || "");
                }
            }
            callback({
                status: "error",
                message: msg
            });
        }
    });
}

function updateConfirmedTrayCount(params) {
    // debugger;
    var routurl = $('body').data('routurl');
    var companyid = $('body').data('companyid');
    var subcompanyid = $('body').data('subcompanyid');
    var projectid = $('body').data('projectid');
    var loginid = $('body').data('loginid');

    let currentRoleId = 0;
    if (typeof loginroleid !== 'undefined' && loginroleid !== null && loginroleid !== '') {
        currentRoleId = parseInt(loginroleid, 10);
    } else if ($('#hidden_loginroleid').length && $('#hidden_loginroleid').val() !== '') {
        currentRoleId = parseInt($('#hidden_loginroleid').val(), 10);
    } else if ($('body').data('loginroleid') !== undefined && $('body').data('loginroleid') !== '') {
        currentRoleId = parseInt($('body').data('loginroleid'), 10);
    } else if (typeof window.loginroleid !== 'undefined') {
        currentRoleId = parseInt(window.loginroleid, 10);
    }

    return $.ajax({
        url: `${routurl}krf-orderandsales/modal/sowing_modal.php?action=UpdateConfirmationSowingData`,
        type: "POST",
        dataType: "json",
        data: {
            companyid: companyid,
            subcompanyid: subcompanyid,
            projectid: projectid,
            stockorderno: params.stockorderno,
            lotno: params.lotno,
            total_trays: params.total,
            confirmed_trays: params.confirmed,
            damaged_trays: params.damaged,
            loginid: loginid,
            loginroleid: currentRoleId
        },
        beforeSend: function () {
            $("#ConfirmUpdateSowingTrayoslot").prop("disabled", true);
        }
    });
}


function SaveAndUpdateGerminationData(pstockorderno, ProductId, ProductName, traycountinabox, dateofpacking, dateofopening, countboxqrcodetoprint, roottype, callback) {

    const routurl = $('body').data('routurl');
    const companyid = $('body').data('companyid');
    const subcompanyid = $('body').data('subcompanyid');
    const projectid = $('body').data('projectid');
    const loginid = $('body').data('loginid');

    const boxCount = parseInt(countboxqrcodetoprint, 10) || 0;
    const traysPerBox = parseInt(traycountinabox, 10) || 0;

    /* 🔴 BASIC VALIDATION */
    if (!pstockorderno || !ProductId || traysPerBox <= 0 || !dateofpacking || !dateofopening || boxCount <= 0) {
        Swal.fire({
            icon: "warning",
            title: "Missing Information",
            text: "Please fill all required fields and enter valid box count before saving."
        });
        return;
    }

    const packingDate = new Date(dateofpacking);
    const openingDate = new Date(dateofopening);

    /* 🔴 DATE VALIDATION */
    if (openingDate < packingDate) {
        Swal.fire({
            icon: "error",
            title: "Invalid Opening Date",
            text: "Box opening date cannot be earlier than packing date."
        });
        return;
    }

    const totalTraysToPack = boxCount * traysPerBox;
    const saveBtn = $("#saveGerminationDetailsBtnpck");
    const originalBtnHtml = saveBtn.data("original-html") || saveBtn.html();
    saveBtn.data("original-html", originalBtnHtml);

    /* 🟡 RECONFIRMATION DIALOG WITH EXACT BOX COUNT */
    Swal.fire({
        icon: "question",
        title: "Confirm Germination Box Details",
        html: `
            <div style="
                background:#f8f9fa;
                border-radius:12px;
                padding:16px;
                text-align:left;
                font-size:14px;
            ">
                <div style="display:flex;align-items:center;margin-bottom:10px;">
                    <span style="font-size:22px;margin-right:8px;">📦</span>
                    <div>
                        <b style="font-size:15px;color:#1e293b;">Germination Packing Confirmation</b>
                        <div style="font-size:12px;color:#64748b;">Order: ${pstockorderno} | ${ProductName}</div>
                    </div>
                </div>

                <hr style="margin:8px 0;border-color:#e2e8f0;">

                <div style="display:flex;justify-content:space-between;margin-bottom:8px;align-items:center;">
                    <span>📦 <b>Full Boxes to Generate:</b></span>
                    <span class="badge bg-primary fs-6 px-3 py-1" style="font-weight:700;">${boxCount} Full Box${boxCount > 1 ? 'es' : ''}</span>
                </div>

                <div style="display:flex;justify-content:space-between;margin-bottom:6px;">
                    <span>📥 Trays Per Box:</span>
                    <span style="font-weight:600;color:#334155;">${traysPerBox} trays/box</span>
                </div>

                <div style="display:flex;justify-content:space-between;margin-bottom:6px;">
                    <span>🌱 Total Trays to Pack:</span>
                    <span style="font-weight:600;color:#16a34a;">${totalTraysToPack} trays</span>
                </div>

                <div style="display:flex;justify-content:space-between;margin-bottom:6px;">
                    <span>📅 Packing Date:</span>
                    <span style="font-weight:600;color:#0d6efd;">${dateofpacking}</span>
                </div>

                <div style="display:flex;justify-content:space-between;margin-bottom:12px;">
                    <span>📅 Opening Date:</span>
                    <span style="font-weight:600;color:#16a34a;">${dateofopening}</span>
                </div>

                <div style="
                    background:#eff6ff;
                    border-left:4px solid #3b82f6;
                    padding:10px;
                    border-radius:6px;
                    font-size:13px;
                    color:#1e40af;
                ">
                    ℹ️ Only <b>${boxCount} full boxes</b> (${totalTraysToPack} trays) will be generated. Extra partial boxes will not be created.
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: "✅ Yes, Save Details",
        cancelButtonText: "❌ Cancel",
        confirmButtonColor: "#198754",
        cancelButtonColor: "#6c757d",
        width: 500
    }).then((result) => {
        if (!result.isConfirmed) {
            // Restore button state if cancelled
            saveBtn.prop("disabled", false).html(originalBtnHtml);
            return;
        }

        /* 🟢 PROCEED WITH SAVE & DISABLE BUTTON UNTIL SERVER RESPONSE */
        saveBtn.prop("disabled", true).html('<i class="fa fa-spinner fa-spin me-1"></i> Saving...');

        const formData = new FormData();
        formData.append('companyid', companyid);
        formData.append('subcompanyid', subcompanyid);
        formData.append('projectid', projectid);
        formData.append('loginid', loginid);
        formData.append('pstockorderno', pstockorderno);
        formData.append('ProductId', ProductId);
        formData.append('ProductName', ProductName);
        formData.append('traycountinabox', traysPerBox);
        formData.append('countboxqrcodetoprint', boxCount);        
        formData.append('dateofpacking', dateofpacking);
        formData.append('dateofopening', dateofopening);
        formData.append('roottype', roottype);

        $.ajax({
            type: "POST",
            url: `${routurl}krf-orderandsales/modal/sowing_modal.php?action=SaveAndUpdateGerminationDataForQRCodeGenrate`,
            data: formData,
            contentType: false,
            processData: false,
            cache: false,
            dataType: "json",
            success: function (res) {
                // Re-enable button on response
                saveBtn.prop("disabled", false).html(originalBtnHtml);
                if (typeof callback === 'function') {
                    callback(res);
                }
            },
            error: function () {
                // Re-enable button on error
                saveBtn.prop("disabled", false).html(originalBtnHtml);
                Swal.fire({
                    icon: "error",
                    title: "Server Error",
                    text: "Unable to save germination data. Please try again."
                });
            }
        });
    });
}


function GetProductionStockOrderData(yearValue, monthValue, callback) {
    var routurl = $('body').data('routurl');
    var companyid = $('body').data('companyid');
    var subcompanyid = $('body').data('subcompanyid');
    var projectid = $('body').data('projectid');
    var loginid = $('body').data('loginid');
    const formData = new FormData();
    formData.append('companyid', companyid);
    formData.append('subcompanyid', subcompanyid);
    formData.append('projectid', projectid);
    formData.append('yearValue', yearValue);
    formData.append('monthValue', monthValue);

    $.ajax({
        type: 'POST',
        url: `${routurl}krf-orderandsales/modal/product_modal.php?action=GetProductionStockOrderData`,
        data: formData,
        contentType: false,
        processData: false,
        cache: false,
        dataType: 'JSON',
        success: function (res) {
            // console.log(res);
            callback(res);
        }
    });
}

function GetProductionStockOrderYearMonthlist(callback) {
    var routurl = $('body').data('routurl');
    var companyid = $('body').data('companyid');
    var subcompanyid = $('body').data('subcompanyid');
    var projectid = $('body').data('projectid');

    const formData = new FormData();
    formData.append('companyid', companyid);
    formData.append('subcompanyid', subcompanyid);
    formData.append('projectid', projectid);

    $.ajax({
        type: 'POST',
        url: `${routurl}krf-orderandsales/modal/product_modal.php?action=GetProductionStockOrderYearMonthlist`,
        data: formData,
        contentType: false,
        processData: false,
        cache: false,
        dataType: 'JSON',
        success: function (res) {
            // console.log(res);
            callback(res);
        }
    });
}

function downloadOrderPDF(psono, isConfirmed, sono) {
    var routurl = $('body').data('routurl') || '';
    var companyid = $('body').data('companyid') || '';
    var subcompanyid = $('body').data('subcompanyid') || '';
    var projectid = $('body').data('projectid') || '';

    if (!psono && !sono) return;

    if (isConfirmed) {
        var queryParam = sono ? ("sono=" + encodeURIComponent(sono)) : ("psono=" + encodeURIComponent(psono));
        if (psono && sono) {
            queryParam += "&psono=" + encodeURIComponent(psono);
        }
        window.open(routurl + "krf-orderandsales/modal/finalsalesorderPDF.php?" + queryParam + "&companyid=" + companyid + "&subcompanyid=" + subcompanyid + "&projectid=" + projectid, "_blank");
    } else {
        window.open(routurl + "krf-orderandsales/modal/provisionalnvoicePDF.php?psono=" + encodeURIComponent(psono) + "&companyid=" + companyid + "&subcompanyid=" + subcompanyid + "&projectid=" + projectid, "_blank");
    }
}

function provisionalorderDownload(psono, isConfirmed, sono) {
    if (typeof isConfirmed !== 'undefined') {
        downloadOrderPDF(psono, !!isConfirmed, sono);
    } else {
        var routurl = $('body').data('routurl') || '';
        var companyid = $('body').data('companyid') || '';
        var subcompanyid = $('body').data('subcompanyid') || '';
        var projectid = $('body').data('projectid') || '';
        if (psono) {
            window.open(routurl + "krf-orderandsales/modal/provisionalnvoicePDF.php?psono=" + encodeURIComponent(psono) + "&companyid=" + companyid + "&subcompanyid=" + subcompanyid + "&projectid=" + projectid, "_blank");
        }
    }
}

function FinalSalesOrderDownload(sono) {
    var routurl = $('body').data('routurl') || '';
    var companyid = $('body').data('companyid') || '';
    var subcompanyid = $('body').data('subcompanyid') || '';
    var projectid = $('body').data('projectid') || '';
    if (sono) {
        window.open(routurl + "krf-orderandsales/modal/finalsalesorderPDF.php?sono=" + encodeURIComponent(sono) + "&companyid=" + companyid + "&subcompanyid=" + subcompanyid + "&projectid=" + projectid, "_blank");
    }
}

function GetTrayCategoryData(callback) {
    var routurl = $('body').data('routurl');
    var companyid = $('body').data('companyid');
    var subcompanyid = $('body').data('subcompanyid');
    var projectid = $('body').data('projectid');
    var loginid = $('body').data('loginid');
    const formData = new FormData();
    formData.append('companyid', companyid);
    formData.append('subcompanyid', subcompanyid);
    formData.append('projectid', projectid);
    $.ajax({
        type: 'POST',
        url: `${routurl}krf-orderandsales/modal/tray_modal.php?action=GetTrayCategoryData`,
        data: formData,
        contentType: false,
        processData: false,
        cache: false,
        dataType: 'JSON',
        success: function (res) {
            // console.log(res);
            callback(res);
        }
    });
}

function GetTrayCategoryData_option(dropdownid, alreadyexistval = "") {
    var routurl = $("body").data("routurl");
    var projectid = $("body").data("projectid");
    const $dropdown = $("#" + dropdownid);
    const form_data = new FormData();
    $.ajax({
        type: "POST",
        url: `${routurl}krf-orderandsales/modal/tray_modal.php?action=GetTrayCategoryData`,
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType: "JSON",
        success: function (res) {
            // console.log(res);
            $dropdown.empty();
            if (Array.isArray(res) && res.length > 0) {
                if (res.length > 1) {
                    $dropdown.append(`<option value="" selected disabled>Select</option>`);
                }
                res.forEach(item => {
                    // category_id,category_code,category_name
                    $dropdown.append(`<option value="${item.category_id}" data-categorycode="${item.category_code}">${item.category_name}</option>`);
                });

                // Auto-select if only one item
                if (res.length === 1) {
                    $dropdown.val(res[0].category_id).trigger("change");
                }

                // Override with already existing value if provided
                if (alreadyexistval) {
                    $dropdown.val(alreadyexistval).trigger("change");
                }
            } else {
                $dropdown.empty();
                $dropdown.append(`<option value="" disabled selected>No data available for tray category</option>`);
                $dropdown.trigger("change");
            }
        }
    });
}

function GetTrayDataCallback(callback, shortby, shortbyvalue) {
    var routurl = $('body').data('routurl');
    var companyid = $('body').data('companyid');
    var subcompanyid = $('body').data('subcompanyid');
    var projectid = $('body').data('projectid');
    var loginid = $('body').data('loginid');
    const formData = new FormData();
    formData.append('companyid', companyid);
    formData.append('subcompanyid', subcompanyid);
    formData.append('projectid', projectid);
    formData.append('TrayId', TrayId);
    $.ajax({
        type: 'POST',
        url: `${routurl}krf-orderandsales/modal/tray_modal.php?action=GetTrayData`,
        data: formData,
        contentType: false,
        processData: false,
        cache: false,
        dataType: 'JSON',
        success: function (res) {
            // console.log(res);
            callback(res);
        }
    });
}

function GetTrayDataWithOption(dropdownid, shortby, shortbyvalue, alreadyexistval = null) {
    var routurl = $('body').data('routurl');
    var companyid = $('body').data('companyid');
    var subcompanyid = $('body').data('subcompanyid');
    var projectid = $('body').data('projectid');
    var loginid = $('body').data('loginid');
    const $dropdown = $("#" + dropdownid);
    const formData = new FormData();
    formData.append('companyid', companyid);
    formData.append('subcompanyid', subcompanyid);
    formData.append('projectid', projectid);
    formData.append('shortby', shortby);
    formData.append('shortbyvalue', shortbyvalue);

    $.ajax({
        type: 'POST',
        url: `${routurl}krf-orderandsales/modal/tray_modal.php?action=GetTrayData`,
        data: formData,
        contentType: false,
        processData: false,
        cache: false,
        dataType: 'JSON',
        success: function (res) {
            // console.log(res);
            $dropdown.empty();
            if (Array.isArray(res) && res.length > 0) {
                if (res.length > 1) {
                    $dropdown.append(`<option value="" selected disabled>Select</option>`);
                }
                res.forEach(item => {
                    // category_id,category_code,category_name
                    $dropdown.append(`<option value="${item.tray_id}" data-cavitycount="${item.cavity_count}" data-cavitysize="${item.cavitysize}">${item.tray_name}</option>`);
                });

                // Auto-select if only one item
                if (res.length === 1) {
                    $dropdown.val(res[0].tray_id).trigger("change");
                }

                // Override with already existing value if provided
                if (alreadyexistval) {
                    $dropdown.val(alreadyexistval).trigger("change");
                }
            } else {
                $dropdown.empty();
                $dropdown.append(`<option value="" disabled selected>No data available</option>`);
                $dropdown.trigger("change");
            }
        }
    });
}

function GetSuggestedRootStockSKU(RootStockName, Inputid) {
    var routurl = $("body").data("routurl");
    var projectid = $("body").data("projectid");
    const form_data = new FormData();
    form_data.append("projectid", projectid);
    form_data.append("RootStockName", RootStockName);
    $.ajax({
        type: "POST",
        url: `${routurl}krf-orderandsales/modal/rootstock_modal.php?action=GetSuggestedRootStockSKU`,
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType: "JSON",
        success: function (res) {
            console.log(res);
            $("#" + Inputid).val(res);
        }
    });
}

function GetRootStockMasterData_option(dropdownid, alreadyexistval = "") {
    var routurl = $("body").data("routurl");
    var projectid = $("body").data("projectid");
    const $dropdown = $("#" + dropdownid);
    const form_data = new FormData();
    form_data.append("projectid", projectid);
    form_data.append("shortby", "all");
    form_data.append("shortbyvalue", "all");
    // alert(routurl);
    $.ajax({
        type: "POST",
        url: `${routurl}krf-orderandsales/modal/rootstock_modal.php?action=GetRootStockMasterData`,
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType: "JSON",
        success: function (res) {
            console.log(res);
            // $dropdown.empty();
            if (Array.isArray(res) && res.length > 0) {
                // Add default option if more than one item
                if (res.length > 1) {
                    $dropdown.append(`<option value="" selected disabled>Select</option>`);
                }
                res.forEach(item => {
                    // Wrap data-hsncode in quotes
                    $dropdown.append(`<option value="${item.rootstock_id}" data-rootstocksku="${item.rootstock_sku}">${item.rootstock_name}</option>`);
                });
                if (res.length === 1) {
                    $dropdown.val(res[0].rootstock_id).trigger("change");
                }
                // Override with already existing value if provided
                if (alreadyexistval) {
                    $dropdown.val(alreadyexistval).trigger("change");
                }
            } else {
                $dropdown.empty();
                $dropdown.append(`<option value="" disabled selected>No data available</option>`);
                $dropdown.trigger("change");
            }
        }
    });
}

function GetRootStockMasterData_Callback(shortby, shortbyvalue, callback) {
    var routurl = $("body").data("routurl");
    var projectid = $("body").data("projectid");
    const form_data = new FormData();
    form_data.append("projectid", projectid);
    form_data.append("shortby", shortby);
    form_data.append("shortbyvalue", shortbyvalue);
    // alert(routurl);
    $.ajax({
        type: "POST",
        url: `${routurl}krf-orderandsales/modal/rootstock_modal.php?action=GetRootStockMasterData`,
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType: "JSON",
        success: function (res) {
            // console.log(res);
            callback(res);
        }
    });
}


// function GetRootStockOrderData has been removed as part of the ROOT STOCK ORDERS decomposition.

function GetlaboursWithLocationProductForGrafting(graftingdate, locationid, productid, callback) {
    var routurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    var subcompanyid = $("body").data("subcompanyid");
    var projectid = $("body").data("projectid");
    var form_data = new FormData();
    form_data.append("companyid", companyid);
    form_data.append("subcompanyid", subcompanyid);
    form_data.append("projectid", projectid);
    form_data.append("graftingdate", graftingdate);
    form_data.append("locationid", locationid);
    form_data.append("productid", productid);
    $.ajax({
        type: "POST",
        url: routurl +
            "krf-orderandsales/modal/grafting_modal.php?action=GetlaboursWithLocationProductForGrafting_Ajay",

        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType: "JSON",

        success: function (res) {
            callback(res);
        },

        error: function (xhr) {
            console.error(
                "Get labour grafting data failed:",
                xhr.responseText
            );

            callback([]);
        }
    });
}

function ProductionLocationMastercallback(shortby, shortbyvalue, callback) {
    var form_data = new FormData();
    var routurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    form_data.append("companyid", companyid);
    var subcompanyid = $("body").data("subcompanyid");
    form_data.append("subcompanyid", subcompanyid);
    var projectid = $("body").data("projectid");
    form_data.append("projectid", projectid);
    form_data.append("shortby", shortby);
    form_data.append("shortbyvalue", shortbyvalue);
    $.ajax
        ({
            type: "POST",
            url: routurl + "krf-orderandsales/modal/grafting_modal.php?action=ProductionLocationMastercallback",
            data: form_data,
            contentType: false,
            processData: false,
            cache: false,
            dataType: 'JSON',
            success: function (res) {
                callback(res);
            }
        });
}

function GetClientDeliveryAddressData(clientid, callback) {
    var routurl = $('body').data('routurl');
    var companyid = $('body').data('companyid');
    var subcompanyid = $('body').data('subcompanyid');
    var projectid = $('body').data('projectid');
    var loginid = $('body').data('loginid');
    const formData = new FormData();
    formData.append('companyid', companyid);
    formData.append('subcompanyid', subcompanyid);
    formData.append('projectid', projectid);
    formData.append('clientid', clientid);
    $.ajax({
        type: 'POST',
        url: `${routurl}krf-orderandsales/modal/client_modal.php?action=GetClientDeliveryAddressData`,
        data: formData,
        contentType: false,
        processData: false,
        cache: false,
        dataType: 'JSON',
        success: function (res) {
            // console.log(res);
            callback(res);
        }
    });
}

function GetUniqeClientDeliveryAddressData(clientid, callback) {
    var routurl = $('body').data('routurl');
    var companyid = $('body').data('companyid');
    var subcompanyid = $('body').data('subcompanyid');
    var projectid = $('body').data('projectid');
    var loginid = $('body').data('loginid');
    const formData = new FormData();
    formData.append('companyid', companyid);
    formData.append('subcompanyid', subcompanyid);
    formData.append('projectid', projectid);
    formData.append('clientid', clientid);
    // alert(clientid);
    $.ajax({
        type: 'POST',
        url: `${routurl}krf-orderandsales/modal/client_modal.php?action=GetUniqeClientDeliveryAddressData`,
        data: formData,
        contentType: false,
        processData: false,
        cache: false,
        dataType: 'JSON',
        success: function (res) {
            console.log(res);
            callback(res);
        }
    });
}

function SendEmailProvisionalOrderDetails(clientid, psono, callback) {
    var routurl = $('body').data('routurl');
    var companyid = $('body').data('companyid');
    var subcompanyid = $('body').data('subcompanyid');
    var projectid = $('body').data('projectid');
    var loginid = $('body').data('loginid');
    const formData = new FormData();
    formData.append('companyid', companyid);
    formData.append('subcompanyid', subcompanyid);
    formData.append('projectid', projectid);
    formData.append('psono', psono);
    formData.append('clientid', clientid);
    $.ajax({
        type: 'POST',
        url: `${routurl}krf-orderandsales/modal/sendemail_modal.php?action=SendEmailProvisionalOrderDetails`,
        data: formData,
        contentType: false,
        processData: false,
        cache: false,
        dataType: 'JSON',
        success: function (res) {
            // console.log(res);
            callback(res);
        }
    });
}

function SendEmailSalesOrderConfirm(clientid, psono, sono, receiptpath = '', callback) {
    // If receiptpath is passed as the callback function (shifted argument)
    if (typeof receiptpath === 'function') {
        callback = receiptpath;
        receiptpath = '';
    }
    var routurl = $('body').data('routurl');
    var companyid = $('body').data('companyid');
    var subcompanyid = $('body').data('subcompanyid');
    var projectid = $('body').data('projectid');
    var loginid = $('body').data('loginid');
    const formData = new FormData();
    formData.append('companyid', companyid);
    formData.append('subcompanyid', subcompanyid);
    formData.append('projectid', projectid);
    formData.append('psono', psono);
    formData.append('sono', sono);
    formData.append('clientid', clientid);
    formData.append('receiptpath', receiptpath);
    $.ajax({
        type: 'POST',
        url: `${routurl}krf-orderandsales/modal/sendemail_modal.php?action=SendEmailSalesOrderConfirm`,
        data: formData,
        contentType: false,
        processData: false,
        cache: false,
        dataType: 'JSON',
        success: function (res) {
            // console.log(res);
            callback(res);
        }
    });
}

function GraftingTargetMonthYearList(targetyear, alreadyexistval, dropdownid) {
    const $dropdown = $("#" + dropdownid);
    var form_data = new FormData();
    var routurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    form_data.append("companyid", companyid);
    var subcompanyid = $("body").data("subcompanyid");
    form_data.append("subcompanyid", subcompanyid);
    var projectid = $("body").data("projectid");
    form_data.append("projectid", projectid);
    form_data.append("targetyear", targetyear);
    $.ajax
        ({
            type: "POST",
            url: routurl + "krf-orderandsales/modal/grafting_modal.php?action=GraftingTargetMonthYearList",
            data: form_data,
            contentType: false,
            processData: false,
            cache: false,
            dataType: 'JSON',
            success: function (res) {
                if (Array.isArray(res) && res.length > 0) {
                    // Add default option if more than one item
                    if (res.length > 1) {
                        $dropdown.append(`<option value="" selected disabled>Select</option>`);
                    }
                    res.forEach(item => {
                        // Wrap data-hsncode in quotes
                        $dropdown.append(`<option value="${item.code}">${item.label}</option>`);
                    });
                    if (res.length === 1) {
                        $dropdown.val(res[0].code).trigger("change");
                    }
                    // Override with already existing value if provided
                    if (alreadyexistval) {
                        $dropdown.val(alreadyexistval).trigger("change");
                    }
                } else {
                    $dropdown.empty();
                    $dropdown.append(`<option value="" disabled selected>Grafting target not available</option>`);
                    $dropdown.trigger("change");
                }
            }
        });
}

function GraftingDetailsWithMonthYear(yearmonth, tableid) {
    var $table = $("#" + tableid + " tbody");
    var routurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    var subcompanyid = $("body").data("subcompanyid");
    var projectid = $("body").data("projectid");

    // Clear old rows
    $table.empty();
    if(!yearmonth){
        return false;
    }

    // Prepare form data
    var form_data = new FormData();
    form_data.append("companyid", companyid);
    form_data.append("subcompanyid", subcompanyid);
    form_data.append("projectid", projectid);
    form_data.append("yearmonth", yearmonth);

    $.ajax({
        type: "POST",
        url: routurl + "krf-orderandsales/modal/grafting_modal.php?action=GraftingDetailsWithMonthYear",
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType: 'JSON',
        success: function (res) {
    $table.empty();
            if (res && res.length > 0) {
                var rows = "";
                for (var i = 0; i < res.length; i++) {
                    var r = res[i];
                    rows += `
                        
                              <tr
                                data-targetid="${r.id ?? ''}"
                                data-productid="${r.productid ?? ''}"
                                data-locationid="${r.locationid ?? ''}"
                                data-targettraycount="${r.target_traycount ?? ''}"
                                data-plantpertray="${r.plantpertray ?? ''}"
                                data-trayid="${r.trayid ?? ''}"
                                data-targetdate="${r.targetdate ?? ''}"
                            >
                            <td>${r.targetdatedsp || ''}</td>
                            <td class='d-none'>${r.product_name || ''}</td>
                            <td>${r.location_name || ''}</td>
                            <td class='d-none'>${r.plantpertray || 0}</td>
                            <td>${r.target_traycount || 0}</td>
                            <td class='text-center'>
                                 <button class="btn btn-sm btn-primary editTargetBtn" data-id="${r.id}" onclick="openGraftingTargetEditModal(this.closest('tr'))">
                                    <i class="fa fa-edit"></i></button>
                                <button class="btn btn-sm btn-danger d-none deleteTargetBtn" data-id="${r.id}">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                }
                $table.append(rows);
            } else {
                $table.append('<tr><td colspan="6" class="text-center text-muted">No records found</td></tr>');
            }
        },
        error: function (xhr, status, error) {
            console.error("Error fetching grafting details:", error);
            $table.append('<tr><td colspan="6" class="text-center text-danger">Error loading data</td></tr>');
        }
    });
}

function openGraftingTargetEditModal(rowElement) {
    const $row = $(rowElement);

    // Get data attributes from <tr>
    const productid = $row.data("productid");
    const locationid = $row.data("locationid");
    // debugger
    const targetdate = $row.data("targetdate");
    const trayid = $row.data("trayid");
    const targettraycount = $row.data("targettraycount");
    // Fill modal fields
    $("#productlist").val(productid).trigger("change");
    $("#locationlist").val(locationid).trigger("change");
    $("#start_date").val(targetdate);
    $("#end_date").val(targetdate);
    $("#target_traycount").val(targettraycount);
    // Show modal
    $("#GraftingTargetModal").modal("show");
}

function PendingForSubmissionGrafting(graftingdate, locationid, productid, callback) {
    var routurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    var subcompanyid = $("body").data("subcompanyid");
    var projectid = $("body").data("projectid");
    var form_data = new FormData();
    form_data.append("companyid", companyid);
    form_data.append("subcompanyid", subcompanyid);
    form_data.append("projectid", projectid);
    form_data.append("graftingdate", graftingdate);
    form_data.append("locationid", locationid);
    form_data.append("productid", productid);
    $.ajax({
        type: "POST",
        url: routurl + "krf-orderandsales/modal/grafting_modal.php?action=PendingForSubmissionGrafting",
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType: "JSON",
        success: function (res) {
            callback(res);
        }
    });
}

function UpdateSubmissionGraftingDeails(updatebtnid, graftedcount_eid, polyhouseid_eid, processstatus_eid) {
    const routurl = $("body").data("routurl");
    const companyid = $("body").data("companyid");
    const subcompanyid = $("body").data("subcompanyid");
    const projectid = $("body").data("projectid");
    const loginid = $("body").data("loginid");

    const productid = $("#" + updatebtnid).data("productid");
    const labourid = $("#" + updatebtnid).data("labourid");
    const graftingid = $("#" + updatebtnid).data("graftingid");
    const tablerowindex = $("#" + updatebtnid).data("tablerowindex");

    const graftedcount = $("#" + graftedcount_eid + "").val();
    const polyhouseid = $("#" + polyhouseid_eid + "").val();
    const processstatus = $("#" + processstatus_eid + "").val();

    if (!polyhouseid || polyhouseid === "undefined") {
        alertmessage("warning", "Polyhouse details are missing. Please select a valid polyhouse.");
        return false;
    }

    // 🧩 Validate inputs
    if (!graftedcount || isNaN(graftedcount) || graftedcount <= 0) {
        alertmessage("warning", "Invalid grafted count. Please enter a valid number of grafted plants.");
        return false;
    }

    if (!processstatus || processstatus === "undefined" || processstatus == "") {
        alertmessage("warning", "Active status process is missing. Please select a status.");
        return false;
    }

    // 📦 Prepare form data
    const form_data = new FormData();
    form_data.append("companyid", companyid);
    form_data.append("subcompanyid", subcompanyid);
    form_data.append("projectid", projectid);
    form_data.append("loginid", loginid);
    form_data.append("productid", productid);
    form_data.append("labourid", labourid);
    form_data.append("graftingid", graftingid);
    form_data.append("graftedcount", graftedcount);
    form_data.append("polyhouseid", polyhouseid);
    form_data.append("processstatus", processstatus);

    // 🚀 AJAX submission
    $.ajax({
        type: "POST",
        url: `${routurl}krf-orderandsales/modal/grafting_modal.php?action=UpdateSubmissionGraftingDeails`,
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType: "json",
        success: function (res) {
            if (res.status && res.status.toLowerCase() === "success") {
                alertmessage("success", res.message || "Grafting details updated successfully.");
                $("#submissionofgraftingModal").modal("hide");
                // ✅ Remove the updated row safely
                $("#refreshPendingGraftingdata").trigger("click");
                // const table = $("#submissionofdailyGraftingTable tbody");
                // if (table.find("tr").eq(tablerowindex).length) {
                //   table.find("tr").eq(tablerowindex).remove();
                // }

            } else {
                alertmessage("error", res.message || "Failed to update grafting details.");
            }
        },
        error: function (xhr, status, error) {
            alertmessage("error", "An error occurred while updating grafting details. Please try again.");
            console.error("AJAX Error:", status, error);
        }
    });
}

function UpdateTrayDeailsForPolyhousewithBarcode(tableid, polyhouseid, updatebtnid) {
    const routurl = $("body").data("routurl");
    const companyid = $("body").data("companyid");
    const subcompanyid = $("body").data("subcompanyid");
    const projectid = $("body").data("projectid");
    const loginid = $("body").data("loginid");

    const productid = $("#" + updatebtnid).data("productid");
    const labourid = $("#" + updatebtnid).data("labourid");
    const graftingid = $("#" + updatebtnid).data("graftingid");
    const tablerowindex = $("#" + updatebtnid).data("tablerowindex");

    if (!polyhouseid || polyhouseid === "undefined") {
        alertmessage("warning", "Polyhouse details are missing. Please select a valid polyhouse.");
        return false;
    }

    // ✅ Collect checked rows data
    const selectedData = [];
    $("#" + tableid + " tbody tr").each(function () {
        const $tr = $(this);
        if ($tr.find(".barcodecheckbox").is(":checked")) {
            const polyhouseplantid = $tr.data("polyhouseplantid");
            const barcode = $tr.find(".barcodeno").val()?.trim() || "";
            const bed = $tr.find(".bednumber").val()?.trim() || "";
            const rack = $tr.find(".racknumber").val()?.trim() || "";
            const floor = $tr.find(".floarnumber").val()?.trim() || "";
            const tryplace = $tr.find("input.trayposition:checked").val()?.trim() || "NA";


            // push only valid barcodes (non-empty)
            if (barcode !== "") {
                selectedData.push({
                    polyhouseplantid: polyhouseplantid,
                    barcode: barcode,
                    bednumber: bed,
                    racknumber: rack,
                    trayposition: tryplace
                });
            }
        }
    });

    if (selectedData.length === 0) {
        alertmessage("warning", "Please select at least one barcode to update.");
        return false;
    }

    // 📦 Prepare form data
    const form_data = new FormData();
    form_data.append("companyid", companyid);
    form_data.append("subcompanyid", subcompanyid);
    form_data.append("projectid", projectid);
    form_data.append("loginid", loginid);
    form_data.append("productid", productid);
    form_data.append("labourid", labourid);
    form_data.append("graftingid", graftingid);
    form_data.append("polyhouseid", polyhouseid);
    form_data.append("tabledata", JSON.stringify(selectedData)); // send all checked row data

    // 🚀 AJAX submission
    $.ajax({
        type: "POST",
        url: `${routurl}krf-orderandsales/modal/grafting_modal.php?action=UpdateTrayDeailsForPolyhousewithBarcode`,
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType: "json",
        success: function (res) {
            if (res.status && res.status.toLowerCase() === "success") {
                alertmessage("success", res.message || "Grafting details updated successfully.");
                $("#submissionofgraftingModal").modal("hide");

                // ✅ Remove the updated row safely
                const table = $("#submissionofdailyGraftingTable tbody");
                if (table.find("tr").eq(tablerowindex).length) {
                    table.find("tr").eq(tablerowindex).remove();
                }

            } else {
                alertmessage("error", res.message || "Failed to update grafting details.");
            }
        },
        error: function (xhr, status, error) {
            alertmessage("error", "An error occurred while updating grafting details. Please try again.");
            console.error("AJAX Error:", status, error);
        }
    });
}


function GetPolihouseDetailsList(shortby, shortbyvalue, alreadyexistval, dropdownid) {
    const $dropdown = $("#" + dropdownid);
    var form_data = new FormData();
    var routurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    form_data.append("companyid", companyid);
    var subcompanyid = $("body").data("subcompanyid");
    form_data.append("subcompanyid", subcompanyid);
    var projectid = $("body").data("projectid");
    form_data.append("projectid", projectid);
    form_data.append("shortby", shortby);
    form_data.append("shortbyvalue", shortbyvalue);

    $.ajax
        ({
            type: "POST",
            url: routurl + "krf-orderandsales/modal/polihouse_modal.php?action=GetPolihouseDetails",
            data: form_data,
            contentType: false,
            processData: false,
            cache: false,
            dataType: 'JSON',
            success: function (res) {
                // console.log(res)
                $dropdown.empty();
                if (Array.isArray(res) && res.length > 0) {
                    // Add default option if more than one item
                    if (res.length > 1) {
                        $dropdown.append(`<option value="" selected disabled>Select</option>`);
                    }
                    res.forEach(item => {
                        $dropdown.append(`<option value="${item.polyhouseid}" data-beddetails="${item.beddetails}" data-noofbed="${item.no_of_beds}" data-noofrack="${item.no_of_racks}" data-structuretype="${item.structure_type}">${item.polyhousename}</option>`);
                    });
                    if (res.length === 1) {
                        $dropdown.val(res[0].polyhouseid).trigger("change");
                    }
                    // Override with already existing value if provided
                    if (alreadyexistval) {
                        $dropdown.val(alreadyexistval).trigger("change");
                    }
                } else {
                    $dropdown.empty();
                    $dropdown.append(`<option value="" disabled selected>not available</option>`);
                    $dropdown.trigger("change");
                }
            }
        });
}

function GetPolihousebedorrackDetailsList(polyhouseid, alreadyexistval = null, dropdownid) {
    const $dropdown = $("#" + dropdownid);
    var form_data = new FormData();
    var routurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    form_data.append("companyid", companyid);
    var subcompanyid = $("body").data("subcompanyid");
    form_data.append("subcompanyid", subcompanyid);
    var projectid = $("body").data("projectid");
    form_data.append("projectid", projectid);
    form_data.append("polyhouseid", polyhouseid);

    $.ajax
        ({
            type: "POST",
            url: routurl + "krf-orderandsales/modal/polihouse_modal.php?action=PolihousebedorrackDetails",
            data: form_data,
            contentType: false,
            processData: false,
            cache: false,
            dataType: 'JSON',
            success: function (res) {
                console.log(res)
                $dropdown.empty();
                if (Array.isArray(res) && res.length > 0) {
                    // Add default option if more than one item
                    if (res.length > 1) {
                        $dropdown.append(`<option value="" selected disabled>Select</option>`);
                    }
                    res.forEach(item => {
                        $dropdown.append(`<option value="${item.bedname}" data-layercount="${item.layercount}" data-traycapacity="${item.traycapacity}">${item.bedname}</option>`);
                    });
                    if (res.length === 1) {
                        $dropdown.val(res[0].bedname).trigger("change");
                    }
                    // Override with already existing value if provided
                    if (alreadyexistval) {
                        $dropdown.val(alreadyexistval).trigger("change");
                    }
                } else {
                    $dropdown.empty();
                    $dropdown.append(`<option value="" disabled selected>not available</option>`);
                    $dropdown.trigger("change");
                }
            }
        });
}

function loadPolyhouseDetails(shortby, shortbyvalue, callback) {
    var routurl = $('body').data('routurl');
    var companyid = $('body').data('companyid');
    var subcompanyid = $('body').data('subcompanyid');
    var projectid = $('body').data('projectid');
    var loginid = $('body').data('loginid');
    const formData = new FormData();
    formData.append('companyid', companyid);
    formData.append('subcompanyid', subcompanyid);
    formData.append('projectid', projectid);
    formData.append('shortby', shortby);
    formData.append('shortbyvalue', shortbyvalue);

    $.ajax({
        type: 'POST',
        url: `${routurl}krf-orderandsales/modal/polihouse_modal.php?action=GetPolihouseDetails`,
        data: formData,
        contentType: false,
        processData: false,
        cache: false,
        dataType: 'JSON',
        success: function (res) {
            callback(res);
        }
    });
}

function GetUnVerifiedGerminationBoxDetailsData(polyhouseid, locationid, callback) {
    const routurl = $("body").data("routurl");
    const companyid = $("body").data("companyid");
    const subcompanyid = $("body").data("subcompanyid");
    const projectid = $("body").data("projectid");
    // 🔴 Validation
    if (!projectid) {
        console.error("Project ID missing");
        if (typeof callback === "function") {
            callback({ status: "error", message: "Project ID missing" });
        }
        return;
    }
    const form_data = new FormData();
    form_data.append("companyid", companyid);
    form_data.append("subcompanyid", subcompanyid);
    form_data.append("projectid", projectid);

    if (polyhouseid) {
        form_data.append("polyhouseid", polyhouseid);
    }
    if (locationid) {
        form_data.append("locationid", locationid);
    }
    alert(polyhouseid + '--' + locationid);
    $.ajax({
        type: "POST",
        url: routurl + "krf-orderandsales/modal/polihouse_modal.php?action=GetUnVerifiedGerminationBoxDetailsData",
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType: "json",

        success: function (res) {
            if (typeof callback === "function") {
                callback(res);
            }
        },
        error: function (xhr, status, error) {
            console.error("AJAX Error:", status, error);
            if (typeof callback === "function") {
                callback({
                    status: "error",
                    message: "Failed to fetch germination box details"
                });
            }
        }
    });
}

// function UpdateAllocationofGerminationBoxData(polyhouseid_eid, buttonid) {
//     const routurl     = $("body").data("routurl");
//     const companyid   = $("body").data("companyid");
//     const subcompanyid= $("body").data("subcompanyid");
//     const projectid   = $("body").data("projectid");
//     const loginid   = $("body").data("loginid");
//     const polyhouseid = $("#"+polyhouseid_eid).val();
//     const $btn = $("#" + buttonid);
//     const boxid = $btn.data("boxid");
//     const stockorderno = $btn.data("stockorderno");
//     // 🔴 Validation
//     if (!polyhouseid || !boxid || !stockorderno) {
//         Swal.fire({
//             icon: "warning",
//             title: "Missing Data",
//             text: "Required box or polyhouse details are missing."
//         });
//         return;
//     }
//     // 🔵 Confirmation Alert
//     Swal.fire({
//         title: "Confirm Verification",
//         text: "Are you sure you want to verify this germination box for the selected polyhouse?",
//         icon: "question",
//         showCancelButton: true,
//         confirmButtonText: "Yes, Verify",
//         cancelButtonText: "Cancel"
//     }).then((result) => {
//         if (!result.isConfirmed) return;
//         const form_data = new FormData();
//         form_data.append("companyid", companyid);
//         form_data.append("subcompanyid", subcompanyid);
//         form_data.append("projectid", projectid);
//         form_data.append("loginid", loginid);        
//         form_data.append("boxid", boxid);
//         form_data.append("stockorderno", stockorderno);
//         form_data.append("polyhouseid", polyhouseid);
//         let grmdate = $("#GboxOpeningDateList").val();
//         $.ajax({
//             type: "POST",
//             url: routurl + "krf-orderandsales/modal/polihouse_modal.php?action=UpdateAllocationofGerminationBoxData",
//             data: form_data,
//             contentType: false,
//             processData: false,
//             cache: false,
//             dataType: "JSON",
//             success: function (res) {
//                 // console.log(res.data);
//                 Swal.close();
//                 if (res.status === "success") {
//                     $("#GboxOpeningDateList").prop("SlectedIndex", 0);
//                     Swal.fire({
//                         title: "Verified Successfully",
//                         text: res.message || "Germination box verified successfully.",
//                         icon: "success",
//                         confirmButtonText: "OK, Refresh"
//                     }).then((result) => {
//                         if (result.isConfirmed) {
//                             // window.location.reload();
//                             $("#unlockScanner").trigger("click");
//                             $("#GboxOpeningDateList").val(grmdate).trigger("change");
//                             let upgrd = res.data;
//                             $("#GerminatedBoxCount").html(upgrd.allocated_boxes+"/"+upgrd.total_boxes);
//                             updateAllocationProgress(upgrd.allocated_boxes, upgrd.total_boxes);
//                         }
//                     });
//                     // Optional: Disable button after success
//                     $btn.prop("disabled", true).removeClass("btn-primary").addClass("btn-success");
//                 } else {
//                     Swal.fire({
//                         icon: "error",
//                         title: "Verification Failed",
//                         text: res.message || "Unable to verify the germination box."
//                     });
//                 }
//             },
//             error: function (xhr, status, error) {
//                 Swal.close();
//                 Swal.fire({
//                     icon: "error",
//                     title: "Server Error",
//                     text: "Something went wrong. Please try again later."
//                 });
//                 console.error("AJAX Error:", status, error);
//             }
//         });

//     });
// }

function UpdateAllocationofGerminationBoxData(polyhouseid_eid, buttonid) {

    const routurl = $("body").data("routurl");
    const companyid = $("body").data("companyid");
    const subcompanyid = $("body").data("subcompanyid");
    const projectid = $("body").data("projectid");
    const loginid = $("body").data("loginid");

    const polyhouseid = $("#" + polyhouseid_eid).val();
    const $btn = $("#" + buttonid);

    const boxid = $btn.data("boxid");
    const stockorderno = $btn.data("stockorderno");
    const boxbatchdate = $btn.data("boxbatchdate");
    const sowinglotnointernal = $btn.data("sowinglotnointernal");

    /* 🔴 Validation */
    if (!polyhouseid || !boxid || !stockorderno) {
        Swal.fire({
            icon: "warning",
            title: "Missing Data",
            text: "Required box or polyhouse details are missing."
        });
        return;
    }

    /* 🔵 Ask allocation choice */
    Swal.fire({
        title: "Allocation Type",
        text: "How do you want to allocate germination boxes?",
        icon: "question",
        showCancelButton: true,
        confirmButtonText: "Only This Scanned Box",
        cancelButtonText: "All Pending Boxes",
        reverseButtons: true
    }).then((result) => {

        if (result.dismiss === Swal.DismissReason.backdrop) return;

        // 🔹 allocation_mode = single | all
        const allocation_mode = result.isConfirmed ? "single" : "all";

        const form_data = new FormData();
        form_data.append("companyid", companyid);
        form_data.append("subcompanyid", subcompanyid);
        form_data.append("projectid", projectid);
        form_data.append("loginid", loginid);
        form_data.append("boxid", boxid);
        form_data.append("boxbatchdate", boxbatchdate);
        form_data.append("sowinglotnointernal", sowinglotnointernal);
        form_data.append("stockorderno", stockorderno);
        form_data.append("polyhouseid", polyhouseid);
        form_data.append("allocation_mode", allocation_mode);
        // console.log(companyid, subcompanyid, projectid, polyhouseid, allocation_mode);

        let grmdate = $("#GboxOpeningDateList").val();

        $.ajax({
            type: "POST",
            url: routurl + "krf-orderandsales/modal/polihouse_modal.php?action=UpdateAllocationofGerminationBoxData",
            data: form_data,
            contentType: false,
            processData: false,
            cache: false,
            dataType: "JSON",
            success: function (res) {
                // console.log(res);
                Swal.close();
                if (res.status === "success") {
                    // Reset & refresh
                    if ($("#GboxOpeningDateList").length > 0 && $("#GboxOpeningDateList")[0]) {
                        $("#GboxOpeningDateList")[0].selectedIndex = 0;
                    }
                    Swal.fire({
                        title: "Allocation Successful",
                        text: res.message || "Germination box allocation completed.",
                        icon: "success",
                        confirmButtonText: "OK"
                    }).then(() => {

                        $("#ManualAllocationstartScan").trigger("click");
                        $("#GboxOpeningDateList").val(grmdate).trigger("change");

                        $("#AllocationBoxDetailsInfoToPH").hide().html("");
                        $("#noResult").hide();

                        if (res.data) {
                            // $("#GerminatedBoxCount").html(
                            //     res.data.allocated_boxes + "/" + res.data.total_boxes
                            // );
                            // updateAllocationProgress(
                            //     res.data.allocated_boxes,
                            //     res.data.total_boxes
                            // );
                        }
                    });

                    // Disable button after success (single box only)
                    if (allocation_mode === "single") {
                        $btn
                            .prop("disabled", true)
                            .removeClass("btn-primary")
                            .addClass("btn-success");
                    }

                } else {
                    Swal.fire({
                        icon: "error",
                        title: "Allocation Failed",
                        text: res.message || "Unable to allocate germination box."
                    });
                }
            },

            error: function (xhr, status, error) {
                Swal.close();
                Swal.fire({
                    icon: "error",
                    title: "Server Error",
                    text: "Something went wrong. Please try again later."
                });
                console.error("AJAX Error:", status, error);
            }
        });

    });
}


function SubmitGerminationBoxVerificationForPolyhouse(polyhouseid_eid, buttonid) {
    const routurl = $("body").data("routurl");
    const companyid = $("body").data("companyid");
    const subcompanyid = $("body").data("subcompanyid");
    const projectid = $("body").data("projectid");
    const loginid = $("body").data("loginid");
    const polyhouseid = $("#" + polyhouseid_eid).val();


    const $btn = $("#" + buttonid);
    const boxid = $btn.data("boxid");
    const stockorderno = $btn.data("stockorderno");
    // 🔴 Validation
    if (!polyhouseid || !boxid || !stockorderno) {
        Swal.fire({
            icon: "warning",
            title: "Missing Data",
            text: "Required box or polyhouse details are missing."
        });
        return;
    }
    /* 🔵 Ask allocation choice */
    Swal.fire({
        title: "Allocation Type",
        text: "How do you want to allocate germination boxes?",
        icon: "question",
        showCancelButton: true,
        confirmButtonText: "Only This Scanned Box",
        cancelButtonText: "All Pending Boxes",
        reverseButtons: true
    }).then((result) => {
        if (result.dismiss === Swal.DismissReason.backdrop) return;
        // 🔹 allocation_mode = single | all
        const allocation_mode = result.isConfirmed ? "single" : "all";

        const form_data = new FormData();
        form_data.append("companyid", companyid);
        form_data.append("subcompanyid", subcompanyid);
        form_data.append("projectid", projectid);
        form_data.append("loginid", loginid);
        form_data.append("boxid", boxid);
        form_data.append("stockorderno", stockorderno);
        form_data.append("polyhouseid", polyhouseid);
        form_data.append("allocation_mode", allocation_mode);
        let grmdate = $("#GboxOpeningDateList").val();
        $.ajax({
            type: "POST",
            url: routurl + "krf-orderandsales/modal/polihouse_modal.php?action=SubmitGerminationBoxVerificationForPolyhouse",
            data: form_data,
            contentType: false,
            processData: false,
            cache: false,
            dataType: "JSON",
            success: function (res) {
                // console.log(res.data);
                Swal.close();
                if (res.status === "success") {
                    $("#GboxOpeningDateList").prop("SlectedIndex", 0);
                    Swal.fire({
                        title: "Verified Successfully",
                        text: res.message || "Germination box verified successfully.",
                        icon: "success",
                        confirmButtonText: "OK, Refresh"
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // window.location.reload();
                            $("#ManualStartScan").trigger("click");
                            $("#BoxDetailsInfo").hide().html("");
                            $("#noResult").hide();
                            $("#gvpolyhouselist").val(polyhouseid).trigger("change");

                            // $("#GboxOpeningDateList").val(grmdate).trigger("change");
                            // noResult
                            // let upgrd = res.data;
                            // $("#GerminatedBoxCount").html(upgrd.allocated_boxes+"/"+upgrd.total_boxes);
                            // updateAllocationProgress(upgrd.allocated_boxes, upgrd.total_boxes);
                        }
                    });
                    // Optional: Disable button after success
                    $btn.prop("disabled", true).removeClass("btn-primary").addClass("btn-success");
                } else {
                    Swal.fire({
                        icon: "error",
                        title: "Verification Failed",
                        text: res.message || "Unable to verify the germination box."
                    });
                }
            },
            error: function (xhr, status, error) {
                Swal.close();
                Swal.fire({
                    icon: "error",
                    title: "Server Error",
                    text: "Something went wrong. Please try again later."
                });
                console.error("AJAX Error:", status, error);
            }
        });

    });
}

function UpdateTrayAllocationToPolyhouse(gfailurestatusname, rownoinputid, placeofrowinputname, buttonid, sowingtableid) {
    const routurl = $("body").data("routurl");
    const companyid = $("body").data("companyid");
    const subcompanyid = $("body").data("subcompanyid");
    const projectid = $("body").data("projectid");
    const loginid = $("body").data("loginid");
    const bedrackname = $("#" + rownoinputid).val();
    const gfailurestatus = $("input[name='" + gfailurestatusname + "']:checked").val();
    const placeofrow = $("input[name='" + placeofrowinputname + "']:checked").val();
    const $btn = $("#" + buttonid);
    const gboxid = $btn.data("gboxid");
    const polyhouseid = $btn.data("polyhouseid");
    // const sowingplantid = $btn.data("sowingplantid");
    const stockorderno = $btn.data("stockorderno");

    let trayData = [];
    $("#" + sowingtableid + " tbody tr").each(function () {
        trayData.push({
            qrcode: $(this).data("qrcode"),
            boxid: $(this).data("boxid"),
            stockorderno: $(this).data("stockorderno"),
        });
    });

    if (trayData.length === 0) {
        alertmessage("warning", "No trays to save.");
        return;
    }
    trayData = JSON.stringify(trayData);

    // 🔴 Validation
    if (!gboxid) {
        Swal.fire({
            icon: "warning",
            title: "Missing Data",
            text: "Box details did not get in polyhouse."
        });
        return;
    } else if (!bedrackname && placeofrow == "Healthy") {
        Swal.fire({
            icon: "warning",
            title: "Missing Data",
            text: "Row no of polyhouse is missing."
        });
        return;
    } else if (!placeofrow && placeofrow == "Healthy") {
        Swal.fire({
            icon: "warning",
            title: "Missing Data",
            text: "Posion of tray in row is missing."
        });
        return;
    }
    // 🔵 Confirmation Alert
    Swal.fire({
        title: "Confirm Verification",
        text: "Are you sure you want to update as " + gfailurestatus + "?",
        icon: "question",
        showCancelButton: true,
        confirmButtonText: "Yes, Update",
        cancelButtonText: "Cancel"
    }).then((result) => {
        if (!result.isConfirmed) return;
        const form_data = new FormData();
        form_data.append("companyid", companyid);
        form_data.append("subcompanyid", subcompanyid);
        form_data.append("projectid", projectid);
        form_data.append("loginid", loginid);
        form_data.append("boxid", gboxid);
        form_data.append("stockorderno", stockorderno);
        form_data.append("polyhouseid", polyhouseid);
        form_data.append("bedrackname", bedrackname);
        form_data.append("placeofrow", placeofrow);
        form_data.append("gfailurestatus", gfailurestatus);
        form_data.append("trayData", trayData);
        $.ajax({
            type: "POST",
            url: routurl + "krf-orderandsales/modal/polihouse_modal.php?action=UpdateTrayAllocationToPolyhouse",
            data: form_data,
            contentType: false,
            processData: false,
            cache: false,
            dataType: "JSON",
            success: function (res) {
                // console.log(res.data);
                Swal.close();
                if (res.status === "success") {
                    // Optional: Disable button after success
                    $btn.prop("disabled", true).removeClass("btn-primary").addClass("btn-success");
                    Swal.fire({
                        title: "Allocation Successfully",
                        text: res.message || "Allocation saved successfully.",
                        icon: "success",
                        confirmButtonText: "OK, Refresh"
                    }).then((result) => {
                        if (result.isConfirmed) {
                            let upgrd = res.data;
                            $("#allocatedTrayCountofobox").html(upgrd.allocatedtrays);
                            if (upgrd.allocatedtrays === upgrd.traycountinbox) {
                                window.location.reload();
                            }
                            updateTrayAllocationProgress(upgrd.allocatedtrays, upgrd.traycountinbox);
                            $("#ManualAllocationstartScan").trigger("click");
                            $("#FetchedSowingTrayDetails").hide();
                            $("#FetchedTrayDetailsToPH").hide();
                            $("#noResult").hide();
                            $("#sowingtrayinformation tbody").empty();
                            // let scannedQRSet = new Set();
                            scannedQRSet.clear();
                        }
                    });

                } else {
                    // Optional: Disable button after success
                    $btn.prop("disabled", true).removeClass("btn-primary").addClass("btn-success");
                    Swal.fire({
                        icon: "error",
                        title: "Allocation Failed",
                        text: res.message || "Unable to Allocation the tray."
                    });
                }
            },
            error: function (xhr, status, error) {
                // Optional: Disable button after success
                $btn.prop("disabled", false).removeClass("btn-primary").addClass("btn-success");
                Swal.close();
                Swal.fire({
                    icon: "error",
                    title: "Server Error",
                    text: "Something went wrong. Please try again later."
                });
                console.error("AJAX Error:", status, error);
            }
        });

    });
}

function GraftingDatesList(locationid, alreadyexistval, dropdownid) {
    const $dropdown = $("#" + dropdownid);
    var form_data = new FormData();
    var routurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    form_data.append("companyid", companyid);
    var subcompanyid = $("body").data("subcompanyid");
    form_data.append("subcompanyid", subcompanyid);
    var projectid = $("body").data("projectid");
    form_data.append("projectid", projectid);
    form_data.append("locationid", locationid);
    $.ajax
        ({
            type: "POST",
            url: routurl + "krf-orderandsales/modal/grafting_modal.php?action=GraftingDatesList",
            data: form_data,
            contentType: false,
            processData: false,
            cache: false,
            dataType: 'JSON',
            success: function (res) {
                // console.log(res);
                $dropdown.empty();
                if (Array.isArray(res) && res.length > 0) {
                    // Add default option if more than one item
                    if (res.length > 1) {
                        $dropdown.append(`<option value="" selected disabled>Select</option>`);
                    }
                    res.forEach(item => {
                        $dropdown.append(`<option value="${item.graftingdate}">${item.graftingdate}</option>`);
                    });
                    if (res.length === 1) {
                        $dropdown.val(res[0].graftingdate).trigger("change");
                    }
                    // Override with already existing value if provided
                    if (alreadyexistval) {
                        $dropdown.val(alreadyexistval).trigger("change");
                    }
                } else {
                    $dropdown.empty();
                    $dropdown.append(`<option value="" disabled selected>not available</option>`);
                    $dropdown.trigger("change");
                }
            }
        });
}

function GraftingDatesForMissingBarcode(locationid, alreadyexistval, dropdownid) {
    const $dropdown = $("#" + dropdownid);
    var form_data = new FormData();
    var routurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    form_data.append("companyid", companyid);
    var subcompanyid = $("body").data("subcompanyid");
    form_data.append("subcompanyid", subcompanyid);
    var projectid = $("body").data("projectid");
    form_data.append("projectid", projectid);
    form_data.append("locationid", locationid);
    $.ajax
        ({
            type: "POST",
            url: routurl + "krf-orderandsales/modal/grafting_modal.php?action=GraftingDatesForMissingBarcode",
            data: form_data,
            contentType: false,
            processData: false,
            cache: false,
            dataType: 'JSON',
            success: function (res) {
                // console.log(res);
                $dropdown.empty();
                if (Array.isArray(res) && res.length > 0) {
                    // Add default option if more than one item
                    if (res.length > 1) {
                        $dropdown.append(`<option value="" selected disabled>Select</option>`);
                    }
                    res.forEach(item => {
                        $dropdown.append(`<option value="${item.submitted_date}">${item.submitted_date}</option>`);
                    });
                    if (res.length === 1) {
                        $dropdown.val(res[0].submitted_date).trigger("change");
                    }
                    // Override with already existing value if provided
                    if (alreadyexistval) {
                        $dropdown.val(alreadyexistval).trigger("change");
                    }
                } else {
                    $dropdown.empty();
                    $dropdown.append(`<option value="" disabled selected>not available</option>`);
                    $dropdown.trigger("change");
                }
            }
        });
}

function GetGraftedByList(locationid, grafteddate, alreadyexistval, dropdownid) {
    const $dropdown = $("#" + dropdownid);
    var form_data = new FormData();
    var routurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    form_data.append("companyid", companyid);
    var subcompanyid = $("body").data("subcompanyid");
    form_data.append("subcompanyid", subcompanyid);
    var projectid = $("body").data("projectid");
    form_data.append("projectid", projectid);
    form_data.append("locationid", locationid);
    form_data.append("grafteddate", grafteddate);

    $.ajax
        ({
            type: "POST",
            url: routurl + "krf-orderandsales/modal/grafting_modal.php?action=GetGraftedByList",
            data: form_data,
            contentType: false,
            processData: false,
            cache: false,
            dataType: 'JSON',
            success: function (res) {
                $dropdown.empty();
                if (Array.isArray(res) && res.length > 0) {
                    // Add default option if more than one item
                    if (res.length > 1) {
                        $dropdown.append(`<option value="" selected disabled>Select</option>`);
                    }
                    res.forEach(item => {
                        $dropdown.append(`<option value="${item.labourid}">${item.labourname}</option>`);
                    });
                    if (res.length === 1) {
                        $dropdown.val(res[0].labourid).trigger("change");
                    }
                    // Override with already existing value if provided
                    if (alreadyexistval) {
                        $dropdown.val(alreadyexistval).trigger("change");
                    }
                } else {
                    $dropdown.empty();
                    $dropdown.append(`<option value="" disabled selected>not available</option>`);
                    $dropdown.trigger("change");
                }
            }
        });
}

function GetGraftedByForBarcode(locationid, grafteddate, alreadyexistval, dropdownid) {
    const $dropdown = $("#" + dropdownid);
    var form_data = new FormData();
    var routurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    form_data.append("companyid", companyid);
    var subcompanyid = $("body").data("subcompanyid");
    form_data.append("subcompanyid", subcompanyid);
    var projectid = $("body").data("projectid");
    form_data.append("projectid", projectid);
    form_data.append("locationid", locationid);
    form_data.append("grafteddate", grafteddate);

    $.ajax
        ({
            type: "POST",
            url: routurl + "krf-orderandsales/modal/grafting_modal.php?action=GraftedByForBarcode",
            data: form_data,
            contentType: false,
            processData: false,
            cache: false,
            dataType: 'JSON',
            success: function (res) {
                $dropdown.empty();
                if (Array.isArray(res) && res.length > 0) {
                    // Add default option if more than one item
                    if (res.length > 1) {
                        $dropdown.append(`<option value="" selected disabled>Select</option>`);
                    }
                    res.forEach(item => {
                        $dropdown.append(`<option value="${item.labourid}">${item.labourname}</option>`);
                    });
                    if (res.length === 1) {
                        $dropdown.val(res[0].labourid).trigger("change");
                    }
                    // Override with already existing value if provided
                    if (alreadyexistval) {
                        $dropdown.val(alreadyexistval).trigger("change");
                    }
                } else {
                    $dropdown.empty();
                    $dropdown.append(`<option value="" disabled selected>not available</option>`);
                    $dropdown.trigger("change");
                }
            }
        });
}

function GetPendingBarcodeForGraftedPlants(locationid, grafteddate, labourid, callback) {
    var routurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    var subcompanyid = $("body").data("subcompanyid");
    var projectid = $("body").data("projectid");
    var form_data = new FormData();
    form_data.append("companyid", companyid);
    form_data.append("subcompanyid", subcompanyid);
    form_data.append("projectid", projectid);
    form_data.append("locationid", locationid);
    form_data.append("grafteddate", grafteddate);
    form_data.append("labourid", labourid);
    $.ajax({
        type: "POST",
        url: routurl + "krf-orderandsales/modal/grafting_modal.php?action=PendingBarcodeForGraftedPlants",
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType: "JSON",
        success: function (res) {
            callback(res);
        }
    });
}

function GetGraftedPlantsByLabourForDatewithLocation(locationid, grafteddate, labourid, callback) {
    var routurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    var subcompanyid = $("body").data("subcompanyid");
    var projectid = $("body").data("projectid");
    var form_data = new FormData();
    form_data.append("companyid", companyid);
    form_data.append("subcompanyid", subcompanyid);
    form_data.append("projectid", projectid);
    form_data.append("locationid", locationid);
    form_data.append("grafteddate", grafteddate);
    form_data.append("labourid", labourid);
    $.ajax({
        type: "POST",
        url: routurl + "krf-orderandsales/modal/grafting_modal.php?action=GraftedPlantsByLabourForDatewithLocation",
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType: "JSON",
        success: function (res) {
            callback(res);
        }
    });
}

function GetPlantingSourceList(alreadyexistval, dropdownid) {
    const $dropdown = $("#" + dropdownid);
    var form_data = new FormData();
    var routurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    form_data.append("companyid", companyid);
    var subcompanyid = $("body").data("subcompanyid");
    form_data.append("subcompanyid", subcompanyid);
    var projectid = $("body").data("projectid");
    form_data.append("projectid", projectid);
    $.ajax
        ({
            type: "POST",
            url: routurl + "krf-orderandsales/modal/polihouse_modal.php?action=GetPlantingSource",
            data: form_data,
            contentType: false,
            processData: false,
            cache: false,
            dataType: 'JSON',
            success: function (res) {
                $dropdown.empty();
                if (Array.isArray(res) && res.length > 0) {
                    // Add default option if more than one item
                    if (res.length > 1) {
                        $dropdown.append(`<option value="" selected disabled>Select</option>`);
                    }
                    res.forEach(item => {
                        // Wrap data-hsncode in quotes
                        $dropdown.append(`<option value="${item.sourcetypeid}">${item.sourcetype}</option>`);
                    });
                    if (res.length === 1) {
                        $dropdown.val(res[0].sourcetypeid).trigger("change");
                    }
                    // Override with already existing value if provided
                    if (alreadyexistval) {
                        $dropdown.val(alreadyexistval).trigger("change");
                    }
                } else {
                    $dropdown.empty();
                    $dropdown.append(`<option value="" disabled selected>not available</option>`);
                    $dropdown.trigger("change");
                }
            }
        });
}

function GetCultivationTypeList(alreadyexistval, dropdownid) {
    const $dropdown = $("#" + dropdownid);
    var form_data = new FormData();
    var routurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    form_data.append("companyid", companyid);
    var subcompanyid = $("body").data("subcompanyid");
    form_data.append("subcompanyid", subcompanyid);
    var projectid = $("body").data("projectid");
    form_data.append("projectid", projectid);
    $.ajax
        ({
            type: "POST",
            url: routurl + "krf-orderandsales/modal/polihouse_modal.php?action=GetCultivationType",
            data: form_data,
            contentType: false,
            processData: false,
            cache: false,
            dataType: 'JSON',
            success: function (res) {
                $dropdown.empty();
                if (Array.isArray(res) && res.length > 0) {
                    // Add default option if more than one item
                    if (res.length > 1) {
                        $dropdown.append(`<option value="" selected disabled>Select</option>`);
                    }
                    res.forEach(item => {
                        // Wrap data-hsncode in quotes
                        $dropdown.append(`<option value="${item.cultivation_typeid}">${item.cultivation_typename}</option>`);
                    });
                    if (res.length === 1) {
                        $dropdown.val(res[0].cultivation_typeid).trigger("change");
                    }
                    // Override with already existing value if provided
                    if (alreadyexistval) {
                        $dropdown.val(alreadyexistval).trigger("change");
                    }
                } else {
                    $dropdown.empty();
                    $dropdown.append(`<option value="" disabled selected>not available</option>`);
                    $dropdown.trigger("change");
                }
            }
        });
}

function UpdateGerminationFailure(unhealthyplantsid, traygrmRemarkid, updatebtnid) {
    const routurl = $("body").data("routurl");
    const companyid = $("body").data("companyid");
    const subcompanyid = $("body").data("subcompanyid");
    const projectid = $("body").data("projectid");
    const loginid = $("body").data("loginid");

    const polyhouseplantid = $("#" + updatebtnid).data("polyhouseplantid");
    const qrcode = $("#" + updatebtnid).data("qrcode");
    const unhealthyplants = $("#" + unhealthyplantsid).val();
    const graftingqcremark = $("#" + traygrmRemarkid).val();

    if (!unhealthyplants) {
        Swal.fire({
            icon: "warning",
            title: "Germination Failure",
            text: "Germination failure plants count is missing. Please enter a value.",
        });
        return;
    }

    // 📦 Prepare form data
    const form_data = new FormData();
    form_data.append("companyid", companyid);
    form_data.append("subcompanyid", subcompanyid);
    form_data.append("projectid", projectid);
    form_data.append("loginid", loginid);
    form_data.append("polyhouseplantid", polyhouseplantid);
    form_data.append("qrcode", qrcode);
    form_data.append("unhealthyplants", unhealthyplants);
    form_data.append("graftingqcremark", graftingqcremark);

    // 🚀 AJAX submission
    $.ajax({
        type: "POST",
        url: `${routurl}krf-orderandsales/modal/grafting_qc_modal.php?action=UpdateGerminationFailure`,
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        beforeSend: function () {
            $("#" + updatebtnid).prop("disabled", true).text("Saving...");
        },
        success: function (res) {
            console.log("Raw response:", res);
            // ✅ Parse JSON safely
            try {
                if (typeof res === "string") res = JSON.parse(res);
            } catch (e) {
                console.error("JSON Parse Error:", e);
                Swal.fire({
                    icon: "error",
                    title: "Invalid Response",
                    text: "Server returned invalid data format.",
                });
                $("#" + updatebtnid).prop("disabled", false).text("Save QC Status");
                return;
            }

            $("#" + updatebtnid).prop("disabled", false).text("Save QC Status");

            if (res.status === "success") {
                Swal.fire({
                    icon: "success",
                    title: "Saved!",
                    text: res.message,
                    timer: 2000,
                    showConfirmButton: false,
                });
                // $("#germQRStartScan").trigger("click");
                window.location.reload();
            } else {
                Swal.fire({
                    icon: res.status === "warning" ? "warning" : "error",
                    title: "Notice",
                    text: res.message,
                });
            }
        },
        error: function (xhr, status, error) {
            console.error("AJAX Error:", status, error);
            $("#" + updatebtnid).prop("disabled", false).text("Save QC Status");
            Swal.fire({
                icon: "error",
                title: "Server Error",
                text: "An error occurred while saving QC status. Please try again.",
            });
        },
    });
}

// function UpdateGraftingQCStatus(statusinputname, readytosalestatus_eid, remarkinput_eid,correctiontry_eid,replacementcount_eid, updatebtnid) {
//     const routurl = $("body").data("routurl");
//     const companyid = $("body").data("companyid");
//     const subcompanyid = $("body").data("subcompanyid");
//     const projectid = $("body").data("projectid");
//     const loginid = $("body").data("loginid");

//     const polyhouseplantid = $("#" + updatebtnid).data("polyhouseplantid");
//     const graftingqcstatus = $(`input[name="${statusinputname}"]:checked`).val();

//     let qcfinalstatus =  ($("#" + readytosalestatus_eid).is(":checked") && graftingqcstatus === "Healthy")
//         ? $("#" + readytosalestatus_eid).val() : "";
//     const graftingqcremark = $("#" + remarkinput_eid).val();
//     const correctionpolyhouseplantid = $("#" + correctiontry_eid).val();
//     const replacementcount = $("#" + replacementcount_eid).val();
//     // alert(graftingqcstatus+'==='+qcfinalstatus);

//     if (!graftingqcstatus) {
//         Swal.fire({
//           icon: "warning",
//           title: "Select QC Status",
//           text: "Please select a QC status before saving.",
//         });
//         return;
//     }
//     form_data.append("updateto", "");
//     if(qcfinalstatus==="ReadyToSale"){
//          Swal.fire({
//             title: "Confirm Verification",
//             text: "Are you sure you want to update as "+gfailurestatus+"?",
//             icon: "question",
//             showCancelButton: true,
//             confirmButtonText: "Yes, Update",
//             cancelButtonText: "Cancel"
//         }).then((result) => {
//             form_data.append("updateto", "all");
//         });
//     }

//     // 📦 Prepare form data
//     const form_data = new FormData();
//     form_data.append("companyid", companyid);
//     form_data.append("subcompanyid", subcompanyid);
//     form_data.append("projectid", projectid);
//     form_data.append("loginid", loginid);
//     form_data.append("graftingqcstatus", graftingqcstatus);
//     form_data.append("qcfinalstatus", qcfinalstatus);
//     form_data.append("graftingqcremark", graftingqcremark);
//     form_data.append("polyhouseplantid", polyhouseplantid);
//     form_data.append("correctionpolyhouseplantid", correctionpolyhouseplantid);
//     form_data.append("replacementcount", replacementcount);

//   // 🚀 AJAX submission
//   $.ajax({
//     type: "POST",
//     url: `${routurl}krf-orderandsales/modal/grafting_qc_modal.php?action=UpdateGraftingQCStatus`,
//     data: form_data,
//     contentType: false,
//     processData: false,
//     cache: false,
//     beforeSend: function () {
//       $("#" + updatebtnid).prop("disabled", true).text("Saving...");
//     },
//     success: function (res) {
//       console.log("Raw response:", res);
//       // ✅ Parse JSON safely
//       /*try {
//         if (typeof res === "string") res = JSON.parse(res);
//       } catch (e) {
//         console.error("JSON Parse Error:", e);
//         Swal.fire({
//           icon: "error",
//           title: "Invalid Response",
//           text: "Server returned invalid data format.",
//         });
//         $("#" + updatebtnid).prop("disabled", false).text("Save QC Status");
//         return;
//       }

//       $("#" + updatebtnid).prop("disabled", false).text("Save QC Status");

//       if (res.status === "success") {
//         Swal.fire({
//           icon: "success",
//           title: "Saved!",
//           text: res.message,
//           timer: 2000,
//           showConfirmButton: false,
//         });
//         $("#updateTrayQc_modal").modal("hide");
//         // window.location.reload();
//       } else {
//         Swal.fire({
//           icon: res.status === "warning" ? "warning" : "error",
//           title: "Notice",
//           text: res.message,
//         });
//       }*/
//     },
//     error: function (xhr, status, error) {
//       console.error("AJAX Error:", status, error);
//       $("#" + updatebtnid).prop("disabled", false).text("Save QC Status");
//       Swal.fire({
//         icon: "error",
//         title: "Server Error",
//         text: "An error occurred while saving QC status. Please try again.",
//       });
//     },
//   });
// }

function UpdateGraftingQCStatus(
    statusinputname,
    readytosalestatus_eid,
    remarkinput_eid,
    correctiontry_eid,
    replacementcount_eid,
    updatebtnid
) {

    const routurl = $("body").data("routurl");
    const companyid = $("body").data("companyid");
    const subcompanyid = $("body").data("subcompanyid");
    const projectid = $("body").data("projectid");
    const loginid = $("body").data("loginid");

    const polyhouseplantid = $("#" + updatebtnid).data("polyhouseplantid");
    const stockorder_no = $("#" + updatebtnid).data("stockorder_no");

    const graftingqcstatus = $(`input[name="${statusinputname}"]:checked`).val();
    const graftingqcremark = $("#" + remarkinput_eid).val();
    const correctionpolyhouseplantid = $("#" + correctiontry_eid).val();
    const replacementcount = $("#" + replacementcount_eid).val();

    // 🛑 Validate QC status
    if (!graftingqcstatus) {
        Swal.fire({
            icon: "warning",
            title: "QC Status Required",
            text: "Please select a QC status before saving."
        });
        return;
    }

    // ✅ Final status logic
    const qcfinalstatus =
        $("#" + readytosalestatus_eid).is(":checked") &&
            graftingqcstatus === "Healthy"
            ? "ReadyToSale"
            : "";

    // 📦 Prepare FormData
    const form_data = new FormData();
    form_data.append("companyid", companyid);
    form_data.append("subcompanyid", subcompanyid);
    form_data.append("projectid", projectid);
    form_data.append("loginid", loginid);
    form_data.append("stockorder_no", stockorder_no);
    form_data.append("polyhouseplantid", polyhouseplantid);
    form_data.append("graftingqcstatus", graftingqcstatus);
    form_data.append("qcfinalstatus", qcfinalstatus);
    form_data.append("graftingqcremark", graftingqcremark);
    form_data.append("correctionpolyhouseplantid", correctionpolyhouseplantid);
    form_data.append("replacementcount", replacementcount);
    form_data.append("updateto", qcfinalstatus === "ReadyToSale" ? "all" : "");

    // ⚠️ Confirmation only when Ready To Sale
    const proceed = () => {
        $.ajax({
            type: "POST",
            url: `${routurl}krf-orderandsales/modal/grafting_qc_modal.php?action=UpdateGraftingQCStatus`,
            data: form_data,
            contentType: false,
            processData: false,
            cache: false,
            beforeSend: function () {
                $("#" + updatebtnid)
                    .prop("disabled", true)
                    .html('<i class="fas fa-spinner fa-spin"></i> Saving...');
            },
            success: function (res) {
                try {
                    if (typeof res === "string") res = JSON.parse(res);
                } catch (e) {
                    Swal.fire("Error", "Invalid server response.", "error");
                    return;
                }

                $("#" + updatebtnid)
                    .prop("disabled", false)
                    .text("Save QC Status");

                if (res.status === "success") {
                    Swal.fire({
                        icon: "success",
                        title: "Updated",
                        text: res.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    // $("#updateTrayQc_modal").modal("hide");
                    $("#GrafQRStartScan").trigger("click");
                } else {
                    Swal.fire({
                        icon: res.status === "warning" ? "warning" : "error",
                        title: "Notice",
                        text: res.message
                    });
                }
            },
            error: function () {
                $("#" + updatebtnid)
                    .prop("disabled", false)
                    .text("Save QC Status");

                Swal.fire(
                    "Server Error",
                    "Unable to save QC status. Please try again.",
                    "error"
                );
            }
        });
    };

    // 🔔 Ask confirmation if Ready To Sale
    if (qcfinalstatus === "ReadyToSale") {
        Swal.fire({
            title: "Confirm Ready to Sale",
            text: "How do you want to apply this Ready to Sale action?",
            icon: "question",
            showCancelButton: true,
            showDenyButton: true,

            confirmButtonText: "Current Tray Only",
            denyButtonText: "All Healthy Trays",
            cancelButtonText: "Cancel"
        }).then((result) => {

            if (result.isConfirmed) {
                // ✅ Apply to current tray only
                form_data.set("updateto", "");
                proceed();
            }

            if (result.isDenied) {
                // ✅ Apply to all healthy trays
                form_data.set("updateto", "all");
                proceed();
            }

        });

    } else {
        proceed();
    }

}




function GetGraftedQCDoneToday(callback) {
    var routurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    var subcompanyid = $("body").data("subcompanyid");
    var projectid = $("body").data("projectid");
    var loginid = $("body").data("loginid");
    var form_data = new FormData();
    form_data.append("companyid", companyid);
    form_data.append("subcompanyid", subcompanyid);
    form_data.append("projectid", projectid);
    form_data.append("loginid", loginid);
    $.ajax({
        type: "POST",
        url: routurl + "krf-orderandsales/modal/grafting_qc_modal.php?action=GetGraftedQCDoneToday",
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType: "JSON",
        success: function (res) {
            callback(res);
        }
    });
}

function GetGraftedQCHistory(polyhouseplantid, callback) {
    var routurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    var subcompanyid = $("body").data("subcompanyid");
    var projectid = $("body").data("projectid");
    var loginid = $("body").data("loginid");
    var form_data = new FormData();
    form_data.append("companyid", companyid);
    form_data.append("subcompanyid", subcompanyid);
    form_data.append("projectid", projectid);
    form_data.append("polyhouseplantid", polyhouseplantid);
    $.ajax({
        type: "POST",
        url: routurl + "krf-orderandsales/modal/grafting_qc_modal.php?action=GetGraftedQCHistory",
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType: "JSON",
        success: function (res) {
            callback(res);
        }
    });
}

function GetQCCorrectionTray(callback) {
    var routurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    var subcompanyid = $("body").data("subcompanyid");
    var projectid = $("body").data("projectid");
    var loginid = $("body").data("loginid");
    var form_data = new FormData();
    form_data.append("companyid", companyid);
    form_data.append("subcompanyid", subcompanyid);
    form_data.append("projectid", projectid);
    form_data.append("loginid", loginid);
    $.ajax({
        type: "POST",
        url: routurl + "krf-orderandsales/modal/grafting_qc_modal.php?action=GetQCCorrectionTray",
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType: "JSON",
        success: function (res) {
            callback(res);
        }
    });
}


function TotalcalculateTraysRequired(orderqty, bufferpercent, trayHoles) {
    let qty = parseFloat(orderqty) || 0;
    let buffer = parseFloat(bufferpercent) || 0;
    let holes = parseInt(trayHoles) || 0;
    if (qty === 0 || holes === 0) {
        return {
            totaltrays: 0,
            totalquantity: 0
        };
    }
    // Seeds required including buffer %
    let totalSeedsRequired = qty + (qty * buffer / 100);
    // Number of trays needed
    let totaltrays = Math.ceil(totalSeedsRequired / holes);
    let totalquantity = totaltrays * holes;
    return {
        totaltrays: totaltrays,
        totalquantity: totalquantity
    };
}

// function askChangeExpectedDeliveryDate(productstockorderno, productid, sowingdate) {
//     if (!productid || !sowingdate) {
//         return;
//     }

//     const today = new Date();
//     const sowing = new Date(sowingdate);

//     // Calculate difference in days
//     const diffTime = today - sowing;
//     const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));

//     // No difference → no need to ask
//     if (isNaN(diffDays) || diffDays <= 0) {
//         return;
//     }

//     Swal.fire({
//         icon: "question",
//         title: "Update Expected Delivery Date?",
//         html: `
//             <p><b>Stock Order No:</b> ${productstockorderno}</p>
//             <p><b>Sowing Date:</b> ${sowingdate}</p>
//             <p><b>Today's Date:</b> ${today.toISOString().slice(0, 10)}</p>
//             <p class="text-danger">
//                 Difference detected: <b>${diffDays} days</b>
//             </p>
//             <hr>
//             <p>Do you want to recalculate the expected delivery date?</p>
//         `,
//         showCancelButton: true,
//         confirmButtonText: "Yes, Recalculate",
//         cancelButtonText: "No, Keep Existing",
//         confirmButtonColor: "#198754",
//         cancelButtonColor: "#6c757d"
//     }).then((result) => {
//         if (result.isConfirmed) {
//             fetchProductGrowingDetails(productstockorderno,productid, function(rrsst){

//             });
//         }
//     });
// }

function askChangeExpectedDeliveryDate(productstockorderno, productid, sowingdate) {

    if (!productid || !sowingdate) return;

    const today = new Date();
    const sowing = new Date(sowingdate);
    const diffDays = Math.floor((today - sowing) / (1000 * 60 * 60 * 24));

    // No difference → no prompt
    if (isNaN(diffDays) || diffDays <= 0) return;

    Swal.fire({
        icon: "question",
        title: "Recalculate Expected Delivery Date?",
        html: `
            <div class="text-start">
                <p><b>Stock Order No:</b> ${productstockorderno}</p>
                <p><b>Sowing Date:</b> ${sowingdate}</p>
                <p><b>Today's Date:</b> ${today.toISOString().slice(0, 10)}</p>
                <p class="text-danger">
                    Difference detected: <b>${diffDays} days</b>
                </p>
                <hr>
                <p>Do you want to recalculate the expected delivery date?</p>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: "Yes, Recalculate",
        cancelButtonText: "No, Keep Existing",
        confirmButtonColor: "#198754",
        cancelButtonColor: "#6c757d"
    }).then((result) => {

        if (!result.isConfirmed) return;

        // 🔄 Fetch growing details
        fetchProductGrowingDetails(productstockorderno, productid, function (data) {

            const oldDeliveryDate = data.expected_delivery_date || "";
            const suggestedDate = adddaysingivedate(oldDeliveryDate, diffDays) || "";

            Swal.fire({
                icon: "info",
                title: "Confirm New Delivery Date",
                html: `
                    <div class="text-start">
                        <p><b>Old Delivery Date:</b></p>
                        <span class="badge bg-secondary">${oldDeliveryDate || "Not Set"}</span>

                        <hr>

                        <label class="form-label mt-2">
                            <b>Suggested New Expected Delivery Date</b>
                        </label>
                        <input type="date"
                               id="newExpectedDeliveryDate"
                               class="form-control"
                               value="${suggestedDate}">
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: "Save Delivery Date",
                cancelButtonText: "Cancel",
                confirmButtonColor: "#0d6efd",
                preConfirm: () => {
                    const newDate = document.getElementById("newExpectedDeliveryDate").value;
                    if (!newDate) {
                        Swal.showValidationMessage("Please select a delivery date");
                    }
                    return newDate;
                }
            }).then((saveResult) => {
                if (saveResult.isConfirmed) {
                    const newDeliveryDate = saveResult.value;

                    // ✅ Final output – you can save/update from here
                    console.log({ productstockorderno, productid, oldDeliveryDate, newDeliveryDate });
                    // Optional AJAX save here
                    saveUpdatedDeliveryDate(productstockorderno, productid, newDeliveryDate);
                }

            });

        });

    });
}


function fetchProductGrowingDetails(productstockorderno, productid, callback) {
    var routurl = $("body").data("routurl");
    let companyid = $("body").data("companyid");
    let subcompanyid = $("body").data("subcompanyid");
    let projectid = $("body").data("projectid");
    $.ajax({
        url: routurl + "krf-orderandsales/modal/product_modal.php?action=getproductionorderProductdata",
        type: "POST",
        dataType: "json",
        data: { companyid: companyid, subcompanyid: subcompanyid, projectid: projectid, productid: productid, productstockorderno: productstockorderno },
        success: function (res) {

            if (!res || res.length === 0) {
                Swal.fire("No Data Found", "Growing date details not available.", "info");
                return;
            }
            if (typeof callback === "function") {
                callback(res[0]); // return product JSON
            }
        },
        error: function () {
            Swal.fire("Error", "Unable to fetch product growing details.", "error");
        }
    });
}

function saveUpdatedDeliveryDate(productstockorderno, productid, newDeliveryDate) {
    var routurl = $("body").data("routurl");
    let companyid = $("body").data("companyid");
    let subcompanyid = $("body").data("subcompanyid");
    let projectid = $("body").data("projectid");
    $.ajax({
        url: routurl + "krf-orderandsales/modal/product_modal.php?action=saveUpdatedDeliveryDate",
        type: "POST",
        dataType: "json",
        data: { companyid: companyid, subcompanyid: subcompanyid, projectid: projectid, productid: productid, productstockorderno: productstockorderno, DeliveryDate: newDeliveryDate },
        success: function (res) {
            console.log(res);
            if (!res || res.length === 0) {
                Swal.fire("No Data Found", "Growing date details not available.", "info");
                return;
            }
            if (typeof callback === "function") {
                callback(res[0]); // return product JSON
            }
        },
        error: function () {
            Swal.fire("Error", "Unable to fetch product growing details.", "error");
        }
    });
}

// Custom Refresh Event Handlers for Modals
$(document).on("click", "#refreshSowingModalBtn", function () {
    const stockNo = $("#sowingQRCodeModal").data("stockorderno");
    const rType = $("#sowingQRCodeModal").data("roottype") || "Root";
    if (stockNo) {
        // Spin effect on icon
        const $icon = $(this).find(".fa-sync");
        $icon.addClass("fa-spin");
        generateSeedLotCardsByStockOrder(stockNo, rType);
        setTimeout(() => { $icon.removeClass("fa-spin"); }, 1000);
    }
});

$(document).on("click", "#refreshGermPackingModalBtn", function () {
    const stockNo = $("#germpackingQRCodeModal").data("stockorderno");
    const rType = $("#germpackingQRCodeModal").data("roottype") || "Root";
    if (stockNo) {
        // Spin effect on icon
        const $icon = $(this).find(".fa-sync");
        $icon.addClass("fa-spin");
        sowingtrysofgerminationbox(stockNo, rType, 'alreadyprintedboxqrcode');
        pendingsowingtrysofgerminationbox(stockNo, rType);
        const prodId = $("#saveGerminationDetailsBtnpck").data("dataproductidpck");
        const prodName = $("#saveGerminationDetailsBtnpck").data("dataproductnamepck");
        if (typeof loadSuggestedBoxOpenDays === "function") {
            loadSuggestedBoxOpenDays(prodId, prodName, stockNo);
        }
        setTimeout(() => { $icon.removeClass("fa-spin"); }, 1000);
    }
});

$(document).off("click", ".remove-germbox-batch-btn").on("click", ".remove-germbox-batch-btn", function (e) {
    e.preventDefault();
    const btn = $(this);
    const boxbatchno = btn.data("boxbatchno");
    const stockorderno = btn.data("stockorderno");
    const roottype = btn.data("roottype") || "Root";
    const projectid = $("body").data("projectid");
    const routurl = $("body").data("routurl");

    if (!boxbatchno || !stockorderno) {
        Swal.fire("Error", "Missing batch number or order number.", "error");
        return;
    }

    Swal.fire({
        title: "Remove Box Batch?",
        text: `Are you sure you want to remove Germination Box Batch #${boxbatchno}? All trays in this batch will be reverted to pending sowing.`,
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#ef4444",
        cancelButtonColor: "#6c757d",
        confirmButtonText: "Yes, Remove Batch",
        cancelButtonText: "Cancel"
    }).then((result) => {
        if (result.isConfirmed) {
            btn.prop("disabled", true).html('<i class="fa fa-spinner fa-spin me-1"></i> Removing...');
            $.ajax({
                url: `${routurl}krf-orderandsales/modal/sowing_modal.php?action=RemoveGerminationBoxBatch`,
                type: "POST",
                dataType: "JSON",
                data: {
                    projectid: projectid,
                    stockorderno: stockorderno,
                    boxbatchno: boxbatchno,
                    roottype: roottype
                },
                success: function (res) {
                    if (res && res.status === "success") {
                        Swal.fire({
                            title: "Removed!",
                            text: res.message || "Germination box batch removed successfully.",
                            icon: "success",
                            timer: 2000,
                            showConfirmButton: false
                        });
                        // Refresh the modal views
                        sowingtrysofgerminationbox(stockorderno, roottype, "alreadyprintedboxqrcode");
                        pendingsowingtrysofgerminationbox(stockorderno, roottype);
                        // Trigger week selector change to update grid if present
                        if ($("#sowingweekSelector").length) {
                            $("#sowingweekSelector").trigger("change");
                        }
                    } else {
                        btn.prop("disabled", false).html('<i class="fa fa-trash me-1"></i> Remove');
                        Swal.fire("Error", res ? res.message : "Failed to remove batch.", "error");
                    }
                },
                error: function (xhr, status, error) {
                    btn.prop("disabled", false).html('<i class="fa fa-trash me-1"></i> Remove');
                    Swal.fire("Error", "Server error occurred while removing batch.", "error");
                }
            });
        }
    });
});
