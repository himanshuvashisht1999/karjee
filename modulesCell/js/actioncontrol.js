function navigateCardWithContainerid(direction,prevButtonid,nextButtonid,carcontainerid) {
    const totalCards = $("#"+carcontainerid+" .card").length;  // Get total card count dynamically
    if (direction === 'next' && currentCard < totalCards) {
        currentCard++;
    } else if (direction === 'prev' && currentCard > 1) {
        currentCard--;
    }

    $("#"+carcontainerid+" .card").hide();
    $('#card' + currentCard).show();

    if (currentCard === 1) {
        $('#'+prevButtonid+'').hide();
    } else {
        $('#'+prevButtonid+'').show();
    }

    if (currentCard === totalCards) {
        $('#'+nextButtonid+'').hide();
    } else {
        $('#'+nextButtonid+'').show();
    }
}

function nextdivshow(currentdivid,nextdivid){
  $("#"+currentdivid+"").hide();
  $("#"+nextdivid+"").show();
}

function previousdivshow(currentdivid,previousdivid){
  $("#"+currentdivid+"").hide();
  $("#"+previousdivid+"").show();
}

function isNumberKey(evt) {
    var charCode = (evt.which) ? evt.which : evt.keyCode;
    if (charCode != 46 && charCode > 31 &&
      (charCode < 48 || charCode > 57))
      return false;
    return true;
}
function openPageInNewTab(url) {
    const newWindow = window.open(url, '_blank');
    newWindow.focus();
}
function url_btoa(data)
{
  var b64 = window.btoa(data);
  if (!b64) {
    return false;
  }
  var url = b64.replace('+', '-').replace('/','_');
  return url.replace(/=/g,'');
}

function url_atob(data)
{
  var b64 = data.replace('-', '+').replace('_','/');
  return window.atob(b64);
}

function checkuncheck_checkbox(allcheckboxid, checkboxclass){
  if($("#"+allcheckboxid+"").is(":checked")){
    $('.'+checkboxclass+'').prop('checked', true);
  }else{
    $('.'+checkboxclass+'').prop('checked', false);
  }

  if($("."+checkboxclass+"").length==$("."+checkboxclass+":checked").length){
    $("#"+allcheckboxid+"").prop("checked", true);
  }else{
    $("#"+allcheckboxid+"").prop("checked", false);
  }
}

function checkuncheck_allcheckbox(allCheckboxId, checkboxClass) {
  const allBoxes = $("." + checkboxClass);
  const checkedBoxes = allBoxes.filter(":checked");
  $("#" + allCheckboxId).prop("checked", allBoxes.length === checkedBoxes.length && allBoxes.length > 0);
}

function handleMasterCheckboxAll(masterCheckboxId, checkboxClass) {
  // Master checkbox toggle handler
  $("#" + masterCheckboxId).on("change", function () {
    const isChecked = $(this).prop("checked");
    $("." + checkboxClass + ":not(:disabled)").prop("checked", isChecked);
  });

  // Individual checkbox toggle handler
  $(document).on("change", "." + checkboxClass, function () {
    const total = $("." + checkboxClass + ":not(:disabled)").length;
    const checked = $("." + checkboxClass + ":checked:not(:disabled)").length;
    $("#" + masterCheckboxId).prop("checked", total === checked);
  });
}


function onclickcheckboxdivhideshow(checkboxid,hideshowdivid){
  if($("#"+checkboxid+"").is(":checked")==true){
    $("#"+hideshowdivid+"").show();
  }else{
    $("#"+hideshowdivid+"").hide();
  }
}

function onclickcheckboxdivhideshow_ofdivclass(checkboxid,hideshowdivclass){
  if($("#"+checkboxid+"").is(":checked")==true){
    $("."+hideshowdivclass+"").show();
  }else{
    $("."+hideshowdivclass+"").hide();
  }
}

function onclickradioyesdivhideshow(radioname,hideshowdivid){
  $("#"+hideshowdivid+"").find('input').val('');
  if($("input[name='"+radioname+"']:checked").val()=="yes"){
    $("#"+hideshowdivid+"").show();
  }else{
    $("#"+hideshowdivid+"").hide();
  }
}

function hideshowdivifvaluismatched(radioname,hideshowdivid,radiovalmatched){
  $("#"+hideshowdivid+"").find('input').val('');
  if($("input[name='"+radioname+"']:checked").val()==radiovalmatched){
    $("#"+hideshowdivid+"").show();
  }else{
    $("#"+hideshowdivid+"").hide();
  }
}

function hideAllRowsExceptCurrent(tableId, radioName) {
    // Handle the click event on the radio buttons
  if($("input[name='" + radioName + "']:checked").length==0){
    $("#" + tableId + " tbody tr").show();
  }else{
    $("#" + tableId + " tbody tr").each(function(){
      if($(this).find("td:eq(0)").find("input[name='" + radioName + "']").is(":checked")){
        $(this).show();
      }else{
        $(this).hide();
      }
    })
  }
}

function loadDocumentInDiv(filePath, divId) {
    // Clear any existing content in the div
    $("#" + divId).empty();
    // Determine the file extension
    var fileExtension = getFileExtension(filePath);
    // Create the appropriate element to view the document
    var element;
    // For PDFs and images
    if (fileExtension === "pdf") {
        element = '<iframe src="' + filePath + '" width="100%" height="450" style="border:none;"></iframe>';
    } else if (fileExtension === "jpg" || fileExtension === "jpeg" || fileExtension === "png" || fileExtension === "gif") {
        element = '<img src="' + filePath + '" style="max-width: 100%; max-height: 100%;" />';
    } else {
        element = '<p>Cannot display this file type.</p>';
    }
    // Append the element to the div
    $("#" + divId).append(element);
    $("#" + divId).show();
    $("#climagediv").show();
}

function cleardivcontent(divid){
  $("#"+divid+"").html('');
}

function getFileExtension(filename) {
    // Extract and return the file extension
    if (filename.lastIndexOf(".") !== -1) {
        return filename.split('.').pop().toLowerCase();
    }
    return null;
}


function extractMobileNumbers(text) {
    // Define a regular expression to match 10-digit mobile numbers
    var regex = /((\+44\s?|0)[1-9][0-9]{0,4}\s?(\d{3}\s?\d{4}|\d{6,8}))|(\(?(01\d{1}|02\d{1}|03\d{1}|05\d{1}|07\d{1}|08\d{1})\)?\s?\d{3,4}\s?\d{4})/g;
    var matches = text.match(regex);
    if(matches){
      return matches;
    }else{
      var regex = /(?:(?:\+44\s?(?:\(0\)[\s-]*)?)?(?:(?:(?:1\d{1}|0[1-9])\d{8})|(?:\d{5}\s?\d{4,5})|(?:\d{4}\s?\d{5})))\b/g;
      // Use the match() method to find all matches in the text
        var matches = text.match(regex);
        if(matches){
          return matches;
      }
    }
}

function extractMobileNumbers_1(text) {
    // Define a regular expression to match 10-digit mobile numbers
    var regex = /\b\d{10}\b/g;
    // Use the match() method to find all matches in the text
    var matches = text.match(regex);
    if(matches){
      return matches;
    }else{
      var regex = /\b\d{11}\b/g;
      // Use the match() method to find all matches in the text
        var matches = text.match(regex);
        if(matches){
          return matches;
      }else{
      var regex = /(\+\d{1,2}\s?)?(\(\d{3}\)|\d{3})[-\s]?\d{3}[-\s]?\d{4}/g;
      var matches = text.match(regex);
        if(matches){
          // alert('1: '+ matches);
          return matches;
        }else{
          var regex = /(\+\d{1,2}\s?)?(\(\d{3}\)|\d{3})[-\s]?\d{3}[-\s]?\d{4}|\d{5}\s\d{5}/g;
        var matches = text.match(regex);
          if(matches){
            // alert('2: '+ matches);
            return matches;
          }else{
            var regex = /(\+\d{1,2}\s?)?(\(\d{3}\)|\d{3})[-\s]?\d{3,5}[-\s]?\d{4}/g;
          var matches = text.match(regex);
            if(matches){
              // alert('3: '+ matches);
              return matches;
            }else{
              var regex = /(\+\d{1,2}\s?)?(\(\d{3}\)|\d{3})[-\s]?\d{2,5}[-\s]?\d{5}/g;
            var matches = text.match(regex);
              if(matches){
                // alert('4: '+ matches);
                return matches;
              }else{
                // alert('5');
              }
            }
          } 
        }
      }
    }
}

