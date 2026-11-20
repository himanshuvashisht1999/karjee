/*
 * latlong_handler.js
 * Utility: Split "lat,long" and fetch address details.
 * Uses an Iframe to avoid SyntaxErrors and script conflicts.
 */

(function($) {
    'use strict';

    function fetchAddress(lat, lng, clientid) {
        var $container = $('#appenddeliveryaddressdiv');
        if (!$container.length) return;

        lat = (lat && !isNaN(lat)) ? lat : '';
        lng = (lng && !isNaN(lng)) ? lng : '';
        clientid = clientid || $('#clientname').val() || $('#clientid').val() || '';

        var url = 'locatioinaddressget_api.php?lat=' + encodeURIComponent(lat) + 
                  '&lon=' + encodeURIComponent(lng) + 
                  '&clid=' + encodeURIComponent(clientid) + '&bindbtnid=latlonginput'; 

        // If already loading this exact URL, stop
        var currentIframe = $container.find('iframe');
        if (currentIframe.length && currentIframe.attr('src') === url) return;

        // Show loading state
        $container.html('<div class="text-center py-5" style="min-height:400px; display:flex; flex-direction:column; justify-content:center; align-items:center;"><div class="spinner-border text-primary" role="status"></div><p class="mt-3 text-muted fw-bold">Loading location & address tool...</p></div>');

        var iframeHtml = '<iframe src="' + url + '" id="location-iframe" style="width:100%; height:640px; border:none; border-radius:12px; overflow:hidden;" scrolling="no"></iframe>';
        
        setTimeout(function() {
            $container.html(iframeHtml);
        }, 150);
    }

    function handleIndividualInputs() {
        var lat = $('#latinput').val();
        var lng = $('#longinput').val();
        var clientid = $('#clientname').val() || $('#clientid').val();
        fetchAddress(lat, lng, clientid);
    }

    function processLatLongSplit() {
        var val = $('#latlonginput').val();
        if (!val) {
            handleIndividualInputs();
            return;
        }
        var parts = val.split(',');
        var lat = parts[0] ? parts[0].trim() : '';
        var lng = parts[1] ? parts[1].trim() : '';
        
        $('#latinput').val(lat);
        $('#longinput').val(lng);
        var clientid = $('#clientname').val() || $('#clientid').val();
        fetchAddress(lat, lng, clientid);
    }

    function initHandlers() {
        $(document).off('input blur paste', '#latlonginput').on('input blur paste', '#latlonginput', function(e) {
            if (e.type === 'paste') {
                setTimeout(processLatLongSplit, 0);
            } else {
                processLatLongSplit();
            }
        });

        $(document).off('input blur paste', '#latinput, #longinput').on('input blur paste', '#latinput, #longinput', function(e) {
            if (e.type === 'paste') {
                setTimeout(handleIndividualInputs, 0);
            } else {
                handleIndividualInputs();
            }
        });

        processLatLongSplit();
    }

    window.initLatLongSplit = initHandlers;

    $(function() {
        $(document).on('shown.bs.modal', '#adddeliveryaddressModal', function () {
            var lat = $('#latinput').val() || '';
            var lng = $('#longinput').val() || '';
            var clientid = $('#clientname').val() || $('#clientid').val() || '';
            fetchAddress(lat, lng, clientid);
            initHandlers();
        });
    });

})(jQuery);
