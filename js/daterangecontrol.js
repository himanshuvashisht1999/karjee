$(function() {
    var start = moment();
    var end = moment();
    var enableRanges = true; 

    $('.daterange').each(function() {
        var $this = $(this); 
        $this.html(start.format('DD-MM-YYYY') + ' To ' + end.format('DD-MM-YYYY'));
        enableRanges = $this.hasClass("enableRanges")?true:false;
        var options = {
            startDate: start,
            endDate: end,
            ranges: enableRanges ? { 
                'Today': [moment(), moment()],
                'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                'This Month': [moment().startOf('month'), moment().endOf('month')],
                'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
            } : undefined 
        };

        $this.daterangepicker(options, function cb(start, end) {
            $this.html(start.format('DD-MM-YYYY') + ' To ' + end.format('DD-MM-YYYY'));
            const datestart = start.format('YYYY-MM-DD');
            const dateend = end.format('YYYY-MM-DD');
            $this.data("start", datestart);
            $this.data("end", dateend);
            $this.trigger("change");
        });
    });
});