function extractEmails(text) {
  // Define a regular expression pattern for matching email addresses
  var regex = /[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/g;

  // Use the match() method to find all matches in the text
  var matches = text.match(regex);

  return matches;
}

function extractUKPostcode(text) {
  // Define a regular expression pattern for matching email addresses
  var regex = /[A-Z]{1,2}\d[A-Z\d]? \d[A-Z]{2}/g;

  // Use the match() method to find all matches in the text
  var matches = text.match(regex);

  return matches;
}

function filepreview_Onchange(event, prviewdivid) {
    var file = event.target.files[0]; // Get the first selected file
    if (file) {
        var documentPath = URL.createObjectURL(file); // Create a URL for the file
        var extension = file.name.split('.').pop().toLowerCase(); // Get extension from the file name
        
        // Clear previous previews
        $("#" + prviewdivid).empty();

        if (extension === "pdf") {
            // For PDF files, embed a PDF viewer using an iframe
            var pdfIframe = $("<iframe>", {
                src: documentPath,
                frameborder: "0",
                width: "100%",
                height: "400px"
            });
            $("#" + prviewdivid).append(pdfIframe);

        } else if (extension === "txt" || extension === "md") {
            // For text-based files, use jQuery to load and display the content
            $.get(documentPath, function(data) {
                var contentDiv = $("<div>", {
                    html: $("<pre>", {
                        text: data
                    })
                });
                $("#" + prviewdivid).append(contentDiv);
            }).fail(function() {
                $("#" + prviewdivid).html("Error loading the document.");
            });

        } else if (extension === "doc" || extension === "docx" || extension === "xlsx" || extension === "csv") {
            // Convert DOC, DOCX, or XLSX to PDF using an external service
            var conversionUrl = 'https://view.officeapps.live.com/op/embed.aspx?src=' + encodeURIComponent(documentPath);
            var pdfIframe = $("<iframe>", {
                src: conversionUrl,
                frameborder: "0",
                width: "100%",
                height: "400px"
            });
            $("#" + prviewdivid).append(pdfIframe);

        } else if (["jpg", "jpeg", "png", "gif", "bmp", "svg"].includes(extension)) {
            // For image files, display the image
            var imgElement = $("<img>", {
                src: documentPath,
                alt: "Image preview",
                style: "max-width: 100%; height: auto;"
            });
            $("#" + prviewdivid).append(imgElement);

        } else {
            // Display a message for unsupported formats
            $("#" + prviewdivid).html("Preview for '" + extension + "' files is not supported.");
        }
    }
}



function validateEmailwithval($email) {
  var emailReg = /^([\w-\.]+@([\w-]+\.)+[\w-]{2,4})?$/;
  return emailReg.test( $email );
}

function validateMobile(mobile_no) {
  var mobile_noReg =/^\d{10}$/;
  return mobile_noReg.test( mobile_no );
}

function cancel () {
  document.execCommand('Stop')
  window.location.reload();
}

function refreshWindow(){
  window.location.reload(true);
}

function closeModal(modalid){
  $("#"+modalid+"").modal("hide");
}

function addRowwithEnterCell(tableid){
  document.getElementById(tableid).addEventListener("keydown", function (event) {
    if (event.key === "Enter") {
      event.preventDefault(); // Prevent default Enter behavior

      const table = event.target.closest("table");
      if (!table) return;

      const tbody = table.querySelector("tbody");
      const firstRow = tbody.rows[0]; // Get the first row of the tbody

      if (firstRow) {
        const newRow = firstRow.cloneNode(true); // Clone the first row deeply

        // Reset all fields in the cloned row
        const textInputs = newRow.querySelectorAll('input[type="text"]');
        textInputs.forEach(input => {
          input.value = ""; // Clear text input
        });

        const selects = newRow.querySelectorAll("select");
        selects.forEach(select => {
          select.selectedIndex = 0; // Reset select to the first option
        });

        const textareas = newRow.querySelectorAll("textarea");
        textareas.forEach(textarea => {
          textarea.value = ""; // Clear textarea
        });

        const checkboxes = newRow.querySelectorAll('input[type="checkbox"]');
        checkboxes.forEach(checkbox => {
          checkbox.checked = false; // Uncheck checkbox
        });

        const radios = newRow.querySelectorAll('input[type="radio"]');
        radios.forEach(radio => {
          radio.checked = false; // Uncheck radio button
        });

        tbody.appendChild(newRow); // Append the new row to the tbody
      }
    }
  });
}

function addRow(tableID) 
{
    var table = document.getElementById(tableID).getElementsByTagName('tbody')[0];
    // var tbody = document.getElementById('myTable').getElementsByTagName('tbody')[0];    
    var rowCount = table.rows.length;
    var row = table.insertRow(rowCount);
    var colCount = table.rows[0].cells.length;
    for(var i=0; i<colCount; i++)
    {
        var newcell = row.insertCell(i);
        newcell.innerHTML = table.rows[0].cells[i].innerHTML;
        for(var j=0; j<newcell.childNodes.length; j++){
            switch(newcell.childNodes[j].type) {
                case "text": case "hidden": case "date":
                    newcell.childNodes[j].value = "";
                    break;
                case "checkbox":
                    newcell.childNodes[j].checked=false;
                    break;
                case "select-one":
                    newcell.childNodes[j].selectedIndex = 0;
                    break;
            }
        }
    }
}

function addRowtbody(tableID) 
{
  var table = $('#'+tableID+'');
  var row = table.find('tbody tr:first').clone();
  row.find('input').each(function() {
      switch (this.type) {
          case 'text':
          case 'password':
          case 'date':
              $(this).val('');
              break;
          case 'checkbox':
              $(this).prop('checked', false);
              break;
      }
  });
  row.find('select').each(function() {
      $(this).prop('selectedIndex', 0);
  });

  table.find('tbody').append(row);
}

function deleteRowNew(tableID) {
  $('#'+tableID+'').on('click', '.delete-btn', function(){
    var table = $('#'+tableID+'');
    var rowlength = table.find('tbody tr').length;
    if(rowlength>1){
      $(this).closest('tr').remove();
    }else{
      alert('Atleast 1 row required in table head');
      return false;
    }
  });
}

function deleteRow(tableID) {
    try {
    var table = document.getElementById(tableID);
    var rowCount = table.rows.length;
    
    for(var i=2; i<rowCount; i++) {
        var row = table.rows[i];
        var chkbox = row.cells[0].childNodes[0];        
        if(null != chkbox && true == chkbox.checked) {
            
            table.deleteRow(i);
            rowCount--;
            i--;
        }else{
            
        }
    }
    }catch(e) {
        alert(e);
    }
}

function clear_radioinput(){
  $("input[type='radio']").prop("checked", false);
}

function clear_checkboxinput(){
  $("input[type='checkbox']").prop("checked", false);
}

function clear_textinput(){
  $("input[type='text']").val('');
}

function clear_hiddeninput(){
  $("input[type='hidden']").val('');
}

function clear_searchinput(){
  $("input[type='search']").val('');
}

function clear_dateinput(){
  $("input[type='date']").val('');
}

function clear_timeinput(){
  $("input[type='time']").val('');
}

function clear_numberinput(){
  $("input[type='number']").val('');
}

function clear_fileinput(){
  $("input[type='file']").val('');
}

function clear_textareiainput(){
  $("textarea").html('');
}


function clear_selectboxinput(){
  $("select").prop('selectedIndex', 0);
}

function clearFormElements(divId) {
  const container = document.getElementById(divId);
  if (!container) return;
  const inputs = container.querySelectorAll("input, textarea, select");
  inputs.forEach(element => {
    if (element.tagName.toLowerCase() === "select") {
      element.selectedIndex = 0; // Set to first option
    } else if (element.type === "checkbox" || element.type === "radio") {
      element.checked = false;
    } else {
      element.value = "";
    }
  });
}

function clearFormElementsInDiv(parentDivId) {
    const $parent = $("#" + parentDivId);    
    // Clear all text-like inputs
    $parent.find('input[type="text"], input[type="number"], input[type="email"], input[type="password"], input[type="url"], input[type="search"], input[type="date"], input[type="datetime-local"], textarea').val('');
    // Clear checkboxes and radios
    $parent.find('input[type="checkbox"], input[type="radio"]').prop('checked', false);
    // Reset regular select elements
    $parent.find('select').not('.select2').prop('selectedIndex', 0);
    // Reset select2 elements
    $parent.find('select.select2').val(null).trigger('change');
}


function bindUpperCaseInput(className) {
  // Select all elements with the given class
  document.querySelectorAll(`.${className}`).forEach(input => {
    // Attach the 'input' event to each element
    input.addEventListener("input", function (event) {
      event.target.value = event.target.value.toUpperCase();
    });
  });
}

function UpperCaseInput() {
  // Select all elements with the given class
  document.querySelectorAll(`.ucaseinput`).forEach(input => {
    // Attach the 'input' event to each element
    input.addEventListener("input", function (event) {
      event.target.value = event.target.value.toUpperCase();
    });
  });
}

function sweetalertmessage(errortype,message){
  Swal.fire({
    position: 'top-end',
    icon: errortype,
    title: message,
    showConfirmButton: false,
    timer: 1500
  })
}

function SweetAlertmessageFadein(status,message){
  if(status=='success' || status=='Success'){
    Swal.fire({
      title: "Success!",
      text: message,
      icon: "success",
      showClass: {
        popup: 'animate__animated animate__fadeInDown'
      },
      hideClass: {
        popup: 'animate__animated animate__fadeOutUp'
      }
    }).then((result) => {
      if (result.isConfirmed) {
        // Page refresh after clicking "OK"
        window.location.reload();
      }
    });
  }else if(status=='error' || status=='Error'){
    Swal.fire({
      title: "Error!",
      text: message,
      icon: "error",
      showClass: {
        popup: 'animate__animated animate__fadeInDown'
      },
      hideClass: {
        popup: 'animate__animated animate__fadeOutUp'
      }
    }).then((result) => {
      if (result.isConfirmed) {
        // Page refresh after clicking "OK"
        window.location.reload();
      }
    });
  }else if(status=='info' || status=='Info'){
    Swal.fire({
      title: "Info!",
      text: message,
      icon: "error",
      showClass: {
        popup: 'animate__animated animate__fadeInDown'
      },
      hideClass: {
        popup: 'animate__animated animate__fadeOutUp'
      }
    }).then((result) => {
      if (result.isConfirmed) {
        // Page refresh after clicking "OK"
        window.location.reload();
      }
    });
  }
}

function SweetAlertmessageFadeinwithoutrefresh(status,message){
  if(status=='success' || status=='Success'){
    Swal.fire({
      title: "Success!",
      text: message,
      icon: "success",
      showClass: {
        popup: 'animate__animated animate__fadeInDown'
      },
      hideClass: {
        popup: 'animate__animated animate__fadeOutUp'
      }
    }).then((result) => {
      if (result.isConfirmed) {
        // Page refresh after clicking "OK"
        // window.location.reload();
      }
    });
  }else if(status=='error' || status=='Error'){
    Swal.fire({
      title: "Error!",
      text: message,
      icon: "error",
      showClass: {
        popup: 'animate__animated animate__fadeInDown'
      },
      hideClass: {
        popup: 'animate__animated animate__fadeOutUp'
      }
    }).then((result) => {
      if (result.isConfirmed) {
        // Page refresh after clicking "OK"
        // window.location.reload();
      }
    });
  }else if(status=='info' || status=='Info'){
    Swal.fire({
      title: "Info!",
      text: message,
      icon: "error",
      showClass: {
        popup: 'animate__animated animate__fadeInDown'
      },
      hideClass: {
        popup: 'animate__animated animate__fadeOutUp'
      }
    }).then((result) => {
      if (result.isConfirmed) {
        // Page refresh after clicking "OK"
        //window.location.reload();
      }
    });
  }
}

function sweetalert_toprightcorner_singleline(errortype,message){
  const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 5000,
    timerProgressBar: true,
    didOpen: (toast) => {
      toast.addEventListener('mouseenter', Swal.stopTimer)
      toast.addEventListener('mouseleave', Swal.resumeTimer)
    }
  })

  Toast.fire({
    icon: errortype,
    title: message
  })
}

