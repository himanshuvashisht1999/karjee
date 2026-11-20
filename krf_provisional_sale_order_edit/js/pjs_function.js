// Local order-screen helpers, extracted without changing function bodies.
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
            url: routurl + "krf_provisional_sale_order_edit/modal/client_modal.php?action=getStateByDistrict",
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
        url: routurl + "krf_provisional_sale_order_edit/modal/client_modal.php?action=getStatesFromMaster",
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
        url: routurl + "krf_provisional_sale_order_edit/modal/client_modal.php?action=getDistrictsFromMaster",
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

$(document).on('input change', '#cityname, #tahseel', function () {
    updateAddressForNewClient();
});

$(document).on('change', '#statename', function () {
    var stateVal = $(this).val();
    var stateId = $(this).find('option:selected').data('stateid') || '';
    $("#stateid").val(stateId);
    updateAddressForNewClient();
});

$(document).on('change', '#districtname', function () {
    var distVal = $(this).val();
    var distId = $(this).find('option:selected').data('districtid') || '';
    $("#districtid").val(distId);
    updateAddressForNewClient();
});

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

    // -----------------------------
    // Fetch delivery addresses (Disabled auto-append, user picks via modal)
    // -----------------------------
}

function applyCheckedExistingClient() { 
    $("input[name='existclient']").each(function () {
        if ($(this).is(":checked")) {
            handleExistingClientSelection(this);
            return false; // stop after first checked
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
        url: `${routurl}krf_provisional_sale_order_edit/modal/product_modal.php?action=ProductListFrommaster`,
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
        url: `${routurl}krf_provisional_sale_order_edit/modal/client_modal.php?action=GetClientDeliveryAddressData`,
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
        url: `${routurl}krf_provisional_sale_order_edit/modal/client_modal.php?action=GetUniqeClientDeliveryAddressData`,
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
        url: `${routurl}krf_provisional_sale_order_edit/modal/sendemail_modal.php?action=SendEmailProvisionalOrderDetails`,
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
