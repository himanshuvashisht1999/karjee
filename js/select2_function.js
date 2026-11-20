jQuery(document).delegate(".injectsrch","select2:open", function(e){
	if($(this).val()){ 
		var data= $(this).select2('data');
		var term="";
		if(data){
			term=data[0].text;
			select2_search($(this), term);
		}
	}
});

function setselect2val(controlid,fldval){

	var $newOption = $("<option selected='selected'></option>").val(fldval).text(fldval); 
	$(controlid).append($newOption).trigger('change');
 
}

function select2_search ($el, term) {
  $el.select2('open');
  var $search = $el.data('select2').dropdown.$search || $el.data('select2').selection.$search;
  $search.val(term);
}

function getData(tblname,fieldname,condition,callback) {
    $.ajax({
        url: "storefunction/getdata.php",
        type: "GET",
        dataType: "json",
		data: {tablename: tblname,fieldname: fieldname,	orderby: fieldname,	condition: condition},
        success: function(data) {
            if (Array.isArray(data)) {
                // If the data is an array, invoke the callback with the data
                callback(null, data);
            } else {
                // If the response is not an array, invoke the callback with an error
                callback("Invalid response from the server", null);
            }
        },
        error: function(xhr, status, error) {
            // If there is an AJAX error, invoke the callback with an error
            callback("AJAX Error: " + status + " - " + error, null);
        }
    });
}

function paginateselectgennew(tblname, controlid, fieldname, manualfed, condition) {
  getData(tblname,fieldname,condition,function(error, data) {
		if (!error) {
			if(data.length==1){
				paginateselectgen(tblname,controlid,fieldname,manualfed,condition);
				setselect2val(controlid,data[0].id);
			}
			else{	
				paginateselectgen(tblname,controlid,fieldname,manualfed,condition);
			}
		}
	});
}

 function paginateselectgen(tblname,controlid,fieldname,manualfed,condition){
	$(controlid).select2({	 
		tags: manualfed,
		placeholder: "Search "+fieldname,
		allowClear: true,
		ajax: {
			url: "storefunction/getdata.php",
			dataType: 'json',
			delay: 250,
			cache: false,
			data: function (params) {
				return {
					tablename: tblname,
					fieldname: fieldname,
					orderby: fieldname,
					condition: condition,
					term: params.term,
					page: params.page || 1,
				};
			},			
			processResults: function(data, params) {
				var page = params.page || 1;
				return {
					results: $.map(data, function (item) { return {id: item.col, text: item.col}}),
					pagination: {
						more: (page * 10) <= data[0].total_count
					}
				};
			},
		}
	});
}  

function paginateselect(controlid,fieldname,manualfed,condition){
	$(controlid).select2({	 
	tags: manualfed,
	placeholder: "Search "+fieldname,
	allowClear: true,
	    ajax: {
	        url: "storefunction/getdata.php",
	        dataType: 'json',
	        delay: 250,
	        cache: false,
	        data: function (params) {
	            return {
	            	tablename: 'Master',
	            	fieldname: fieldname,
	            	orderby: fieldname,
	            	condition: condition,
	              term: params.term,
	              page: params.page || 1,
	            };
	        },
	        processResults: function(data, params) {
	            var page = params.page || 1;
	            return {
	                results: $.map(data, function (item) { return {id: item.col, text: item.col}}),
	                pagination: {
	                    more: (page * 10) <= data[0].total_count
	                }
	            };
	        },              
	    }
	});
}

function paginateselectyrn(controlid,fieldname,manualfed,condition,tblname){
	$(controlid).select2({	 
	tags:  manualfed,
	placeholder: "Search "+fieldname,
	allowClear: true,
	    ajax: {
	        url: "storefunction/getdata_all.php",
	        dataType: 'json',
	        delay: 250,
	        cache: false,
	        data: function (params) {
	            return {
	            	tablename: tblname,
	            	fieldname: fieldname,
	            	orderby: fieldname,
	            	condition: condition,
	              term: params.term,
	              page: params.page || 1,
	            };
	        },
	        processResults: function(data, params) {
	            var page = params.page || 1;
	            return {
	                results: $.map(data, function (item) { return {id: item.col, text: item.col}}),
	                pagination: {
	                    more: (page * 10) <= data[0].total_count
	                }
	            };
	        },              
	    }
	});
}


function paginateprodcutionorder(controlid,fieldname,manualfed,condition,tblname){
	$(controlid).select2({	 
	tags:  manualfed,
	placeholder: "Search "+fieldname,
	allowClear: true,
	    ajax: {
	        url: "storefunction/getdata.php",
	        dataType: 'json',
	        delay: 250,
	        cache: false,
	        data: function (params) {
	            return {
	            	tablename: tblname,
	            	fieldname: fieldname,
	            	orderby: fieldname,
	            	condition: condition,
	              term: params.term,
	              page: params.page || 1,
	            };
	        },
	        processResults: function(data, params) {
	            var page = params.page || 1;
	            return {
	                results: $.map(data, function (item) { return {id: item.col, text: item.col}}),
	                pagination: {
	                    more: (page * 10) <= data[0].total_count
	                }
	            };
	        },              
	    }
	});
}

function paginateselect_report_stockyrn(controlid,fieldname,manualfed,condition,action){
	$(controlid).select2({	 
	tags: manualfed,
	placeholder: "Search "+fieldname,
	allowClear: true,
	    ajax: {
	        url: "../getdata_rpt.php?action="+action,
	        dataType: 'json',
	        delay: 250,
	        cache: false,
	        data: function (params) {
	            return {	            	 
	            	fieldname: fieldname,
	            	orderby: fieldname,
	            	condition: condition,
	              term: params.term,
	              page: params.page || 1,
	            };
	        },
	        processResults: function(data, params) {
	            var page = params.page || 1;
	            return {
	                results: $.map(data, function (item) { return {id: item.col, text: item.col}}),
	                pagination: {
	                    more: (page * 10) <= data[0].total_count
	                }
	            };
	        },              
	    }
	});
}