function alertmessage(errortype,message){
    if(errortype=='error'){
      iziToast.error({
        title: 'Error',
        message: message,
      });
    }else if(errortype=='warning'){
      iziToast.warning({
        title: 'Warning',
        message: message,
      });
    }else if(errortype=='success'){
      iziToast.success({
        title: 'Success',
        message: message,
      });
    }
}

function messageAfterresult(viewtitle,viewmessage){
  iziToast.show({
  // timeout: 6000,
  zindex: 999,
    theme: 'dark',
    // icon: 'icon-person',
    title: viewtitle,
    message: viewmessage,
    position: 'center', // bottomRight, bottomLeft, topRight, topLeft, topCenter, bottomCenter
    progressBarColor: 'rgb(0, 255, 184)',
    buttons: [
        ['<button>Close</button>', function (instance, toast) {
            instance.hide({
                transitionOut: 'fadeOutUp',
                onClosing: function(instance, toast, closedBy){
                    console.info('closedBy: ' + closedBy); // The return will be: 'closedBy: buttonName'
                }
            }, toast, 'buttonName');

        }]
    ],
    onClosing: function(instance, toast, closedBy){
        // location.reload();
        console.info('closedBy: ' + closedBy); // tells if it was closed by 'drag' or 'button'
    }
});
}

function messageAfterresultwithoption(){
  Swal.fire({
    title: "Do you want to save the changes?",
    showDenyButton: true,
    showCancelButton: true,
    confirmButtonText: "Save",
    denyButtonText: `Don't save`
  }).then((result) => {
    /* Read more about isConfirmed, isDenied below */
    if (result.isConfirmed) {
      Swal.fire("Saved!", "", "success");
    } else if (result.isDenied) {
      Swal.fire("Changes are not saved", "", "info");
    }
  });
}

function addZerobeforten(i) {
  if (i < 10) {i = "0" + i}
  return i;
}

function removeZeroBeforeTen(i) {
  if (i[0] === "0") {
    i = i.slice(1);
  }
  return i;
}

function addMinutesToTime(time, minsAdd) {
  function z(n){ return (n<10? '0':'') + n;};
  var bits = time.split(':');
  var mins = bits[0]*60 + +bits[1] + +minsAdd;
  return z(mins%(24*60)/60 | 0) + ':' + z(mins%60);
}

function subtractMinutesToTime(time, minsAdd) {
  function z(n){ return (n<10? '0':'') + n;};
  var bits = time.split(':');
  var mins = bits[0]*60 + +bits[1] - +minsAdd;
  return z(mins%(24*60)/60 | 0) + ':' + z(mins%60);
}


function adddaysingivedate(datevalue, noofdays){
    // concantenate values to date_start and date_end hidden inputs
      var dateString = datevalue,
      date = new Date(dateString);
      var daten = date.getFullYear() + '-'+ ('0' + (date.getMonth()+1).toString()).slice(-2) + '-' + ('0' +date.getDate().toString()).slice(-2);

      var numDays = noofdays;
      date.setDate(date.getDate() + parseInt(numDays)); 

      var dateEnd = date.getFullYear() + '-'+ ('0' + (date.getMonth()+1).toString()).slice(-2) + '-'+ ('0' + date.getDate().toString()).slice(-2);
   return dateEnd;
}

function calculatedaysbetweendates(fromdate,todate){
  var dt1 = new Date(fromdate);
    var dt2 = new Date(todate);
    var time_difference = dt2.getTime() - dt1.getTime();
    var result = time_difference / (1000 * 60 * 60 * 24);
    return result;
}

function calculateyearmonthbetweendates(fromdate,todate){
  var date1 = new Date(fromdate);
    var date2 = new Date(todate);

    var timeDifference = date2 - date1; // Difference in milliseconds

    // Convert milliseconds to days
    var daysDifference = Math.floor(timeDifference / (1000 * 60 * 60 * 24));

    // Calculate years and months
    var yearsDifference = Math.floor(daysDifference / 365.25); // Average days per year including leap years
    var monthsDifference = Math.floor((daysDifference % 365.25) / 30.44); // Average days per month

    // Calculate remaining days
    var result = yearsDifference+'.'+monthsDifference;
    return result;
}

function changeDateFormatYmd_dMY(dateStr) {
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
}

function changeDateFormatYmd_ddmmyyyy(dateStr) {
    // Check if dateStr is a valid string
    if (typeof dateStr !== "string" || !dateStr.includes("-")) {
        return "Invalid date format";
    }

    // Split the date string by hyphen
    const dateParts = dateStr.split("-");

    // Ensure we have exactly 3 parts
    if (dateParts.length !== 3) {
        return "Invalid date format";
    }

    const [year, month, day] = dateParts;

    // Check if all parts are numeric and valid
    if (
        isNaN(year) || isNaN(month) || isNaN(day) ||
        year.length !== 4 || month.length !== 2 || day.length !== 2
    ) {
        return "Invalid date format";
    }

    // Return the formatted date in dd-mm-yyyy format
    return `${day}-${month}-${year}`;
}


function changeDateFormat_dMY_Ymd(dateStr) {
    if (!dateStr || typeof dateStr !== "string" || !dateStr.includes("-")) {
        return dateStr || "";
    }
    // Split the date string by the hyphen
    const dateParts = dateStr.split("-");
    if (dateParts.length < 3) {
        return dateStr;
    }

    // Extract day, month name, and year
    const day = dateParts[0];
    const monthName = dateParts[1];
    const year = dateParts[2];

    // Define month names
    const monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
    var monthIndex = 0;
    if(monthNames.includes(monthName)){
      monthIndex = monthNames.indexOf(monthName) + 1;
    }
    else{
      if(isNum(monthName)){
        monthIndex = parseInt(monthName, 10);
      }
    }
    
    // Pad the month with leading zero if needed
    const month = monthIndex < 10 ? `0${monthIndex}` : monthIndex;

    // Return the formatted date
    return `${year}-${month}-${(day || '').padStart(2, '0')}`;
}

function isNum(str) {
    return !isNaN(str);
}

function isValidFinancialYear(financialYear) {
    // Regular expression to match the format 'YYYY-YYYY'
    const fyRegex = /^\d{4}-\d{4}$/;

    // Check if it matches the format
    if (!fyRegex.test(financialYear)) {
        return false; // Invalid format
    }

    // Split the years
    const years = financialYear.split('-');
    const startYear = parseInt(years[0], 10);
    const endYear = parseInt(years[1], 10);

    // Validate the logical conditions
    if (endYear !== startYear + 1) {
        return false; // End year should be the next year after start year
    }

    if (startYear < 1900 || endYear > 2100) {
        return false; // Restrict to a reasonable range for financial years
    }

    return true; // Valid financial year
}

function getCurrentFinancialYear(inputDateStr, startMonth = 4) {
    const inputDate = new Date(inputDateStr);
    if (isNaN(inputDate)) {
        return "Invalid Date";
    }

    const month = inputDate.getMonth() + 1; // JS months are 0-indexed
    const year = inputDate.getFullYear();

    let startYear, endYear;

    if (month >= startMonth) {
        startYear = year;
        endYear = year + 1;
    } else {
        startYear = year - 1;
        endYear = year;
    }

    return `${startYear}-${endYear}`;
}

function getFinancialYear(startMonth = 4) {
    const today = new Date();
    const currentYear = today.getFullYear();
    const currentMonth = today.getMonth() + 1; // 0-based index

    let startYear, endYear;

    if (currentMonth < startMonth) {
        startYear = currentYear - 1;
        endYear = currentYear;
    } else {
        startYear = currentYear;
        endYear = currentYear + 1;
    }

    return `${startYear}-${endYear}`;
}

function autofillDatesFromFY(finyearval, startmonth=4, datefrom_eid, dateto_eid) {
  if (!finyearval || !datefrom_eid || !dateto_eid) return;

  const fyRegex = /^\d{4}-\d{4}$/;
  const $fromInput = $('#' + datefrom_eid).prop("disabled", true);
  const $toInput = $('#' + dateto_eid).prop("disabled", true);

  if (!fyRegex.test(finyearval)) {
    $fromInput.val('').prop("disabled", false);
    $toInput.val('').prop("disabled", false);
    return false;
  }

  const [startYear, endYear] = finyearval.split('-').map(Number);
  const isCalendarYear = startYear === endYear;
  const startMonth = isCalendarYear ? 0 : 3; // 0 = Jan, 3 = Apr

  const fromDate = new Date(startYear, startMonth, 1);
  let toDate;

  if (isCalendarYear) {
    toDate = new Date(endYear, 11, 31); // Dec 31 of same year
  } else {
    toDate = new Date(endYear, startMonth, 0); // Last day of March (or previous month)
  }
  const today = new Date();
  today.setHours(0, 0, 0, 0); // Normalize

  if (toDate > today) toDate = today;

  const formatDate = (date) => {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
  };

  $fromInput.val(formatDate(fromDate));
  $toInput.val(formatDate(toDate)).trigger("change");
}

// -------------------------------------------
//alphanumeric_onlys
function alphanumeric_onlys(event)
 {
 var keycode;
 keycode=event.keyCode?event.keyCode:event.which;
 if ((keycode == 8) || (keycode >47 && keycode < 58) || (keycode == 13) || (keycode == 61) || (keycode >= 32 && keycode <= 36) || (keycode >= 39 && keycode <= 41) || (keycode >= 44 && keycode <= 47) || (keycode >= 65 && keycode <= 90) || (keycode >= 97 && keycode <= 122)) 
  {
        return true; 
  }
 else 
  {
      return false;
  }

}

