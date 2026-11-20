$(function () {
  // Scanned Trays Collection State
  let scannedTraysList = [];
  let currentActiveBatch = null;
  let currentScopeMode = 'scanned_only'; // 'scanned_only' or 'all_trays'

  // Moved Trays History Registry (persists within session)
  let movedTraysHistory = {};
  try {
    const saved = sessionStorage.getItem('krf_moved_trays_history');
    if (saved) {
      movedTraysHistory = JSON.parse(saved);
    }
  } catch (e) {
    movedTraysHistory = {};
  }

  function recordMovedTrays(trays, movementInfo) {
    if (!trays || !trays.length) return;
    const nowStr = movementInfo.moved_at || new Date().toLocaleString('en-IN', {
      day: '2-digit', month: 'short', year: 'numeric',
      hour: '2-digit', minute: '2-digit', hour12: true
    });

    trays.forEach(t => {
      const code = (t.barcode || '').trim().toUpperCase();
      if (code) {
        movedTraysHistory[code] = {
          barcode: t.barcode,
          stockorder_no: t.stockorder_no || movementInfo.stockorder_no || '-',
          productname: t.productname || movementInfo.productname || '-',
          variety: t.variety || movementInfo.variety || '-',
          movement_reason: movementInfo.movement_reason || 'Moved',
          from_bed: t.bed_no || movementInfo.from_bed || '-',
          target_bed_no: movementInfo.target_bed_no || movementInfo.bed_no || '-',
          moved_at: nowStr,
          moved_by: movementInfo.moved_by || 'Admin',
          narration: movementInfo.narration || '',
          timestamp: Date.now()
        };
      }
    });

    try {
      sessionStorage.setItem('krf_moved_trays_history', JSON.stringify(movedTraysHistory));
    } catch (e) { }
  }

  // In-flight requests set & Torch State
  const inFlightBarcodes = new Set();
  let torchTrack = null;
  let isTorchOn = false;

  // Safe input focus helper that never triggers abrupt page scrolling
  function focusBarcodeInput() {
    const el = document.getElementById('inputBarcodeQuery');
    if (el) {
      try {
        el.focus({ preventScroll: true });
      } catch (e) {
        el.focus();
      }
    }
  }

  // Camera & Scan Lock State
  let html5QrCode = null;
  let isCameraScanning = false;
  let currentFacingMode = "environment";
  let currentCameraId = null;
  let availableCameras = [];
  let isAlertModalOpen = false;
  let isScanLocked = false;
  let lastScannedPayload = '';
  let lastScannedTimestamp = 0;
  const SCAN_COOLDOWN_MS = 1400;      // 1.4s cooldown between different tray scans
  const SAME_CODE_COOLDOWN_MS = 3200; // 3.2s cooldown before re-scanning the same code

  // Helper to extract clean ID from raw QR/Barcode string, URL, or JSON
  function cleanQrPayload(str) {
    if (!str) return '';
    str = String(str).trim();
    try {
      if ((str.startsWith('{') && str.endsWith('}')) || (str.startsWith('[') && str.endsWith(']'))) {
        const obj = JSON.parse(str);
        if (typeof obj === 'object' && obj !== null) {
          return obj.barcode || obj.bedqrcode || obj.stockorder_no || obj.psono || obj.code || obj.qr_text || obj.bed_no || str;
        }
      }
    } catch (e) { }

    if (str.indexOf('http://') === 0 || str.indexOf('https://') === 0 || str.indexOf('://') !== -1) {
      try {
        const url = new URL(str, window.location.origin);
        const params = url.searchParams;
        return params.get('barcode') || params.get('stockorder_no') || params.get('stockorder') || params.get('psono') || params.get('bedqrcode') || params.get('code') || params.get('bed_no') || str;
      } catch (e) { }
    }

    // Support multiline QR payloads (e.g., "Tray: TRY123\nBatch: KFP...")
    if (str.includes('\n')) {
      const lines = str.split(/\r?\n/).map(l => l.trim()).filter(Boolean);
      for (const line of lines) {
        const m = line.match(/^(?:tray(?:\s*(?:no|barcode|id))?|barcode)\s*[:=]\s*(.+)$/i);
        if (m && m[1]) return m[1].trim();
      }
      if (lines.length > 0) return lines[0];
    }

    return str.replace(/[\x00-\x1F\x7F\xC2\xA0]/g, '').trim();
  }

  // Non-blocking Toast helper for rapid mobile scanning feedback
  function showToastInfo(msg) {
    if (typeof Swal !== 'undefined') {
      const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 2000,
        timerProgressBar: true
      });
      Toast.fire({
        icon: 'info',
        title: msg
      });
    }
  }

  // Haptic feedback for mobile devices
  function triggerHaptic(type = 'success') {
    if (navigator.vibrate) {
      try {
        if (type === 'success') {
          navigator.vibrate([60]);
        } else if (type === 'duplicate') {
          navigator.vibrate([40, 60, 40]);
        } else if (type === 'warning') {
          navigator.vibrate([100, 50, 100]);
        }
      } catch (e) { }
    }
  }

  // Audio Beep: Standard scan beep
  function playScanBeep() {
    try {
      const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
      const osc = audioCtx.createOscillator();
      const gain = audioCtx.createGain();
      osc.connect(gain);
      gain.connect(audioCtx.destination);
      osc.type = "sine";
      osc.frequency.setValueAtTime(880, audioCtx.currentTime);
      gain.gain.setValueAtTime(0.2, audioCtx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.00001, audioCtx.currentTime + 0.15);
      osc.start();
      osc.stop(audioCtx.currentTime + 0.15);
    } catch (e) { }
  }

  // Audio Alert Tone: Warning sound for duplicate / already moved tray
  function playWarningAlertTone() {
    try {
      const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
      const osc = audioCtx.createOscillator();
      const gain = audioCtx.createGain();
      osc.connect(gain);
      gain.connect(audioCtx.destination);
      osc.type = "sawtooth";
      osc.frequency.setValueAtTime(360, audioCtx.currentTime);
      osc.frequency.setValueAtTime(260, audioCtx.currentTime + 0.14);
      gain.gain.setValueAtTime(0.25, audioCtx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.0001, audioCtx.currentTime + 0.35);
      osc.start();
      osc.stop(audioCtx.currentTime + 0.35);
    } catch (e) { }
  }

  // Modal Alert: Show warning popup when an already moved tray QR is rescanned
  function showAlreadyMovedAlert(info, onAllowRescan = null) {
    playWarningAlertTone();
    isAlertModalOpen = true;
    isScanLocked = true;

    // Immediately clear scanner input box so old QR code is never left behind
    $('#inputBarcodeQuery').val('');

    const bc = info.barcode || 'Unknown Barcode';
    const reason = info.movement_reason || info.status || info.raw_status || 'Movement Completed';
    const product = info.productname || 'Plant';
    const variety = info.variety && info.variety !== '-' ? ` (${info.variety})` : '';
    const orderNo = info.stockorder_no || info.psono || '-';
    const fromBed = info.from_bed || '';
    const toBed = info.target_bed_no || info.to_bed || info.bed_no || '';
    const movedAt = info.moved_at || info.move_at || info.updated_at || 'Recently';
    const movedBy = info.moved_by || 'Admin';
    const note = info.narration || info.movementremark || info.message || '';

    let locationShiftHtml = '';
    if (fromBed && toBed && fromBed.toLowerCase() !== toBed.toLowerCase()) {
      locationShiftHtml = `
        <div class="d-flex justify-content-between py-1.5 border-bottom">
          <span class="text-muted"><i class="fa fa-arrows-alt-h text-primary mr-1"></i> Shifted:</span>
          <span><span class="badge bg-secondary">${esc(fromBed)}</span> &rarr; <span class="badge bg-success font-weight-bold">${esc(toBed)}</span></span>
        </div>
      `;
    } else if (toBed) {
      locationShiftHtml = `
        <div class="d-flex justify-content-between py-1.5 border-bottom">
          <span class="text-muted"><i class="fa fa-map-marker-alt text-danger mr-1"></i> Current Bed:</span>
          <span class="font-weight-bold text-dark">${esc(toBed)}</span>
        </div>
      `;
    }

    let noteHtml = '';
    if (note) {
      noteHtml = `
        <div class="mt-2 p-2 rounded text-left small" style="background:#f8fafc; border-left:3px solid #f59e0b; color:#475569; word-break:break-word;">
          <strong><i class="fa fa-info-circle text-warning mr-1"></i> Movement Log / Remarks:</strong><br>
          ${esc(note)}
        </div>
      `;
    }

    const modalHtml = `
      <div class="text-center" style="font-family:inherit;">
        <div class="mb-3">
          <span class="badge bg-danger text-white py-1 px-3 shadow-sm" style="font-size:0.85rem; letter-spacing:0.5px;">
            <i class="fa fa-exclamation-triangle mr-1"></i> MOVEMENT ALREADY COMPLETED
          </span>
        </div>
        <p class="text-secondary mb-3" style="font-size:0.95rem;">
          This tray QR code was already scanned and its movement was finished.
        </p>
        <div class="card p-3 shadow-sm text-left mb-2" style="background:#fff; border:1px solid #fecaca; border-radius:10px;">
          <div class="d-flex justify-content-between py-1 border-bottom">
            <span class="text-muted"><i class="fa fa-barcode text-primary mr-1"></i> Tray Barcode:</span>
            <span class="tray-barcode-badge font-weight-bold" style="font-size:0.95rem;">${esc(bc)}</span>
          </div>
          <div class="d-flex justify-content-between py-1 border-bottom">
            <span class="text-muted"><i class="fa fa-file-invoice text-info mr-1"></i> Order / PSO:</span>
            <span class="font-weight-bold text-primary font-monospace">${esc(orderNo)}</span>
          </div>
          <div class="d-flex justify-content-between py-1 border-bottom">
            <span class="text-muted"><i class="fa fa-seedling text-success mr-1"></i> Product:</span>
            <span class="font-weight-bold">${esc(product)}${esc(variety)}</span>
          </div>
          <div class="d-flex justify-content-between py-1 border-bottom">
            <span class="text-muted"><i class="fa fa-truck-moving text-warning mr-1"></i> Movement Action:</span>
            <span class="badge bg-primary text-white font-weight-bold" style="font-size:0.85rem;">${esc(reason)}</span>
          </div>
          ${locationShiftHtml}
          <div class="d-flex justify-content-between py-1 border-bottom">
            <span class="text-muted"><i class="fa fa-clock text-secondary mr-1"></i> Moved At:</span>
            <span class="font-weight-bold text-dark">${esc(movedAt)}</span>
          </div>
          <div class="d-flex justify-content-between py-1">
            <span class="text-muted"><i class="fa fa-user-check text-muted mr-1"></i> Moved By:</span>
            <span class="font-weight-bold">${esc(movedBy)}</span>
          </div>
          ${noteHtml}
        </div>
      </div>
    `;

    const isAllowPossible = typeof onAllowRescan === 'function';

    Swal.fire({
      icon: 'warning',
      title: 'Tray Already Moved!',
      html: modalHtml,
      confirmButtonText: '<i class="fa fa-check mr-1"></i> OK, Understood',
      confirmButtonColor: '#dc2626',
      showCancelButton: isAllowPossible,
      cancelButtonText: '<i class="fa fa-redo mr-1"></i> Move Again Anyway',
      cancelButtonColor: '#4f46e5',
      allowOutsideClick: false,
      willOpen: () => {
        $('#inputBarcodeQuery').val('');
      },
      didOpen: () => {
        $('#inputBarcodeQuery').val('');
      },
      didClose: () => {
        isAlertModalOpen = false;
        setTimeout(() => { isScanLocked = false; }, 800);
        $('#inputBarcodeQuery').val('');
        focusBarcodeInput();
      }
    }).then((result) => {
      isAlertModalOpen = false;
      setTimeout(() => { isScanLocked = false; }, 800);
      $('#inputBarcodeQuery').val('');
      focusBarcodeInput();
      if (result.dismiss === Swal.DismissReason.cancel && isAllowPossible) {
        onAllowRescan();
      }
    });
  }

  // 1. Scanner Tab Switching
  $('#tabManual').on('click', function () {
    $(this).addClass('active');
    $('#tabCamera').removeClass('active');
    $('#sectionManual').show();
    $('#sectionCamera').hide();
    stopCamera();
    isScanLocked = false;
    focusBarcodeInput();
  });

  $('#tabCamera').on('click', function () {
    $(this).addClass('active');
    $('#tabManual').removeClass('active');
    $('#sectionManual').hide();
    $('#sectionCamera').show();
    currentFacingMode = "environment";
    currentCameraId = null;
    isScanLocked = false;
    startCamera();
  });

  // 2. Camera QR Scanner Setup
  async function startCamera(preferredId = null) {
    if (isCameraScanning) return;

    if (typeof Html5Qrcode === 'undefined') {
      Swal.fire('Error', 'QR Scanner library not loaded. Please check your internet connection.', 'error');
      return;
    }

    $('#cameraStatusMsg').html('<i class="fa fa-spinner fa-spin mr-1"></i> Opening back camera...');
    $('#btnToggleCamera').prop('disabled', true);

    try {
      if (html5QrCode) {
        try {
          if (html5QrCode.isScanning) {
            await html5QrCode.stop();
          }
          html5QrCode.clear();
        } catch (e) { }
        html5QrCode = null;
      }

      html5QrCode = new Html5Qrcode("qrCameraViewport");

      // Focused targeting area (prevents peripheral barcode capture)
      const scanConfig = {
        fps: 10,
        qrbox: (viewfinderWidth, viewfinderHeight) => {
          const w = Math.min(260, Math.max(160, Math.floor(viewfinderWidth * 0.70)));
          const h = Math.min(180, Math.max(120, Math.floor(viewfinderHeight * 0.55)));
          return {
            width: w,
            height: h
          };
        },
        aspectRatio: 1.333333
      };

      const onScanSuccess = (decodedText) => {
        if (isAlertModalOpen || isScanLocked) return;

        const cleanPayload = cleanQrPayload(decodedText);
        if (!cleanPayload) return;
        const cleanUpper = cleanPayload.toUpperCase();

        // 1. Guard against duplicate in-flight network requests
        if (inFlightBarcodes.has(cleanUpper)) {
          return;
        }

        // 2. Instant guard if already present in scanned list (non-blocking toast + vibrate)
        const alreadyInGrid = scannedTraysList.some(t => (t.barcode || '').trim().toUpperCase() === cleanUpper);
        if (alreadyInGrid) {
          triggerHaptic('duplicate');
          const existingIdx = scannedTraysList.findIndex(t => (t.barcode || '').trim().toUpperCase() === cleanUpper);
          if (existingIdx !== -1) {
            highlightRow(existingIdx);
          }
          showToastInfo(`Tray "${cleanPayload}" is already in scanned grid (Row #${existingIdx + 1}).`);
          return;
        }

        const now = Date.now();
        // Anti-burst scanning protection (cooldown between scans)
        if (now - lastScannedTimestamp < SCAN_COOLDOWN_MS) {
          return;
        }
        if (cleanUpper === lastScannedPayload.toUpperCase() && (now - lastScannedTimestamp < SAME_CODE_COOLDOWN_MS)) {
          return;
        }

        // Lock scanning immediately to prevent duplicate triggers
        isScanLocked = true;
        lastScannedPayload = cleanPayload;
        lastScannedTimestamp = now;

        $('#cameraStatusMsg').html('<span class="text-primary font-weight-bold"><i class="fa fa-spinner fa-spin mr-1"></i> Scanned: ' + esc(cleanPayload) + '...</span>');

        playScanBeep();
        triggerHaptic('success');

        lookupAndAddTray(cleanPayload, false, function () {
          // Cooldown unlock
          setTimeout(() => {
            isScanLocked = false;
            if (isCameraScanning && !isAlertModalOpen) {
              $('#cameraStatusMsg').html('<span class="text-success"><i class="fa fa-check-circle mr-1"></i> Ready for next tray. Point at Tray QR code.</span>');
            }
          }, 1000);
        });
      };

      if (preferredId) {
        await html5QrCode.start(preferredId, scanConfig, onScanSuccess, () => { });
        currentCameraId = preferredId;
      } else {
        await html5QrCode.start({ facingMode: "environment" }, scanConfig, onScanSuccess, () => { });
      }

      isCameraScanning = true;
      isScanLocked = false;
      $('#scannerLaserLine').show();
      $('#btnToggleCamera').html('<i class="fa fa-stop mr-1"></i> Stop Camera').removeClass('btn-primary').addClass('btn-danger').prop('disabled', false);
      $('#cameraStatusMsg').html('<span class="text-success"><i class="fa fa-check-circle mr-1"></i> Camera scanning active. Point at Tray QR code.</span>');

      // Detect torch / flashlight capability on active camera track
      setTimeout(() => {
        try {
          const videoEl = document.querySelector("#qrCameraViewport video");
          if (videoEl && videoEl.srcObject) {
            const tracks = videoEl.srcObject.getVideoTracks();
            if (tracks && tracks.length > 0) {
              torchTrack = tracks[0];
              const caps = typeof torchTrack.getCapabilities === 'function' ? torchTrack.getCapabilities() : {};
              if (caps && caps.torch) {
                $('#btnToggleTorch').show().removeClass('btn-warning').addClass('btn-outline-warning').html('<i class="fa fa-lightbulb mr-1"></i> Torch');
                isTorchOn = false;
              } else {
                $('#btnToggleTorch').hide();
              }
            }
          }
        } catch (e) {
          console.warn('Torch detection error', e);
        }
      }, 500);

    } catch (err) {
      isCameraScanning = false;
      isScanLocked = false;
      $('#scannerLaserLine').hide();
      $('#btnToggleCamera').html('<i class="fa fa-play mr-1"></i> Start Camera').removeClass('btn-danger').addClass('btn-primary').prop('disabled', false);
      $('#cameraStatusMsg').html('<span class="text-danger"><i class="fa fa-exclamation-circle mr-1"></i> Camera access error: ' + (err.message || err) + '</span>');
    }
  }

  async function stopCamera() {
    if (!html5QrCode) return;
    try {
      if (html5QrCode.isScanning) {
        await html5QrCode.stop();
      }
      html5QrCode.clear();
    } catch (e) { }
    html5QrCode = null;
    isCameraScanning = false;
    isScanLocked = false;
    $('#scannerLaserLine').hide();

    // Turn off torch if active
    if (torchTrack) {
      try {
        if (isTorchOn) {
          torchTrack.applyConstraints({ advanced: [{ torch: false }] });
        }
      } catch (e) { }
      torchTrack = null;
    }
    isTorchOn = false;
    $('#btnToggleTorch').hide().removeClass('btn-warning').addClass('btn-outline-warning').html('<i class="fa fa-lightbulb mr-1"></i> Torch');

    $('#btnToggleCamera').html('<i class="fa fa-play mr-1"></i> Start Camera').removeClass('btn-danger').addClass('btn-primary').prop('disabled', false);
    $('#cameraStatusMsg').html('<span class="text-muted"><i class="fa fa-info-circle text-primary mr-1"></i> Camera stopped.</span>');
  }

  $('#btnToggleCamera').on('click', function () {
    if (isCameraScanning) {
      stopCamera();
    } else {
      startCamera(currentCameraId);
    }
  });

  $('#btnSwitchCamera').on('click', function () {
    currentFacingMode = (currentFacingMode === "environment") ? "user" : "environment";
    currentCameraId = null;
    if (isCameraScanning) {
      stopCamera().then(() => startCamera());
    } else {
      startCamera();
    }
  });

  // Torch Toggle Button Click Handler
  $('#btnToggleTorch').on('click', async function () {
    if (!torchTrack) return;
    try {
      isTorchOn = !isTorchOn;
      await torchTrack.applyConstraints({
        advanced: [{ torch: isTorchOn }]
      });
      if (isTorchOn) {
        $(this).removeClass('btn-outline-warning').addClass('btn-warning').html('<i class="fa fa-lightbulb mr-1"></i> Torch Off');
      } else {
        $(this).removeClass('btn-warning').addClass('btn-outline-warning').html('<i class="fa fa-lightbulb mr-1"></i> Torch On');
      }
    } catch (e) {
      console.warn('Torch apply error', e);
    }
  });

  // Photo snap & upload decode helpers
  window.openPhotoCapture = function (facing) {
    const $file = $('#inputScanImageFile');
    if (facing === 'environment') {
      $file.attr('capture', 'environment');
    } else {
      $file.removeAttr('capture');
    }
    $file.val('');
    $file.click();
  };

  window.handleQrImageUpload = function (input) {
    if (!input.files || !input.files[0]) return;
    const file = input.files[0];
    const reader = new FileReader();
    reader.onload = function (e) {
      const img = new Image();
      img.onload = function () {
        const canvas = document.createElement("canvas");
        canvas.width = img.width;
        canvas.height = img.height;
        const ctx = canvas.getContext("2d");
        ctx.drawImage(img, 0, 0, img.width, img.height);
        const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);

        let code = null;
        if (typeof jsQR !== 'undefined') {
          code = jsQR(imageData.data, imageData.width, imageData.height);
        }
        if (code && code.data) {
          if (isAlertModalOpen) return;
          playScanBeep();
          const clean = cleanQrPayload(code.data);
          lookupAndAddTray(clean);
        } else {
          Swal.fire('No QR Detected', 'Could not find a readable QR code or Barcode in this image. Please try a clearer picture.', 'warning');
        }
      };
      img.src = e.target.result;
    };
    reader.readAsDataURL(file);
  };

  // 3. Scan & Lookup Tray by Barcode
  window.triggerScanLookup = function () {
    if (isAlertModalOpen || isScanLocked) return;
    const raw = $.trim($('#inputBarcodeQuery').val() || '');
    if (!raw) {
      Swal.fire('Input Required', 'Please scan or enter a Tray Barcode.', 'info');
      $('#inputBarcodeQuery').val('');
      focusBarcodeInput();
      return;
    }
    const clean = cleanQrPayload(raw);
    const cleanUpper = clean.toUpperCase();
    if (inFlightBarcodes.has(cleanUpper)) {
      return;
    }
    isScanLocked = true;
    $('#inputBarcodeQuery').val('');
    lookupAndAddTray(clean);
  };

  window.clearInput = function () {
    $('#inputBarcodeQuery').val('');
    focusBarcodeInput();
  };

  function lookupAndAddTray(query, bypassMovedCheck = false, onComplete = null) {
    const cleanUpper = (query || '').trim().toUpperCase();

    const finishLookup = () => {
      inFlightBarcodes.delete(cleanUpper);
      if (typeof onComplete === 'function') {
        onComplete();
      } else {
        setTimeout(() => { isScanLocked = false; }, 800);
      }
    };

    if (!query) {
      finishLookup();
      return;
    }

    inFlightBarcodes.add(cleanUpper);
    $('#inputBarcodeQuery').val('');

    // 0. Restrict to Tray Barcode only: Check if query is an Order ID
    const isOrderId = /^(RSO|OS|ORD|PSO|PO|SO|KFP|KF)[-\s]?/i.test(query);
    if (isOrderId) {
      $('#inputBarcodeQuery').val('');
      focusBarcodeInput();
      Swal.fire({
        icon: 'warning',
        title: 'Order ID Not Allowed',
        html: `Order ID <b>"${esc(query)}"</b> cannot be scanned or searched on this page.<br><br>This page accepts <b>only individual Tray Barcodes</b>.<br><small class="text-muted">To scan or search by Order ID (RSO- / OS-), please use the <b>Plant Movement QR Scanner</b> page.</small>`,
        confirmButtonColor: '#4f46e5',
        didClose: () => { finishLookup(); }
      }).then(() => { finishLookup(); });
      return;
    }

    // 1. Check if already in current scanned grid (Instant non-blocking toast feedback)
    const existingIndex = scannedTraysList.findIndex(t => (t.barcode || '').trim().toUpperCase() === cleanUpper);
    if (existingIndex !== -1) {
      playScanBeep();
      triggerHaptic('duplicate');
      highlightRow(existingIndex);
      showToastInfo(`Tray "${query}" is already in scanned grid (Row #${existingIndex + 1}).`);
      $('#inputBarcodeQuery').val('');
      focusBarcodeInput();
      finishLookup();
      return;
    }

    // 2. Check if this tray QR code was already moved (session / local history)
    if (!bypassMovedCheck && movedTraysHistory[cleanUpper]) {
      const movedInfo = movedTraysHistory[cleanUpper];
      showAlreadyMovedAlert(movedInfo, function () {
        // User clicked "Move Again Anyway"
        lookupAndAddTray(query, true, onComplete);
      });
      finishLookup();
      return;
    }

    // 3. Query server to verify barcode
    const $btn = $('#btnSearchBarcode');
    const origBtn = $btn.html();
    $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin mr-1"></i> Searching...');

    $.post('modal/verifiedtraymovement_modal.php?action=scan_qr', { query: query, tray_barcode_only: 1 }, function (res) {
      $btn.prop('disabled', false).html(origBtn);

      // Server reports tray has completed movement / terminal status
      if (res.status === 'already_moved') {
        finishLookup();
        const d = res.data || {};
        const isAllowPossible = res.is_terminal === false;
        showAlreadyMovedAlert({
          barcode: d.barcode || query,
          status: d.status || d.movement_reason || 'Already Moved',
          movement_reason: d.movement_reason || d.status || 'Already Moved',
          bed_no: d.bed_no || '-',
          stockorder_no: d.stockorder_no || '-',
          productname: d.productname || '-',
          variety: d.variety || '',
          moved_at: d.move_at || d.updated_at || 'Recently',
          narration: d.movementremark || res.message || ''
        }, isAllowPossible ? function () {
          // If allowed, lookup and bypass
          lookupAndAddTray(query, true, onComplete);
        } : null);
        return;
      }

      if (res.status === 'warning_status') {
        finishLookup();
        showAlreadyMovedAlert({
          barcode: query,
          status: res.current_status || 'Empty / Dispatched',
          movement_reason: res.current_status || 'Empty / Dispatched',
          bed_no: res.bed_no || '-',
          stockorder_no: res.stockorder_no || '-',
          productname: res.productname || '-',
          narration: res.message || 'All active trays in this bed have already been dispatched or removed.'
        }, null);
        return;
      }

      if (res.status === 'success' && res.data) {
        const d = res.data;
        if (res.scanned_tray_status_warning) {
          d.scanned_tray_status_warning = res.scanned_tray_status_warning;
        }

        // Only truly terminal statuses (when no active trays in bed)
        const rawStatus = (d.raw_status || d.status || '').trim();
        const terminalStatuses = ['Final Dispatch', 'Internal Dispatch', 'Removed', 'Remove After Over Age', 'Empty After Shifting', 'Empty After Grafting', 'Destroy After grafted', 'Tray Free After Shifting', 'Tray Dead'];

        if (!bypassMovedCheck && (!d.active_trays_list || d.active_trays_list.length === 0) && terminalStatuses.map(s => s.toLowerCase()).includes(rawStatus.toLowerCase())) {
          finishLookup();
          showAlreadyMovedAlert({
            barcode: d.barcode || query,
            status: rawStatus,
            movement_reason: rawStatus,
            bed_no: d.bed_no,
            stockorder_no: d.stockorder_no,
            productname: d.productname,
            variety: d.variety,
            moved_at: d.move_at || d.updated_at || 'Recently',
            narration: d.movementremark || ''
          }, function () {
            proceedAddOrBatch(d, finishLookup);
          });
          return;
        }

        proceedAddOrBatch(d, finishLookup);

      } else {
        finishLookup();
        $('#inputBarcodeQuery').val('');
        Swal.fire({
          icon: res.status === 'error' ? 'error' : 'warning',
          title: res.status === 'error' ? 'Scan Restricted' : 'Not Found',
          text: res.message || 'No plant record found matching scanned barcode.',
          confirmButtonColor: '#4f46e5',
          didClose: () => {
            $('#inputBarcodeQuery').val('');
            focusBarcodeInput();
          }
        }).then(() => {
          $('#inputBarcodeQuery').val('');
          focusBarcodeInput();
        });
      }
    }, 'json').fail(function () {
      $btn.prop('disabled', false).html(origBtn);
      $('#inputBarcodeQuery').val('');
      finishLookup();
      Swal.fire('Error', 'Failed to communicate with server.', 'error').then(() => {
        $('#inputBarcodeQuery').val('').focus();
      });
    });
  }

  // Render 1-Line Scanned Tray Status Banner / Warning
  function renderScannedTrayActiveCard(d) {
    if (!d) {
      $('#trayScanStatusAlert').hide();
      return;
    }

    const t = d.scanned_tray || d;
    const barcode = t.barcode || '-';
    const status = (t.status || t.raw_status || 'Polyhouse').trim();
    const terminalStatuses = ['final dispatch', 'internal dispatch', 'removed', 'remove after over age', 'empty after shifting', 'empty after grafting', 'destroy after grafted', 'tray free after shifting', 'tray dead'];
    const isActive = (t.is_active !== undefined) ? Boolean(t.is_active) : !terminalStatuses.includes(status.toLowerCase());
    const bed = t.bed_no || '-';

    let html = '';
    const $alert = $('#trayScanStatusAlert');

    if (!isActive) {
      // Inactive tray 1-line warning message
      $alert.removeClass('alert-info-theme').addClass('alert-warning-theme');
      html = `
        <i class="fa fa-exclamation-triangle text-warning" style="font-size:1.05rem;"></i>
        <span><b>Warning:</b> Scanned tray <code style="font-weight:700; color:#b45309; background:#fef3c7; padding:2px 7px; border-radius:4px; font-size:0.92rem;">${esc(barcode)}</code> status is <span class="badge bg-danger text-white px-2 py-1">${esc(status)}</span> (Inactive). Displaying remaining active trays in <b>${esc(bed)}</b> for movement.</span>
      `;
    } else {
      // Active tray 1-line status message
      $alert.removeClass('alert-warning-theme').addClass('alert-info-theme');
      let stBadgeClass = 'bg-success text-white';
      if (status.toLowerCase() === 'openstock') stBadgeClass = 'bg-primary text-white';
      else if (status.toLowerCase() === 'polyhouse') stBadgeClass = 'bg-info text-white';
      else if (['hardening', 'healing', 'grafted'].includes(status.toLowerCase())) stBadgeClass = 'bg-warning text-dark';

      html = `
        <i class="fa fa-check-circle text-success" style="font-size:1.05rem;"></i>
        <span>Scanned tray <code style="font-weight:700; color:#047857; background:#dcfce7; padding:2px 7px; border-radius:4px; font-size:0.92rem;">${esc(barcode)}</code> status is <span class="badge ${stBadgeClass} px-2 py-1">${esc(status)}</span> (Active in <b>${esc(bed)}</b>).</span>
      `;
    }

    $('#trayScanStatusContent').html(html);
    $alert.slideDown(200);
  }

  function proceedAddOrBatch(d, callback = null) {
    // If server returned active_trays_list, load batch active trays with checkboxes
    if (d && d.active_trays_list && d.active_trays_list.length > 0) {
      if (scannedTraysList.length > 0 && currentActiveBatch) {
        const isDiffBed = (currentActiveBatch.bed_no || '').toLowerCase() !== (d.bed_no || '').toLowerCase();
        const isDiffOrder = (currentActiveBatch.stockorder_no || '').toLowerCase() !== (d.stockorder_no || '').toLowerCase();

        if (isDiffBed || isDiffOrder) {
          Swal.fire({
            title: 'Different Batch Detected',
            html: `This tray is from <b>${esc(d.bed_no)}</b> (${esc(d.stockorder_no)}), but your grid currently has trays from <b>${esc(currentActiveBatch.bed_no)}</b>.<br><br>Do you want to switch to this bed?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Switch to New Bed',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#4f46e5',
            cancelButtonColor: '#64748b',
            didClose: () => { if (typeof callback === 'function') callback(); }
          }).then((choice) => {
            if (choice.isConfirmed) {
              loadBatchActiveTrays(d);
            }
            if (typeof callback === 'function') callback();
          });
          return;
        } else {
          // Same bed already loaded: highlight scanned barcode, update active card, and ensure it is checked
          const scannedBarcode = (d.barcode || (d.scanned_tray && d.scanned_tray.barcode) || '').trim().toUpperCase();
          const matchIdx = scannedTraysList.findIndex(t => (t.barcode || '').trim().toUpperCase() === scannedBarcode);
          renderScannedTrayActiveCard(d);

          if (matchIdx !== -1) {
            scannedTraysList[matchIdx].is_checked = true;
            renderScannedGrid();
            highlightRow(matchIdx);
            showToastInfo(`Tray "${scannedBarcode}" located & checked in grid.`);
          } else {
            // If active tray wasn't previously in list, insert at top checked ONLY IF status is polyhouse or readytosale
            const newTray = d.scanned_tray || d;
            const newSt = (newTray.status || newTray.raw_status || '').toLowerCase().replace(/[\s_]/g, '');
            if (newSt === 'polyhouse' || newSt === 'readytosale' || newSt === '') {
              newTray.is_checked = true;
              newTray.is_scanned = true;
              scannedTraysList.unshift(newTray);
              renderScannedGrid();
              highlightRow(0);
              showToastInfo(`Tray "${scannedBarcode}" added & checked in grid.`);
            } else {
              Swal.fire({
                icon: 'warning',
                title: 'Tray Not Eligible',
                text: `Tray "${scannedBarcode}" has status "${newTray.status || newTray.raw_status}". Only trays with status 'Polyhouse' or 'Ready to Sale' can be moved.`,
                confirmButtonColor: '#4f46e5'
              });
            }
          }
          $('#inputBarcodeQuery').val('').focus();
          if (typeof callback === 'function') callback();
          return;
        }
      }

      loadBatchActiveTrays(d);
      if (typeof callback === 'function') callback();
      return;
    }

    // Fallback if no active_trays_list
    if (scannedTraysList.length > 0 && currentActiveBatch) {
      const isDiffBed = (currentActiveBatch.bed_no || '').toLowerCase() !== (d.bed_no || '').toLowerCase();
      const isDiffOrder = (currentActiveBatch.stockorder_no || '').toLowerCase() !== (d.stockorder_no || '').toLowerCase();

      if (isDiffBed || isDiffOrder) {
        Swal.fire({
          title: 'Different Batch Detected',
          html: `This tray is from <b>${esc(d.bed_no)}</b> (${esc(d.stockorder_no)}), but your grid currently has trays from <b>${esc(currentActiveBatch.bed_no)}</b>.<br><br>Do you want to add it to this batch or start a new batch?`,
          icon: 'warning',
          showCancelButton: true,
          confirmButtonText: 'Start New Batch',
          cancelButtonText: 'Add Anyway',
          confirmButtonColor: '#4f46e5',
          cancelButtonColor: '#059669',
          didClose: () => { if (typeof callback === 'function') callback(); }
        }).then((choice) => {
          if (choice.isConfirmed) {
            clearAllScanned(false);
          }
          addTrayToCollection(d);
          if (typeof callback === 'function') callback();
        });
        return;
      }
    }

    addTrayToCollection(d);
    if (typeof callback === 'function') callback();
  }

  function loadBatchActiveTrays(d) {
    playScanBeep();
    triggerHaptic('success');

    // 1. Render Scanned Tray Active Details Card
    renderScannedTrayActiveCard(d);

    const scannedBarcode = (d.barcode || (d.scanned_tray && d.scanned_tray.barcode) || '').trim().toUpperCase();
    const isScannedActive = (d.scanned_tray && d.scanned_tray.is_active !== undefined) ? Boolean(d.scanned_tray.is_active) : true;

    // Filter to ensure only polyhouse and readytosale trays are loaded
    const filteredList = (d.active_trays_list || []).filter(t => {
      const st = (t.status || '').toLowerCase().replace(/[\s_]/g, '');
      return st === 'polyhouse' || st === 'readytosale' || st === '';
    });

    // 2. Load active trays from server and check the physically scanned tray
    scannedTraysList = filteredList.map(t => {
      const isThisScanned = (t.barcode || '').trim().toUpperCase() === scannedBarcode;
      return {
        ...t,
        is_checked: (isThisScanned && isScannedActive) || (t.is_checked === true)
      };
    });

    currentActiveBatch = d;
    $('#inputBarcodeQuery').val('');
    focusBarcodeInput();
    renderScannedGrid();

    // Scroll to & highlight the physically scanned tray
    if (isScannedActive) {
      const matchIdx = scannedTraysList.findIndex(t => (t.barcode || '').trim().toUpperCase() === scannedBarcode);
      if (matchIdx !== -1) {
        setTimeout(() => highlightRow(matchIdx), 150);
      }
    }
  }

  function addTrayToCollection(d) {
    if (!d) return;
    const bcode = (d.barcode || '').trim().toUpperCase();
    const pid = d.polyhouseplantid ? String(d.polyhouseplantid) : null;

    const stClean = ((d.status || d.raw_status || (d.scanned_tray && d.scanned_tray.status) || '')).toLowerCase().replace(/[\s_]/g, '');
    if (stClean !== 'polyhouse' && stClean !== 'readytosale' && stClean !== '') {
      Swal.fire({
        icon: 'warning',
        title: 'Tray Not Eligible',
        text: `Tray "${bcode}" has status "${d.status || d.raw_status}". Only trays with status 'Polyhouse' or 'Ready to Sale' can be moved.`,
        confirmButtonColor: '#4f46e5'
      });
      return;
    }

    // Strict double-check deduplication: reject if already in collection
    const isDup = scannedTraysList.some(t => {
      const tb = (t.barcode || '').trim().toUpperCase();
      const tpid = t.polyhouseplantid ? String(t.polyhouseplantid) : null;
      if (bcode && tb && bcode === tb) return true;
      if (pid && tpid && pid === tpid) return true;
      return false;
    });

    if (isDup) {
      console.warn('Blocked duplicate tray from entering collection:', bcode || pid);
      triggerHaptic('duplicate');
      showToastInfo(`Tray "${d.barcode}" is already in the list.`);
      return;
    }

    playScanBeep();
    triggerHaptic('success');
    d.is_checked = true;
    scannedTraysList.push(d);
    currentActiveBatch = d;
    $('#inputBarcodeQuery').val('');
    focusBarcodeInput();
    renderScannedGrid();
  }

  function highlightRow(index) {
    const $row = $('#tblScannedTraysBody tr').eq(index);
    if ($row.length) {
      $row.addClass('highlight-pulse');
      setTimeout(() => $row.removeClass('highlight-pulse'), 1300);
    }
    const $card = $('#tblScannedTraysCards .mobile-scanned-card').eq(index);
    if ($card.length) {
      $card.addClass('highlight-pulse');
      setTimeout(() => $card.removeClass('highlight-pulse'), 1300);
    }
  }

  window.removeTray = function (index) {
    if (index >= 0 && index < scannedTraysList.length) {
      scannedTraysList.splice(index, 1);
      if (scannedTraysList.length === 0) {
        currentActiveBatch = null;
      }
      renderScannedGrid();
    }
  };

  window.clearAllScanned = function (showConfirm = true) {
    if (showConfirm && scannedTraysList.length > 0) {
      Swal.fire({
        title: 'Clear Grid?',
        text: 'Are you sure you want to clear all scanned trays from the grid?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Yes, Clear All'
      }).then((res) => {
        if (res.isConfirmed) {
          scannedTraysList = [];
          currentActiveBatch = null;
          $('#trayScanStatusAlert').hide();
          renderScannedGrid();
        }
      });
    } else {
      scannedTraysList = [];
      currentActiveBatch = null;
      $('#trayScanStatusAlert').hide();
      renderScannedGrid();
    }
  };

  // Calculate Actual Plants in Tray using Formula: qtypertray - failurecount - dispatchqty
  function calculateActualPlantInTray(trayOrQty, failureCount = 0, dispatchQty = 0) {
    if (!trayOrQty && trayOrQty !== 0) return 0;

    let qtypertray = 0;
    let failurecount = 0;
    let dispatchqty = 0;

    if (typeof trayOrQty === 'object' && trayOrQty !== null) {
      qtypertray = parseInt(trayOrQty.qtypertray !== undefined ? trayOrQty.qtypertray : (trayOrQty.cavity_count || 126), 10);
      failurecount = parseInt(trayOrQty.failurecount || 0, 10);
      dispatchqty = parseInt(trayOrQty.dispatchqty || 0, 10);
    } else {
      qtypertray = parseInt(trayOrQty || 126, 10);
      failurecount = parseInt(failureCount || 0, 10);
      dispatchqty = parseInt(dispatchQty || 0, 10);
    }

    if (isNaN(qtypertray)) qtypertray = 126;
    if (isNaN(failurecount)) failurecount = 0;
    if (isNaN(dispatchqty)) dispatchqty = 0;

    const actual = qtypertray - failurecount - dispatchqty;
    return Math.max(0, actual);
  }

  // 4. Render Scanned Grid & Controls
  function renderScannedGrid() {
    const totalCount = scannedTraysList.length;
    const checkedCount = scannedTraysList.filter(t => t.is_checked === true).length;

    if (totalCount === 0) {
      $('#lblGridCounter').text('0 active trays (0 checked)');
      $('#trayScanStatusAlert').hide();
      $('#trayAutoSelectToolbar').hide();
      $('#inputAutoSelectCount').val('');
      $('#boxSuggestedQuickBtns').empty();
      $('#emptyGridPlaceholder').show();
      $('#traysTableContainer').hide();
      $('#batchSummaryBox').hide();
      $('#panelMovementActions').hide();
      $('#panelQuickRts').hide();
      $('#panelExecuteMovement').hide();
      $('#inputGridSearch').val('');
      $('#btnClearGridSearch').hide();
      $('#gridSearchStatusStrip').hide();
      return;
    }

    $('#emptyGridPlaceholder').hide();
    $('#traysTableContainer').show();
    $('#batchSummaryBox').show();
    $('#panelMovementActions').show();

    // Calculate totals
    let totalPlants = 0;
    let checkedPlants = 0;
    let bHtml = '';
    let cHtml = '';

    scannedTraysList.forEach((t, i) => {
      const q = calculateActualPlantInTray(t);
      totalPlants += q;
      const isChecked = (t.is_checked === true);
      if (isChecked) {
        checkedPlants += q;
      }

      // Modern Status Badges
      let stBadge = '<span class="tray-badge-pill pill-polyhouse"><i class="fa fa-warehouse mr-1"></i>Polyhouse</span>';
      const st = (t.status || '').toLowerCase().trim();
      if (st === 'readytosale' || st === 'ready to sale' || t.is_ready_to_sale === true) {
        stBadge = '<span class="tray-badge-pill pill-ready"><i class="fa fa-check-circle mr-1"></i>Ready to Sale</span>';
      } else if (st === 'openstock') {
        stBadge = '<span class="tray-badge-pill pill-openstock"><i class="fa fa-store mr-1"></i>Open Stock</span>';
      } else if (st === 'polyhouse' || !st) {
        stBadge = '<span class="tray-badge-pill pill-polyhouse"><i class="fa fa-warehouse mr-1"></i>Polyhouse</span>';
      } else {
        stBadge = `<span class="tray-badge-pill pill-default">${esc(t.status)}</span>`;
      }

      // Desktop Table Row (Order/PSO and Product & Variety columns hidden)
      bHtml += `
        <tr class="${isChecked ? '' : 'opacity-75 table-light'}" id="trayRow_${i}">
          <td style="text-align:center;">
            <input type="checkbox" class="tray-select-chk" data-index="${i}" ${isChecked ? 'checked' : ''} onchange="handleTrayCheckChange(${i}, this)" title="Select tray for movement">
          </td>
          <td style="text-align:center;"><strong>${i + 1}</strong></td>
          <td><span class="tray-barcode-badge">${esc(t.barcode)}</span></td>
          <td class="col-hide-field font-monospace text-primary font-weight-bold" style="display:none;">${esc(t.stockorder_no || t.psono || '-')}</td>
          <td class="col-hide-field" style="display:none;">
            <strong>${esc(t.productname || 'Plant')}</strong>
            ${t.variety && t.variety !== '-' ? `<small class="text-muted d-block">${esc(t.variety)}</small>` : ''}
          </td>
          <td>
            <span class="badge bg-dark text-white mr-1">${esc(t.bed_no || '-')}</span>
            <small class="text-muted">${esc(t.polyhousename || 'PH')}</small>
          </td>
          <td>${stBadge}</td>
          <td class="text-center">
            <button type="button" class="btn-remove-tray" onclick="removeTray(${i})" title="Remove tray from list">
              <i class="fa fa-times"></i>
            </button>
          </td>
        </tr>
      `;

      // Modern Scanned Tray Card
      cHtml += `
        <div class="mobile-scanned-card ${isChecked ? 'is-checked' : 'is-unchecked'}" id="trayCard_${i}">
          <div class="tray-card-inner">
            <!-- Left Info Cluster -->
            <div class="tray-card-left">
              <input type="checkbox" class="tray-select-chk" data-index="${i}" ${isChecked ? 'checked' : ''} onchange="handleTrayCheckChange(${i}, this)" title="Select tray for movement">
              <span class="tray-card-idx">#${i + 1}</span>
              <span class="tray-card-barcode"><i class="fa fa-qrcode mr-1 text-primary"></i>${esc(t.barcode)}</span>
              ${stBadge}
            </div>

            <!-- Middle Meta Cluster -->
            <div class="tray-card-meta">
              <span class="tray-meta-chip tray-chip-loc">
                <i class="fa fa-map-marker-alt text-danger mr-1"></i><b>${esc(t.bed_no || '-')}</b>
                <span class="tray-chip-sub">(${esc(t.polyhousename || 'PH')})</span>
              </span>
              <span class="tray-meta-chip tray-chip-plants">
                <i class="fa fa-leaf text-success mr-1"></i><b>${q}</b> <span class="text-muted">Plants</span>
              </span>
            </div>

            <!-- Right Action Cluster -->
            <div class="tray-card-right">
              <button type="button" class="btn-remove-tray-card" onclick="removeTray(${i})" title="Remove tray from list">
                <i class="fa fa-times"></i>
              </button>
            </div>
          </div>
        </div>
      `;
    });

    $('#tblScannedTraysBody').html(bHtml);
    $('#tblScannedTraysCards').html(cHtml);

    // Update Live Batch Strip
    const b = currentActiveBatch || scannedTraysList[0];
    $('#lblBatchOrder').text(b.stockorder_no || b.psono || 'N/A');
    $('#lblBatchProduct').text(b.productname || 'Plant');
    $('#lblBatchBed').text(`${b.bed_no || '-'} (${b.polyhousename || 'PH'})`);

    const bedTotal = parseInt(b.occupied_trays || totalCount, 10);
    $('#lblBatchBedTotal').text(`${bedTotal} Trays`);
    $('#lblBatchPolyhouse').text(b.polyhouse_trays !== undefined ? b.polyhouse_trays : '-');
    $('#lblBatchReady').text(b.ready_to_sale_trays !== undefined ? b.ready_to_sale_trays : '-');

    $('#lblGridCounter').text(`${totalCount} active trays (${checkedCount} checked for movement)`);
    $('#lblCountCheckedTrays').text(checkedCount);
    $('#lblCountScannedTrays').text(totalCount);
    $('#lblCountScannedPlants').text(checkedPlants.toLocaleString());

    // 1. Conditional visibility of "Mark as Ready to Sell": Visible ONLY IF trays with status 'Polyhouse' > 0
    const polyhouseTrays = scannedTraysList.filter(t => (t.status || '').trim().toLowerCase() === 'polyhouse');
    const polyhouseCount = polyhouseTrays.length;
    if (polyhouseCount > 0) {
      $('#panelQuickRts').show();
      $('#lblQuickRtsScope').text(`${polyhouseCount} Tray${polyhouseCount === 1 ? '' : 's'}`);
    } else {
      $('#panelQuickRts').hide();
    }

    // 2. Conditional visibility of "Execute Plant Movement / Shifting": Visible ONLY IF selected tray count > 0
    if (checkedCount > 0) {
      $('#panelExecuteMovement').show();
      $('#lblCheckedTraysMoveCount').text(checkedCount);
      $('#lblSelectedTraysBadge').html(`<i class="fa fa-check-circle mr-1"></i> ${checkedCount} Selected`);
      $('#lblSubmitMovementText, #lblSubmitMovementTextMobile').text(`Confirm & Move ${checkedCount} Checked Tray${checkedCount === 1 ? '' : 's'}`);
    } else {
      $('#panelExecuteMovement').hide();
    }

    const allChecked = totalCount > 0 && checkedCount === totalCount;
    const noneChecked = checkedCount === 0;
    $('#chkSelectAllTrays').prop('checked', allChecked);
    $('#chkSelectAllTrays').prop('indeterminate', !allChecked && !noneChecked);

    updateScopeSelectionUI();
    populateDestinationDropdowns(b);
    renderAutoSelectToolbar(totalCount, checkedCount);

    // Re-apply search filter if user already entered search query
    const activeSearchQuery = ($('#inputGridSearch').val() || '').trim();
    if (activeSearchQuery) {
      filterGridTrays();
    } else {
      $('#btnClearGridSearch').hide();
      $('#gridSearchStatusStrip').hide();
    }
  }

  // Checkbox state management
  window.handleTrayCheckChange = function (index, el) {
    if (index >= 0 && index < scannedTraysList.length) {
      scannedTraysList[index].is_checked = $(el).is(':checked');
      updateCheckedCountsUI();
    }
  };

  window.handleSelectAllChange = function (el) {
    const isChecked = $(el).is(':checked');
    const query = ($('#inputGridSearch').val() || '').trim();
    if (query) {
      toggleSelectFilteredTrays(isChecked);
    } else {
      toggleSelectAllTrays(isChecked);
    }
  };

  window.toggleSelectAllTrays = function (check) {
    scannedTraysList.forEach(t => {
      t.is_checked = check;
    });
    renderScannedGrid();
  };

  // Auto-Select Trays & Suggested Quick Count Toolbar
  function renderAutoSelectToolbar(totalCount, checkedCount) {
    if (totalCount <= 0) {
      $('#trayAutoSelectToolbar').hide();
      return;
    }

    $('#trayAutoSelectToolbar').show();
    $('#inputAutoSelectCount').attr('max', totalCount);
    $('#inputAutoSelectCount').val(checkedCount > 0 ? checkedCount : '');

    // Suggested count switch buttons (dynamic based on totalCount)
    const presets = [5, 10, 20, 25, 50, 100, 200];
    let buttonsHtml = '';

    presets.forEach(num => {
      if (num < totalCount) {
        const isActive = (checkedCount === num);
        buttonsHtml += `
          <button type="button" class="btn-suggest-pill ${isActive ? 'active' : ''}" data-val="${num}" onclick="applyQuickTrayCount(${num})" title="Select first ${num} trays">
            ${num}
          </button>
        `;
      }
    });

    // All button
    const isAllActive = (checkedCount === totalCount);
    buttonsHtml += `
      <button type="button" class="btn-suggest-pill pill-all ${isAllActive ? 'active' : ''}" data-val="${totalCount}" onclick="applyQuickTrayCount(${totalCount})" title="Select all ${totalCount} trays">
        <i class="fa fa-check-double mr-1"></i> All (${totalCount})
      </button>
    `;

    // Clear (0) button
    const isClearActive = (checkedCount === 0);
    buttonsHtml += `
      <button type="button" class="btn-suggest-pill pill-clear ${isClearActive ? 'active' : ''}" data-val="0" onclick="applyQuickTrayCount(0)" title="Uncheck all trays">
        <i class="fa fa-times mr-1"></i> Clear
      </button>
    `;

    $('#boxSuggestedQuickBtns').html(buttonsHtml);
  }

  function syncSuggestPillActiveState(count) {
    $('#boxSuggestedQuickBtns .btn-suggest-pill').each(function () {
      const val = parseInt($(this).attr('data-val'), 10);
      if (val === count) {
        $(this).addClass('active');
      } else {
        $(this).removeClass('active');
      }
    });
  }

  window.handleAutoSelectCountInput = function (el) {
    // Digits only sanitization
    let raw = el.value.replace(/[^0-9]/g, '');
    if (el.value !== raw) {
      el.value = raw;
    }

    const totalCount = scannedTraysList.length;
    if (totalCount === 0) return;

    let count = parseInt(raw, 10);
    if (isNaN(count)) count = 0;

    // Clamp to totalCount
    if (count > totalCount) {
      count = totalCount;
      el.value = totalCount;
    }

    // Auto select first N trays in the table grid
    scannedTraysList.forEach((t, idx) => {
      t.is_checked = (idx < count);
    });

    updateCheckedCountsUI(false);
    syncSuggestPillActiveState(count);
  };

  window.applyQuickTrayCount = function (val) {
    const totalCount = scannedTraysList.length;
    let count = parseInt(val, 10);
    if (isNaN(count)) count = 0;
    if (count > totalCount) count = totalCount;

    $('#inputAutoSelectCount').val(count > 0 ? count : '');

    scannedTraysList.forEach((t, idx) => {
      t.is_checked = (idx < count);
    });

    updateCheckedCountsUI(false);
    syncSuggestPillActiveState(count);
  };

  function updateCheckedCountsUI(syncInput = true) {
    const totalCount = scannedTraysList.length;
    const checkedCount = scannedTraysList.filter(t => t.is_checked === true).length;
    let checkedPlants = 0;
    scannedTraysList.forEach(t => {
      if (t.is_checked === true) {
        checkedPlants += calculateActualPlantInTray(t);
      }
    });

    const activeQuery = ($('#inputGridSearch').val() || '').trim();
    if (!activeQuery) {
      $('#lblGridCounter').text(`${totalCount} active trays (${checkedCount} checked for movement)`);
    }
    $('#lblCountCheckedTrays').text(checkedCount);
    $('#lblCountScannedPlants').text(checkedPlants.toLocaleString());

    // 1. Conditional visibility of "Mark as Ready to Sell": Visible ONLY IF trays with status 'Polyhouse' > 0
    const polyhouseTrays = scannedTraysList.filter(t => (t.status || '').trim().toLowerCase() === 'polyhouse');
    const polyhouseCount = polyhouseTrays.length;
    if (polyhouseCount > 0) {
      $('#panelQuickRts').show();
      $('#lblQuickRtsScope').text(`${polyhouseCount} Tray${polyhouseCount === 1 ? '' : 's'}`);
    } else {
      $('#panelQuickRts').hide();
    }

    // 2. Conditional visibility of "Execute Plant Movement / Shifting": Visible ONLY IF selected tray count > 0
    if (checkedCount > 0) {
      $('#panelExecuteMovement').show();
      $('#lblCheckedTraysMoveCount').text(checkedCount);
      $('#lblSelectedTraysBadge').html(`<i class="fa fa-check-circle mr-1"></i> ${checkedCount} Selected`);
      $('#lblSubmitMovementText, #lblSubmitMovementTextMobile').text(`Confirm & Move ${checkedCount} Checked Tray${checkedCount === 1 ? '' : 's'}`);
    } else {
      $('#panelExecuteMovement').hide();
    }

    const allChecked = totalCount > 0 && checkedCount === totalCount;
    const noneChecked = checkedCount === 0;
    $('#chkSelectAllTrays').prop('checked', allChecked);
    $('#chkSelectAllTrays').prop('indeterminate', !allChecked && !noneChecked);

    // Sync desktop checkboxes and row styling
    $('#tblScannedTraysBody tr').each(function (i) {
      const isChecked = scannedTraysList[i] && scannedTraysList[i].is_checked === true;
      $(this).find('.tray-select-chk').prop('checked', isChecked);
      if (isChecked) {
        $(this).removeClass('opacity-75 table-light');
      } else {
        $(this).addClass('opacity-75 table-light');
      }
    });

    // Sync mobile checkboxes and card styling
    $('#tblScannedTraysCards .mobile-scanned-card').each(function (i) {
      const isChecked = scannedTraysList[i] && scannedTraysList[i].is_checked === true;
      $(this).find('.tray-select-chk').prop('checked', isChecked);
      if (isChecked) {
        $(this).addClass('is-checked').removeClass('is-unchecked opacity-75 bg-light');
      } else {
        $(this).addClass('is-unchecked opacity-75 bg-light').removeClass('is-checked');
      }
    });

    if (currentScopeMode === 'scanned_only') {
      $('#lblSubmitMovementText, #lblSubmitMovementTextMobile').text(`Confirm & Move ${checkedCount} Checked Tray${checkedCount === 1 ? '' : 's'}`);
    }

    if (syncInput) {
      $('#inputAutoSelectCount').val(checkedCount > 0 ? checkedCount : '');
      syncSuggestPillActiveState(checkedCount);
    }
  }

  // Live Grid Tray Search & Selection Functions
  window.filterGridTrays = function () {
    const rawQuery = ($('#inputGridSearch').val() || '').trim();
    const query = rawQuery.toLowerCase();

    if (!query) {
      clearGridSearch(false);
      return;
    }

    $('#btnClearGridSearch').show();
    let matchingCount = 0;

    scannedTraysList.forEach((t, i) => {
      const bc = (t.barcode || '').toLowerCase();
      const order = (t.stockorder_no || t.psono || '').toLowerCase();
      const product = (t.productname || '').toLowerCase();
      const variety = (t.variety || '').toLowerCase();
      const bed = (t.bed_no || '').toLowerCase();
      const ph = (t.polyhousename || '').toLowerCase();
      const st = (t.status || '').toLowerCase();
      const idxStr = String(i + 1);

      const matches = bc.includes(query) ||
        order.includes(query) ||
        product.includes(query) ||
        variety.includes(query) ||
        bed.includes(query) ||
        ph.includes(query) ||
        st.includes(query) ||
        idxStr === query;

      const $row = $(`#trayRow_${i}`);
      const $card = $(`#trayCard_${i}`);

      if (matches) {
        $row.show();
        $card.show();
        matchingCount++;
      } else {
        $row.hide();
        $card.hide();
      }
    });

    const checkedCount = scannedTraysList.filter(t => t.is_checked === true).length;
    $('#lblGridCounter').text(`${scannedTraysList.length} active trays (${checkedCount} checked) · Showing ${matchingCount} matching`);
    $('#lblGridSearchSummary').text(`Showing ${matchingCount} of ${scannedTraysList.length} matching trays`);
    $('#gridSearchStatusStrip').css('display', 'flex');

    // Show or remove placeholder if 0 matches
    $('#rowNoSearchMatch').remove();
    if (matchingCount === 0) {
      $('#tblScannedTraysBody').append(`
        <tr id="rowNoSearchMatch">
          <td colspan="6" class="text-center py-4 text-muted" style="background:#f8fafc;">
            <i class="fa fa-search fa-2x mb-2 text-muted" style="opacity:0.35;"></i>
            <div class="font-weight-bold" style="font-size:0.95rem;">No active trays match "${esc(rawQuery)}"</div>
            <div class="small mt-1">Check barcode number or <a href="javascript:void(0)" onclick="clearGridSearch()" class="text-primary font-weight-bold">clear search</a> to view all ${scannedTraysList.length} trays.</div>
          </td>
        </tr>
      `);
    }
  };

  window.clearGridSearch = function (clearInput = true) {
    if (clearInput) {
      $('#inputGridSearch').val('');
    }
    $('#btnClearGridSearch').hide();
    $('#gridSearchStatusStrip').hide();
    $('#rowNoSearchMatch').remove();
    $('#tblScannedTraysBody tr').show();
    $('#tblScannedTraysCards .mobile-scanned-card').show();
    const checkedCount = scannedTraysList.filter(t => t.is_checked === true).length;
    $('#lblGridCounter').text(`${scannedTraysList.length} active trays (${checkedCount} checked for movement)`);
  };

  window.toggleSelectFilteredTrays = function (check) {
    const query = ($('#inputGridSearch').val() || '').trim().toLowerCase();
    if (!query) {
      toggleSelectAllTrays(check);
      return;
    }

    let affectedCount = 0;
    scannedTraysList.forEach((t, i) => {
      const bc = (t.barcode || '').toLowerCase();
      const order = (t.stockorder_no || t.psono || '').toLowerCase();
      const product = (t.productname || '').toLowerCase();
      const variety = (t.variety || '').toLowerCase();
      const bed = (t.bed_no || '').toLowerCase();
      const ph = (t.polyhousename || '').toLowerCase();
      const st = (t.status || '').toLowerCase();
      const idxStr = String(i + 1);

      const matches = bc.includes(query) ||
        order.includes(query) ||
        product.includes(query) ||
        variety.includes(query) ||
        bed.includes(query) ||
        ph.includes(query) ||
        st.includes(query) ||
        idxStr === query;

      if (matches) {
        t.is_checked = check;
        affectedCount++;
      }
    });

    updateCheckedCountsUI();
    const totalChecked = scannedTraysList.filter(t => t.is_checked === true).length;
    showToastInfo(`${check ? 'Checked' : 'Unchecked'} ${affectedCount} matching tray(s) (${totalChecked} total checked).`);
  };

  // Edit & Decrease Plant Count in a Tray
  window.editTrayPlantCount = function (idx) {
    const t = scannedTraysList[idx];
    if (!t) return;

    const currentActual = calculateActualPlantInTray(t);
    const qtypertray = parseInt(t.qtypertray !== undefined ? t.qtypertray : (t.cavity_count || 126), 10);
    const failurecount = parseInt(t.failurecount || 0, 10);
    const dispatchqty = parseInt(t.dispatchqty || 0, 10);
    const barcode = t.barcode || '';
    const polyhouseplantid = t.polyhouseplantid || 0;

    Swal.fire({
      title: 'Edit Plant Count in Tray',
      html: `
        <div style="text-align: left; font-size: 0.9rem; color: #334155;">
          <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 12px; margin-bottom: 12px;">
            <div style="margin-bottom: 4px;"><b>Tray Barcode:</b> <code style="color:#d946ef; font-weight:700;">${esc(barcode)}</code></div>
            <div style="margin-bottom: 4px;"><b>Product:</b> ${esc(t.productname || '-')}</div>
            <div style="display: flex; gap: 12px; font-size: 0.82rem; color: #64748b; margin-top: 6px;">
              <span>Capacity (Qty): <b>${qtypertray}</b></span>
              <span>Failed: <b>${failurecount}</b></span>
              <span>Dispatched: <b>${dispatchqty}</b></span>
            </div>
            <div style="margin-top: 6px; font-size: 0.92rem; color: #0f172a;">
              <b>Current Available Plants:</b> <span class="text-success font-weight-bold" style="font-size: 1.05rem;">${currentActual} Plants</span>
            </div>
          </div>

          <label class="form-label" style="font-weight: 700; margin-bottom: 6px; display: block;">
            New Plant Count <span class="text-danger">*</span>
          </label>
          <input type="number" id="swalNewPlantCount" class="swal2-input" style="margin: 0; width: 100%; box-sizing: border-box; font-size: 1.25rem; font-weight: 800; text-align: center; color: #0f172a;" min="0" max="${currentActual}" value="${currentActual}">
          
          <div id="swalPlantFeedback" style="margin-top: 10px; font-size: 0.82rem; min-height: 22px;">
            <span class="text-muted"><i class="fa fa-info-circle text-primary mr-1"></i> You can only <b>decrease</b> plant count (0 to ${currentActual}). Increasing is not allowed.</span>
          </div>
        </div>
      `,
      showCancelButton: true,
      confirmButtonColor: '#4f46e5',
      cancelButtonColor: '#64748b',
      confirmButtonText: '<i class="fa fa-check mr-1"></i> Update Plants',
      cancelButtonText: 'Cancel',
      focusConfirm: false,
      didOpen: () => {
        const $inp = $('#swalNewPlantCount');
        const $feedback = $('#swalPlantFeedback');

        $inp.focus().select();

        // Real-time clamping and validation on keystroke / paste
        $inp.on('input keyup change paste', function () {
          const raw = $(this).val();
          if (raw === '') {
            $feedback.html('<span class="text-danger"><i class="fa fa-exclamation-circle mr-1"></i> Please enter a number.</span>');
            return;
          }
          let val = parseInt(raw, 10);
          if (val > currentActual) {
            this.value = currentActual;
            $feedback.html(`<span class="text-danger font-weight-bold"><i class="fa fa-ban mr-1"></i> You cannot increase plant count above ${currentActual}! Clamped to ${currentActual}.</span>`);
          } else if (val < 0) {
            this.value = 0;
            $feedback.html('<span class="text-danger"><i class="fa fa-ban mr-1"></i> Plant count cannot be negative. Set to 0.</span>');
          } else {
            const diff = currentActual - val;
            if (diff > 0) {
              $feedback.html(`<span class="text-success font-weight-bold"><i class="fa fa-arrow-down mr-1"></i> Decreasing by ${diff} plant(s) (New Total: ${val} Plants)</span>`);
            } else {
              $feedback.html(`<span class="text-muted"><i class="fa fa-info-circle text-primary mr-1"></i> Maximum allowed: <b>${currentActual}</b> plants.</span>`);
            }
          }
        });
      },
      preConfirm: () => {
        const valStr = $('#swalNewPlantCount').val();
        if (valStr === '' || isNaN(valStr)) {
          Swal.showValidationMessage('Please enter a valid plant count.');
          return false;
        }
        const newVal = parseInt(valStr, 10);
        if (newVal < 0) {
          Swal.showValidationMessage('Plant count cannot be negative.');
          return false;
        }
        if (newVal > currentActual) {
          Swal.showValidationMessage(`You cannot increase plant count. Maximum allowed is ${currentActual}.`);
          return false;
        }
        return newVal;
      }
    }).then((result) => {
      if (result.isConfirmed && typeof result.value === 'number') {
        const newPlantCount = result.value;

        // If no change, return
        if (newPlantCount === currentActual) return;

        Swal.fire({
          title: 'Updating...',
          text: 'Saving updated plant count to database...',
          allowOutsideClick: false,
          didOpen: () => {
            Swal.showLoading();
          }
        });

        $.ajax({
          url: 'modal/verifiedtraymovement_modal.php',
          type: 'POST',
          dataType: 'json',
          data: {
            action: 'update_tray_plant_count',
            barcode: barcode,
            polyhouseplantid: polyhouseplantid,
            new_plant_count: newPlantCount
          },
          success: function (res) {
            if (res.status === 'success') {
              // Update local tray state
              t.failurecount = res.data?.failurecount ?? (qtypertray - dispatchqty - newPlantCount);
              t.actual_plant_count = res.data?.actual_plant_count ?? newPlantCount;
              t.cavity_count = t.actual_plant_count;

              renderScannedGrid();

              Swal.fire({
                icon: 'success',
                title: 'Plant Count Updated!',
                text: res.message || `Plant count updated to ${newPlantCount} for tray ${barcode}.`,
                timer: 1800,
                showConfirmButton: false
              });
            } else {
              Swal.fire('Error', res.message || 'Failed to update plant count.', 'error');
            }
          },
          error: function (xhr, st, err) {
            Swal.fire('Server Error', 'Could not communicate with server: ' + err, 'error');
          }
        });
      }
    });
  };

  // Helper to safely resolve user role ID (handles 0 for WebAdministrator, 1 for SuperAdmin, 2 for Admin)
  function resolveUserRoleId() {
    if (typeof window.userRoleId !== 'undefined' && window.userRoleId !== null && window.userRoleId !== '') {
      return parseInt(window.userRoleId, 10);
    }
    const hiddenVal = $('#hidden_loginroleid').val();
    if (hiddenVal !== undefined && hiddenVal !== null && hiddenVal !== '') {
      return parseInt(hiddenVal, 10);
    }
    const bodyVal = $('body').data('loginroleid');
    if (bodyVal !== undefined && bodyVal !== null && bodyVal !== '') {
      return parseInt(bodyVal, 10);
    }
    return 4;
  }

  function isUserAdminRole() {
    const role = resolveUserRoleId();
    // WebAdministrator (0), SuperAdmin (1), Admin (2) -> All are < 3
    return (!isNaN(role) && role >= 0 && role < 3);
  }

  // Helper to check if active scanned product is a Grafted product
  function isBatchGraftedProduct() {
    if (currentActiveBatch) {
      if (currentActiveBatch.is_grafted !== undefined) {
        return Boolean(currentActiveBatch.is_grafted);
      }
      if (currentActiveBatch.scanned_tray && currentActiveBatch.scanned_tray.is_grafted !== undefined) {
        return Boolean(currentActiveBatch.scanned_tray.is_grafted);
      }
    }
    if (scannedTraysList && scannedTraysList.length > 0) {
      for (let i = 0; i < scannedTraysList.length; i++) {
        const t = scannedTraysList[i];
        if (t.is_grafted !== undefined) {
          return Boolean(t.is_grafted);
        }
        const pName = (t.productname || '').toLowerCase();
        if (pName.includes('nongraft') || pName.includes('non-graft')) {
          return false;
        }
        if (pName.includes('graft')) {
          return true;
        }
      }
    }
    return false;
  }

  // Dynamically update Movement Reasons based on scope mode & product grafting status
  function updateMovementReasonDropdown() {
    const curVal = $('#selectMovementReason').val();
    const isAdmin = isUserAdminRole();
    const isGrafted = isBatchGraftedProduct();

    let opts = '<option value="">-- Select Movement Reason --</option>';
    const reasons = [];

    if (isAdmin) {
      // Admin / Senior Management (loginroleid < 3): Unconditionally show ALL options
      reasons.push(
        'Polyhouse Shifting',
        'Bed Shifting',
        'Move for Grafting',
        'Healing',
        'Hardening',
        'Clip Stick Removal',
        'Empty After Grafting',
        'Empty After Shifting',
        'Move for Opensale',
        'Remove After Over Age',
        'Tray Dead',
        'Removed',
        'Add Trays',
        'Grafted'
      );
    } else {
      // Restricted Roles (loginroleid >= 3): Standard reasons + grafting-only if product is Grafted
      reasons.push(
        'Polyhouse Shifting',
        'Bed Shifting'
      );

      // Grafting-specific reasons: visible ONLY when product is Grafted (otherwise hidden)
      if (isGrafted) {
        reasons.push(
          'Move for Grafting',
          'Healing',
          'Hardening',
          'Clip Stick Removal',
          'Empty After Grafting'
        );
      }

      // General movement reasons
      reasons.push(
        'Empty After Shifting',
        'Move for Opensale',
        'Remove After Over Age',
        'Tray Dead'
      );

      // "Grafted" option: visible ONLY when product is Grafted
      if (isGrafted) {
        reasons.push('Grafted');
      }
    }

    reasons.forEach(r => {
      opts += `<option value="${r}">${r}</option>`;
    });

    $('#selectMovementReason').html(opts);

    if (reasons.includes(curVal)) {
      $('#selectMovementReason').val(curVal);
    } else {
      $('#selectMovementReason').val('');
    }

    handleMovementReasonChange();
  }

  // 5. Movement Scope Mode (Strictly Selected / Checked Trays)
  window.setScopeMode = function (mode) {
    currentScopeMode = 'scanned_only';
    updateScopeSelectionUI();
  };

  function updateScopeSelectionUI() {
    currentScopeMode = 'scanned_only';
    const checkedCount = scannedTraysList.filter(t => t.is_checked === true).length;
    $('#lblSubmitMovementText, #lblSubmitMovementTextMobile').text(`Confirm & Move ${checkedCount} Checked Tray${checkedCount === 1 ? '' : 's'}`);
    $('#lblCheckedTraysMoveCount').text(checkedCount);
    $('#lblSelectedTraysBadge').html(`<i class="fa fa-check-circle mr-1"></i> ${checkedCount} Selected`);
    updateMovementReasonDropdown();
  }

  // 6. Destination Dropdowns Population
  function populateOpenSaleLocations(list) {
    if (!list || !list.length) {
      $('#selectOpenSaleLocation').html('<option value="">-- No Open Sale Location Found --</option>');
      return;
    }
    const curVal = $('#selectOpenSaleLocation').val();
    let opts = '<option value="">-- Select Open Sale Location --</option>';
    list.forEach(item => {
      const locName = item.polyhousename;
      const phId = item.polyhouseid;
      const locId = item.locationid || '';
      opts += `<option value="${esc(locName)}" data-polyhouseid="${esc(phId)}" data-locationid="${esc(locId)}">${esc(locName)}</option>`;
    });
    $('#selectOpenSaleLocation').html(opts);
    if (curVal) {
      $('#selectOpenSaleLocation').val(curVal);
    }
  }

  function fetchOpenSaleLocations(callback) {
    $.getJSON('modal/verifiedtraymovement_modal.php?action=get_opensale_locations', function (res) {
      if (res && res.status === 'success' && res.data) {
        populateOpenSaleLocations(res.data);
        if (typeof callback === 'function') callback(res.data);
      }
    }).fail(function () {
      $('#selectOpenSaleLocation').html('<option value="">-- Error Loading Open Sale Locations --</option>');
    });
  }

  function populateDestinationDropdowns(b) {
    // Populate Same Polyhouse Bed Dropdown
    if (b.polyhouse_beds && b.polyhouse_beds.length) {
      let bOpts = '<option value="">-- Select Destination Bed --</option>';
      const curBed = (b.bed_no || '').toLowerCase();
      b.polyhouse_beds.forEach(bed => {
        if (bed.toLowerCase() !== curBed) {
          bOpts += `<option value="${esc(bed)}">${esc(bed)}</option>`;
        }
      });
      $('#selectTargetBed').html(bOpts);
    }

    // Populate All Polyhouses Dropdown
    if (b.all_polyhouses && b.all_polyhouses.length) {
      let phOpts = '<option value="">-- Select Destination Polyhouse --</option>';
      const curPhId = String(b.polyhouseid || '');
      b.all_polyhouses.forEach(ph => {
        const phId = String(ph.polyhouseid || '');
        if (phId !== curPhId) {
          phOpts += `<option value="${esc(phId)}">${esc(ph.polyhousename)}</option>`;
        }
      });
      $('#selectTargetPolyhouse').html(phOpts);
    }

    // Populate Open Sale Locations Dropdown
    if (b.opensale_locations && b.opensale_locations.length) {
      populateOpenSaleLocations(b.opensale_locations);
    }
  }

  window.handleMovementReasonChange = function () {
    const reason = $('#selectMovementReason').val();

    $('#boxTargetBed').hide();
    $('#boxTargetPolyhouse').hide();
    $('#boxTargetPolyhouseBed').hide();
    $('#boxOpenSaleLocation').hide();

    // Check if Final Dispatch selected while trays are not yet Ready to Sale
    if (reason === 'Final Dispatch') {
      const hasUnready = scannedTraysList.some(t => (t.status || '').toLowerCase() !== 'readytosale' && !t.is_ready_to_sale);
      if (hasUnready) {
        Swal.fire({
          icon: 'warning',
          title: 'Mark Ready to Sell First',
          html: 'This tray has not been marked as <b>Ready to Sale</b> yet.<br><br>Please click the green <b>"Mark as Ready to Sell"</b> button above before performing <b>Final Dispatch</b>.',
          confirmButtonColor: '#059669',
          confirmButtonText: '<i class="fa fa-seedling mr-1"></i> OK, Got It'
        });
        $('#selectMovementReason').val('');
        $('#narrationNoticeBox').hide();
        return;
      }
    }

    if (reason === 'Bed Shifting') {
      $('#boxTargetBed').show();
      $('#selectTargetBed').prop('required', true);
    } else {
      $('#selectTargetBed').prop('required', false);
    }

    if (reason === 'Polyhouse Shifting' || reason === 'Hardening') {
      const isHardening = (reason === 'Hardening');
      const b = currentActiveBatch || (scannedTraysList.length ? scannedTraysList[0] : null);
      if (b && b.all_polyhouses && b.all_polyhouses.length) {
        let phOpts = '<option value="">-- Select Destination Polyhouse --</option>';
        const curPhId = String(b.polyhouseid || '');
        b.all_polyhouses.forEach(ph => {
          const phId = String(ph.polyhouseid || '');
          if (isHardening) {
            const isCurrent = (phId === curPhId) ? ' (Current)' : '';
            phOpts += `<option value="${esc(phId)}">${esc(ph.polyhousename + isCurrent)}</option>`;
          } else {
            if (phId !== curPhId) {
              phOpts += `<option value="${esc(phId)}">${esc(ph.polyhousename)}</option>`;
            }
          }
        });
        $('#selectTargetPolyhouse').html(phOpts);
      }
      $('#boxTargetPolyhouse').show();
      $('#boxTargetPolyhouseBed').show();
      $('#selectTargetPolyhouse').prop('required', true);
      $('#selectTargetPolyhouseBed').prop('required', true);
    } else {
      $('#selectTargetPolyhouse').prop('required', false);
      $('#selectTargetPolyhouseBed').prop('required', false);
    }

    if (reason === 'Move for Opensale') {
      $('#boxOpenSaleLocation').show();
      $('#selectOpenSaleLocation').prop('required', true);
      const b = currentActiveBatch || (scannedTraysList.length ? scannedTraysList[0] : null);
      if (b && b.opensale_locations && b.opensale_locations.length) {
        populateOpenSaleLocations(b.opensale_locations);
      } else {
        fetchOpenSaleLocations();
      }
    } else {
      $('#selectOpenSaleLocation').prop('required', false);
    }

    // Narration requirements
    const $noticeBox = $('#narrationNoticeBox');
    const $status = $('#lblNarrationReqStatus');
    if (reason === 'Internal Dispatch') {
      $status.html('<span class="text-danger font-weight-bold">(Mandatory)</span>');
      $noticeBox.show().css({ background: '#eff6ff', border: '1px solid #bfdbfe' });
      $('#narrationNoticeIcon').attr('class', 'fa fa-info-circle text-primary mt-0.5');
      $('#narrationNoticeTitle').text('Internal Dispatch Narration Required:').css('color', '#1e40af');
      $('#narrationNoticeText').text('Please state the destination polyhouse/bed and reason for moving plants.').css('color', '#1e3a8a');
    } else if (reason === 'Final Dispatch') {
      $status.html('<span class="text-danger font-weight-bold">(Mandatory)</span>');
      $noticeBox.show().css({ background: '#fef2f2', border: '1px solid #fecaca' });
      $('#narrationNoticeIcon').attr('class', 'fa fa-truck text-danger mt-0.5');
      $('#narrationNoticeTitle').text('Final Dispatch Narration Required:').css('color', '#991b1b');
      $('#narrationNoticeText').text('Please write Client Full Details, Order No., and Payment Clearance details.').css('color', '#7f1d1d');
    } else if (reason === 'Move for Grafting') {
      $status.text('(Optional)');
      $noticeBox.show().css({ background: '#faf5ff', border: '1px solid #c084fc' });
      $('#narrationNoticeIcon').attr('class', 'fa fa-cut mt-0.5').css('color', '#9333ea');
      $('#narrationNoticeTitle').text('Move for Grafting:').css('color', '#7e22ce');
      $('#narrationNoticeText').text('Selected trays will be staged for the Grafting Unit / Execution Module.').css('color', '#6b21a8');
    } else if (reason === 'Empty After Grafting') {
      $status.text('(Optional)');
      $noticeBox.show().css({ background: '#fef2f2', border: '1px solid #fca5a5' });
      $('#narrationNoticeIcon').attr('class', 'fa fa-ban text-danger mt-0.5');
      $('#narrationNoticeTitle').text('Empty After Grafting:').css('color', '#991b1b');
      $('#narrationNoticeText').text('Selected trays will be marked as Empty After Grafting in plant inventory records.').css('color', '#7f1d1d');
    } else if (reason === 'Add Trays') {
      $status.text('(Optional)');
      $noticeBox.show().css({ background: '#f0fdf4', border: '1px solid #86efac' });
      $('#narrationNoticeIcon').attr('class', 'fa fa-plus-circle text-success mt-0.5');
      $('#narrationNoticeTitle').text('Add Trays (Admin Only):').css('color', '#166534');
      $('#narrationNoticeText').text('System will increment Stock Order quantity and insert new tray rows with barcodes.').css('color', '#14532d');
    } else if (reason === 'Removed') {
      $status.text('(Optional)');
      $noticeBox.show().css({ background: '#fef2f2', border: '1px solid #fca5a5' });
      $('#narrationNoticeIcon').attr('class', 'fa fa-trash-alt text-danger mt-0.5');
      $('#narrationNoticeTitle').text('Removed (Admin Only):').css('color', '#991b1b');
      $('#narrationNoticeText').text('Selected trays will be permanently removed, stock order quantity deducted, and ready stock deleted.').css('color', '#7f1d1d');
    } else {
      $status.text('(Optional)');
      $noticeBox.hide();
    }
  };

  window.handlePolyhouseChange = function () {
    const phId = $('#selectTargetPolyhouse').val();
    const $bSelect = $('#selectTargetPolyhouseBed');
    $bSelect.html('<option value="">-- Loading Beds... --</option>');

    if (!phId) {
      $bSelect.html('<option value="">-- Select Destination Bed --</option>');
      return;
    }

    $.post('modal/verifiedtraymovement_modal.php?action=get_polyhouse_beds', { polyhouseid: phId }, function (res) {
      if (res.status === 'success' && res.beds && res.beds.length) {
        let opts = '<option value="">-- Select Destination Bed --</option>';
        res.beds.forEach(bed => {
          opts += `<option value="${esc(bed)}">${esc(bed)}</option>`;
        });
        $bSelect.html(opts);
      } else {
        $bSelect.html('<option value="">-- No beds configured --</option>');
      }
    }, 'json').fail(function () {
      $bSelect.html('<option value="">-- Error loading beds --</option>');
    });
  };

  // 7. Execute Mark as Ready to Sell
  window.triggerMarkReadyToSale = function () {
    if (scannedTraysList.length === 0 || !currentActiveBatch) {
      Swal.fire('No Trays', 'Please scan at least 1 tray first.', 'info');
      return;
    }

    const b = currentActiveBatch;
    const totalCount = scannedTraysList.length;
    const checkedTrays = scannedTraysList.filter(t => t.is_checked === true);

    // Requirement: All active trays must be checked to mark as Ready to Sell
    if (checkedTrays.length < totalCount) {
      Swal.fire({
        icon: 'warning',
        title: 'Select All Active Trays First',
        html: `Marking as <b>Ready to Sell</b> requires all active trays to be selected.<br><br>Currently only <b>${checkedTrays.length} of ${totalCount}</b> active trays are checked.<br><br>Please select all active trays first to update.`,
        confirmButtonColor: '#059669',
        confirmButtonText: '<i class="fa fa-check-double mr-1"></i> Select All Active Trays',
        showCancelButton: true,
        cancelButtonColor: '#64748b',
        cancelButtonText: 'Cancel'
      }).then((res) => {
        if (res.isConfirmed) {
          toggleSelectAllTrays(true);
        }
      });
      return;
    }

    const countToMark = checkedTrays.length;
    const promptMsg = `Mark all <b>${countToMark} active tray(s)</b> from Bed <b>${esc(b.bed_no)}</b> as <b>Ready to Sale</b>?`;

    Swal.fire({
      title: 'Mark Ready to Sell?',
      html: promptMsg,
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#059669',
      cancelButtonColor: '#64748b',
      confirmButtonText: '<i class="fa fa-check mr-1"></i> Yes, Mark Ready to Sell'
    }).then((res) => {
      if (!res.isConfirmed) return;

      const $btn = $('#btnExecuteRts');
      const origText = $btn.html();
      $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin mr-1"></i> Marking...');

      const postData = {
        mode: 'scanned_only',
        bed_no: b.bed_no,
        locationid: b.locationid,
        polyhouseid: b.polyhouseid,
        stockorder_no: b.stockorder_no,
        psono: b.psono,
        productid: b.productid,
        trays_to_mark: countToMark,
        selected_barcodes: checkedTrays.map(t => t.barcode),
        selected_plant_ids: checkedTrays.map(t => t.polyhouseplantid)
      };

      $.post('modal/verifiedtraymovement_modal.php?action=mark_ready_to_sale', postData, function (response) {
        $btn.prop('disabled', false).html(origText);

        if (response.status === 'success') {
          // Update status in local scanned collection to Ready to Sale
          scannedTraysList.forEach(t => {
            t.status = 'Readytosale';
            t.is_ready_to_sale = true;
          });
          if (b) {
            b.status = 'Readytosale';
            b.is_ready_to_sale = true;
            b.polyhouse_trays = 0;
            b.ready_to_sale_trays = (parseInt(b.ready_to_sale_trays, 10) || 0) + countToMark;
          }

          renderScannedGrid();
          updateMovementReasonDropdown();

          Swal.fire({
            icon: 'success',
            title: 'Trays Marked Ready to Sell!',
            html: `<b>${esc(response.message || 'Trays successfully marked as Ready to Sale.')}</b>`,
            confirmButtonColor: '#059669',
            confirmButtonText: '<i class="fa fa-check mr-1"></i> OK'
          });
        } else if (response.status === 'info' || (response.message && response.message.toLowerCase().includes('already mark'))) {
          Swal.fire({
            icon: 'info',
            title: 'Already Marked',
            text: response.message || 'Trays already marked as Ready to Sale.',
            confirmButtonColor: '#059669'
          });
        } else {
          Swal.fire({
            icon: 'warning',
            title: 'Cannot Mark Ready to Sell',
            html: response.message || 'Failed to mark trays.',
            confirmButtonColor: '#f59e0b'
          });
        }
      }, 'json').fail(function () {
        $btn.prop('disabled', false).html(origText);
        Swal.fire('Server Error', 'Failed to communicate with server.', 'error');
      });
    });
  };

  // 8. Execute Plant Movement Form
  window.submitMovement = function () {
    if (scannedTraysList.length === 0 || !currentActiveBatch) {
      Swal.fire('No Trays', 'Please scan at least 1 tray first.', 'info');
      return;
    }

    const b = currentActiveBatch;
    const reason = $('#selectMovementReason').val();
    const narration = $.trim($('#inputCustomNarration').val() || '');
    const checkedTrays = scannedTraysList.filter(t => t.is_checked === true);

    if (!reason) {
      Swal.fire('Validation', 'Please select why you are moving the entered trays.', 'warning');
      $('#selectMovementReason').focus();
      return;
    }

    if (currentScopeMode === 'scanned_only' && checkedTrays.length === 0) {
      Swal.fire({
        icon: 'warning',
        title: 'No Trays Checked',
        html: 'Please check at least 1 tray with checkbox in <b>"Scanned Trays Grid"</b> to execute movement.',
        confirmButtonColor: '#4f46e5'
      });
      return;
    }

    if (reason === 'Final Dispatch') {
      const targetTrays = (currentScopeMode === 'scanned_only') ? checkedTrays : scannedTraysList;
      const unready = targetTrays.filter(t => (t.status || '').toLowerCase() !== 'readytosale' && !t.is_ready_to_sale);
      if (unready.length > 0) {
        Swal.fire({
          icon: 'warning',
          title: 'Mark Ready to Sell Required',
          html: `Tray <b>${esc(unready[0].barcode)}</b> is not marked as <b>Ready to Sale</b> yet.<br><br>Please click the green <b>"Mark as Ready to Sell"</b> button above before executing <b>Final Dispatch</b>.`,
          confirmButtonColor: '#059669',
          confirmButtonText: '<i class="fa fa-seedling mr-1"></i> OK, Got It'
        });
        return;
      }
    }

    let targetBedNo = '';
    let targetPolyhouseId = '';
    let targetOpenSaleLocation = '';
    let targetOpenSalePolyhouseId = '';
    let targetOpenSaleLocationId = '';

    if (reason === 'Bed Shifting') {
      targetBedNo = $('#selectTargetBed').val();
      if (!targetBedNo) {
        Swal.fire('Validation', 'Please select destination Bed No in this polyhouse.', 'warning');
        $('#selectTargetBed').focus();
        return;
      }
      if (targetBedNo.toLowerCase() === (b.bed_no || '').toLowerCase()) {
        Swal.fire('Validation', 'Destination Bed cannot be the same as current Bed.', 'warning');
        return;
      }
    } else if (reason === 'Polyhouse Shifting' || reason === 'Hardening') {
      targetPolyhouseId = $('#selectTargetPolyhouse').val();
      targetBedNo = $('#selectTargetPolyhouseBed').val();
      if (!targetPolyhouseId) {
        Swal.fire('Validation', 'Please select destination Polyhouse' + (reason === 'Hardening' ? ' for Hardening.' : '.'), 'warning');
        $('#selectTargetPolyhouse').focus();
        return;
      }
      if (!targetBedNo) {
        Swal.fire('Validation', 'Please select destination Bed in target polyhouse' + (reason === 'Hardening' ? ' for Hardening.' : '.'), 'warning');
        $('#selectTargetPolyhouseBed').focus();
        return;
      }
    } else if (reason === 'Move for Opensale') {
      const $opt = $('#selectOpenSaleLocation option:selected');
      targetOpenSaleLocation = $('#selectOpenSaleLocation').val();
      targetOpenSalePolyhouseId = $opt.attr('data-polyhouseid') || $opt.data('polyhouseid') || '';
      targetOpenSaleLocationId = $opt.attr('data-locationid') || $opt.data('locationid') || '';

      if (!targetOpenSaleLocation) {
        Swal.fire('Validation', 'Please select destination Open Sale Location.', 'warning');
        $('#selectOpenSaleLocation').focus();
        return;
      }
    }

    if ((reason === 'Internal Dispatch' || reason === 'Final Dispatch') && !narration) {
      Swal.fire('Narration Required', `Please enter required narration for ${reason}.`, 'warning');
      $('#inputCustomNarration').focus();
      return;
    }

    let countToMove = checkedTrays.length;
    let confirmScopeDesc = `<b>${countToMove} checked tray${countToMove === 1 ? '' : 's'}</b>`;

    let confirmText = `Move ${confirmScopeDesc} to <b>"${esc(reason)}"</b>?`;
    if (reason === 'Bed Shifting') {
      confirmText = `Shift ${confirmScopeDesc} from Bed <b>${esc(b.bed_no)}</b> to <b>${esc(targetBedNo)}</b> in the same polyhouse?`;
    } else if (reason === 'Polyhouse Shifting') {
      const phText = $('#selectTargetPolyhouse option:selected').text();
      confirmText = `Shift ${confirmScopeDesc} from Bed <b>${esc(b.bed_no)}</b> to <b>${esc(phText)} (${esc(targetBedNo)})</b>?`;
    } else if (reason === 'Hardening') {
      const phText = $('#selectTargetPolyhouse option:selected').text();
      confirmText = `Move ${confirmScopeDesc} for Hardening to <b>${esc(phText)} (${esc(targetBedNo)})</b>?`;
    } else if (reason === 'Move for Opensale') {
      confirmText = `Move ${confirmScopeDesc} to Open Sale: <b>${esc(targetOpenSaleLocation)}</b>?`;
    }

    Swal.fire({
      title: 'Confirm Plant Movement?',
      html: confirmText,
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#4f46e5',
      cancelButtonColor: '#64748b',
      confirmButtonText: '<i class="fa fa-truck-moving mr-1"></i> Yes, Execute Movement'
    }).then((res) => {
      if (!res.isConfirmed) return;

      const $btn = $('#btnSubmitMovement, #btnSubmitMovementMobile');
      const origBtn = $('#btnSubmitMovement').html();
      const origBtnMobile = $('#btnSubmitMovementMobile').html();
      $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin mr-1"></i> Processing Movement...');

      const postData = {
        mode: 'scanned_only',
        bed_no: b.bed_no,
        locationid: b.locationid,
        polyhouseid: b.polyhouseid,
        stockorder_no: b.stockorder_no,
        productid: b.productid,
        stock_status: b.status,
        trays_to_move: countToMove,
        movement_reason: reason,
        target_bed_no: targetBedNo,
        target_polyhouseid: targetPolyhouseId,
        open_sale_location: targetOpenSaleLocation,
        target_opensale_polyhouseid: targetOpenSalePolyhouseId,
        target_opensale_locationid: targetOpenSaleLocationId,
        custom_narration: narration,
        selected_barcodes: checkedTrays.map(t => t.barcode),
        selected_plant_ids: checkedTrays.map(t => t.polyhouseplantid)
      };

      $.post('modal/verifiedtraymovement_modal.php?action=move_trays', postData, function (response) {
        $('#btnSubmitMovement').prop('disabled', false).html(origBtn);
        $('#btnSubmitMovementMobile').prop('disabled', false).html(origBtnMobile);

        if (response.status === 'success') {
          // Record all moved trays to history registry
          const movedTrays = (currentScopeMode === 'scanned_only')
            ? [...checkedTrays]
            : (scannedTraysList.length ? [...scannedTraysList] : [{
              barcode: b.barcode,
              stockorder_no: b.stockorder_no,
              productname: b.productname,
              variety: b.variety,
              bed_no: b.bed_no
            }]);

          recordMovedTrays(movedTrays, {
            movement_reason: reason,
            from_bed: b.bed_no,
            target_bed_no: targetBedNo || response.bed_no || b.bed_no,
            moved_by: response.moved_by || 'Admin',
            moved_at: response.moved_at || '',
            narration: narration || response.narration || '',
            stockorder_no: b.stockorder_no,
            productname: b.productname,
            variety: b.variety
          });

          if (reason === 'Move for Opensale') {
            Swal.fire({
              icon: 'success',
              title: 'Moved to Open Sale!',
              html: `${esc(response.message)}<br><br><small class="text-muted">Trays are now live in Open Sale inventory.</small>`,
              showCancelButton: true,
              confirmButtonColor: '#10b981',
              cancelButtonColor: '#64748b',
              confirmButtonText: '<i class="fa fa-shopping-bag mr-1"></i> Go to Open Sale Booking',
              cancelButtonText: 'Stay on Scanner'
            }).then(r => {
              if (r.isConfirmed) {
                window.location.href = '../../opensale_booking/index.php';
              }
            });
          } else {
            Swal.fire({
              icon: 'success',
              title: 'Movement Recorded!',
              text: response.message,
              timer: 2500,
              showConfirmButton: false
            });
          }

          // Reset form fields and clear moved trays
          $('#inputCustomNarration').val('');
          $('#selectMovementReason').val('').trigger('change');
          clearAllScanned(false);
          focusBarcodeInput();
        } else {
          Swal.fire('Error', response.message || 'Failed to execute movement.', 'error');
        }
      }, 'json').fail(function () {
        $('#btnSubmitMovement').prop('disabled', false).html(origBtn);
        $('#btnSubmitMovementMobile').prop('disabled', false).html(origBtnMobile);
        Swal.fire('Server Error', 'Failed to communicate with server.', 'error');
      });
    });
  };

  function esc(s) {
    if (s == null) return '';
    return $('<div>').text(s).html();
  }

  // Live Grid Search Event Bindings
  $('#inputGridSearch').on('input', function () {
    filterGridTrays();
  });

  $('#inputGridSearch').on('keydown', function (e) {
    if (e.key === 'Enter') {
      e.preventDefault();
      const rawVal = ($(this).val() || '').trim();
      const query = rawVal.toUpperCase();
      if (!query) return;

      // Find exact or partial matching barcode
      let matchIdx = scannedTraysList.findIndex(t => (t.barcode || '').trim().toUpperCase() === query);
      if (matchIdx === -1) {
        matchIdx = scannedTraysList.findIndex(t => (t.barcode || '').trim().toUpperCase().includes(query));
      }

      if (matchIdx !== -1) {
        scannedTraysList[matchIdx].is_checked = true;
        updateCheckedCountsUI();
        highlightRow(matchIdx);
        showToastInfo(`Tray "${scannedTraysList[matchIdx].barcode}" selected!`);
      } else {
        showToastInfo(`No matching tray barcode found for "${rawVal}"`);
      }
    } else if (e.key === 'Escape') {
      clearGridSearch();
    }
  });

  // Initialize movement reason dropdown
  updateMovementReasonDropdown();

  // Initial focus: only auto-focus on desktop (>= 992px) to prevent mobile browser viewport jump & keyboard hiding header
  if (window.innerWidth >= 992) {
    const bInp = document.getElementById('inputBarcodeQuery');
    if (bInp) bInp.focus({ preventScroll: true });
  }
});
