(function ($) {
    'use strict';
    const mobileSelector = '#mobileno, #enq_mobileno';
    $(document).on('input', mobileSelector, function () {
        this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);
    });
    $(document).on('keydown', mobileSelector + ', input[type="number"]', function (event) {
        if (event.ctrlKey || event.metaKey || event.altKey || event.key.length !== 1) return;
        const mobile = $(this).is(mobileSelector);
        if ((mobile && !/^[0-9]$/.test(event.key)) || (!mobile && /^[eE+]$/.test(event.key))) event.preventDefault();
    });
    $(document).on('paste', 'input[type="number"]', function (event) {
        const clipboard = event.originalEvent.clipboardData;
        if (clipboard && /[eE+]/.test(clipboard.getData('text'))) event.preventDefault();
    });
    function enhanceSelects() {
        if (!$.fn.select2) return;
        $('#salesOrderForm select, #adddeliveryaddressModal select, #newProductEnquiryModal select').each(function () {
            const $select = $(this), $modal = $select.closest('.modal');
            if ($select.hasClass('select2-hidden-accessible')) return;
            // These controls have their own product tags and state/district setup.
            if ($select.is('.enq-product, #enq_statename, #enq_districtname, .productname')) return;
            if ($modal.length && !$modal.hasClass('show') && !$modal.hasClass('in')) return;
            $select.removeClass('searchselect');
            $select.select2({width: '100%', dropdownParent: $modal.length ? $modal : $(document.body),
                templateResult: function (item) { return item.text; },
                templateSelection: function (item) { return item.text; }});
        });
    }
    $(function () {
        $(mobileSelector).attr({inputmode: 'numeric', maxlength: '10', minlength: '10', pattern: '[0-9]{10}'});
        let queued = false;
        const observer = new MutationObserver(function () {
            if (queued) return;
            queued = true;
            setTimeout(function () { queued = false; enhanceSelects(); }, 0);
        });
        observer.observe(document.body, {childList: true, subtree: true});
        enhanceSelects();
        $(document).on('shown.bs.modal', enhanceSelects);
    });
})(jQuery);