function isFloatNumber(event, input) {
    event = event || window.event;
    var charCode = event.which || event.keyCode;
    
    // Get current input value
    var value = input.value;
    
    // Prevent multiple decimal points
    if (charCode === 46 && value.indexOf('.') !== -1) {
        return false;
    }

    // Allow only numbers (48-57) and a single decimal point (46)
    if ((charCode >= 48 && charCode <= 57) || charCode === 46) {
        return true;
    } else {
        return false;
    }
}
 
//only isNumber
function isNumber(event) {
  event = (event) ? event : window.event;
  var charCode = (event.which) ? event.which : event.keyCode;
  if ((charCode > 47 && charCode < 58) ) //|| charCode ==46
  {
      return true;
  }
  else
  {
      return false;
  }
}


function convertToFloat(inputElement) {
    const $input = $(inputElement);
    const value = $input.val().trim();
    const floatValue = parseFloat(value);

    if (isNaN(floatValue) || floatValue <= 0) {
        $input.css("border", "2px solid red").focus();
        return null;
    }

    $input.css("border", ""); // Remove red border if valid
    return floatValue;
}

function removeContainer(containerId) {
  $("#" + containerId).remove(); // Removes the container div
}


// Restrict input to numbers and a single decimal point
$(document).on("input", ".float-only", function () {
    this.value = this.value.replace(/[^0-9.]/g, ""); // Allow only numbers and dots
    if ((this.value.match(/\./g) || []).length > 1) {
        this.value = this.value.slice(0, -1); // Prevent multiple dots
    }
});

// Function to convert a string to a float with 2 decimal places
function convertToFloatWithValue(value) {
    if (typeof value !== "string") {
        alert("Invalid input. Please enter a valid number.");
        return null;
    }
    let floatValue = parseFloat(value.trim()); // Trim and convert to float
    if (isNaN(floatValue)) {
        alert("The entered value is not a valid number."); // Handle invalid input
        return null;
    }
    return parseFloat(floatValue.toFixed(2)); // Return float rounded to 2 decimal places
}


//Only one dot allowed and after dot allowed only 2 digits
function CheckFloatAmount(el, evt) {
    var charCode = (evt.which) ? evt.which : event.keyCode;
    var number = el.value.split('.');
    if (charCode != 46 && charCode > 31 && (charCode < 48 || charCode > 57)) {
        return false;
    }
    //just one dot
    if(number.length>1 && charCode == 46){
         return false;
    }
    //get the carat position
    var caratPos = getSelectionStart(el);
    var dotPos = el.value.indexOf(".");
    if( caratPos > dotPos && dotPos>-1 && (number[1].length > 1)){
        return false;
    }
    return true;
}

function getSelectionStart(o) {
  if (o.createTextRange) {
    var r = document.selection.createRange().duplicate()
    r.moveEnd('character', o.value.length)
    if (r.text == '') return o.value.length
    return o.value.lastIndexOf(r.text)
  } else return o.selectionStart
}


//Alphabets Only
function onlyAlphabets(e) {
try {
   if (window.event) {
    var charCode = window.event.keyCode;
  }
   else if (e) {
     var charCode = e.which;
   }
   else { return true; }
  if ((charCode == 32)||(charCode > 64 && charCode < 91) || (charCode > 96 && charCode < 123) )
    return true;
  else
    return false;
  }
  catch (err) {
  alert(err.Description);
  }
}


//Address only
function AddressOnlys(evt) {
  evt = (evt) ? evt : window.event;
    var charCode = (evt.which) ? evt.which : evt.keyCode;

  if ((charCode == 8)  || (charCode == 110) || (charCode >=40 && charCode < 58)|| (charCode >= 65 && charCode <= 90) || (charCode == 13) || (charCode == 32) || (charCode >= 97 && charCode <= 125)) 
  {
        return true; 
  }
  else 
  {
      return false;
  }
}

function validateIndianBankAccountNumber(accountNumberinputid) {
  // Indian bank account numbers are typically between 9 and 18 digits
  var accountNumber = $('#' + accountNumberinputid + '').val();
  if(accountNumber!=""){
    let regex = /^\d{9,18}$/;
    if(regex.test(accountNumber)==false){
      $('#' + accountNumberinputid + '').css({
        "border": "2px solid red"
      });
      result = false;
    } else {
      $('#' + accountNumberinputid + '').css({
        "border": "2px solid white"
      });
    }
    return false;
  }else{
    $('#' + accountNumberinputid + '').css({
      "border": "2px solid white"
    });
  }    
}

function validateIFSCCode(ifscCodeinputid) {
    // IFSC code format: 4 alphabets, 1 mandatory '0', 6 alphanumeric characters
  var ifscCode = $('#' + ifscCodeinputid + '').val();
  if(ifscCode!=""){
    let regex = /^[A-Z]{4}0[A-Z0-9]{6}$/i;
    if(regex.test(ifscCode)==false){
      $('#' + ifscCodeinputid + '').css({
        "border": "2px solid red"
      });
      result = false;
    } else {
      $('#' + ifscCodeinputid + '').css({
        "border": "2px solid white"
      });
    }
    return false;
  }else{
    $('#' + ifscCodeinputid + '').css({
      "border": "2px solid white"
    });
  }   
}

function validatePAN(paninputid) {
  // Get the PAN number value from the input field
  var panno = $('#' + paninputid).val().trim();
  // If the PAN number field is not empty
  if (panno !== "") {
    // Check the length of the input (should be exactly 10)
    if (panno.length !== 10) {
      $('#' + paninputid).css({
        "border": "2px solid red"
      });
      return false; // Return false for incorrect length
    }
    // Define the PAN validation regex
    let regex = /^[A-Z]{5}[0-9]{4}[A-Z]$/;
    // Check if the PAN number is valid
    if (!regex.test(panno)) {
      // If invalid, set the border to red
      $('#' + paninputid).css({
        "border": "2px solid red"
      });
      return false; // Return false for invalid PAN
    } else {
      // If valid, set the border to white (or default)
      $('#' + paninputid).css({
        "border": "2px solid white"
      });
      return true; // Return true for valid PAN
    }
  } else {
    // If the input is empty, reset the border
    $('#' + paninputid).css({
      "border": "2px solid white"
    });
    return false; // Return false for empty input
  }
}

function validateGST(gstinputid) {
  // Indian bank account numbers are typically between 9 and 18 digits
  var gstno = $('#' + gstinputid + '').val();
  if(gstno!=""){
    let regex = /^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[A-Z0-9]{1}Z[A-Z0-9]{1}$/;
    if(regex.test(gstno)==false){
      $('#' + gstinputid + '').css({
        "border": "2px solid red"
      });
      result = false;
    } else {
      $('#' + gstinputid + '').css({
        "border": "2px solid white"
      });
    }
    return false;
  }else{
    $('#' + gstinputid + '').css({
      "border": "2px solid white"
    });
  }    
}

function validateMobile(mobileinputid) {
  // Indian bank account numbers are typically between 9 and 18 digits
  var mobileno = $('#' + mobileinputid + '').val();
  if(mobileno!=""){
    let regex = /^[6-9]\d{9}$/;
    if(regex.test(mobileno)==false){
      $('#' + mobileinputid + '').css({
        "border": "2px solid red"
      });
      result = false;
    } else {
      $('#' + mobileinputid + '').css({
        "border": "2px solid white"
      });
    }
    return false;
  }else{
    $('#' + mobileinputid + '').css({
      "border": "2px solid white"
    });
  }    
}

function validateEmail(input_id) {
  var email = $('#' + input_id + '').val();
  if (email != "") {
    var reg = /^([A-Za-z0-9_\-\.])+\@([A-Za-z0-9_\-\.])+\.([A-Za-z]{2,4})$/;
    if (reg.test(email) == false) {
      $('#' + input_id + '').css({
        "border": "2px solid red"
      });
      result = false;
    } else {
      $('#' + input_id + '').css({
        "border": "2px solid white"
      });
    }
    return false;
  } else {
    $('#' + input_id + '').css({
      "border": "2px solid white"
    });
  }
}

function validateCreditCardNumber(cardNumber) {
  // Remove any non-digit characters (e.g., spaces or dashes)
  cardNumber = cardNumber.replace(/\D/g, '');

  // Check if the card number has the correct length (typically 13-19 digits)
  if (cardNumber.length < 13 || cardNumber.length > 19) {
      return false;
  }

  // Luhn Algorithm for validation
  let sum = 0;
  let alt = false;

  for (let i = cardNumber.length - 1; i >= 0; i--) {
      let n = parseInt(cardNumber.charAt(i), 10);

      if (alt) {
          n *= 2;
          if (n > 9) {
              n -= 9;
          }
      }

      sum += n;
      alt = !alt;
  }

  // Valid if the sum is a multiple of 10
  return sum % 10 === 0;
}

function isValidAmount(amountInputId) {
  // Get the input value
  var amount = $('#' + amountInputId).val().trim();

  // Regular expression to check if the value is a valid amount
  // Allows up to 2 decimal places
  var amountRegex = /^(?!0\.00)\d+(\.\d{1,2})?$/;

  if (amount === "") {
    // If the input is empty, show an error and return false
    $('#' + amountInputId).css("border", "2px solid red");
    return false;
  } else if (!amountRegex.test(amount)) {
    // If the input does not match the regex, show an error and return false
    $('#' + amountInputId).css("border", "2px solid red");
    return false;
  } else {
    // If the input is valid, reset the border color and return true
    $('#' + amountInputId).css("border", "2px solid green");
    return true;
  }
}

function toTitleCase(str) {
    return str.replace(/(?:^|\s)\w/g, function(match) {
        return match.toUpperCase();
    });
}

function ChangetoLowerCase(str) {
    return str.toLowerCase();
}

function formatCurrency(value) {
  const num = parseFloat(value) || 0; // Parse or default to 0
  return num.toFixed(2); // Format to 2 decimal places (string)
}


function audiofileview(rooturl,audiofilepath,voicefilehtmlid){
  $("#"+voicefilehtmlid+"").append("<table style='width:100%;'><tr><td><p class='text-right mb-0'><i class='audiotimescircle' style='cursor:pointer;'>&#10060;</i></p><div><audio controlsList='nodownload' class='form-control audioPlayback' src='"+rooturl+audiofilepath+"' controls></audio></div></td></tr></table>");
}


  function getTagNameById(elementId) {
      // Get the element by ID and retrieve its tag name
      const tagName = $('#' + elementId).prop('tagName').toLowerCase();
      return tagName;
  }

  function emulateKeyPress(keyCode, elementId) {
      const e = $.Event('keydown', { keyCode: keyCode, which: keyCode });
      $('#' + elementId).trigger(e);
  }

  function getvalforText(mySelect,text) {
    var select = $('#'+mySelect);
    var options = select.find('option').filter(function() {
      return $(this).text() === text;
    });

    if (options.length) {
      var value = options.val();
      return value;
    } else {
      return null;
    }
  }


function filterTable(inputSelector, tableSelector) {
    // Check if inputSelector and tableSelector exist
    if ($(inputSelector).length === 0 || $(tableSelector).length === 0) {
        console.error("Invalid selectors: ", inputSelector, tableSelector);
        return;
    }

    // Listen for keyup events on the input field
    $(inputSelector).on("keyup", function () {
        var value = $(this).val().toLowerCase(); // Get the search query

        // Loop through each table row
        $(tableSelector + " tbody tr").each(function () {
            var row = $(this);
            var rowMatches = false;

            // Search in all <td> elements and their contents
            row.find("td").each(function () {
                var cell = $(this);
                var cellText = cell.text().toLowerCase(); // Get text content
                var inputValue = cell.find("input, select, textarea").val() || ""; // Get value from form elements
                inputValue = inputValue.toLowerCase(); // Convert to lowercase for comparison

                // Check if the search query matches either the cell text or input value
                if (cellText.indexOf(value) > -1 || inputValue.indexOf(value) > -1) {
                    rowMatches = true;
                    return false; // Exit loop early if a match is found
                }
            });

            // Show or hide the row based on the match
            if (rowMatches) {
                row.show();
            } else {
                row.hide();
            }
        });
    });
}
//Anant
function filterTablechange(inputSelector, tableSelector) {
    // Check if inputSelector and tableSelector exist
    if ($(inputSelector).length === 0 || $(tableSelector).length === 0) {
        console.error("Invalid selectors: ", inputSelector, tableSelector);
        return;
    }

    // Listen for keyup events on the input field
    //$(inputSelector).on("keyup", function () {
        var value = $(inputSelector).val().toLowerCase(); // Get the search query

        // Loop through each table row
        $(tableSelector + " tbody tr").each(function () {
            var row = $(this);
            var rowMatches = false;

            // Search in all <td> elements and their contents
            row.find("td").each(function () {
                var cell = $(this);
                var cellText = cell.text().toLowerCase(); // Get text content
                var inputValue = cell.find("input, select, textarea").val() || ""; // Get value from form elements
                inputValue = inputValue.toLowerCase(); // Convert to lowercase for comparison

                // Check if the search query matches either the cell text or input value
                if (cellText.indexOf(value) > -1 || inputValue.indexOf(value) > -1) {
                    rowMatches = true;
                    return false; // Exit loop early if a match is found
                }
            });

            // Show or hide the row based on the match
            if (rowMatches) {
                row.show();
                 row.find('input[type="checkbox"]').prop('checked', true);
            } else {
                row.hide();
                row.find('input[type="checkbox"]').prop('checked', false);
            }
        });
    //});
}

function previewmobilepdf(pdfpath, pdfcontainer) {
    var container = document.getElementById(pdfcontainer);
    container.innerHTML = "";
    container.style.width = "100%";
    container.style.height = "600px";
    container.style.overflow = "auto";
    container.style.backgroundColor = "black";
    var loadingTask = pdfjsLib.getDocument(pdfpath);
    loadingTask.promise.then(function(pdf) {
        for (var cnt = 1; cnt <= pdf.numPages; cnt++) {
            pdf.getPage(cnt).then(function(page) {
                var scale = 1;
                var viewport = page.getViewport({ scale: scale });

                var canvas = document.createElement('canvas');
                canvas.setAttribute("id", "pg" + cnt);
                canvas.setAttribute("oncontextmenu", "return false;");
                canvas.style.margin = '4px';
                container.appendChild(canvas);

                var context = canvas.getContext("2d");
                canvas.height = viewport.height;
                canvas.width = viewport.width;

                var renderContext = {
                    canvasContext: context,
                    viewport: viewport
                };
                page.render(renderContext);
            });
        }
    });
}

function showPreview_document(filetarget, file_passed_as = "") {
    var previewDivHtml = `
        <div class="modal fade" id="previewdocumentdiv" style="max-height: 90vh;" tabindex="-1" data-backdrop="static" data-keyboard="false">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Preview(s)</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div id="previewDiv" style="max-height:65vh;overflow:auto"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="button" id="deleteFileBtn" class="btn btn-danger" style="display: none;">Delete</button>
                    </div>
                </div>
            </div>
        </div>`;
    var modal = document.getElementById("previewdocumentdiv");
    if (!modal) {
        document.body.insertAdjacentHTML('beforeend', previewDivHtml);
        modal = document.getElementById("previewdocumentdiv");
    }
    var previewDiv = document.getElementById("previewDiv");
    previewDiv.innerHTML = ''; // Clear previous preview
    let file, fileType;
    // alert(file_passed_as+'==='+filetarget.files[0]);
    if (file_passed_as === "blob" && filetarget.files && filetarget.files[0]) {
        file = filetarget.files[0];
        fileType = file.type.toLowerCase();
        if (!file || file.size === 0) {
            alert("The file is empty or not selected.");
            return false;
        }
    } else if (typeof filetarget === "string") {
        if (!filetarget) {
            return false;
        }
        file = filetarget; 
        fileType = filetarget.split('.').pop().toLowerCase();;
    } else {
        alert("Invalid file target or type.");
        return false;
    }
    var container = document.createElement('div');
    container.className = 'preview-item';    
    if (file_passed_as === "blob") {
        var reader = new FileReader();
        reader.onload = function(e) {
            if (fileType.includes('image')) {
                var img = document.createElement('img');
                img.src = e.target.result;
                container.appendChild(img);
            } else if (fileType === 'application/pdf') {
                var fileUrl = URL.createObjectURL(file);
                previewmobilepdf(fileUrl, "previewDiv");
            } else {
                container.innerHTML = '<p>Unsupported file type.</p>';
            }
            previewDiv.appendChild(container);
        };
        reader.readAsDataURL(file);
    } else {
        if (fileType.includes('image')||fileType.includes('jpeg')||fileType.includes('jpg')||fileType.includes('png')) {
            var img = document.createElement('img');
            img.src = file;
            container.appendChild(img);
        } else if (fileType === 'application/pdf' ||fileType === 'pdf') {
            previewmobilepdf(file, "previewDiv");
        } else {
            container.innerHTML = '<p>Unsupported file type.</p>';
        }
        previewDiv.appendChild(container);
    }
    var deleteFileBtn = modal.querySelector("#deleteFileBtn");
    deleteFileBtn.onclick = function() {
        if (filetarget instanceof HTMLInputElement && filetarget.files) {
          filetarget.value = ""; // Clear the file input value 
          filetarget.dispatchEvent(new Event('change'));
        }
        $("#previewdocumentdiv").modal('hide');
    };
    $('#previewdocumentdiv').modal('show');
    deleteFileBtn.style.display = file_passed_as === "blob" ? "inline-block" : "none";
    $('#previewdocumentdiv').on('hidden.bs.modal', function () {
        // Re-apply scroll lock to the background modal when the preview modal is closed
        if ($('.modal.show').length > 0) {
            $('body').addClass('modal-open');
        }
    });
}


function detectmobile(){
   var agnt = navigator.userAgent;
   var isMobile = {
    Android: function() {
        return agnt.match(/Android/i);
    },
    BlackBerry: function() {
        return agnt.match(/BlackBerry/i);
    },
    iOS: function() {
        return agnt.match(/iPhone|iPad|iPod/i);
    },
    Opera: function() {
        return agnt.match(/Opera Mini/i);
    },
    Windows: function() {
        return agnt.match(/IEMobile/i) || navigator.userAgent.match(/WPDesktop/i);
    },
    any: function() {
        return (isMobile.Android() || isMobile.BlackBerry() || isMobile.iOS() || isMobile.Opera() || isMobile.Windows());
    }
};

  if (isMobile.any()){
  return "mobile";
  }
  else  {
  return "desktop";
  }
}

function copyText(sourceinput,destinationinput) {
    // Get the source input field value
    var sourceText = document.getElementById(sourceinput).value;
    // Set the destination input field value to the source text
    document.getElementById(destinationinput).value = sourceText;
}

function copyTextCheckboxchecked(sourceinput,destinationinput,checkboxid) {
    // Get the source input field value
  if($("#"+checkboxid+"").is(":checked")){
    var sourceText = document.getElementById(sourceinput).value;
    // Set the destination input field value to the source text
    document.getElementById(destinationinput).value = sourceText;
  }else{
    document.getElementById(destinationinput).value ="";
  }
    
}


function checkradioifvalue(radioinputname,targetValue){
  // Select all radio buttons with the name 'option'
  const radios = document.querySelectorAll('input[name="'+radioinputname+'"]');
  // Loop through the radio buttons to find the match
  if(targetValue && radios){
    for (const radio of radios) {
      if (radio.value.toLowerCase() === targetValue.toLowerCase()) {
        radio.checked = true;
        break; // Exit the loop after finding the match      
      }
    }
  }
}

function selectOptionIfTextExists(selector, text) {
  const option = $(selector).find('option').filter(function() {
      return $(this).text() === text; // Match the text
  });

  if (option.length > 0) { // Check if the option exists
      option.prop('selected', true); // Set it as selected
      $(selector).trigger('change'); // Trigger the change event
  }
}

function printModalContent(modalId) {
    // Get the content of the specified modal
    const modalContent = document.getElementById(modalId).innerHTML;

    // Open a new window to hold the modal content
    const printWindow = window.open('', '_blank', 'width=600,height=600');

    // Write the modal content to the new window, with basic HTML structure and styles
    printWindow.document.write(`
        <html>
            <head>
                <title>Print Modal</title>
                <style>
                    /* Add any styles you need for the print layout */
                    body, .modal-content {
                        font-family: Arial, sans-serif;
                        margin: 0;
                        padding: 20px;
                        font-size: 1em;
                    }
                </style>
            </head>
            <body>${modalContent}</body>
        </html>
    `);
 
    printWindow.document.close();

    // Wait for the content to load and then trigger print
    printWindow.onload = function () {
        printWindow.print();
        printWindow.close();
    };
}
function toggleSubCheckboxes(moduleName) { 
  let mainCheckbox = document.getElementById(`main_${moduleName}`);
  let isChecked = mainCheckbox.checked; 
  let subCheckboxes = document.querySelectorAll(`.sub_${moduleName}`);
  let subRows = subCheckboxes.forEach(subCheckbox => {
    let parentRow = subCheckbox.closest("tr"); 
   // parentRow.style.display = isChecked ? "table-row" : "none"; 
    subCheckbox.checked = isChecked;
  });
}
function checkMainCheckbox(moduleName) {
    let mainCheckbox = document.getElementById(`main_${moduleName}`);
    let subCheckboxes = document.querySelectorAll(`.sub_${moduleName}`);
    let allChecked = Array.from(subCheckboxes).every(subCheckbox => subCheckbox.checked);
    mainCheckbox.checked = allChecked;
}
$(document).delegate(".heading td","click",function(){
  if($(this).index()>0){
    const classes = $(this).closest("tr").attr('class').split(' ');
    const dynamicClass = classes.find(cls => cls !== 'heading');
    $(`.details.${dynamicClass}`).toggle();
  }
});

$(document).delegate(".all-opendetailtag","click",function(){
  const details = document.querySelectorAll('details');
  details.forEach(detail => {
      detail.open = true; // Set the open property to true
  });
});

$(document).delegate(".all-closedetailtag","click",function(){
  const details = document.querySelectorAll('details');
  details.forEach(detail => {
      detail.open = false; // Set the open property to true
  });
});

$(document).on("input change", "input[required], select[required], textarea[required]", function() {
    if ($(this).val().trim() !== "") {
        $(this).css("border", ""); // Remove red border only for required fields
    }
});

function chkmoduletbl(tblid) {
    var Authmdl = [];
    var i = 0; 
    $('#'+tblid+ ' tbody tr').each(function() { 
        var checkbox = $(this).find('input[type="checkbox"]'); // This finds both main and sub checkboxes 
        var moduleidInput = $(this).find("td.modulename_td input.moduleid_input");
        var tablename = $(this).data('usertablename')||""; //modified by ajay 31-05-2025 
        var nonintegrated_groupid = $(this).data('nonintegrated_groupid')||""; //modified by ajay 31-05-2025 
          //modified by ajay 31-05-2025 
        if (checkbox.is(":checked") && moduleidInput.val()) {
            // Add the moduleid to the Authmdl array
            Authmdl[i] = {
                "moduleid": moduleidInput.val(),
                "non_integratedtbl": tablename ,//modified by ajay 31-05-2025
                "nonintegrated_groupid": nonintegrated_groupid //modified by ajay 31-05-2025
            };
            i++;
        }
    });

    return Authmdl;
}

function initializeDateRangePicker(daterangeId, headingdisplayId, dateFrom, dateTo, rangeval=null) {
  if(rangeval=="Last7Days"){
    var start = moment().subtract(6, 'days');
    var end = moment();
    var ranges = 'Last 7 Days';
  }else if(rangeval=="Last30Days"){
    var start = moment().subtract(29, 'days');
    var end = moment();
    var ranges = 'Last 30 Days';
  }else if(rangeval=="ThisMonth"){
    var start = moment().startOf('month');
    var end = moment().endOf('month');
    var ranges = 'This Month';
  }else{
    var start = moment();
    var end = moment();
    var ranges = 'Today';
  }
    function cb(start, end, ranges) {
        $(`#${daterangeId} span`).html(start.format('DD-MM-YYYY') + ' - ' + end.format('DD-MM-YYYY'));
        $(`#${dateFrom}`).val(start.format('YYYY-MM-DD'));
        $(`#${dateTo}`).val(end.format('YYYY-MM-DD'));
        $(`#${dateTo}`).trigger("change");
        if (ranges.includes('Last') || ranges.includes('Month') || ranges.includes('Custom')) {
            $(`#${headingdisplayId}`).html("Data From " + ranges + ' (' + start.format('DD-MM-YYYY') + ' to ' + end.format('DD-MM-YYYY') + ')');
        } else {
            $(`#${headingdisplayId}`).html("Data for " + ranges + ' (' + start.format('DD-MM-YYYY') + ')');
        }
    }

    $(`#${daterangeId}`).daterangepicker({
        startDate: start,
        endDate: end,
        ranges: {
            'Today': [moment(), moment()],
            'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
            'Last 7 Days': [moment().subtract(6, 'days'), moment()],
            'Last 30 Days': [moment().subtract(29, 'days'), moment()],
            'This Month': [moment().startOf('month'), moment().endOf('month')],
            'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
        }
    }, cb);

    cb(start, end, ranges);
}


function initializeDateRangePickerWithFuterfilter(daterangeId, headingdisplayId, dateFrom, dateTo, rangeval = null) {
    let start, end, ranges;
    // alert(rangeval);
    switch (rangeval) {
        case "Tomorrow":
            start = moment().add(1, 'days');
            end = moment().add(1, 'days');
            ranges = 'Tomorrow';
            break;
        case "Last7Days":
            start = moment().subtract(6, 'days');
            end = moment();
            ranges = 'Last 7 Days';
            break;
        case "Last30Days":
            start = moment().subtract(29, 'days');
            end = moment();
            ranges = 'Last 30 Days';
            break;
        case "Last60Days":
            start = moment().subtract(59, 'days');
            end = moment();
            ranges = 'Last 60 Days';
            break;
        case "ThisMonth":
            start = moment().startOf('month');
            end = moment().endOf('month');
            ranges = 'This Month';
            break;
        case "After2Days":
            start = moment().add(2, 'days');
            end = moment().add(2, 'days');
            ranges = 'After 2 Days';
            break;
        case "After10Days":
            start = moment().add(10, 'days');
            end = moment().add(10, 'days');
            ranges = 'After 10 Days';
            break;
        default:
            start = moment();
            end = moment();
            ranges = 'Today';
    }

    function cb(start, end, ranges) {
        $(`#${daterangeId} span`).html(start.format('DD-MM-YYYY') + ' - ' + end.format('DD-MM-YYYY'));
        $(`#${dateFrom}`).val(start.format('YYYY-MM-DD'));
        $(`#${dateTo}`).val(end.format('YYYY-MM-DD'));
        $(`#${dateTo}`).trigger("change");
        if (ranges.includes('Last') || ranges.includes('Month') || ranges.includes('Custom') || ranges.includes('After')) {
            $(`#${headingdisplayId}`).html(" From " + ranges + ' (' + start.format('DD-MM-YYYY') + ' to ' + end.format('DD-MM-YYYY') + ')');
        } else {
            $(`#${headingdisplayId}`).html(" for " + ranges + ' (' + start.format('DD-MM-YYYY') + ')');
        }
    }

    $(`#${daterangeId}`).daterangepicker({
        startDate: start,
        endDate: end,
        ranges: {
            'Today': [moment(), moment()],
            'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
            'Last 7 Days': [moment().subtract(6, 'days'), moment()],
            'Last 30 Days': [moment().subtract(29, 'days'), moment()],
            'Last 60 Days': [moment().subtract(59, 'days'), moment()],
            'This Month': [moment().startOf('month'), moment().endOf('month')],
            'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
            'Tomorrow': [moment().add(1, 'days'), moment().add(1, 'days')],
            'After 2 Days': [moment().add(2, 'days'), moment().add(2, 'days')],
            'After 10 Days': [moment().add(10, 'days'), moment().add(10, 'days')]
        }
    }, cb);

    cb(start, end, ranges);
}

window.addEventListener('DOMContentLoaded', () => {
    const now = new Date();
    const offset = now.getTimezoneOffset();
    const localDateTime = new Date(now.getTime() - offset * 60 * 1000).toISOString().slice(0, 16);
    document.querySelectorAll('.currentdatetime').forEach(input => {
      input.value = localDateTime;
    });
});


function sortTable_bytd(tableSelector, tdIndex, ascending = true) {
    var rows = $(tableSelector + " tbody tr").get();

    rows.sort(function (a, b) {
        var A = $(a).find("td").eq(tdIndex).text().trim().toLowerCase();
        var B = $(b).find("td").eq(tdIndex).text().trim().toLowerCase();

        // Try parsing as float if possible
        var numA = parseFloat(A);
        var numB = parseFloat(B);
        var isNumeric = !isNaN(numA) && !isNaN(numB);

        if (isNumeric) {
            return ascending ? numA - numB : numB - numA;
        }

        // Fallback to string comparison
        return ascending ? A.localeCompare(B) : B.localeCompare(A);
    });

    $.each(rows, function (index, row) {
        $(tableSelector + " tbody").append(row);
    });
}

function filterTableSortByTd_input(inputSelector, tableSelector, tdIndex) {
    if ($(inputSelector).length === 0 || $(tableSelector).length === 0) {
        console.error("Invalid selectors: ", inputSelector, tableSelector);
        return;
    }
    var value = $(inputSelector).val().toLowerCase().trim();
    var rows = $(tableSelector + " tbody tr").get();
    rows.sort(function (a, b) {
        var cellA = $(a).find("td").eq(tdIndex);
        var cellB = $(b).find("td").eq(tdIndex);
        // Get cell content or input/select/textarea value
        var textA = (cellA.find("input, select, textarea").val() || cellA.text()).toLowerCase();
        var textB = (cellB.find("input, select, textarea").val() || cellB.text()).toLowerCase();

        // Sort by how closely they match the input (exact match > starts with > contains > other)
        var scoreA = getMatchScore(textA, value);
        var scoreB = getMatchScore(textB, value);

        return scoreB - scoreA; // Higher score comes first
    });

    // Re-append sorted rows
    $.each(rows, function (index, row) {
        $(tableSelector + " tbody").append(row);
    });
} 
function getMatchScore(text, query) {
    if (!query) return 0;
    if (text === query) return 3;
    if (text.startsWith(query)) return 2;
    if (text.includes(query)) return 1;
    return 0;
}

function setExportButtons(reportDiv, table, filename) {
    return `
        <div id="export-buttons-wrapper" class="row justify-content-end">
            <button class="btn btn-light m-2 border border-black" onclick="PrintHtml('${reportDiv}');">
                <i class="fa fa-print" style="color:grey;"></i> Print
            </button>
            <button class="btn btn-light m-2 border border-black" onclick="generatemultitablePDF('${reportDiv}', '${filename}');">
                <i class="fa fa-file-pdf" style="color:red;"></i> PDF
            </button>
            <button class="btn btn-light m-2 border border-black" id="btnexpn4main" onclick="gridtoexcel_withfilter_chunk('${table}', '${filename}', 'landscape', function(){});">
                <i class="fa fa-file-excel" style="color:green;"></i> Excel
            </button>
        </div>
    `;
}


function getFirstChildRowOfSelectedGroups(tableId) {
  const result = [];
  $(`#${tableId} .group-select:checked`).each(function () {
    const groupId  = $(this).data("group");
    const $firstTr = $(`#${tableId} .child-check[data-group="${groupId}"]`).closest("tr").first();
    let dbsno = "";
    if ($firstTr.length) {
      dbsno = $firstTr.data("banksno");
      const rowData = $firstTr.find("td").map(function () {
        return $(this).text().trim();
      }).get();
      result.push({ groupId, rowData, dbsno });
    }
  });
  return result;
}

function getSelectedReconstructionRows(tableId) {
  const result = [];

  // 1. Handle grouped selections (only first row per group)
  $(`#${tableId} .group-select:checked`).each(function () {
    const groupId = $(this).data("group");
    const $firstTr = $(`#${tableId} .child-check[data-group="${groupId}"]`).closest("tr").first();

    if ($firstTr.length) {
      const dbsno = $firstTr.data("banksno") || "";
      const rowData = $firstTr.find("td").map(function () {
        return $(this).text().trim();
      }).get();
      result.push({ groupId, dbsno, rowData });
    }
  });

  // 2. Handle ungrouped individually selected checkboxes
  $(`#${tableId} .child-check:checked`).each(function () {
    const groupId = $(this).data("group");

    // Skip child rows that belong to groups already processed
    const isAlreadyGrouped = $(`#${tableId} .group-select[data-group="${groupId}"]`).is(":checked");
    if (isAlreadyGrouped) return;

    const $row = $(this).closest("tr");
    const dbsno = $row.data("banksno") || "";
    const rowData = $row.find("td").map(function () {
      return $(this).text().trim();
    }).get();
    result.push({ groupId, dbsno, rowData });
  });

  return result;
}


function groupSimilarByColumn_withunmatchedGrouped(tableid, groupByColIndex = 0, threshold = 0.9) {
  const table = $("#" + tableid);
  const tbody = table.find("tbody");
  const thead = table.find("thead").length ? table.find("thead") : $("<thead></thead>").prependTo(table);
  const columnCount = table.find("tbody tr:first td, th").length || 1;

  ungroupTable(tableid); // reset

  // Restore original rows if available
  if (table.data("original-rows")) {
    tbody.empty().append(table.data("original-rows"));
  }

  // Detach all rows and save copy
  const allRows = tbody.find("tr").detach();
  table.data("original-rows", allRows.clone(true));

  // Insert Controls in <thead>
  thead.find(`#${tableid}-controls-row`).remove();
  const controlRow = $(`
    <tr id="${tableid}-controls-row" class="bg-light sticky-top">
      <th colspan="${columnCount}" class="py-2">
        <div class="d-inline-flex gap-2">
          <button id="${tableid}-expand" class="btn btn-sm m-1 btn-outline-primary" onclick="toggleAllGroups('${tableid}', true)">Expand All</button>
          <button id="${tableid}-collapse" class="btn btn-sm m-1 btn-outline-secondary d-none" onclick="toggleAllGroups('${tableid}', false)">Collapse All</button>
          <button class="btn btn-sm m-1 btn-outline-danger" onclick="ungroupTable('${tableid}')">Ungroup</button>
          <button class="btn btn-sm m-1 btn-outline-success" id="sendemailwithdatatoclient">Send Email To Client For Reconstruction</button>
        </div>
      </th>
    </tr>
  `);
  thead.append(controlRow);

  // Grouping logic
  const groups = [], unmatched = [];
  const groupColors = [
    "#e3f2fd", "#e8f5e9", "#fff3e0", "#fce4ec",
    "#f3e5f5", "#e0f7fa", "#f9fbe7", "#ede7f6"
  ];

  allRows.each(function () {
    const $row = $(this);
    const desc = $row.find("td").eq(groupByColIndex).text().trim();
    let matchedGroup = groups.find(g => similarity(g.description, desc) >= threshold);
    if (matchedGroup) matchedGroup.rows.push($row);
    else unmatched.push({ row: $row, description: desc });
  });

  unmatched.forEach(item => {
    let matched = false;
    for (let group of groups) {
      if (similarity(group.description, item.description) >= threshold) {
        group.rows.push(item.row);
        matched = true;
        break;
      }
    }
    if (!matched) groups.push({ description: item.description, rows: [item.row] });
  });

  const matchedGroups = groups.filter(g => g.rows.length > 1);
  const unmatchedGroups = groups.filter(g => g.rows.length === 1);

  tbody.empty();

  // Matched groups rendering
  matchedGroups.forEach((group, index) => {
    const groupId = `group-${index}`;
    const bgColor = groupColors[index % groupColors.length];

    const groupHeader = $(`
      <tr class="group-header" data-group="${groupId}" style="cursor:pointer;background:${bgColor};">
        <td style="width: 40px; text-align:center;">
          <input type="checkbox" class="group-select" data-group="${groupId}" />
        </td>
        <td colspan="${columnCount - 1}" class="group-label">
          <span><strong>Grouped:</strong> ${group.description} (${group.rows.length})</span>
          <span class="toggle-btn float-end" data-open="0" data-group="${groupId}">➕</span>
        </td>
      </tr>
    `);

    const containerRow = $(`
      <tr class="group-container" data-group-content="${groupId}">
        <td colspan="${columnCount}" style="padding:0;">
          <table class="table mb-0 inner-group-table"><tbody style="display:none;"></tbody></table>
        </td>
      </tr>
    `);

    const innerTbody = containerRow.find("tbody");
    group.rows.forEach($row => {
      $row.css("background", bgColor);
      const firstTD = $row.find("td").eq(0);
      firstTD.find("input[type='checkbox']").remove();
      firstTD.prepend(`<input type="checkbox" class="child-check" data-group="${groupId}"> `);
      innerTbody.append($row);
    });

    tbody.append(groupHeader).append(containerRow);
  });

  // Unmatched rendering
  if (unmatchedGroups.length > 0) {
    const groupId = "unmatched";
    const unmatchedHeader = $(`
      <tr class="group-header" data-group="${groupId}" style="background:#f8d7da;">
        <td style="text-align:center;">
          <input type="checkbox" class="group-select" data-group="${groupId}" />
        </td>
        <td colspan="${columnCount - 1}" class="group-label">
          <strong>Unmatched Entries</strong> (${unmatchedGroups.length})
          <span class="toggle-btn float-end" data-open="0" data-group="${groupId}">➕</span>
        </td>
      </tr>
    `);

    const containerRow = $(`
      <tr class="group-container" data-group-content="${groupId}">
        <td colspan="${columnCount}" style="padding:0;">
          <table class="table mb-0 inner-group-table"><tbody style="display:none;"></tbody></table>
        </td>
      </tr>
    `);

    const innerTbody = containerRow.find("tbody");

    unmatchedGroups.forEach(g => {
      const $row = g.rows[0];
      $row.css("background", "#f8d7da");
      const firstTD = $row.find("td").eq(0);
      firstTD.find("input[type='checkbox']").remove();
      firstTD.prepend(`<input type="checkbox" class="child-check" data-group="${groupId}"> `);
      innerTbody.append($row);
    });

    tbody.append(unmatchedHeader).append(containerRow);
  }

  // Event bindings
  tbody.off("click", ".group-label, .toggle-btn").on("click", ".group-label, .toggle-btn", function () {
    const groupId = $(this).closest("tr").data("group") || $(this).data("group");
    toggleGroup(table, groupId);
  });

  tbody.off("change", ".group-select").on("change", ".group-select", function () {
    const groupId = $(this).data("group");
    const isChecked = $(this).is(":checked");
    tbody.find(`.child-check[data-group="${groupId}"]`).prop("checked", isChecked);
  });

  tbody.off("change", ".child-check").on("change", ".child-check", function () {
    const groupId = $(this).data("group");
    const all = tbody.find(`.child-check[data-group="${groupId}"]`);
    const checked = all.filter(":checked");
    tbody.find(`.group-select[data-group="${groupId}"]`).prop("checked", all.length === checked.length);
  });
}

function groupSimilarByColumn(tableid, groupByColIndex = 0, threshold = 0.9) {
  const table = $("#" + tableid);
  const tbody = table.find("tbody");
  const thead = table.find("thead").length ? table.find("thead") : $("<thead></thead>").prependTo(table);
  const columnCount = table.find("tbody tr:first td, th").length || 1;

  ungroupTable(tableid); // reset

  // Restore original rows if available
  if (table.data("original-rows")) {
    tbody.empty().append(table.data("original-rows"));
  }

  // Detach all rows and save copy
  const allRows = tbody.find("tr").detach();
  table.data("original-rows", allRows.clone(true));

  // Insert Controls in <thead>
  thead.find(`#${tableid}-controls-row`).remove();
  const controlRow = $(`
    <tr id="${tableid}-controls-row" class="bg-light sticky-top">
      <th colspan="${columnCount}" class="py-2">
        <div class="d-inline-flex gap-2">
          <button id="${tableid}-expand" class="btn btn-sm m-1 btn-outline-primary" onclick="toggleAllGroups('${tableid}', true)">Expand All</button>
          <button id="${tableid}-collapse" class="btn btn-sm m-1 btn-outline-secondary d-none" onclick="toggleAllGroups('${tableid}', false)">Collapse All</button>
          <button class="btn btn-sm m-1 btn-outline-danger" onclick="ungroupTable('${tableid}')">Ungroup</button>
          <button class="btn btn-sm m-1 btn-outline-success" id="sendemailwithdatatoclient">Send Email To Client For Reconstruction</button>
        </div>
      </th>
    </tr>
  `);
  thead.append(controlRow);

  // Grouping logic
  const groups = [], unmatched = [];
  const groupColors = [
    "#e3f2fd", "#e8f5e9", "#fff3e0", "#fce4ec",
    "#f3e5f5", "#e0f7fa", "#f9fbe7", "#ede7f6"
  ];

  allRows.each(function () {
    const $row = $(this);
    const desc = $row.find("td").eq(groupByColIndex).text().trim();
    let matchedGroup = groups.find(g => similarity(g.description, desc) >= threshold);
    if (matchedGroup) matchedGroup.rows.push($row);
    else unmatched.push({ row: $row, description: desc });
  });

  unmatched.forEach(item => {
    let matched = false;
    for (let group of groups) {
      if (similarity(group.description, item.description) >= threshold) {
        group.rows.push(item.row);
        matched = true;
        break;
      }
    }
    if (!matched) groups.push({ description: item.description, rows: [item.row] });
  });

  const matchedGroups = groups.filter(g => g.rows.length > 1);
  const unmatchedGroups = groups.filter(g => g.rows.length === 1);

  tbody.empty();

  // Matched groups rendering
  matchedGroups.forEach((group, index) => {
    const groupId = `group-${index}`;
    const bgColor = groupColors[index % groupColors.length];

    const groupHeader = $(`
      <tr class="group-header" data-group="${groupId}" style="cursor:pointer;background:${bgColor};">
        <td style="width: 40px; text-align:center;">
          <input type="checkbox" class="group-select" data-group="${groupId}" />
        </td>
        <td colspan="${columnCount - 1}" class="group-label">
          <span><strong>Grouped:</strong> ${group.description} (${group.rows.length})</span>
          <span class="toggle-btn float-end" data-open="0" data-group="${groupId}">➕</span>
        </td>
      </tr>
    `);

    const containerRow = $(`
      <tr class="group-container" data-group-content="${groupId}">
        <td colspan="${columnCount}" style="padding:0;">
          <table class="table mb-0 inner-group-table"><tbody style="display:none;"></tbody></table>
        </td>
      </tr>
    `);

    const innerTbody = containerRow.find("tbody");
    group.rows.forEach($row => {
      $row.css("background", bgColor);
      const firstTD = $row.find("td").eq(0);
      firstTD.find("input[type='checkbox']").remove();
      firstTD.prepend(`<input type="checkbox" class="child-check" data-group="${groupId}"> `);
      innerTbody.append($row);
    });

    tbody.append(groupHeader).append(containerRow);
  });

  // Unmatched rows as standalone entries
  unmatchedGroups.forEach((group, index) => {
    const $row = group.rows[0];
    $row.css("background", "#f8d7da"); // Light red for unmatched
    const firstTD = $row.find("td").eq(0);
    firstTD.find("input[type='checkbox']").remove();
    firstTD.prepend(`<input type="checkbox" class="child-check" data-group="unmatched-${index}">`);
    tbody.append($row);
  });

  // Event bindings
  tbody.off("click", ".group-label, .toggle-btn").on("click", ".group-label, .toggle-btn", function () {
    const groupId = $(this).closest("tr").data("group") || $(this).data("group");
    toggleGroup(table, groupId);
  });

  tbody.off("change", ".group-select").on("change", ".group-select", function () {
    const groupId = $(this).data("group");
    const isChecked = $(this).is(":checked");
    tbody.find(`.child-check[data-group="${groupId}"]`).prop("checked", isChecked);
  });

  tbody.off("change", ".child-check").on("change", ".child-check", function () {
    const groupId = $(this).data("group");
    const all = tbody.find(`.child-check[data-group="${groupId}"]`);
    const checked = all.filter(":checked");
    tbody.find(`.group-select[data-group="${groupId}"]`).prop("checked", all.length === checked.length);
  });
}



function toggleGroup(table, groupId, expand = null) {
  const tbody = table.find("tbody");
  const toggle = tbody.find(`.toggle-btn[data-group="${groupId}"]`);
  const content = tbody.find(`.group-container[data-group-content="${groupId}"] tbody`);
  const isOpen = toggle.attr("data-open") === "1";
  const shouldExpand = expand !== null ? expand : !isOpen;

  if (shouldExpand) {
    content.slideDown(200);
    toggle.text("➖").attr("data-open", "1");
  } else {
    content.slideUp(200);
    toggle.text("➕").attr("data-open", "0");
  }
}

function toggleAllGroups(tableid, expand = true) {
  const table = $("#" + tableid);
  const tbody = table.find("tbody");

  document.getElementById(`${tableid}-expand`).classList.toggle("d-none", expand);
  document.getElementById(`${tableid}-collapse`).classList.toggle("d-none", !expand);

  tbody.find(".group-header").each(function () {
    const groupId = $(this).data("group");
    toggleGroup(table, groupId, expand);
  });
}

function ungroupTable(tableid) {
  const table = $("#" + tableid);
  const tbody = table.find("tbody");
  if (table.data("original-rows")) {
    tbody.empty().append(table.data("original-rows"));
    table.removeData("original-rows");
    table.find(`#${tableid}-controls-row`).remove();
  }
}

// Helpers
// String similarity scoring logic
function getMatchScore(source, searchText) {
  source = source.toLowerCase();
  searchText = searchText.toLowerCase();

  if (source === searchText) return 1;
  if (source.startsWith(searchText)) return 0.95;
  if (source.includes(searchText)) return 0.85;

  return similarity(source, searchText); // fallback to fuzzy match
}

// Levenshtein-based similarity
function similarity(s1, s2) {
  const longer = s1.length > s2.length ? s1 : s2;
  const shorter = s1.length <= s2.length ? s1 : s2;
  const longerLength = longer.length;
  if (longerLength === 0) return 1.0;

  return (longerLength - editDistance(longer, shorter)) / longerLength;
}

function editDistance(s1, s2) {
  const costs = new Array(s2.length + 1);
  for (let i = 0; i <= s2.length; i++) costs[i] = i;
  for (let i = 1; i <= s1.length; i++) {
    let lastValue = i;
    for (let j = 1; j <= s2.length; j++) {
      let newValue = costs[j - 1];
      if (s1[i - 1] !== s2[j - 1]) {
        newValue = Math.min(newValue, lastValue, costs[j]) + 1;
      }
      costs[j - 1] = lastValue;
      lastValue = newValue;
    }
    costs[s2.length] = lastValue;
  }
  return costs[s2.length];
}



function convertRowToJson(tableId, rowArray) {
  const json = {};
  rowArray.forEach((cellValue, index) => {
    json[index] = cellValue;
  });

  return json;
}

function playVoiceFile(filepath) {
  if (!filepath) {
    alert("No voice file provided.");
    return;
  }

  // Remove existing dialog if any
  $("#voicePlayerDialog").remove();

  // Build HTML for floating dialog
  const dialogHtml = `
    <div id="voicePlayerDialog" style="
      position: fixed;
      top: 20px;
      right: 20px;
      width: 300px;
      background: #fff;
      border: 1px solid #ccc;
      box-shadow: 0 0 10px rgba(0,0,0,0.2);
      padding: 10px;
      z-index: 9999;
      border-radius: 8px;
    ">
      <div style="display: flex; justify-content: space-between; align-items: center;">
        <strong>Voice Playback</strong>
        <button id="closeVoicePlayer" style="
          background: transparent;
          border: none;
          font-weight: bold;
          font-size: 16px;
          cursor: pointer;
        ">&times;</button>
      </div>
      <audio id="floatingVoiceAudio" controls autoplay style="width: 100%; margin-top: 5px;">
        <source src="${filepath}" type="audio/mpeg">
        Your browser does not support the audio element.
      </audio>
    </div>
  `;

  // Append to body
  $("body").append(dialogHtml);

  // Close button handler
  $("#closeVoicePlayer").on("click", function () {
    const player = document.getElementById("floatingVoiceAudio");
    if (player) {
      player.pause();
      player.currentTime = 0;
    }
    $("#voicePlayerDialog").remove();
  });
}


function sortTableByCell(tableSelector, cellIndex, isDate = false, asc = true) {
    const $table = $(tableSelector);
    const $tbody = $table.find("tbody");
    const $rows = $tbody.find("tr").get();

    $rows.sort(function (a, b) {
        const cellA = $(a).children("td").eq(cellIndex).text().trim();
        const cellB = $(b).children("td").eq(cellIndex).text().trim();

        if (isDate) {
            const dateA = new Date(cellA);
            const dateB = new Date(cellB);
            return asc ? dateA - dateB : dateB - dateA;
        } else if ($.isNumeric(cellA) && $.isNumeric(cellB)) {
            return asc ? parseFloat(cellA) - parseFloat(cellB) : parseFloat(cellB) - parseFloat(cellA);
        } else {
            return asc ? cellA.localeCompare(cellB) : cellB.localeCompare(cellA);
        }
    });

    $.each($rows, function (_, row) {
        $tbody.append(row);
    });
}