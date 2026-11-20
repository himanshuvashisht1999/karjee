var baseurl = $("#hidden_rooturl").val() + "expenditure_expencessheet/";
function purposelist_subcat(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,subcategoryname,callback){
	var form_data = new FormData();
	var baseurl = $("#"+routurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);
	form_data.append("purposesubcategory", subcategoryname);
	$.ajax
    ({
      	type: "POST",
      	url: baseurl+"modal/masters_modal.php?action=purposelistwithsubcategory",
      	data: form_data,
      	contentType: false,
      	processData: false,
      	cache: false,
     		dataType:'JSON',
      	success: function(res)
      	{
      		callback(res);
	    	}
    });
}

function getallpurposesubcategorywithcategoryidlist(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,callback){
	var form_data = new FormData();
	var baseurl = $("#"+routurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);
	
	$.ajax
    ({
      	type: "POST",
      	url: baseurl+"modal/masters_modal.php?action=getpurposesubcategorylist",
      	data: form_data,
      	contentType: false,
      	processData: false,
      	cache: false,
     		dataType:'JSON',
      	success: function(res)
      	{
      		callback(res);
	    	}
    });
}

function getallpurposecategorywithcategoryidlist(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,callback){
	var form_data = new FormData();
	var baseurl = $("#"+routurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);
	$.ajax
    ({
      	type: "POST",
      	url: baseurl+"modal/masters_modal.php?action=getpurposecategorylist",
      	data: form_data,
      	contentType: false,
      	processData: false,
      	cache: false,
     		dataType:'JSON',
      	success: function(res)
      	{
      		callback(res);
	    }
    });
}

function getallcreditpurposewithprojectidlist(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,callback){
	var form_data = new FormData();
	var baseurl = $("#"+routurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);
	$.ajax
    ({
      	type: "POST",
      	url: baseurl+"modal/masters_modal.php?action=getallcreditpurposelistofproject",
      	data: form_data,
      	contentType: false,
      	processData: false,
      	cache: false,
     		dataType:'JSON',
      	success: function(res)
      	{
      		callback(res);
	    	}
    });
}

function getallcreditpurposesubcategorywithcategoryidlist(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,callback){
	var form_data = new FormData();
	var baseurl = $("#"+routurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);
	
	$.ajax
    ({
      	type: "POST",
      	url: baseurl+"modal/masters_modal.php?action=getcreditpurposesubcategorylist",
      	data: form_data,
      	contentType: false,
      	processData: false,
      	cache: false,
     		dataType:'JSON',
      	success: function(res)
      	{
      		callback(res);
	    	}
    });
}

function getallcreditpurposecategorywithcategoryidlist(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,callback){
	var form_data = new FormData();
	var baseurl = $("#"+routurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);
	$.ajax
    ({
      	type: "POST",
      	url: baseurl+"modal/masters_modal.php?action=getcreditpurposecategorylist",
      	data: form_data,
      	contentType: false,
      	processData: false,
      	cache: false,
     		dataType:'JSON',
      	success: function(res)
      	{
      		callback(res);
	    }
    });
}

function getaccountdetailsofproject_frmaster(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,loginid_eid,dropdownid,alreadyexistval="", parentdivid=null){
	var form_data = new FormData();
	// var baseurl = $("#"+baseurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);
	var loginid = $("#"+loginid_eid+"").val();
	form_data.append("loginid", loginid);
	// alert(alreadyexistval);
  $.ajax
    ({
      type: "POST",
      url: baseurl+"modal/request_modal.php?action=getauthorized_accountofproject",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
     	dataType:'JSON',
      success: function(res)
      {
      	// console.log(res);
      	$("#"+dropdownid+"").empty();
      	if(res.length>0){
            $("#"+dropdownid+"").append("<option value='' selected disabled>Select Bank Account Name</option>");
          for(var i=0; i<res.length; i++){
            $("#"+dropdownid+"").append("<option value='"+res[i].accountid+"'>"+res[i].bankname+' ['+res[i].accountno+']'+"</option>");
						if(res.length==1){
							$("#"+dropdownid+"").val(res[i].accountid).trigger("change");
							if(parentdivid){
								$("#"+parentdivid+"").hide();
							}
						}            
          }
          if(alreadyexistval){
          	$("#"+dropdownid+"").val(alreadyexistval).trigger("change");
          }
        }
      }
    });
}

function allaccountswithcallback(rooturl_eid,companyid_eid,subcompanyid_eid,projectid_eid,callback){
	var form_data = new FormData();
	var baseurl = $("#"+rooturl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);
  $.ajax
    ({
      type: "POST",
      url: baseurl+"modal/masters_modal.php?action=getaccountofonlybanks",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
     	dataType:'JSON',
      success: function(res)
      {
      	// console.log(res);
      	callback(res);
      }
    });
}

function imprestaccountdetails(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,dropdownid,imprestpaytoid){
	var form_data = new FormData();
	// var baseurl = $("#"+baseurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);
	form_data.append("imprestpaytoid", imprestpaytoid);
	$.ajax
    ({
      type: "POST",
      url: baseurl+"modal/request_modal.php?action=imprestaccountdetails",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
     	dataType:'JSON',
      success: function(res)
      {
      	// console.log(res);
      	$("#"+dropdownid+"").empty();
      	if(res.length>0){
            $("#"+dropdownid+"").append("<option value='' selected disabled>Select Bank Account Name</option>");
          for(var i=0; i<res.length; i++){
            $("#"+dropdownid+"").append("<option value='"+res[i].accountid+"'>"+res[i].accountname+' '+res[i].bankname+' '+' '+res[i].accountno+''+"</option>");
						if(res.length==1){
							$("#"+dropdownid+"").val(res[i].accountid).trigger("change");
						}            
          }
          if(imprestpaytoid){
          	$("#"+dropdownid+"").val(imprestpaytoid).trigger("change");
          }
        }
      }
    });
}
function checkopeningbalancefrombank(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,accountid_eid,datefrom_eid, dateto_edit, displaydivid){
	var form_data = new FormData();
	// var baseurl = $("#"+baseurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);
	var accountid = $("#"+accountid_eid+"").val();
	form_data.append("accountid", accountid);
	var datefrom = $("#"+datefrom_eid+"").val();
	form_data.append("datefrom", datefrom);
	var dateto = $("#"+dateto_edit+"").val();
	form_data.append("dateto", dateto);
  $.ajax
    ({
      type: "POST",
      // url: baseurl+"view/view.php?action=getauthorized_accountofproject",
      url: baseurl+"modal/accountomg_modal.php?action=checkopeningbalancefrombank",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
     	// dataType:'JSON',
      success: function(res)
      {
      	//console.log(res);
        
      }
    });
}
function request_fixeddepositpaytolist(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,dropdownid){
	var form_data = new FormData();
	// var baseurl = $("#"+baseurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);
	$.ajax
    ({
      	type: "POST",
      	url: baseurl+"modal/request_modal.php?action=paytolist_fixeddeposit",
      	data: form_data,
      	contentType: false,
      	processData: false,
      	cache: false,
     		dataType:'JSON',
      	success: function(res)
      	{
      		// console.log(res);
	        $("#"+dropdownid+"").empty();
	        $("#"+dropdownid+"").append(res);
	    }
    });
}

function request_cashpaymentpaytolist(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,dropdownid){
	var form_data = new FormData();
	// var baseurl = $("#"+baseurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);
	$.ajax
    ({
      	type: "POST",
      	url: baseurl+"modal/request_modal.php?action=paytolist_cashpayment",
      	data: form_data,
      	contentType: false,
      	processData: false,
      	cache: false,
     		dataType:'JSON',
      	success: function(res)
      	{
      		// console.log(res);
	        $("#"+dropdownid+"").empty();
	        $("#"+dropdownid+"").append(res);
	    }
    });
}

function request_supplierpaytolist(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,dropdownid){
	var form_data = new FormData();
	// var baseurl = $("#"+baseurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);
	$.ajax
    ({
      	type: "POST",
      	url: baseurl+"modal/request_modal.php?action=paytolist_supplier",
      	data: form_data,
      	contentType: false,
      	processData: false,
      	cache: false,
     		dataType:'JSON',
      	success: function(res)
      	{
      		// console.log(res);
	        $("#"+dropdownid+"").empty();
	        $("#"+dropdownid+"").append(res);
	    }
    });
}

function request_creditcardpaytolist(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,dropdownid){
	var form_data = new FormData();
	// var baseurl = $("#"+baseurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);
	$.ajax
    ({
      	type: "POST",
      	url: baseurl+"modal/request_modal.php?action=paytolist_creditcard",
      	data: form_data,
      	contentType: false,
      	processData: false,
      	cache: false,
     		dataType:'JSON',
      	success: function(res)
      	{
      		// console.log(res);
	        $("#"+dropdownid+"").empty();
	        $("#"+dropdownid+"").append(res);
	    }
    });
}

function request_loanpayto(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,subcategoryid,dropdownid){
	var form_data = new FormData();
	// var baseurl = $("#"+baseurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);
	form_data.append("subcategoryid", subcategoryid);
	$.ajax
    ({
      	type: "POST",
      	url: baseurl+"modal/request_modal.php?action=getloanpaytolist",
      	data: form_data,
      	contentType: false,
      	processData: false,
      	cache: false,
     		dataType:'JSON',
      	success: function(res)
      	{
      		// console.log(res);
		      $("#"+dropdownid+"").empty();
      		var options="";
      		if(res.length>0){
      			if(res.length>1){
      				options+="<option value='' selected disabled>Select Payto name</option>";
      			}
      			for (var i = 0; i < res.length; i++) {
      				options+="<option value='"+res[i].paytoid+"'>"+res[i].accountname+" (***"+res[i].accountno.slice(-4)+")</option>";
      			}
      		}else{
      			options+="<option value=''>Payto not available</option>";
      		}
		      $("#"+dropdownid+"").append(options);
	    }
    });
}

function request_interbankpayto(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,dropdownid){
	var form_data = new FormData();
	// var baseurl = $("#"+baseurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);
	$.ajax
    ({
      	type: "POST",
      	url: baseurl+"modal/request_modal.php?action=getinternaltransferpaytolist",
      	data: form_data,
      	contentType: false,
      	processData: false,
      	cache: false,
     		dataType:'JSON',
      	success: function(res)
      	{
      		// console.log(res);
	        $("#"+dropdownid+"").empty();
	        $("#"+dropdownid+"").append(res);
	        $('.select2').select2();
					$('.select2-selection').css('border-radius','0px');
					$('.select2-container').children().css('border-radius','0px');
					$('.select2-selection').css('height','42px');
	    }
    });
}

function request_paytolist_withhtml(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,paytodivid){
	var form_data = new FormData();
	// var baseurl = $("#"+baseurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);
	$.ajax
    ({
      	type: "POST",
      	url: baseurl+"modal/request_modal.php?action=getpaytolist_withhtml",
      	data: form_data,
      	contentType: false,
      	processData: false,
      	cache: false,
     		dataType:'JSON',
      	success: function(res)
      	{
      		// console.log(res);
	        $("#"+paytodivid+"").empty();
	        $("#"+paytodivid+"").append(res);
	        $('.select2').select2();
					$('.select2-selection').css('border-radius','0px');
					$('.select2-container').children().css('border-radius','0px');
					$('.select2-selection').css('height','42px');
	    }
    });
}

function request_invoicetype(baseurl_eid,alreadyexist,dropdownid){
	var form_data = new FormData();
	// var baseurl = $("#"+baseurl_eid+"").val();
	$.ajax
    ({
      	type: "POST",
      	url: baseurl+"modal/request_modal.php?action=getinvoicetypelist",
      	data: form_data,
      	contentType: false,
      	processData: false,
      	cache: false,
     		// dataType:'JSON',
      	success: function(res)
      	{
      		// console.log(res);
	        $("#"+dropdownid+"").empty();
			    $("#"+dropdownid+"").append(res);
			    if(alreadyexist){
			  	  $("#"+dropdownid+"").val(alreadyexist).trigger("change");
			  	}
	    }
    });
}

function request_employeelistforsalary_withhtml(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,purposecodeval,paytodivid){
	var form_data = new FormData();
	// var baseurl = $("#"+baseurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);
	form_data.append("purposecodeval", purposecodeval);
	$.ajax
    ({
      	type: "POST",
      	url: baseurl+"modal/request_modal.php?action=employeelistforsalary_withhtml",
      	data: form_data,
      	contentType: false,
      	processData: false,
      	cache: false,
     		dataType:'JSON',
      	success: function(res)
      	{
      		// console.log(res);
	        $("#"+paytodivid+"").empty();
	        $("#"+paytodivid+"").append(res);
	    }
    });
}
function request_employeelistforsalary(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,employeetype,paytodivid){
	var form_data = new FormData();
	// var baseurl = $("#"+baseurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);
	form_data.append("employeetype", employeetype);
	$.ajax
    ({
      	type: "POST",
      	url: baseurl+"modal/request_modal.php?action=employeelistforsalary",
      	data: form_data,
      	contentType: false,
      	processData: false,
      	cache: false,
     		dataType:'JSON',
      	success: function(res)
      	{
      		// console.log(res);
	        $("#"+paytodivid+"").empty();
	        $("#"+paytodivid+"").append(res);
	    }
    });
}
function request_paytofrommaster_incometax(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,dropdownid){
	var form_data = new FormData();
	// var baseurl = $("#"+baseurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);
	$.ajax({
   	type: "POST",
   	url: baseurl+"modal/request_modal.php?action=paytofrommaster_incometax",
   	data: form_data,
   	contentType: false,
   	processData: false,
   	cache: false,
  	dataType:'JSON',
   	success: function(res)
   	{
   		// console.log(res);
   		if(res.length>0){
      	$("#"+dropdownid+"").empty();
      	if(res.length>1){
          	$("#"+dropdownid+"").append("<option value='' selected>Select PayTo from master</option>");
				}
       	for (var i = 0; i < res.length; i++) {
         	$("#"+dropdownid+"").append("<option value='"+res[i].paytoid+"'>"+res[i].payto+"</option>");
         	if(res.length==1){
						$("#"+dropdownid+"").val(res[i].paytoid).trigger("change");
					} 
        }
    	}
    }
  });
}

function request_paytofrommaster(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,dropdownid,alreadyexist=""){
	var form_data = new FormData();
	// var baseurl = $("#"+baseurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);
	$.ajax({
   	type: "POST",
   	url: baseurl+"modal/request_modal.php?action=getpaytofrommaster",
   	data: form_data,
   	contentType: false,
   	processData: false,
   	cache: false,
  	dataType:'JSON',
   	success: function(res)
   	{
   		// console.log(res);
   		if(res.length>0){
      	$("#"+dropdownid+"").empty();
      	if(res.length>1){
          	$("#"+dropdownid+"").append("<option value='' selected>Select PayTo from master</option>");
				}
       	for (var i = 0; i < res.length; i++) {
         	$("#"+dropdownid+"").append("<option value='"+res[i].payto+"'>"+res[i].payto+"</option>");
         	if(res.length==1){
						$("#"+dropdownid+"").val(res[i].payto).trigger("change");
					} 
        }

       	if(alreadyexist){
       		$("#"+dropdownid+"").val(alreadyexist).trigger("change");
       	}	     
    	}
    }
  });
}

function request_paytofrommaster_withpaytoid(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,dropdownid,alreadyexist="",alreadyexistname=""){
	var form_data = new FormData();
	// var baseurl = $("#"+baseurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);

	$.ajax
    ({
      	type: "POST",
      	url: baseurl+"modal/request_modal.php?action=getpaytofrommaster",
      	data: form_data,
      	contentType: false,
      	processData: false,
      	cache: false,
     		dataType:'JSON',
      	success: function(res)
      	{
      		// console.log(res);
	        $("#"+dropdownid+"").empty();
	        if(res.length>0){
	    			if (res.length>1) 
	        	{
	            	$("#"+dropdownid+"").append("<option value='' selected>Select</option>");
	        	}
	          for (var i = 0; i < res.length; i++) {
		          $("#"+dropdownid+"").append("<option data-optionpaytoname='"+res[i].payto+"' value='"+res[i].paytoid+"'>"+res[i].payto+"</option>");
		          if(res.length==1){
								$("#"+dropdownid+"").val(res[i].paytoid).trigger("change");
							} 
	          }

	          if(alreadyexist!=""){
	          		$("#"+dropdownid+"").val(alreadyexist).trigger("change");
	          }

	          if(alreadyexistname!=""){
	          		// $("#"+dropdownid+" option").text(alreadyexistname).trigger("change");
	           $("#"+dropdownid+" option").each(function(){
	           	if($(this).text()==alreadyexistname){
	           		$("#"+dropdownid+"").val($(this).val()).trigger("change");
	           	}
	           });
	          }
	          $('.select2').select2({
		            width: '100%' // need to override the changed default
		        });
		        $('.select2-selection').css('border-radius','2px');
		        $('.select2-container').children().css('border-radius','0px');
		        $('.select2-selection').css('height','38px');
	        }
	    }
    });
}
function request_payerfrommaster_withpayertoid(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,dropdownid,alreadyexist="",alreadyexistname=""){
	var form_data = new FormData();
	//var baseurl = $("#"+baseurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);

	$.ajax
    ({
      	type: "POST",
      	url: baseurl+"modal/request_modal.php?action=getpayerfrommaster",
      	data: form_data,
      	contentType: false,
      	processData: false,
      	cache: false,
     		dataType:'JSON',
      	success: function(res)
      	{
      		 //console.log(res);
	        $("#"+dropdownid+"").empty();
	       
	        if(res.length>0){
	    			if (res.length>1) 
	        	{
	            	$("#"+dropdownid+"").append("<option value='' selected>Select payer name</option>");
	        	}
	          for (var i = 0; i < res.length; i++) {
		          $("#"+dropdownid+"").append("<option data-optionpaytoname='"+res[i].payername+"' value='"+res[i].payerid+"'>"+res[i].payername+"</option>");
		          if(res.length==1){
								$("#"+dropdownid+"").val(res[i].payerid).trigger("change");
							} 
	          }

	          if(alreadyexist!=""){
	          		$("#"+dropdownid+"").val(alreadyexist).trigger("change");
	          }

	          if(alreadyexistname!=""){
	          		// $("#"+dropdownid+" option").text(alreadyexistname).trigger("change");
	           $("#"+dropdownid+" option").each(function(){
	           	if($(this).text()==alreadyexistname){
	           		$("#"+dropdownid+"").val($(this).val()).trigger("change");
	           	}
	           });
	          }
	        }
	    }
    });
}
function request_combine_clientandagents_deposit(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,dropdownid,alreadyexist="",alreadyexistname=""){
	var form_data = new FormData();
	 var baseurl = $("#"+baseurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);
	$.ajax
    ({
      	type: "POST",
      	url: baseurl+"modal/request_modal.php?action=getcombine_agentsandclient_deposit",
      	data: form_data,
      	contentType: false,
      	processData: false,
      	cache: false,
     		dataType:'JSON',
      	success: function(res)
      	{
      		// console.log(res);
	        $("#"+dropdownid+"").empty();
	        if(res.length>0){
	    			if (res.length>1) 
	        	{
	            	$("#"+dropdownid+"").append("<option value='' selected>Select Paid By</option>");
	        	}
	          for (var i = 0; i < res.length; i++) { 
		          $("#"+dropdownid+"").append("<option data-optionpaytoname='"+res[i].paytoid+"' data-clienttype='"+res[i].clienttype+"' value='"+res[i].paytoid+"'>"+res[i].paytoname+"</option>");
		          if(res.length==1){
								$("#"+dropdownid+"").val(res[i].paytoid).trigger("change");
							} 
	          }

	          if(alreadyexist!=""){
	          		$("#"+dropdownid+"").val(alreadyexist).trigger("change");
	          }

	          if(alreadyexistname!=""){
	          		// $("#"+dropdownid+" option").text(alreadyexistname).trigger("change");
	           $("#"+dropdownid+" option").each(function(){
	           	if($(this).text()==alreadyexistname){
	           		$("#"+dropdownid+"").val($(this).val()).trigger("change");
	           	}
	           });
	          }
	        }
	    }
    });
}
function modeoftransferfrommaster_todropdown(baseurl_eid,alreadimprestaccountyexist,dropdownid){
	var form_data = new FormData();
	// var baseurl = $("#"+baseurl_eid+"").val();
	$.ajax
  ({
    type: "POST",
    url: baseurl+"modal/request_modal.php?action=modeoftransferlist_todropdown",
    data: form_data,
    contentType: false,
    processData: false,
    cache: false,
   	dataType:'JSON',
    success: function(res)
    {
    		console.log(res);
        $("#"+dropdownid+"").empty();
        if(res.length>0){
    			if (res.length>1) 
        	{
            	$("#"+dropdownid+"").append("<option value='' selected disabled>Select mode of transfer</option>");
        	}
          for (var i = 0; i < res.length; i++) {
	          $("#"+dropdownid+"").append("<option value='"+res[i].modeoftransfer+"'>"+res[i].modeoftransfer+"</option>");
	          if(res.length==1){
							$("#"+dropdownid+"").val(res[i].modeoftransfer).trigger("change");
						}
          }
          if(alreadimprestaccountyexist!=""){
          		$("#"+dropdownid+"").val(alreadimprestaccountyexist).trigger("change");
          }
        }
      }
  });
}

function request_imprestpaytofrommaster_todropdown(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,alreadimprestaccountyexist,dropdownid){
	var form_data = new FormData();
	// var baseurl = $("#"+baseurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);
	var loginname=$("body").data("loginname");
	form_data.append("loginname", loginname);
	var loginid=$("body").data("loginid");
	form_data.append("loginid", loginid);
	var loginroleid=$("body").data("loginroleid");
	form_data.append("loginroleid", loginroleid);
	$.ajax
  ({
    type: "POST",
    url: baseurl+"modal/request_modal.php?action=imprestpaytolist_todropdown",
    data: form_data,
    contentType: false,
    processData: false,
    cache: false,
   	dataType:'JSON',
    success: function(res)
    {
    		// console.log(res);
        $("#"+dropdownid+"").empty();
        if(res.length>0){
    			if (res.length>1) 
        	{
            	$("#"+dropdownid+"").append("<option value='' selected disabled>Imprest Payto Name</option>");
        	}
          for (var i = 0; i < res.length; i++) {
	          $("#"+dropdownid+"").append("<option value='"+res[i].imprestpaytoid+"'>"+res[i].imprestpayto+"</option>");
	          if(res.length==1){
							$("#"+dropdownid+"").val(res[i].imprestpaytoid).trigger("change");
						}
          }
          if(alreadimprestaccountyexist!=""){
          		$("#"+dropdownid+"").val(alreadimprestaccountyexist).trigger("change");
          }
        }
    }
  });
}

function imprestpaytowithloginid_todropdown(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,alreadimprestaccountyexist,dropdownid){
	var form_data = new FormData();
	// var baseurl = $("#"+baseurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);
	var loginid=$("body").data("loginid");
	form_data.append("loginid", loginid);
	$.ajax
  ({
    type: "POST",
    url: baseurl+"modal/request_modal.php?action=imprestpaytowithloginid",
    data: form_data,
    contentType: false,
    processData: false,
    cache: false,
   	dataType:'JSON',
    success: function(res)
    {
    		// console.log(res);
        $("#"+dropdownid+"").empty();
        if(res.length>0){
    			if (res.length>1) 
        	{
            	$("#"+dropdownid+"").append("<option value='' selected>Imprest Payto</option>");
        	}
          for (var i = 0; i < res.length; i++) {
	          $("#"+dropdownid+"").append("<option value='"+res[i].imprestpaytoid+"'>"+res[i].imprestpayto+"</option>");
	          if(res.length==1){
							$("#"+dropdownid+"").val(res[i].imprestpaytoid).trigger("change");
						}
          }
          if(alreadimprestaccountyexist!=""){
          		$("#"+dropdownid+"").val(alreadimprestaccountyexist).trigger("change");
          }
        }
    }
  });
}

function imprestpaytofrommaster_callback(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,callback){
	var form_data = new FormData();
	// var baseurl = $("#"+baseurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);
	var loginname=$("body").data("loginname");
	form_data.append("loginname", loginname);
	var loginid=$("body").data("loginid");
	form_data.append("loginid", loginid);
	var loginroleid=$("body").data("loginroleid");
	form_data.append("loginroleid", loginroleid);
	
	$.ajax
  ({
    type: "POST",
    url: baseurl+"modal/request_modal.php?action=imprestpaytolist_todropdown",
    data: form_data,
    contentType: false,
    processData: false,
    cache: false,
   	dataType:'JSON',
    success: function(res)
    {
    	// console.log(res);
    	callback(res);
    }
  });
}

function creditcarduser_callback(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,callback){
	var form_data = new FormData();
	// var baseurl = $("#"+baseurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);
	var loginname=$("body").data("loginname");
	form_data.append("loginname", loginname);
	var loginid=$("body").data("loginid");
	form_data.append("loginid", loginid);
	var loginroleid=$("body").data("loginroleid");
	form_data.append("loginroleid", loginroleid);
	
	$.ajax
  ({
    type: "POST",
    url: baseurl+"modal/request_modal.php?action=creditcardholderlist",
    data: form_data,
    contentType: false,
    processData: false,
    cache: false,
   	dataType:'JSON',
    success: function(res)
    {
    	// console.log(res);
    	callback(res);
    }
  });
}

function creditcarddetial_callback(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,paytoid_eid,callback){
	var form_data = new FormData();
	// var baseurl = $("#"+baseurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);
	var paytoid = $("#"+paytoid_eid+"").val();
	form_data.append("paytoid", paytoid);	
	var loginname=$("body").data("loginname");
	form_data.append("loginname", loginname);
	var loginid=$("body").data("loginid");
	form_data.append("loginid", loginid);
	var loginroleid=$("body").data("loginroleid");
	form_data.append("loginroleid", loginroleid);
	
	$.ajax
  ({
    type: "POST",
    url: baseurl+"modal/request_modal.php?action=creditcarddetial",
    data: form_data,
    contentType: false,
    processData: false,
    cache: false,
   	dataType:'JSON',
    success: function(res)
    {
    	// console.log(res);
    	callback(res);
    }
  });
}

function getdocumentforrequest_fromemailbin(baseurl_eid,emailid,callback){
	var form_data = new FormData();
	// var baseurl = $("#"+baseurl_eid+"").val();
	form_data.append('imprestpayemail', emailid);
	$.ajax({
		type: "POST",
		url: baseurl+"modal/request_modal.php?action=getImtestpaytoattach",
		data: form_data,
		contentType: false,
		processData: false,
		cache:	false,
		dataType: 'JSON',
		success: function(res)
		{
			// console.log(res);
			callback(res);
		}
	});
}

function getdatareadyforrequstwithemailid(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,emailid,callback)
{
	var form_data = new FormData();
	// var baseurl = $("#"+baseurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);
	form_data.append('imprestpayemail', emailid);
	$.ajax({
		type: "POST",
		// url: "view/request_view.php?action=getdataforImtestrequest",
		url: baseurl+"modal/request_modal.php?action=getdataforImtestrequest",
		data: form_data,
		contentType: false,
		processData: false,
		cache:	false,
		dataType: 'JSON',
		success: function(res)
		{
			// console.log(res);
			callback(res);
		}
	});
}


function checkexist_paytonamefrommaster(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,paytoname_eid,paytoviewdivid){
	var form_data = new FormData();
	// var baseurl = $("#"+baseurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);
	
	var paytoname = $("#"+paytoname_eid+"").val();
	form_data.append("paytoname", paytoname);
	
	
	$.ajax
  ({
    type: "POST",
    url: baseurl+"modal/request_modal.php?action=checkexist_paytoname",
    data: form_data,
    contentType: false,
    processData: false,
    cache: false,
   	dataType:'JSON',
    success: function(res)
    {
    		// console.log(res);
        $("#"+paytoviewdivid+"").empty();
        if(res.length>0){
    			$("#"+paytoviewdivid+"").append(res);
        }
    }
  });
}

function checkexist_payertonamefrommaster(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,paytoname_eid,paytoviewdivid){
	var form_data = new FormData();
	// var baseurl = $("#"+baseurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);
	
	var paytoname = $("#"+paytoname_eid+"").val();
	form_data.append("paytoname", paytoname);
	
	
	$.ajax
  ({
    type: "POST",
    url: baseurl+"modal/request_modal.php?action=checkexist_payertoname",
    data: form_data,
    contentType: false,
    processData: false,
    cache: false,
   	dataType:'JSON',
    success: function(res)
    {
    		// console.log(res);
        $("#"+paytoviewdivid+"").empty();
        if(res.length>0){
    			$("#"+paytoviewdivid+"").append(res);
        }
    }
  });
}
function savetodb_newpatytodetails(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,paytoname_eid,bankname_eid,accountnumber_eid,bankifsccode_eid,newpaytosavebtnid,loginname_eid){
	var form_data = new FormData();
	// var baseurl = $("#"+baseurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	let modalId = $("#"+newpaytosavebtnid+"").closest(".modal").attr("id");
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);
	var paytoname = $("#"+paytoname_eid+"").val();
	if(paytoname=="" || paytoname==null || paytoname=="undefined"){
		alertmessage('error','Payto name missing');
		return false;
	}
	form_data.append("paytoname", paytoname);
	var bankname = $("#"+bankname_eid+"").val();
	form_data.append("bankname", bankname);
	var accountnumber = $("#"+accountnumber_eid+"").val();
	form_data.append("accountnumber", accountnumber);	
	var bankifsccode = $("#"+bankifsccode_eid+"").val();
	form_data.append("bankifsccode", bankifsccode);

	var loginname = $("#"+loginname_eid+"").val();
	form_data.append("loginname", loginname);
	var requestno = $("#"+newpaytosavebtnid+"").data('requestno');
	var referenceno = $("#"+newpaytosavebtnid+"").data('referenceno');

	var oldbanknametxtid = $("#"+newpaytosavebtnid+"").data('oldbanknametxtid');
	var oldaccountnotxtid = $("#"+newpaytosavebtnid+"").data('oldaccountnotxtid');
	var oldifscodetxtid = $("#"+newpaytosavebtnid+"").data('oldifscodetxtid');

	var btnval = $("#"+newpaytosavebtnid+"").html();
	$("#"+newpaytosavebtnid+"").html('Please wait data are saving...');
	$("#"+newpaytosavebtnid+"").prop("disabled", true);
		
 	$.ajax
  ({
	  type: "POST",
    url: baseurl+"modal/request_modal.php?action=savetoodb_newpaytodetails",
    data: form_data,
    contentType: false,
    processData: false,
    cache: false,
    dataType:'JSON',
    success: function(res)
    {
        // console.log(res);
        if(res.length>0){
        	for (var i = 0; i < res.length; i++) {
        		if(res[i].status=="Success"){
        			if(modalId){
	        			//$('body').removeClass('modal-open');
	        			$("#"+modalId+"").modal("hide");
	        			$(".close").trigger("click");
        			}
		      		alertmessage('success',res[i].message);
		      		if(requestno || referenceno){
								updatepatytodetailswithref(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,bankname_eid,accountnumber_eid,bankifsccode_eid,requestno,referenceno);
							}
							if(oldbanknametxtid!="" && oldaccountnotxtid!="" && oldifscodetxtid!=""){
								$("#"+oldbanknametxtid+"").html(bankname);
								$("#"+oldaccountnotxtid+"").html(accountnumber);
								$("#"+oldifscodetxtid+"").html(bankifsccode);
							}
		      		$("#spe_paytoname").val(res[i].paytoid).trigger("change");
		      		$("#"+newpaytosavebtnid+"").html(btnval);
							$("#"+newpaytosavebtnid+"").prop("disabled", false);
							
		      	}else if(res[i].status=="alreadyupdated"){
		      		if(modalId){
		      			// $('body').removeClass('modal-open');
	        			// $("#"+modalId+"").modal("hide");
	        			$(".close").trigger("click");
        			}
		      		if(requestno || referenceno){
								updatepatytodetailswithref(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,bankname_eid,accountnumber_eid,bankifsccode_eid,requestno,referenceno);
							}
							if(oldbanknametxtid!="" && oldaccountnotxtid!="" && oldifscodetxtid!=""){
								$("#"+oldbanknametxtid+"").html(bankname);
								$("#"+oldaccountnotxtid+"").html(accountnumber);
								$("#"+oldifscodetxtid+"").html(bankifsccode);
							}
							$("#"+newpaytosavebtnid+"").html(btnval);
							$("#"+newpaytosavebtnid+"").prop("disabled", false);
						}else{
		      		alertmessage('error',res[i].message);
		      	}		
        	}
        }
      	
      }
    });
}

function savetodb_newpayertodetails(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,paytoname_eid,bankname_eid,accountnumber_eid,bankifsccode_eid,newpaytosavebtnid,loginname_eid){
	var form_data = new FormData();
	// var baseurl = $("#"+baseurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	let modalId = $("#"+newpaytosavebtnid+"").closest(".modal").attr("id");
	
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);
	var paytoname = $("#"+paytoname_eid+"").val();
	if(paytoname=="" || paytoname==null || paytoname=="undefined"){
		alertmessage('error','Payto name missing');
		return false;
	}
	form_data.append("paytoname", paytoname);
	var bankname = $("#"+bankname_eid+"").val();
	form_data.append("bankname", bankname);
	var accountnumber = $("#"+accountnumber_eid+"").val();
	form_data.append("accountnumber", accountnumber);	
	var bankifsccode = $("#"+bankifsccode_eid+"").val();
	form_data.append("bankifsccode", bankifsccode);

	var loginname = $("#"+loginname_eid+"").val();
	form_data.append("loginname", loginname);
	var requestno = $("#"+newpaytosavebtnid+"").data('requestno');
	var referenceno = $("#"+newpaytosavebtnid+"").data('referenceno');

	var oldbanknametxtid = $("#"+newpaytosavebtnid+"").data('oldbanknametxtid');
	var oldaccountnotxtid = $("#"+newpaytosavebtnid+"").data('oldaccountnotxtid');
	var oldifscodetxtid = $("#"+newpaytosavebtnid+"").data('oldifscodetxtid');

	var btnval = $("#"+newpaytosavebtnid+"").html();
	$("#"+newpaytosavebtnid+"").html('Please wait data are saving...');
	$("#"+newpaytosavebtnid+"").prop("disabled", true);
		
 	$.ajax
  ({
	  type: "POST",
    url: baseurl+"modal/request_modal.php?action=savetoodb_newpayertodetails",
    data: form_data,
    contentType: false,
    processData: false,
    cache: false,
    dataType:'JSON',
    success: function(res)
    {
        // console.log(res);
        if(res.length>0){
        	for (var i = 0; i < res.length; i++) {
        		if(res[i].status=="Success"){
        			if(modalId){
	        			$("#"+modalId+"").modal("hide");
        			}
		      		alertmessage('success',res[i].message);
		      		if(requestno || referenceno){
								updatepatytodetailswithref(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,bankname_eid,accountnumber_eid,bankifsccode_eid,requestno,referenceno);
							}
							if(oldbanknametxtid!="" && oldaccountnotxtid!="" && oldifscodetxtid!=""){
								$("#"+oldbanknametxtid+"").html(bankname);
								$("#"+oldaccountnotxtid+"").html(accountnumber);
								$("#"+oldifscodetxtid+"").html(bankifsccode);
							}
		      		$("#spe_paytoname").val(res[i].paytoid).trigger("change");
		      		$("#"+newpaytosavebtnid+"").html(btnval);
							$("#"+newpaytosavebtnid+"").prop("disabled", false);
							
		      	}else if(res[i].status=="alreadyupdated"){
		      		if(modalId){
	        			$("#"+modalId+"").modal("hide");
        			}
		      		if(requestno || referenceno){
								updatepatytodetailswithref(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,bankname_eid,accountnumber_eid,bankifsccode_eid,requestno,referenceno);
							}
							if(oldbanknametxtid!="" && oldaccountnotxtid!="" && oldifscodetxtid!=""){
								$("#"+oldbanknametxtid+"").html(bankname);
								$("#"+oldaccountnotxtid+"").html(accountnumber);
								$("#"+oldifscodetxtid+"").html(bankifsccode);
							}
							$("#"+newpaytosavebtnid+"").html(btnval);
							$("#"+newpaytosavebtnid+"").prop("disabled", false);
						}else{
		      		alertmessage('error',res[i].message);
		      	}		
        	}
        }
      	
      }
    });
}

function updatepatytodetailswithref(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,bankname_eid,accountnumber_eid,bankifsccode_eid,requestno,referenceno){
	var form_data = new FormData();
	// var baseurl = $("#"+baseurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);

	var bankname = $("#"+bankname_eid+"").val();
	form_data.append("bankname", bankname);
	var accountnumber = $("#"+accountnumber_eid+"").val();
	form_data.append("accountnumber", accountnumber);	
	var bankifsccode = $("#"+bankifsccode_eid+"").val();
	form_data.append("bankifsccode", bankifsccode);

	var bankifsccode = $("#"+bankifsccode_eid+"").val();
	form_data.append("bankifsccode", bankifsccode);
	form_data.append("requestno", requestno);
	form_data.append("referenceno", referenceno);
		
 	$.ajax
  ({
	  type: "POST",
    url: baseurl+"modal/request_modal.php?action=updatepaytobankinfo_withreference",
    data: form_data,
    contentType: false,
    processData: false,
    cache: false,
    dataType:'JSON',
    success: function(res)
    {
        // console.log(res);
        if(res.length>0){
        	for (var i = 0; i < res.length; i++) {
        		if(res[i].status=="Success"){
		      		alertmessage('success',res[i].message);
		      	}else{
		      		alertmessage('error',res[i].message);
		      	}		
        	}
        }
      }
    });
}

function savetodb_newbeneficiarydetails(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,beneficiaryname_eid,loginname_eid,savetobtnid){
	var form_data = new FormData();
	// var baseurl = $("#"+baseurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);

	var beneficiaryname = $("#"+beneficiaryname_eid+"").val();
	if(beneficiaryname=="" || beneficiaryname==null || beneficiaryname=="undefined"){
		alertmessage('error','Beneficiary name missing');
		return false;
	}
	form_data.append("beneficiaryname", beneficiaryname);
	var loginname = $("#"+loginname_eid+"").val();
	form_data.append("loginname", loginname);
	$("#"+savetobtnid+"").prop("disabled", true);
 	$.ajax
  ({
	  type: "POST",
    url: baseurl+"modal/request_modal.php?action=savetoodb_newbeneficiaryname",
    data: form_data,
    contentType: false,
    processData: false,
    cache: false,
    dataType:'JSON',
    success: function(res)
    {
        // console.log(res);
      	$("#"+savetobtnid+"").prop("disabled", false);
      	if(res.status=="Success"){
      		alertmessage('success',res.message);
      		$("#"+beneficiaryname_eid+"").val('');
      	}else{
      		alertmessage('error',res.message);
      	}
      }
    });
}
function request_beneficiaryfrommaster(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,dropdownid,alreadyexistval=null){
	var form_data = new FormData();
	// var baseurl = $("#"+baseurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);
	var selval="";
	$.ajax({
   	type: "POST",
   	url: baseurl+"modal/request_modal.php?action=beneficiarylist",
   	data: form_data,
   	contentType: false,
   	processData: false,
   	cache: false,
  	dataType:'JSON',
   	success: function(res)
   	{
   		// console.log(res);
   		$("#"+dropdownid+"").empty();
     	if(res.length>0){
     		if(res.length>1){
     			$("#"+dropdownid+"").append("<option value='' disabled selected>Select</option>");
				}
        for (var i = 0; i < res.length; i++) {
	        $("#"+dropdownid+"").append("<option value='"+res[i].beneficiaryname+"'>"+res[i].beneficiaryname+"</option>");
	        if(res.length==1){
	        	$("#"+dropdownid+"").val(res[i].beneficiaryname).trigger("change");
	        }
	   		}
	   		if(alreadyexistval){
	   			$("#"+dropdownid+"").val(alreadyexistval.trim().toUpperCase()).trigger("change");
	   		}
	   	}
	  }
  });
}


function request_clientlistfrommaster(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,dropdownid,alreadyexistval=null){
	var form_data = new FormData();
	var baseurl = $("#"+baseurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);
	var selval="";
	$.ajax({
   	type: "POST",
   	url: baseurl+"modal/request_modal.php?action=clientlist_all",
   	data: form_data,
   	contentType: false,
   	processData: false,
   	cache: false,
  		dataType:'JSON',
   	success: function(res)
   	{
   		// console.log(res);
   		$("#"+dropdownid+"").empty();
     	if(res.length>0){
     		if(res.length>1){
     			$("#"+dropdownid+"").append("<option value='' disabled selected>Select Paid On Behalf</option>");
				}			

        for (var i = 0; i < res.length; i++) {
	        $("#"+dropdownid+"").append("<option value='"+res[i].client_id+"'>"+res[i].client_name+"</option>");
	         if(res.length==1){
	        	$("#"+dropdownid+"").val(res[i].client_id).trigger("change");
	        } 
	   		}
	   		if(alreadyexistval){
	   			$("#"+dropdownid+"").val(alreadyexistval.trim()).trigger("change");
	   		}
	   	}
	  }
  });
}

function all_beneficiarylistfrommaster(baseurl_eid,dropdownid){
	var form_data = new FormData();
	// var baseurl = $("#"+baseurl_eid+"").val();
	$.ajax
    ({
      	type: "POST",
      	url: baseurl+"modal/request_modal.php?action=allbeneficiarylist",
      	data: form_data,
      	contentType: false,
      	processData: false,
      	cache: false,
     		dataType:'JSON',
      	success: function(res)
      	{
      		// console.log(res);
	        $("#"+dropdownid+"").empty();
	        if(res.length>0){
	    		if (res.length>1) 
	        	{
	            	$("#"+dropdownid+"").append("<option value='' selected>Select beneficiaryname from master</option>");
	        	}
	          	for (var i = 0; i < res.length; i++) {
		            $("#"+dropdownid+"").append("<option value='"+res[i].beneficiaryname+"'>"+res[i].beneficiaryname+"</option>");
		            if(res.length==1){
									$("#"+dropdownid+"").val(res[i].beneficiaryname).trigger("change");
								}
	          	}
	        }
	    }
    });
}
function request_purposecategoryfrommaster(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,alreadyexist_catgid,dropdownid){
	var form_data = new FormData();
	// var baseurl = $("#"+baseurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);
	$.ajax
  ({
      	type: "POST",
      	url: baseurl+"modal/request_modal.php?action=purpose_categoryfrommaster",
      	data: form_data,
      	contentType: false,
      	processData: false,
      	cache: false,
     	dataType:'JSON',
      	success: function(res)
      	{
      		// console.log(res);
	        $("#"+dropdownid+"").empty();
	        if(res.length>0){
	    			if(res.length>1) 
	        	{
	           	$("#"+dropdownid+"").append("<option value='' selected>Select purpose category</option>");
	        	}
	          for (var i = 0; i < res.length; i++) {
		          $("#"+dropdownid+"").append("<option value='"+res[i].pcategorycode+"'>"+res[i].pcategory+"</option>");
		          if(res.length==1){
		          	$("#"+dropdownid+"").val(res[i].pcategorycode).trigger("change");
		          }
	          }
	          if(alreadyexist_catgid){
	          	$("#"+dropdownid+"").val(alreadyexist_catgid).trigger("change");
	          }
	        }
	    	}
    });
}
function getunauditeddaterange(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,callback){
	var form_data = new FormData();
	var baseurl = $("#"+baseurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);

	$.ajax({
  	type: "POST",
  	url: baseurl+"modal/request_modal.php?action=unauditeddaterange",
  	data: form_data,
  	contentType: false,
  	processData: false,
  	cache: false,
 		dataType:'JSON',
  	success: function(res)
  	{
  		//console.log(res)
  		callback(res);
  	}
  });
}
function request_purposesubcategoryfrommaster(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,pcategoryidval,alreadyexistval,dropdownid,parenddivid=null)
{
	var form_data = new FormData();
	// var baseurl = $("#"+baseurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);
	form_data.append("categoryidval", pcategoryidval);
	form_data.append("shortbyvalue", alreadyexistval);
	// alert(parenddivid);
	if(parenddivid){
		$("#"+parenddivid+"").show();
	}
	// alert(pcategoryidval+"---"+alreadyexistval);
	$.ajax
    ({
      	type: "POST",
      	url: baseurl+"modal/request_modal.php?action=purpose_subcategoryfrommaster",
      	data: form_data,
      	contentType: false,
      	processData: false,
      	cache: false,
     		dataType:'JSON',
      	success: function(res)
      	{
      		// console.log(res);
	       	$("#"+dropdownid+"").empty();
	        if(res.length>0){
	    			if (res.length>1) 
	        	{
	            	$("#"+dropdownid+"").append("<option value='' selected>Select purpose subcategory</option>");
	        	}
	          for (var i = 0; i < res.length; i++) {
		          $("#"+dropdownid+"").append("<option data-drcategoryid='"+res[i].pcategorycode+"'  data-drcategoryname='"+res[i].pcategory+"' value='"+res[i].psub_categoryid+"'>"+res[i].psub_category+"</option>");
		          if(res.length==1){
		          	$("#"+dropdownid+"").val(res[i].psub_categoryid).trigger("change");
		          	if(parenddivid){
		          		$("#"+parenddivid+"").hide();
		          	}
		          }
	          }
	          if(alreadyexistval=="" || alreadyexistval==null || alreadyexistval=='null' || alreadyexistval=='undefined' ){	          	
	          }else{
	          	let valueToCheck = String(alreadyexistval); // Ensure it's a string
	          	if (valueToCheck.includes("salar")) {
					        $("#" + dropdownid+"").find('option').each(function() {
					            if ($(this).text().includes("Salar") || $(this).text().includes("salar")) {
					                $(this).prop('selected', true);
					                $("#" + dropdownid).trigger('change');
					                return false;
					            }
					        });
					    } else {
					    	// alert(alreadyexistval);
					    	$("#" + dropdownid+"").val(alreadyexistval).trigger("change");
					    }
	          }
	        }
	    }
    });
}

function request_purposesubcategoryfrommaster_credit(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,pcategoryidval,alreadyexistval,dropdownid)
{
	var form_data = new FormData();
	// var baseurl = $("#"+baseurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);
	form_data.append("categoryidval", pcategoryidval);
	$.ajax
  ({
    	type: "POST",
    	url: baseurl+"modal/request_modal.php?action=purpose_subcategoryfrommaster_credit",
    	data: form_data,
    	contentType: false,
    	processData: false,
    	cache: false,
   		dataType:'JSON',
    	success: function(res)
    	{
    		// console.log(res);
       	$("#"+dropdownid+"").empty();
        if(res.length>0){
    			if (res.length>1) 
        	{
            	$("#"+dropdownid+"").append("<option value='' selected>Select purpose subcategory</option>");
        	}
          for (var i = 0; i < res.length; i++) {
	          $("#"+dropdownid+"").append("<option value='"+res[i].psub_categoryid+"'>"+res[i].psub_category+"</option>");
	          if(res.length==1){
	          	$("#"+dropdownid+"").val(res[i].psub_categoryid).trigger("change");
	          }
          }
          if(alreadyexistval=="" || alreadyexistval==null || alreadyexistval=='null' || alreadyexistval=='undefined' ){
          	
          }else{
          	let valueToCheck = String(alreadyexistval); // Ensure it's a string
          	if (valueToCheck.includes("salary")) {
				        $("#" + dropdownid+"").find('option').each(function() {
				            if ($(this).text().includes("Salary") || $(this).text().includes("salary")) {
				                $(this).prop('selected', true);
				                $("#" + dropdownid).trigger('change');
				                return false;
				            }
				        });
				    } else {
				    	$("#" + dropdownid+"").val(alreadyexistval).trigger("change");
				    }
          }
        }
    }
  });
}

function request_purposecodefrommaster(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,pcategoryidval,psubcategoryidval,alreadyexistval,dropdownid,parentdivid=null){
	var form_data = new FormData();
	// var baseurl = $("#"+baseurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);
	form_data.append("categoryidval", pcategoryidval);
	form_data.append("subcategoryidval", psubcategoryidval);
	if(parentdivid){
		$("#"+parentdivid+"").show();
	}
	// alert(psubcategoryidval);
	$.ajax
    ({
      	type: "POST",
      	url: baseurl+"modal/request_modal.php?action=purposecodefrommaster",
      	data: form_data,
      	contentType: false,
      	processData: false,
      	cache: false,
     		dataType:'JSON',
      	success: function(res)
      	{
      		// console.log(res);
      		$("#"+dropdownid+"").empty();
	        if(res.length>0){
	    			if (res.length>1) 
	        	{
	            	$("#"+dropdownid+"").append("<option value='' selected>Select purpose name</option>");
	        	}
	          for (var i = 0; i < res.length; i++) {
		          $("#"+dropdownid+"").append("<option value='"+res[i].purposecode+"'>"+res[i].provided_code+"-"+res[i].purpose+"</option>");
		          if(res.length==1){
		          	$("#"+dropdownid+"").val(res[i].purposecode).trigger("change");
		          	if(parentdivid){
									$("#"+parentdivid+"").hide();
								}
		          }
	          }
	          if(alreadyexistval){
	          	$("#"+dropdownid+"").val(alreadyexistval).trigger("change");
	          }
	        }
	    	}
    });
}

function request_purposecodefrommaster_withclientdata(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,pcategoryidval,psubcategoryidval,alreadyexistval,dropdownid,parentdivid=null){
	var form_data = new FormData();
	// var baseurl = $("#"+baseurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);
	form_data.append("categoryidval", pcategoryidval);
	form_data.append("subcategoryidval", psubcategoryidval);
	if(parentdivid){
		$("#"+parentdivid+"").show();
	}
	// alert(psubcategoryidval);
	$.ajax
    ({
      	type: "POST",
      	url: baseurl+"modal/request_modal.php?action=purposecodefrommaster_withclientdata",
      	data: form_data,
      	contentType: false,
      	processData: false,
      	cache: false,
     		dataType:'JSON',
      	success: function(res)
      	{
      		// console.log(res);
      		$("#"+dropdownid+"").empty();
	        if(res.length>0){
	    			if (res.length>1) 
	        	{
	            	$("#"+dropdownid+"").append("<option value='' selected>Select purpose name</option>");
	        	}
	          for (var i = 0; i < res.length; i++) {
		          $("#"+dropdownid+"").append("<option value='"+res[i].purposecode+"'>"+res[i].provided_code+"-"+res[i].purpose+"</option>");
		          if(res.length==1){
		          	$("#"+dropdownid+"").val(res[i].purposecode).trigger("change");
		          	if(parentdivid){
									$("#"+parentdivid+"").hide();
								}
		          }
	          }
	          if(alreadyexistval){
	          	$("#"+dropdownid+"").val(alreadyexistval).trigger("change");
	          }
	        }
	    	}
    });
}



function addotherpurposedetailshtml(purposecode,otherpurposehtmlviewid,otherinputid){
	 $othephtml='<div class="row mt-2"><div class="col-md-12"><label class="lbl-text lbl-full">Purpose name<label><input type="checkbox" class="form-control" id="'+otherinputid+'" /> </div></div>';
	 return $othephtml;
}


function request_purposecodefrommaster_markfromnote4(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,pcategoryidval,psubcategoryidval,alreadyexistval,dropdownid){
	var form_data = new FormData();
	// var baseurl = $("#"+baseurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);
	form_data.append("categoryidval", pcategoryidval);
	form_data.append("subcategoryidval", psubcategoryidval);
	
	$.ajax
    ({
      	type: "POST",
      	url: baseurl+"modal/request_modal.php?action=purposecodefrommaster_markfromnote4",
      	data: form_data,
      	contentType: false,
      	processData: false,
      	cache: false,
     		dataType:'JSON',
      	success: function(res)
      	{
      		// console.log(res);
      		$("#"+dropdownid+"").empty();
	        if(res.length>0){
	    			if (res.length>1) 
	        	{
	            	$("#"+dropdownid+"").append("<option value='' selected>Select purpose name</option>");
	        	}
	          for (var i = 0; i < res.length; i++) {
		          $("#"+dropdownid+"").append("<option value='"+res[i].purposecode+"'>"+res[i].purpose+"</option>");
		          if(res.length==1){
		          	$("#"+dropdownid+"").val(res[i].purposecode).trigger("change");
		          }
	          }
	          $("#"+dropdownid+"").append("<option value='01010101'>Other</option>");
	          if(alreadyexistval){
	          	$("#"+dropdownid+"").val(alreadyexistval).trigger("change");
	          }
	        }
	    	}
    });
}

function DigitizeVoiceInstructionintoBookEntry(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,subprojectid_eid,accountid_eid,paytoname_eid,paytoamount_eid,modeoftransfer_eid,transactionno_eid,transactiondate_eid,purposecode_eid,description_eid,savetobtnid){
	var form_data = new FormData();
	// var baseurl = $("#"+baseurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);

	var subprojectid = $("#"+subprojectid_eid+"").val();
	form_data.append("subprojectid", subprojectid);

	var voiceinstid = $("#"+savetobtnid+"").data("voiceinstid");
	form_data.append("voiceinstid", voiceinstid);

	var requesttypename = $("#"+savetobtnid+"").data("requesttype");
	form_data.append("requesttypename", requesttypename);

	var accountid = $("#"+accountid_eid+"").val();	
	form_data.append("accountid", accountid);

	var paytoid = $("#"+paytoname_eid+"").val();
	if(paytoid=="" || paytoid==null || paytoid=="undefined"){
		alertmessage('error','Payto name missing');
		return false;
	}
	form_data.append("paytoid", paytoid);

	var paytoamount = $("#"+paytoamount_eid+"").val();
	if(paytoamount=="" || paytoamount==null || paytoamount=="undefined"){
		alertmessage('error','Amount is missing');
		return false;
	}
	form_data.append("paytoamount", paytoamount);

	var modeoftransfer = $("#"+modeoftransfer_eid+"").val();
	form_data.append("modeoftransfer", modeoftransfer);

	var transactionno = $("#"+transactionno_eid+"").val();
	form_data.append("transactionno", transactionno);

	var transactiondate = $("#"+transactiondate_eid+"").val();
	form_data.append("transactiondate", transactiondate);
		
	var purposecode = $("#"+purposecode_eid+"").val();
	if(purposecode==null || purposecode=="null" || purposecode=="" || purposecode=="undefined"){
			alertmessage('error','Purpose is missing, choose the purpose');
			$("#"+purposecode_eid+"").focus();
			return false;
	}
	form_data.append("purposecode", purposecode);
	
	var purposename = $("#"+purposecode_eid+" selected:option").text();
	form_data.append("purposename", purposename);

	var description = $("#"+description_eid+"").val();
	if(description==null || description=="null" || description=="" || description=="undefined"){
		alertmessage('error','Description is missing, Atleast 20-25 character description is required');
		$("#"+description_eid+"").focus();
		return false;
	}
	form_data.append("description", description);

	var loginname = $("#hidden_loginname").val();
	form_data.append("loginname", loginname);
	if(requesttypename=="Post-Payment" && voiceinstid!=""){
		if(accountid=="" || accountid==null || accountid=="undefined"){
			alertmessage('error','Please choose the from account name');
			return false;
		}else if(modeoftransfer==null || modeoftransfer=="null" || modeoftransfer=="" || modeoftransfer=="undefined"){
			alertmessage('error','Mode of transfer is required');
			return false;
		}else if(transactionno==null || transactionno=="null" || transactionno=="" || transactionno=="undefined"){
			alertmessage('error','Transaction no is missing');
			return false;
		}else if(transactiondate==null || transactiondate=="null" || transactiondate=="" || transactiondate=="undefined"){
			alertmessage('error','Transaction date is missing');
			return false;
		}
	}
	
	var btnval=$("#"+savetobtnid+"").val();
	$("#"+savetobtnid+"").prop("disabled", true);
	$("#"+savetobtnid+"").val("Please wait data saving");
  $.ajax
  ({
      type: "POST",
      url: baseurl+"modal/request_modal.php?action=DigitizeVoiceInstructionintoBookEntry",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType:'JSON',
      success: function(res)
      {
        // console.log(res);
        $("#"+savetobtnid+"").prop("disabled", false);
				$("#"+savetobtnid+"").val(btnval);
      	if(res.length>0){
      		for(var i=0; i<res.length; i++){
      			if(res[i].status=="Success"){
			      	SweetAlertmessageFadein('success',res[i].message);
	      		}else{
		      		alertmessage('error',res[i].message);
		      	}
	      	}
      	}else{
      		alertmessage('error','Your data did not save, due to technical issue');
      	}
    	}
  });
}


function savetodb_requestdataforsalary(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,subprojectid_eid,accountid_eid,paytoname_eid, paytoamount_eid, modeoftransfer_eid, transactionno_eid, transactiondate_eid,paymentreceiptfile_eid,purposecategory_name,purposesubcategory_eid,purposecode_eid,salaryyear_eid,salarymonth_eid,salarydays_eid,description_eid,loginname_eid,attached_eid,audiofile_eid,paymenttype_eidname,clicktosavebtnid,attpathfromemail_eid,attsnofromemail_eid,emailsnofromemail_eid,updatecategoriz_ename,voiceinstid_eid,paymentduedate_eid=null){
	var form_data = new FormData();
	// var baseurl = $("#"+baseurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);

	var subprojectid = $("#"+subprojectid_eid+"").val();
	if(subprojectid=="" || subprojectid==null || subprojectid=="undefined"){
		alertmessage('error','Sub-project name missing');
		$("#"+subprojectid_eid+"").focus();
		return false;
	}
	form_data.append("subprojectid", subprojectid);

	var paymentduedate = $("#"+paymentduedate_eid+"").val();
	form_data.append("paymentduedate", paymentduedate);

	var closetobook_bankdbsno = $("#htmlcontainerdiv").data("unrbankdbsno");
	form_data.append("closetobook_bankdbsno", closetobook_bankdbsno);

	var bankdescription = $("#htmlcontainerdiv").data("unrbankdescription");
	form_data.append("bankdescription", bankdescription);


	var accountid = $("#"+accountid_eid+"").val();
	form_data.append("accountid", accountid);	
	var paytoid = $("#"+paytoname_eid+"").val();
	if(paytoid=="" || paytoid==null || paytoid=="undefined"){
		alertmessage('error','Payto name missing');
		$("#"+paytoname_eid+"").focus();
		return false;
	}
	form_data.append("paytoid", paytoid);
	var paytoamount = $("#"+paytoamount_eid+"").val();
	if(paytoamount=="" || paytoamount==null || paytoamount=="undefined"){
		alertmessage('error','Salary amount is missing');
		$("#"+paytoamount_eid+"").focus();
		return false;
	}
	form_data.append("paytoamount", paytoamount);
	var modeoftransfer = $("#"+modeoftransfer_eid+"").val();
	form_data.append("modeoftransfer", modeoftransfer);
	var transactionno = $("#"+transactionno_eid+"").val();
	form_data.append("transactionno", transactionno);
	var transactiondate = $("#"+transactiondate_eid+"").val();
	form_data.append("transactiondate", transactiondate);
	
	var purposecategoryid = $("input[name='"+purposecategory_name+"']:checked").val();
	form_data.append("purposecategoryid", purposecategoryid);
	var updatecategoriz = $("input[name='"+updatecategoriz_ename+"']:checked").val();
	form_data.append("updatecategoriz", updatecategoriz);
	
	var purposesubcategoryid = $("#"+purposesubcategory_eid+"").val();
	form_data.append("purposesubcategoryid", purposesubcategoryid)
	
	var purposecode = $("#"+purposecode_eid+"").val();
	form_data.append("purposecode", purposecode);

	var purposename = $("#"+purposecode_eid+" selected:option").text();
	form_data.append("purposename", purposename);
	
	var salaryyear = $("#"+salaryyear_eid+"").val();
	form_data.append("salaryyear", salaryyear);
	
	var salarymonth = $("#"+salarymonth_eid+"").val();
	form_data.append("salarymonth", salarymonth);
	
	var salarydays = $("#"+salarydays_eid+"").val();
	if(salarydays=="" || salarydays==null || salarydays=="undefined"){
		alertmessage('error','Salary days is missing');
		$("#"+salarydays_eid+"").focus();
		return false;
	}
	form_data.append("salarydays", salarydays);
	var description = $("#"+description_eid+"").val();
	form_data.append("description", description);
	var loginname = $("#"+loginname_eid+"").val();
	form_data.append("loginname", loginname);
	var requesttypename = $("input[name='"+paymenttype_eidname+"']:checked").val();
	form_data.append("requesttypename", requesttypename);

	var attachedfile = $("#"+attached_eid+"")[0].files[0];
	form_data.append("attachedfile", attachedfile);
	
	var paymentreceipt = $("#"+paymentreceiptfile_eid+"")[0].files[0];
	form_data.append("paymentreceipt", paymentreceipt);

	var audioFile = $("#"+audiofile_eid+"").data("audioFile");
	form_data.append('audiofile', audioFile);

	var voiceinstid = $("#"+voiceinstid_eid+"").val();
	form_data.append("voiceinstid", voiceinstid);

	var attpathfromemail = $("#"+attpathfromemail_eid+"").val();
	form_data.append("attpathfromemail", attpathfromemail);
	var attsnofromemail = $("#"+attsnofromemail_eid+"").val();
	form_data.append("attsnofromemail", attsnofromemail);
	var emailsnofromemail = $("#"+emailsnofromemail_eid+"").val();
	form_data.append("emailsnofromemail", emailsnofromemail);

	
	if(requesttypename=="Post-Payment" || voiceinstid!=""){
		if(purposesubcategoryid==null || purposesubcategoryid=="null" || purposesubcategoryid=="" || purposesubcategoryid=="undefined"){
			alertmessage('error','Purpose sub category is missing, choose the purpose subcategory');
			return false;
		}else if(modeoftransfer==null || modeoftransfer=="null" || modeoftransfer=="" || modeoftransfer=="undefined"){
			alertmessage('error','Mode of transfer is required');
			return false;
		}else if(transactionno==null || transactionno=="null" || transactionno=="" || transactionno=="undefined"){
			alertmessage('error','Transaction no is missing');
			return false;
		}else if(transactiondate==null || transactiondate=="null" || transactiondate=="" || transactiondate=="undefined"){
			alertmessage('error','Transaction date is missing');
			return false;
		}else if(purposecode==null || purposecode=="null" || purposecode=="" || purposecode=="undefined"){
			alertmessage('error','Purpose is missing, choose the purpose');
			return false;
		}else if(description==null || description=="null" || description=="" || description=="undefined"){
			alertmessage('error','Description is missing, Atleast 20-25 character description is required');
			return false;
		}
	}
	
	var btnval=$("#"+clicktosavebtnid+"").val();
	$("#"+clicktosavebtnid+"").prop("disabled", true);
	$("#"+clicktosavebtnid+"").val("Please wait data saving");
	$.ajax
  ({
      type: "POST",
      url: baseurl+"modal/request_modal.php?action=savetoodb_requestdataforsalary",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
     	dataType:'JSON',
      success: function(res)
      {
        // console.log(res);
        $("#"+clicktosavebtnid+"").prop("disabled", false);
				$("#"+clicktosavebtnid+"").val(btnval);
      	if(res.length>0){
      		for(var i=0; i<res.length; i++){
      			if(res[i].status=="Success"){
		      		// alertmessage('success',res[i].message);
		      		$("#"+paytoname_eid+"").val(null).trigger('change.select2');
		      		$("#"+modeoftransfer_eid+"").prop('selectedIndex', 0);
		      		$("#"+paytoamount_eid+"").val(0);
		      		$("#"+salarydays_eid+"").val(0);
		      		$("#"+description_eid+"").val('');
		      		$("#"+attached_eid+"").val('');

		      		if(attpathfromemail && updatecategoriz=="yes"){
      					$("#paymentrequestclose").trigger("click");
      				}else{
								if(requesttypename=="Post-Payment"){
									
									$("input[name='uppreceiptradio']").prop("checked", false);
									$("#updpreceipt_modal").modal("show");
									$("#updpreceipt_modal").css("z-index","9999");
									$('#updpreceipt_modal').on('hidden.bs.modal', function () {
										$('body').addClass('modal-open');
									});
			      			$("#uploadreceiptfilebtn").data('referenceno', res[i].referenceno);
			      			$("#uploadreceiptfilebtn").data('dataupdatewith', res[i].dataupdatewith);
			      			$("#outputmsg").text(res[i].message);

			      			if(requesttypename=="Post-Payment" && res[i].referenceno!="" && closetobook_bankdbsno!=""){
			      				markautoreconcilation(baseurl_eid,companyid,subcompanyid,projectid,accountid,transactiondate,transactionno,closetobook_bankdbsno,res[i].referenceno);
			      			}
			      		}else{
			      			SweetAlertmessageFadein(res[i].status,res[i].message);
			      		}
		      		}
		      	}else{
		      		alertmessage('error',res[i].message);
		      	}
	      	}
      	}else{
      		alertmessage('error','Result not available');
      	}
      }
    });
}


function savetodb_requestfor_taxinvoicedata(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,subprojectid_eid,paymenttype_eidname,fromaccountid_eid,paytoname_eid,paytogstnumber_eid,paytoamount_eid,invoicetype_eid,invoiceno_eid,invoicedate_eid,invoicetable_eid,modeoftransfer_eid,transactionno_eid,transactiondate_eid,description_eid,attached_eid,audiofile_eid,attpathfromemail_eid,attsnofromemail_eid,emailsnofromemail_eid,updatecategoriz_ename,voiceinstid_eid,paymentduedate_eid,ponumber_eid,clicktosavebtnid)
{
	var form_data = new FormData();
	// var baseurl = $("#"+baseurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);

	var subprojectid = $("#"+subprojectid_eid+"").val();
	if(subprojectid=="" || subprojectid==null || subprojectid=="undefined"){
		alertmessage('error','Sub-Project name missing');
		$("#"+subproject_eid+"").focus();
		return false;
	}
	form_data.append("subprojectid", subprojectid);

	var isforproduction=$("#"+clicktosavebtnid+"").data("isforproduction");
	form_data.append("isforproduction", isforproduction);

	var requesttypename = $("input[name='"+paymenttype_eidname+"']:checked").val();
	form_data.append("requesttypename", requesttypename);

	var fromaccountid = $("#"+fromaccountid_eid+"").val();	
	if(requesttypename=="Post-Payment"){
		if(fromaccountid=="" || fromaccountid==null || fromaccountid=="undefined"){
			alertmessage('error','Please choose the from account name');
			return false;
		}
	}
	form_data.append("fromaccountid", fromaccountid);	

	var paytoamount = $("#"+paytoamount_eid+"").val();
	if(paytoamount=="" || paytoamount==null || paytoamount=="undefined"){
		alertmessage('error','Amount is missing');
		$("#"+paytoamount_eid+"").focus();
		return false;
	}
	form_data.append("paytoamount", paytoamount);

	var paytoid = $("#"+paytoname_eid+"").val();
	if(paytoid=="" || paytoid==null || paytoid=="undefined"){
		alertmessage('error','Payto name missing');
		$("#"+paytoname_eid+"").focus();
		return false;
	}
	form_data.append("paytoid", paytoid);

	var paytogstnumber = $("#"+paytogstnumber_eid+"").val();
	form_data.append("paytogstnumber", paytogstnumber);


	var invoicetype = $("#"+invoicetype_eid+"").val();
	form_data.append("invoicetype", invoicetype);

	var invoiceno = $("#"+invoiceno_eid+"").val();
	if(invoiceno=="" || invoiceno==null || invoiceno=="undefined"){
		alertmessage('error','Invoice number is name missing');
		$("#"+invoiceno_eid+"").focus();
		return false;
	}
	form_data.append("invoiceno", invoiceno);

	var invoicedate = $("#"+invoicedate_eid+"").val();
	if(invoicedate=="" || invoicedate==null || invoicedate=="undefined"){
		alertmessage('error','Invoice date is missing');
		$("#"+invoicedate_eid+"").focus();
		return false;
	}
	form_data.append("invoicedate", invoicedate);
	
	

	let TableData1; // Declare TableData1
	let ttlpayableamount = 0; // Declare and initialize ttlpayableamount outside the function

	TableData1 = storeTblValues1('#' + invoicetable_eid + ' tbody'); // Invoke the function
	TableData1 = JSON.stringify(TableData1); // Convert to JSON

	function storeTblValues1(mytable) {
	  const TableData_A1 = []; // Initialize array to store table data
	  const mytableid = mytable + ' tr';
	  let validationError = false; // Track validation errors

	  $(mytableid).each(function (row, tr) {
	  	const description = $(tr).find('td').find('input.description_td').val()?.trim();
	    const purpose = $(tr).find('td').find('select.purpose_td').val()?.trim();
	    const rate = $(tr).find('td').find('input.rate_td').val()?.trim();
	    const qty = $(tr).find('td').find('input.qty_td').val()?.trim();
	    const rowamount = $(tr).find('td').find('input.famount_td').val()?.trim();
	    const ttlgstamount = (parseFloat($(tr).find('td').find('input.cgstamt_td').val()?.trim()) || 0) + (parseFloat($(tr).find('td').find('input.sgstamt_td').val()?.trim()) || 0) + (parseFloat($(tr).find('td').find('input.igstamt_td').val()?.trim()) || 0);

	    ttlpayableamount += parseFloat(rowamount || 0) + parseFloat(ttlgstamount || 0);

	    let isforstock = '';
	    if ($(tr).find('td').find('input.isforstock_td').is(":checked")) {
	      isforstock = "yes";
	    }

	    // Perform validations
	    if (!description) {
	      alertmessage('error', 'Description missing for an entry');
	      validationError = true;
	      return false;
	    }
	    if (!purpose) {
	      alertmessage('error', 'Purpose missing for an entry');
	      validationError = true;
	      return false;
	    }
	    if (!rate) {
	      alertmessage('error', 'Rate missing for an entry');
	      validationError = true;
	      return false;
	    }
	    if (!qty) {
	      alertmessage('error', 'Quantity missing for an entry');
	      validationError = true;
	      return false;
	    }

	    // Add data to TableData_A1
	    TableData_A1[row] = {
	      "item-description": description,
	      "item-purposecode": purpose,
	      "item-isforstock": isforstock,
	      "item-hsbn": $(tr).find('td').find('input.hsbn_td').val()?.trim(),
	      "item-mrp": $(tr).find('td').find('input.mrp_td').val()?.trim(),
	      "item-rate": rate,
	      "item-qty": qty,
	      "item-cgst-prt": $(tr).find('td').find('input.cgstprt_td').val()?.trim(),
	      "item-cgst-amt": $(tr).find('td').find('input.cgstamt_td').val()?.trim(),
	      "item-sgst-prt": $(tr).find('td').find('input.sgstprt_td').val()?.trim(),
	      "item-sgst-amt": $(tr).find('td').find('input.sgstamt_td').val()?.trim(),
	      "item-igst-prt": $(tr).find('td').find('input.igstprt_td').val()?.trim(),
	      "item-igst-amt": $(tr).find('td').find('input.igstamt_td').val()?.trim(),
	      "item-dist-prt": $(tr).find('td').find('input.distprt_td').val()?.trim(),
	      "item-distamt": $(tr).find('td').find('input.distamt_td').val()?.trim(),
	      "item-amount": $(tr).find('td').find('input.famount_td').val()?.trim(),
	      "item-txablamount": $(tr).find('td').find('input.txableamount_td').val()?.trim()
	    };
	  });

	  // If validation errors occurred, return an empty array and exit
	  if (validationError) {
	    return [];
	  }
	  return TableData_A1;
	}

	if (parseFloat(paytoamount) !== parseFloat(ttlpayableamount)) {
	  if (paytoamount > ttlpayableamount) {
	    alertmessage("error", "The pay-to amount exceeds the invoice total amount.");
	  } else {
	    alertmessage("error", "The pay-to amount is less than the invoice total amount.");
	  }
	  $("#"+paytoamount_eid+"").focus();
	  return false;
	}


	// Append TableData1 to form_data
	form_data.append('invoicerowdata', TableData1);

	var totalrowsamount = $("#"+invoicetable_eid+"").data("ttlrows-amount");
	form_data.append("totalrowsamount", totalrowsamount);

	var totalgstamount = $("#"+invoicetable_eid+"").data("ttlgst-amount");
	form_data.append("totalgstamount", totalgstamount);

	var totalrowsdiscount = $("#"+invoicetable_eid+"").data("ttldiscount-amount");
	form_data.append("totalrowsdiscount", totalrowsdiscount);
	
	var totalpayableamount = $("#"+invoicetable_eid+"").data("ttlpayable-amount");
	form_data.append("totalpayableamount", totalpayableamount);

	var modeoftransfer = $("#"+modeoftransfer_eid+"").val();
	form_data.append("modeoftransfer", modeoftransfer);

	var transactionno = $("#"+transactionno_eid+"").val();
	form_data.append("transactionno", transactionno);

	var transactiondate = $("#"+transactiondate_eid+"").val();
	form_data.append("transactiondate", transactiondate);

	var description = $("#"+description_eid+"").val();
	form_data.append("description", description);	

	var attachedfile = $("#"+attached_eid+"")[0].files[0];
	if (!attachedfile) {
	    alertmessage("error","Please attach a file before proceeding.");
	    return false;
	}
	form_data.append("attachedfile", attachedfile);

	var audioFile = $("#"+audiofile_eid+"").data("audioFile");
	form_data.append('audiofile', audioFile);

	var closetobook_bankdbsno = $("#htmlcontainerdiv").data("unrbankdbsno");
	form_data.append("closetobook_bankdbsno", closetobook_bankdbsno);

	var bankdescription = $("#htmlcontainerdiv").data("unrbankdescription");
	form_data.append("bankdescription", bankdescription);				

	var attpathfromemail = $("#"+attpathfromemail_eid+"").val();
	form_data.append("attpathfromemail", attpathfromemail);
	var attsnofromemail = $("#"+attsnofromemail_eid+"").val();
	form_data.append("attsnofromemail", attsnofromemail);
	var emailsnofromemail = $("#"+emailsnofromemail_eid+"").val();
	form_data.append("emailsnofromemail", emailsnofromemail);
	var updatecategoriz = $("input[name='"+updatecategoriz_ename+"']:checked").val();
	form_data.append("updatecategoriz", updatecategoriz);	

	var voiceinstid = $("#"+voiceinstid_eid+"").val();
	form_data.append("voiceinstid", voiceinstid);

	var paymentduedate = $("#"+paymentduedate_eid+"").val();
	form_data.append("paymentduedate", paymentduedate);
	
	if(ponumber_eid){
		var ponumber=$("#"+ponumber_eid+"").val();
		form_data.append("ponumber", ponumber);
	}else{
		form_data.append("ponumber", '');
	}	

	if(requesttypename=="Post-Payment" || voiceinstid!=""){
		if(modeoftransfer==null || modeoftransfer=="null" || modeoftransfer=="" || modeoftransfer=="undefined"){
			alertmessage('error','Mode of transfer is required');
			return false;
		}else if(transactionno==null || transactionno=="null" || transactionno=="" || transactionno=="undefined"){
			alertmessage('error','Transaction no is missing');
			return false;
		}else if(transactiondate==null || transactiondate=="null" || transactiondate=="" || transactiondate=="undefined"){
			alertmessage('error','Transaction date is missing');
			return false;
		} 
	}
	if(description==null || description=="null" || description=="" || description=="undefined"){
		alertmessage('error','Description is missing, Atleast 20-25 character description is required');
		return false;
	}
	
	var loginname = $("body").data("loginname");
	form_data.append("loginname", loginname);

	var btnval=$("#"+clicktosavebtnid+"").val();
	$("#"+clicktosavebtnid+"").prop("disabled", true);
	$("#"+clicktosavebtnid+"").val("Please wait data saving");

	
  $.ajax
  ({
      type: "POST",
      url: baseurl+"modal/request_modal.php?action=savetodb_requestfor_taxinvoicedata",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType:'JSON',
      success: function(res)
      {
        // console.log(res);
        $("#"+clicktosavebtnid+"").prop("disabled", false);
				$("#"+clicktosavebtnid+"").val(btnval);
      	if(res.length>0){
      		for(var i=0; i<res.length; i++){
      			if(res[i].status=="Success"){
      				if(res[i].requestno){
	      				SendEmailAfterNewRequest_ForApproval(res[i].requestno);
      				}
      				if(attpathfromemail && updatecategoriz=="yes"){
      					$("#paymentrequestclose").trigger("click");
      				}else{
								if(requesttypename=="Post-Payment"){
									$("input[name='uppreceiptradio']").prop("checked", false);
									$("#updpreceipt_modal").modal("show");
									$("#updpreceipt_modal").css("z-index","9999");
									$('#updpreceipt_modal').on('hidden.bs.modal', function () {
										$('body').addClass('modal-open');
									});

			      			$("#uploadreceiptfilebtn").data('referenceno', res[i].referenceno);
			      			$("#uploadreceiptfilebtn").data('dataupdatewith', res[i].dataupdatewith);
			      			$("#outputmsg").text(res[i].message);
			      			if(requesttypename=="Post-Payment" && res[i].referenceno!="" && closetobook_bankdbsno>0){
			      				markautoreconcilation(baseurl_eid,companyid,subcompanyid,projectid,accountid,transactiondate,transactionno,closetobook_bankdbsno,res[i].referenceno);
			      			}
			      		}else{
			      			SweetAlertmessageFadein(res[i].status,res[i].message);
			      		}
		      		}
	      		}else{
		      		alertmessage('error',res[i].message);
		      	}
	      	}
      	}else{
      		alertmessage('error','Your data did not save, due to technical issue');
      	}
    	}
  });
}

function savetodb_requestdataforall(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,subprojectid_eid,accountid_eid,paytoname_eid,paytoamount_eid,invoicetype_eid,invoiceno_eid,invoicedate_eid,modeoftransfer_eid,transactionno_eid,transactiondate_eid,purposesubcategory_eid,purposecode_eid,description_eid,loginname_eid,paymentreceipt_eid,attached_eid,audiofile_eid,paymenttype_eidname,clicktosavebtnid,attpathfromemail_eid,attsnofromemail_eid,emailsnofromemail_eid,updatecategoriz_ename,voiceinstid_eid,beneficiaryname_eid=null,paymentduedate_eid=null,taxableamount_eid=null, invoicegstamount_eid=null,ponumber_eid=null){
	var form_data = new FormData();
	// var baseurl = $("#"+baseurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);

	var subprojectid = $("#"+subprojectid_eid+"").val();
	form_data.append("subprojectid", subprojectid);

	var isforproduction=$("#"+clicktosavebtnid+"").data("isforproduction");
	form_data.append("isforproduction", isforproduction);

	var categoryofrequest=$("#"+clicktosavebtnid+"").data("categoryofrequest");
	form_data.append("categoryofrequest", categoryofrequest);

	var closetobook_bankdbsno = $("#htmlcontainerdiv").data("unrbankdbsno");
	form_data.append("closetobook_bankdbsno", closetobook_bankdbsno);
	// alert(closetobook_bankdbsno);

	var bankdescription = $("#htmlcontainerdiv").data("unrbankdescription");
	form_data.append("bankdescription", bankdescription);

	var requesttypename = $("input[name='"+paymenttype_eidname+"']:checked").val();
	form_data.append("requesttypename", requesttypename);

	var updatecategoriz = $("input[name='"+updatecategoriz_ename+"']:checked").val();
	form_data.append("updatecategoriz", updatecategoriz);
	
	var paytoid = $("#"+paytoname_eid+"").val();
	// alert(paytoid+'=='+paytoname_eid);
	if(paytoid=="" || paytoid==null || paytoid=="undefined"){
		alertmessage('error','Payto name missing');
		return false;
	}
	form_data.append("paytoid", paytoid);
	var paytoamount = $("#"+paytoamount_eid+"").val();
	if(paytoamount=="" || paytoamount==null || paytoamount=="undefined"){
		alertmessage('error','Amount is missing');
		return false;
	}
	form_data.append("paytoamount", paytoamount);
	var taxableamount = $("#"+taxableamount_eid+"").val();
	form_data.append("taxableamount", taxableamount);
	var invoicegstamount = $("#"+invoicegstamount_eid+"").val();
	form_data.append("invoicegstamount", invoicegstamount);	

	var accountid = $("#"+accountid_eid+"").val();	
	if(requesttypename=="Post-Payment"){
		if(accountid=="" || accountid==null || accountid=="undefined"){
			alertmessage('error','Please choose the from account name');
			return false;
		}

	}
	form_data.append("accountid", accountid);	

	
	var invoicetype = $("#"+invoicetype_eid+"").val();
	form_data.append("invoicetype", invoicetype);

	var invoiceno = $("#"+invoiceno_eid+"").val();
	form_data.append("invoiceno", invoiceno);

	var invoicedate = $("#"+invoicedate_eid+"").val();
	form_data.append("invoicedate", invoicedate);

	var invoicedata = $("#"+invoicetype_eid+"").data("invoicedata");
	form_data.append("invoicedata", invoicedata);

	var totalrowsamount = $("#"+invoicetype_eid+"").data("totalrowsamount");
	form_data.append("totalrowsamount", totalrowsamount);

	var totalgstamount = $("#"+invoicetype_eid+"").data("totalgstamount");
	form_data.append("totalgstamount", totalgstamount);

	var totalrowsdiscount = $("#"+invoicetype_eid+"").data("totalrowsdiscount");
	form_data.append("totalrowsdiscount", totalrowsdiscount);

	var otherdiscount = $("#"+invoicetype_eid+"").data("otherdiscount");
	form_data.append("otherdiscount", otherdiscount);

	var totalpayableamount = $("#"+invoicetype_eid+"").data("totalpayableamount");
	form_data.append("totalpayableamount", totalpayableamount);

	var modeoftransfer = $("#"+modeoftransfer_eid+"").val();
	form_data.append("modeoftransfer", modeoftransfer);

	var transactionno = $("#"+transactionno_eid+"").val();
	form_data.append("transactionno", transactionno);
	var transactiondate = $("#"+transactiondate_eid+"").val();
	form_data.append("transactiondate", transactiondate);
		
	// var purposecategoryid = $("input[name='"+purposecategory_name+"']:checked").val();
	form_data.append("purposecategoryid", '');
	
	var purposesubcategoryid = $("#"+purposesubcategory_eid+"").val();
	form_data.append("purposesubcategoryid", purposesubcategoryid);
	
	var purposecode = $("#"+purposecode_eid+"").val();
	form_data.append("purposecode", purposecode);
	
	var purposename = $("#"+purposecode_eid+" selected:option").text();
	form_data.append("purposename", purposename);
	// alert(description_eid)
	var description = $("#"+description_eid+"").val();
	form_data.append("description", description);
	// alert(description);

	var loginname = $("#"+loginname_eid+"").val();
	form_data.append("loginname", loginname);

	var attachedfile = $("#"+attached_eid+"")[0].files[0];
	form_data.append("attachedfile", attachedfile);
	// alert(attached_eid+'=='+attachedfile);
	var paymentreceipt = $("#"+paymentreceipt_eid+"")[0].files[0];
	form_data.append("paymentreceipt", paymentreceipt);

	var audioFile = $("#"+audiofile_eid+"").data("audioFile");
	form_data.append('audiofile', audioFile);

	var voiceinstid = $("#"+voiceinstid_eid+"").val();
	form_data.append("voiceinstid", voiceinstid);

	var attpathfromemail = $("#"+attpathfromemail_eid+"").val();
	form_data.append("attpathfromemail", attpathfromemail);
	var attsnofromemail = $("#"+attsnofromemail_eid+"").val();
	form_data.append("attsnofromemail", attsnofromemail);
	var emailsnofromemail = $("#"+emailsnofromemail_eid+"").val();
	form_data.append("emailsnofromemail", emailsnofromemail);

	var paymentduedate = $("#"+paymentduedate_eid+"").val();
	form_data.append("paymentduedate", paymentduedate);
	

	if(beneficiaryname_eid){
		var beneficiaryname = $("#" + beneficiaryname_eid + " option:selected").text().trim();
		form_data.append("beneficiaryname", beneficiaryname);
	}else{
		form_data.append("beneficiaryname", '');
	}


	if(ponumber_eid){
		var ponumber=$("#"+ponumber_eid+"").val();
		form_data.append("ponumber", ponumber);
	}else{
		form_data.append("ponumber", '');
	}
	// alert(ponumber_eid+'=='+ponumber);
	// alert(description);
	if(requesttypename=="Post-Payment" || voiceinstid!=""){
		if(modeoftransfer==null || modeoftransfer=="null" || modeoftransfer=="" || modeoftransfer=="undefined"){
			alertmessage('error','Mode of transfer is required');
			return false;
		}else if(transactionno==null || transactionno=="null" || transactionno=="" || transactionno=="undefined"){
			alertmessage('error','Transaction no is missing');
			return false;
		}else if(transactiondate==null || transactiondate=="null" || transactiondate=="" || transactiondate=="undefined"){
			alertmessage('error','Transaction date is missing');
			return false;
		}else if(purposecode==null || purposecode=="null" || purposecode=="" || purposecode=="undefined"){
			alertmessage('error','Purpose is missing, choose the purpose');
			return false;
		}else if(description==null || description=="null" || description=="" || description=="undefined"){
			alertmessage('error','Description is missing, Atleast 20-25 character description is required');
			return false;
		}
	}

	if(description==null || description=="null" || description=="" || description=="undefined"){
		alertmessage('error','Description is missing, Atleast 20-25 character description is required');
		return false;
	}
	
	var btnval=$("#"+clicktosavebtnid+"").val();
	$("#"+clicktosavebtnid+"").prop("disabled", true);
	$("#"+clicktosavebtnid+"").val("Please wait data saving");
  $.ajax
  ({
      type: "POST",
      url: baseurl+"modal/request_modal.php?action=savetoodb_requestdataforall",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType:'JSON',
      success: function(res)
      {
        // console.log(res);
        $("#"+clicktosavebtnid+"").prop("disabled", false);
				$("#"+clicktosavebtnid+"").val(btnval);
      	if(res.length>0){
      		for(var i=0; i<res.length; i++){
      			if(res[i].status=="Success"){
      				if(res[i].requestno){
	      				SendEmailAfterNewRequest_ForApproval(res[i].requestno);
      				}
      				// alertmessage('success',res[i].message);      				
		      		$("#"+accountid_eid+"").prop('selectedIndex', 0);
		      		// $("#"+paytoname_eid+"").prop('selectedIndex', 0);
		      		$("#"+paytoname_eid+"").val(null).trigger('change.select2');
		      		$("#"+paytoamount_eid+"").val('');
		      		$("#"+modeoftransfer_eid+"").prop('selectedIndex', 0);
		      		$("#"+transactionno_eid+"").val('');
		      		$("#"+transactiondate_eid+"").val('');
		      		$("#"+description_eid+"").val('');
		      		$("#"+attached_eid+"").val('');
		      		// $("#"+audiofile_eid+"")
		      		$("#"+voiceinstid_eid+"").val('');
		      		// $("#"+attimg_eid+"").hide();
		      		if(attpathfromemail && updatecategoriz=="yes"){
      					$("#paymentrequestclose").trigger("click");
      				}else{
								if(requesttypename=="Post-Payment"){
									$("input[name='uppreceiptradio']").prop("checked", false);
									$("#updpreceipt_modal").modal("show");
									$("#updpreceipt_modal").css("z-index","9999");
									$('#updpreceipt_modal').on('hidden.bs.modal', function () {
										$('body').addClass('modal-open');
									});

			      			$("#uploadreceiptfilebtn").data('referenceno', res[i].referenceno);
			      			$("#uploadreceiptfilebtn").data('dataupdatewith', res[i].dataupdatewith);
			      			$("#outputmsg").text(res[i].message);
			      			// alert(requesttypename+'=='+res[i].referenceno+'=='+closetobook_bankdbsno);
			      			if(requesttypename=="Post-Payment" && res[i].referenceno!="" && closetobook_bankdbsno>0){
			      				markautoreconcilation(baseurl_eid,companyid,subcompanyid,projectid,accountid,transactiondate,transactionno,closetobook_bankdbsno,res[i].referenceno);
			      			}
			      		}else{
			      			SweetAlertmessageFadein(res[i].status,res[i].message);
			      		}
		      		}
	      		}else{
		      		alertmessage('error',res[i].message);
		      	}
	      	}
      	}else{
      		alertmessage('error','Your data did not save, due to technical issue');
      	}
    	}
  });
}
function savetodb_requestdatafor_itax(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,subprojectid_eid,accountid_eid,paytoname_eid,paytoamount_eid,invoicetype_eid,invoiceno_eid,invoicedate_eid,modeoftransfer_eid,transactionno_eid,transactiondate_eid,purposesubcategory_eid,purposecode_eid,description_eid,loginname_eid,attached_eid,paymentreceipt_eid,audiofile_eid,paymenttype_eidname,clicktosavebtnid,attpathfromemail_eid,attsnofromemail_eid,emailsnofromemail_eid,updatecategoriz_ename,voiceinstid_eid,beneficiaryname_eid=null,paymentduedate_eid=null,taxableamount_eid=null, invoicegstamount_eid=null,ponumber_eid=null){
	var form_data = new FormData();
	// var baseurl = $("#"+baseurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);

	var subprojectid = $("#"+subprojectid_eid+"").val();
	form_data.append("subprojectid", subprojectid);

	var isforproduction=$("#"+clicktosavebtnid+"").data("isforproduction");
	form_data.append("isforproduction", isforproduction);

	var categoryofrequest=$("#"+clicktosavebtnid+"").data("categoryofrequest");
	form_data.append("categoryofrequest", categoryofrequest);

	var closetobook_bankdbsno = $("#htmlcontainerdiv").data("unrbankdbsno");
	form_data.append("closetobook_bankdbsno", closetobook_bankdbsno);
	// alert(closetobook_bankdbsno);

	var bankdescription = $("#htmlcontainerdiv").data("unrbankdescription");
	form_data.append("bankdescription", bankdescription);

	var requesttypename = $("input[name='"+paymenttype_eidname+"']:checked").val();
	form_data.append("requesttypename", requesttypename);

	var updatecategoriz = $("input[name='"+updatecategoriz_ename+"']:checked").val();
	form_data.append("updatecategoriz", updatecategoriz);
	
	var paytoid = $("#"+paytoname_eid+"").val();
	if(paytoid=="" || paytoid==null || paytoid=="undefined"){
		alertmessage('error','Payto name missing');
		return false;
	}
	form_data.append("paytoid", paytoid);
	var paytoamount = $("#"+paytoamount_eid+"").val();
	if(paytoamount=="" || paytoamount==null || paytoamount=="undefined"){
		alertmessage('error','Amount is missing');
		return false;
	}
	form_data.append("paytoamount", paytoamount);
	var taxableamount = $("#"+taxableamount_eid+"").val();
	form_data.append("taxableamount", taxableamount);
	var invoicegstamount = $("#"+invoicegstamount_eid+"").val();
	form_data.append("invoicegstamount", invoicegstamount);	

	var accountid = $("#"+accountid_eid+"").val();	
	if(requesttypename=="Post-Payment"){
		if(accountid=="" || accountid==null || accountid=="undefined"){
			alertmessage('error','Please choose the from account name');
			return false;
		}

	}
	form_data.append("accountid", accountid);	

	
	var invoicetype = $("#"+invoicetype_eid+"").val();
	form_data.append("invoicetype", invoicetype);

	var invoiceno = $("#"+invoiceno_eid+"").val();
	form_data.append("invoiceno", invoiceno);

	var invoicedate = $("#"+invoicedate_eid+"").val();
	form_data.append("invoicedate", invoicedate);

	var invoicedata = $("#"+invoicetype_eid+"").data("invoicedata");
	form_data.append("invoicedata", invoicedata);

	var totalrowsamount = $("#"+invoicetype_eid+"").data("totalrowsamount");
	form_data.append("totalrowsamount", totalrowsamount);

	var totalgstamount = $("#"+invoicetype_eid+"").data("totalgstamount");
	form_data.append("totalgstamount", totalgstamount);

	var totalrowsdiscount = $("#"+invoicetype_eid+"").data("totalrowsdiscount");
	form_data.append("totalrowsdiscount", totalrowsdiscount);

	var otherdiscount = $("#"+invoicetype_eid+"").data("otherdiscount");
	form_data.append("otherdiscount", otherdiscount);

	var totalpayableamount = $("#"+invoicetype_eid+"").data("totalpayableamount");
	form_data.append("totalpayableamount", totalpayableamount);

	var modeoftransfer = $("#"+modeoftransfer_eid+"").val();
	form_data.append("modeoftransfer", modeoftransfer);

	var transactionno = $("#"+transactionno_eid+"").val();
	form_data.append("transactionno", transactionno);
	var transactiondate = $("#"+transactiondate_eid+"").val();
	form_data.append("transactiondate", transactiondate);
		
	// var purposecategoryid = $("input[name='"+purposecategory_name+"']:checked").val();
	form_data.append("purposecategoryid", '');
	
	var purposesubcategoryid = $("#"+purposesubcategory_eid+"").val();
	form_data.append("purposesubcategoryid", purposesubcategoryid);
	
	var purposecode = $("#"+purposecode_eid+"").val();
	if(purposecode=="" || purposecode==null || purposecode=="undefined"){
		alertmessage('error','Purpose name is missing');
		return false;
	}
	form_data.append("purposecode", purposecode);
	
	var purposename = $("#"+purposecode_eid+" selected:option").text();
	form_data.append("purposename", purposename);
	// alert(description_eid)
	var description = $("#"+description_eid+"").val();
	form_data.append("description", description);
	// alert(description);

	var loginname = $("#"+loginname_eid+"").val();
	form_data.append("loginname", loginname);

	var paymentreceipt = $("#"+paymentreceipt_eid+"")[0].files[0];
	form_data.append("paymentreceipt", paymentreceipt);

	var attachedfile = $("#"+attached_eid+"")[0].files[0];
	form_data.append("attachedfile", attachedfile);

	var audioFile = $("#"+audiofile_eid+"").data("audioFile");
	form_data.append('audiofile', audioFile);

	var voiceinstid = $("#"+voiceinstid_eid+"").val();
	form_data.append("voiceinstid", voiceinstid);

	var attpathfromemail = $("#"+attpathfromemail_eid+"").val();
	form_data.append("attpathfromemail", attpathfromemail);
	var attsnofromemail = $("#"+attsnofromemail_eid+"").val();
	form_data.append("attsnofromemail", attsnofromemail);
	var emailsnofromemail = $("#"+emailsnofromemail_eid+"").val();
	form_data.append("emailsnofromemail", emailsnofromemail);

	var paymentduedate = $("#"+paymentduedate_eid+"").val();
	form_data.append("paymentduedate", paymentduedate);
	

	if(beneficiaryname_eid){
		var beneficiaryname=$("#"+beneficiaryname_eid+"").val();
		form_data.append("beneficiaryname", beneficiaryname);
	}else{
		form_data.append("beneficiaryname", '');
	}


	if(ponumber_eid){
		var ponumber=$("#"+ponumber_eid+"").val();
		form_data.append("ponumber", ponumber);
	}else{
		form_data.append("ponumber", '');
	}
	if(requesttypename=="Post-Payment" || voiceinstid!=""){
		if(purposesubcategoryid==null || purposesubcategoryid=="null" || purposesubcategoryid=="" || purposesubcategoryid=="undefined"){
			alertmessage('error','Purpose sub category is missing, choose the purpose subcategory');
			return false;
		}else if(modeoftransfer==null || modeoftransfer=="null" || modeoftransfer=="" || modeoftransfer=="undefined"){
			alertmessage('error','Mode of transfer is required');
			return false;
		}else if(transactionno==null || transactionno=="null" || transactionno=="" || transactionno=="undefined"){
			alertmessage('error','Transaction no is missing');
			return false;
		}else if(transactiondate==null || transactiondate=="null" || transactiondate=="" || transactiondate=="undefined"){
			alertmessage('error','Transaction date is missing');
			return false;
		}else if(purposecode==null || purposecode=="null" || purposecode=="" || purposecode=="undefined"){
			alertmessage('error','Purpose is missing, choose the purpose');
			return false;
		}else if(description==null || description=="null" || description=="" || description=="undefined"){
			alertmessage('error','Description is missing, Atleast 20-25 character description is required');
			return false;
		}
	}

	var itaxsno = [];
	$("#incometaxrequestdatatbl tbody tr").each(function() {
	    var sno = $(this).data("itaxsno"); // Directly fetch data attribute
	    if (sno !== undefined) {
	        itaxsno.push(sno);
	    }
	});
	var itaxsnoList = JSON.stringify(itaxsno);
	var btnval=$("#"+clicktosavebtnid+"").val();
	$("#"+clicktosavebtnid+"").prop("disabled", true);
	$("#"+clicktosavebtnid+"").val("Please wait data saving");
  $.ajax
  ({
      type: "POST",
      url: baseurl+"modal/request_modal.php?action=savetoodb_requestdatafor_itax",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType:'JSON',
      success: function(res)
      {
        // console.log(res);
        $("#"+clicktosavebtnid+"").prop("disabled", false);
				$("#"+clicktosavebtnid+"").val(btnval);
      	if(res.length>0){
      		for(var i=0; i<res.length; i++){
      			if(res[i].status=="Success"){
      				if(res[i].requestno){
	      				SendEmailAfterNewRequest_ForApproval(res[i].requestno);
      				}
      				if(res[i].requestno){
	      				markedItaxRequestCreated(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,res[i].requestno,itaxsnoList);
      				}else if(res[i].referenceno){
	      				markedItaxRequestCreated(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,res[i].referenceno,itaxsnoList);
      				}
		      		if(attpathfromemail && updatecategoriz=="yes"){
      					$("#paymentrequestclose").trigger("click");
      				}else{
								if(requesttypename=="Post-Payment"){
									$("input[name='uppreceiptradio']").prop("checked", false);
									$("#updpreceipt_modal").modal("show");
									$("#updpreceipt_modal").css("z-index","9999");
									$('#updpreceipt_modal').on('hidden.bs.modal', function () {
										$('body').addClass('modal-open');
									});
			      			$("#uploadreceiptfilebtn").data('referenceno', res[i].referenceno);
			      			$("#uploadreceiptfilebtn").data('dataupdatewith', res[i].dataupdatewith);
			      			$("#outputmsg").text(res[i].message);
			      			if(requesttypename=="Post-Payment" && res[i].referenceno!="" && closetobook_bankdbsno>0){
			      				markautoreconcilation(baseurl_eid,companyid,subcompanyid,projectid,accountid,transactiondate,transactionno,closetobook_bankdbsno,res[i].referenceno);
			      			}
			      		}else{
			      			SweetAlertmessageFadein(res[i].status,res[i].message);
			      		}
		      		}
	      		}else{
		      		alertmessage('error',res[i].message);
		      	}
	      	}
      	}else{
      		alertmessage('error','Your data did not save, due to technical issue');
      	}
    	}
  });
}

function markedItaxRequestCreated(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,requestno,itaxsnoarray){
	var form_data = new FormData();
	// var baseurl = $("#"+routurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);
	var loginname = $("body").data("loginname");
	form_data.append("loginname", loginname);
	form_data.append("requestno", requestno);
	form_data.append("itaxsnoarray", itaxsnoarray);
	$.ajax
  ({
      type: "POST",
      url: baseurl+"modal/request_modal.php?action=markedItaxRequestCreated",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
     	// dataType:'JSON',
      success: function(res)
      {
      	console.log(res);
      	
    	}
  });
}

function otherthanrequest(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,subprojectid_eid,accountid_eid,paytoname_eid,paytoamount_eid,transactionno_eid,transactiondate_eid,purposecategory_name,modeoftransfer_ename,balanceamountforreceipt_textid,bnfdivclass,loginname_eid,attpathfromemail_eid,attsnofromemail_eid,emailsnofromemail_eid,updatecategoriz_ename,paymenttype_eidname,voiceinstid_eid,paymentduedate_eid=null,clicktosavebtnid=null){
	var form_data = new FormData();
	// var baseurl = $("#"+baseurl_eid+"").val();
	var rooturl = $("#hidden_rooturl").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);

	var subprojectid = $("#"+subprojectid_eid+"").val();
	if(subprojectid=="" || subprojectid==null || subprojectid=="undefined"){
		alertmessage('error','Sub-Project name missing');
		$("#"+subproject_eid+"").focus();
		return false;
	}
	form_data.append("subprojectid", subprojectid);

	var paymentduedate =$("#"+paymentduedate_eid+"").val(); 
	form_data.append("paymentduedate", paymentduedate);
	
	var closetobook_bankdbsno = $("#htmlcontainerdiv").data("unrbankdbsno");
	form_data.append("closetobook_bankdbsno", closetobook_bankdbsno);

	var categoryofrequest=$("#"+clicktosavebtnid+"").data("categoryofrequest");
	form_data.append("categoryofrequest", categoryofrequest);

	var bankdescription = $("#htmlcontainerdiv").data("unrbankdescription");
	form_data.append("bankdescription", bankdescription);

	if(bankdescription){
		bankdescription=", bankdescription:- "+bankdescription;
	}else{
		bankdescription="";
	}

	var isforproduction=$("#"+clicktosavebtnid+"").data("isforproduction");
	form_data.append("isforproduction", isforproduction);
	
	var accountid = $("#"+accountid_eid+"").val();
	form_data.append("accountid", accountid);
		
	var paytoid = $("#"+paytoname_eid+"").val();
	form_data.append("paytoid", paytoid);
	var paytoamount = $("#"+paytoamount_eid+"").val();
	form_data.append("paytoamount", paytoamount);
	var transactionno = $("#"+transactionno_eid+"").val();
	form_data.append("transactionno", transactionno);

	var purposecategoryid = $("input[name='"+purposecategory_name+"']:checked").val();
	form_data.append("purposecategoryid", purposecategoryid);
	
	var transactiondate = $("#"+transactiondate_eid+"").val();
	form_data.append("transactiondate", transactiondate);
	var requesttypename = $("input[name='"+paymenttype_eidname+"']:checked").val();
	form_data.append("requesttypename", requesttypename);
	var modeoftransfer = $("input[name='"+modeoftransfer_ename+"']:checked").val();
	form_data.append("modeoftransfer", modeoftransfer);
	var balanceamountforreceipt = $("#"+balanceamountforreceipt_textid+"").text();
	form_data.append("balanceamountforreceipt", balanceamountforreceipt);
	var updatecategoriz = $("input[name='"+updatecategoriz_ename+"']:checked").val();
	form_data.append("updatecategoriz", updatecategoriz);
	var attpathfromemail = $("#"+attpathfromemail_eid+"").val();
	form_data.append("attpathfromemail", attpathfromemail);
	var attsnofromemail = $("#"+attsnofromemail_eid+"").val();
	form_data.append("attsnofromemail", attsnofromemail);
	var emailsnofromemail = $("#"+emailsnofromemail_eid+"").val();
	form_data.append("emailsnofromemail", emailsnofromemail);

	var voiceinstid = $("#"+voiceinstid_eid+"").val();
	form_data.append("voiceinstid", voiceinstid);

	if(requesttypename=="Post-Payment" || voiceinstid!=""){
		if(modeoftransfer==null || modeoftransfer=="null" || modeoftransfer=="" || modeoftransfer=="undefined"){
			alertmessage('error','Mode of transfer is required');
			return false;
		}else if(transactionno==null || transactionno=="null" || transactionno=="" || transactionno=="undefined"){
			alertmessage('error','Transaction no is missing');
			return false;
		}else if(transactiondate==null || transactiondate=="null" || transactiondate=="" || transactiondate=="undefined"){
			alertmessage('error','Transaction date is missing');
			return false;
		}
	}

	var loginname = $("#"+loginname_eid+"").val();
	form_data.append("loginname", loginname);
	var noofinvoicediv = $("."+bnfdivclass+"").length;
	var ArrayDataLIst = new Array();
	var divc = 1;
	let ttlbrkamount=0;
	for(var cs=0; cs<noofinvoicediv; cs++){
			var parentdiv_id = parseInt(cs) + parseInt(divc);
			var beneficiaryname=$("#beneficiaryname_"+parentdiv_id).val();
			var expenseamount=$("#expenseamount_"+parentdiv_id).val();
			ttlbrkamount+=parseInt(expenseamount);
			var taxableamount=$("#taxableamount_"+parentdiv_id).val();
			var gstamount=$("#gstamount_"+parentdiv_id).val();
			var vouchertype=$("#invoicetype_"+parentdiv_id).val();
			var voucherno=$("#invoiceno_"+parentdiv_id).val();
			var voucherdate=$("#invoicedate_"+parentdiv_id).val();
			var purposecode=$("#purposecode_"+parentdiv_id).val();
			var description=$("#description_"+parentdiv_id).val()+bankdescription;
			var attachmentpath=$("#attachments_"+parentdiv_id).prop('src').replace(rooturl, '');
			var docsno=$("#docsno_"+parentdiv_id).val();
			var ponumber=$("#ponumber_"+parentdiv_id).val();
			if(requesttypename=="Post-Payment"){
				if(purposecode==null || purposecode=="null" || purposecode=="" || purposecode=="undefined"){
					alertmessage('error','Purpose missing, Please choose the purpose name againt this expense');
					return false;
				}
			}
			
			var invoicedata = $("#invoicetype_"+parentdiv_id).data("invoicedata");
			var totalrowsamount ="";
			var totalgstamount ="";
			var totalrowsdiscount ="";
			var totalpayableamount ="";
			var otherdiscount ="";

		ArrayDataLIst[cs]={
			"beneficiaryname" :beneficiaryname
			, "expenseamount" :expenseamount
			, "invoiceno" :voucherno
			, "invoicetype" :vouchertype
			, "invoicedate" :voucherdate
			, "description" :description
			, "purposecode" :purposecode
			, "attachmentpath" : attachmentpath
			, "invoicegstamount" : gstamount
			, "taxableamount" : taxableamount
			, "ponumber" : ponumber
			, "docsno" : docsno
			, "srm_attsno" : ''
			, "srm_emailsno" : ''
			, "invoicedata" : invoicedata ? JSON.parse(invoicedata) : {}
			, "totalrowsamount" : totalrowsamount
			, "totalgstamount" : totalgstamount
			, "totalrowsdiscount" : totalrowsdiscount
			, "otherdiscount" : otherdiscount
			, "totalpayableamount" : totalpayableamount
		}
	}
	ArrayDataLIst = ArrayDataLIst;
	ArrayDataLIst = JSON.stringify(ArrayDataLIst);
	form_data.append('voucherinfo', ArrayDataLIst);
	// console.log(ArrayDataLIst);
	// alert(paytoamount+'==='+ttlbrkamount);

  	$.ajax
    ({
      type: "POST",
      url: baseurl+"modal/request_modal.php?action=savetootherthanrequest",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
     	dataType:'JSON',
      success: function(res)
      {
      	// itax_
      	if(res.length>0){
      		for(var i=0; i<res.length; i++){
      			if(res[i].status=="Success"){
      				if(res[i].requestno){
	      				SendEmailAfterNewRequest_ForApproval(res[i].requestno);
      				}
		      		alertmessage('success',res[i].message);
		      		if(attpathfromemail && updatecategoriz=="yes"){
      					$("#paymentrequestclose").trigger("click");
      				}else{
								if(requesttypename=="Post-Payment"){
									$("input[name='uppreceiptradio']").prop("checked", false);
									$("#updpreceipt_modal").modal("show");
									$("#updpreceipt_modal").css("z-index","9999");
									$('#updpreceipt_modal').on('hidden.bs.modal', function () {
										$('body').addClass('modal-open');
									});

			      			$("#uploadreceiptfilebtn").data('referenceno', res[i].referenceno);
			      			$("#uploadreceiptfilebtn").data('dataupdatewith', res[i].dataupdatewith);
			      			$("#outputmsg").text(res[i].message);
			      			if(requesttypename=="Post-Payment" && res[i].referenceno!="" && closetobook_bankdbsno>0){
			      				markautoreconcilation(baseurl_eid,companyid,subcompanyid,projectid,accountid,transactiondate,transactionno,closetobook_bankdbsno,res[i].referenceno);
			      			}
			      		}else{
			      			SweetAlertmessageFadein(res[i].status,res[i].message);
			      		}
		      		}
		      		// setTimeout(function(){
							//    window.location.reload(1);
							// }, 3000);
		      	}else{
		      		alertmessage('error',res[i].message);
		      	}
      		}
      	}
      }
    });
}

function savetodb_billpaymentrequest(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,billpaymentamount_eid,billinvoicetype_eid,billpaymentnumber_eid,billpaymentdate_eid,billpaymentduedate_eid,billpaymentremark_eid,billattachement_eid,loginname_eid,clicktosavebtnid){
	var form_data = new FormData();
	// var baseurl = $("#"+baseurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);
	
	var billid = $("#"+clicktosavebtnid+"").data("billid");
	form_data.append("billid", billid);

	var billdbsno = $("#"+clicktosavebtnid+"").data("billdbsno");
	form_data.append("billdbsno", billdbsno);

	var billamount = $("#"+billpaymentamount_eid+"").val();
	if(billamount=="" || billamount==null || billamount=="undefined" || billamount<=0){
		alertmessage('error','Amount must be greater than or equal to zero');
		return false;
	}
	form_data.append("billamount", billamount);
	
	var billtype = $("#"+billinvoicetype_eid+"").val();
	if(billtype=="" || billtype==null || billtype=="undefined"){
		alertmessage('error','Bill/Invoice type is missing');
		$("#"+billinvoicetype_eid+"").focus();
		return false;
	}
	form_data.append("billtype", billtype);

	var billno = $("#"+billpaymentnumber_eid+"").val();
	form_data.append("billno", billno);

	var billdate = $("#"+billpaymentdate_eid+"").val();
	form_data.append("billdate", billdate);

	if(billtype=="Invoice" || billtype=="Kachha Bill /Boucher"){
		if(billno=="" || billno==null || billno=="undefined"){
			alertmessage('error','Bill/Invoice number is missing');
			$("#"+billpaymentnumber_eid+"").focus();
			return false;
		}else if(billdate=="" || billdate==null || billdate=="undefined"){
			alertmessage('error','Bill/Invoice date is missing');
			$("#"+billpaymentdate_eid+"").focus();
			return false;
		}	
	}

	var paymentduedate = $("#"+billpaymentduedate_eid+"").val();
	if(paymentduedate=="" || paymentduedate==null || paymentduedate=="undefined"){
		alertmessage('error','Payment due date is missing');
		$("#"+billpaymentduedate_eid+"").focus();
		return false;
	}
	form_data.append("paymentduedate", paymentduedate);

	
	var billremark = $("#"+billpaymentremark_eid+"").val();
	if(billremark=="" || billremark==null || billremark=="undefined"){
		alertmessage('error','Bill remark is miising');
		$("#"+billpaymentremark_eid+"").focus();
		return false;
	}
	form_data.append("billremark", billremark);

	// var attachedfile = $("#"+billattachement_eid+"")[0].files[0];
	// form_data.append("billattachedfile", attachedfile);
	var attachedfile = $("#" + billattachement_eid)[0].files[0];
	if (!attachedfile) {
	    alertmessage("error","Please attach a file before submitting.");
	    return; 
	}

	form_data.append("billattachedfile", attachedfile);
	
	var loginname = $("body").data("loginname");
	form_data.append("loginname", loginname);

	var loginid = $("body").data("loginid");
	form_data.append("loginid", loginid);
	
	var btnval=$("#"+clicktosavebtnid+"").html();
	// $("#"+clicktosavebtnid+"").prop("disabled", true);
	// $("#"+clicktosavebtnid+"").html("Please wait data saving");
  $.ajax
  ({
      type: "POST",
      url: baseurl+"modal/request_modal.php?action=savetoodb_billpaymentrequest",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
     	dataType:'JSON',
      success: function(res)
      {
        console.log(res);
        $("#"+clicktosavebtnid+"").prop("disabled", false);
				$("#"+clicktosavebtnid+"").html(btnval);
  			if(res.status=="Success"){
    			SweetAlertmessageFadein('success',res.message);
    		}else{
      		alertmessage('error',res.message);
    		}	      	
    	}
  });
}



function markunknownfromReconciledbookentry(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,purposecode,finyear,updateby,updatebyvalue){
	var form_data = new FormData();
	var baseurl = $("#"+routurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);
	form_data.append("purposecode", purposecode);
	form_data.append("finyear", finyear);
	form_data.append("updateby", updateby);
	form_data.append("updatebyvalue", updatebyvalue);
	form_data.append("loginname", $('body').data('loginname'));

	if(purposecode==null || purposecode=="null" || purposecode=="" || purposecode=="undefined"){
		alertmessage('error','Purpose code is missing');
		return false;
	}

	$.ajax
  ({
      type: "POST",
      url: baseurl+"modal/allnotes_modal.php?action=markunknownfromReconciledentry",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
     	dataType:'JSON',
      success: function(res)
      {
      	// itax_
      	if (res.status=="Success") {
		    	alertmessage('success',res.message);
		       	$('#editpurposedetails').modal("hide");
		      	$("#genreport").trigger("click");
		    }else{
		    	alertmessage('error',res.message);
		    }
    	}
  });
}

function markunknownfromReconciledbookentry_credit(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,purposecode,finyear,updateby,updatebyvalue){
	var form_data = new FormData();
	var baseurl = $("#"+routurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);
	form_data.append("purposecode", purposecode);
	form_data.append("finyear", finyear);
	form_data.append("updateby", updateby);
	form_data.append("updatebyvalue", updatebyvalue);
	form_data.append("loginname", $('body').data('loginname'));

	if(purposecode==null || purposecode=="null" || purposecode=="" || purposecode=="undefined"){
		alertmessage('error','Purpose code is missing');
		return false;
	}

	$.ajax
  ({
      type: "POST",
      url: baseurl+"modal/allnotes_modal.php?action=markunknownfromReconciledentry_credit",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
     	dataType:'JSON',
      success: function(res)
      {
      	// itax_
      	if (res.status=="Success") {
		    	alertmessage('success',res.message);
		       	$('#editpurposedetails').modal("hide");
		      	$("#genreport").trigger("click");
		    }else{
		    	alertmessage('error',res.message);
		    }
    	}
  });
}
function markautoreconcilation(baseurl_eid,companyidval,subcompanyidval,projectidval,accountidval,transactiondate,transactionno,closetobookdbsno,referenceno){
	var form_data = new FormData();
	// var baseurl = $("#"+baseurl_eid+"").val();
	var rooturl = $("#hidden_rooturl").val();
	form_data.append("companyid", companyidval);
	form_data.append("subcompanyid", subcompanyidval);
	form_data.append("projectid", projectidval);
	form_data.append("accountid", accountidval);
	form_data.append("closetobookdbsno", closetobookdbsno);
	form_data.append("transactiondate", transactiondate);
	form_data.append("transactionno", transactionno);
	form_data.append("referenceno", referenceno);

	if(accountidval==null || accountidval=="null" || accountidval=="" || accountidval=="undefined"){
		alertmessage('error','Paid from account is missing, choose the account');
		return false;
	}else if(closetobookdbsno==null || closetobookdbsno=="null" || closetobookdbsno=="" || closetobookdbsno=="undefined"){
		alertmessage('error','Close to book reference no is missing');
		return false;
	}else if(referenceno==null || referenceno=="null" || referenceno=="" || referenceno=="undefined"){
		alertmessage('error','Actual reference no is missing');
		return false;
	}

	$.ajax
  ({
      type: "POST",
      url: baseurl+"modal/request_modal.php?action=markautoreconcilationmanual",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
     	dataType:'JSON',
      success: function(res)
      {
      	 // itax_
    	}
  });
}

function smallcashrequest(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,accountid_eid,paytoidval,paytoamount_eid,transactionno_eid,transactiondate_eid,purposecategory_name,modeoftransfer_ename,balanceamountforreceipt_textid,bnfdivclass,loginname_eid,attpathfromemail_eid,attsnofromemail_eid,emailsnofromemail_eid,updatecategoriz_ename,paymenttype_eidname){
	var form_data = new FormData();
	// var baseurl = $("#"+baseurl_eid+"").val();
	var rooturl = $("#hidden_rooturl").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);
	
	var accountid = $("#"+accountid_eid+"").val();
	form_data.append("accountid", accountid);

	form_data.append("paytoid", paytoidval);
	var paytoamount = paytoamount_eid;
	form_data.append("paytoamount", paytoamount);
	var transactionno = '';
	form_data.append("transactionno", transactionno);

	var purposecategoryid = '';
	form_data.append("purposecategoryid", purposecategoryid);
	
	var transactiondate = '';
	form_data.append("transactiondate", transactiondate);
	var requesttypename = "pre-payment";
	form_data.append("requesttypename", requesttypename);
	var modeoftransfer = '';
	form_data.append("modeoftransfer", modeoftransfer);
	var balanceamountforreceipt = '';
	form_data.append("balanceamountforreceipt", balanceamountforreceipt);
	var updatecategoriz = '';
	form_data.append("updatecategoriz", updatecategoriz);
	var attpathfromemail = '';
	form_data.append("attpathfromemail", attpathfromemail);
	var attsnofromemail = '';
	form_data.append("attsnofromemail", attsnofromemail);
	var emailsnofromemail = '';
	form_data.append("emailsnofromemail", emailsnofromemail);

	var loginname = $("#"+loginname_eid+"").val();
	form_data.append("loginname", loginname);
	var noofinvoicediv = $("#"+bnfdivclass+"").length;
	var ArrayDataLIst = new Array();
	var divc = 1;
	$("#"+bnfdivclass+ " tr").each(function(row, tr){
      if($(tr).find("td:eq(7)").find("input[name='cashcheckbox']").is(":checked")){
          ArrayDataLIst[row]={
            "beneficiaryname" : $(tr).find('td:eq(9)').text()
            , "expenseamount" :$(tr).find('td:eq(2)').text()
            , "invoiceno" :''
            , "invoicetype" :''
            , "invoicedate" :''
            , "description" :$(tr).find('td:eq(5)').text()
            , "purposecode" :$(tr).find('td:eq(4)').text()
            , "attachmentpath" :$(tr).find('td:eq(6)').text()
						, "docsno" : ''
						, "srm_attsno" : ''
						, "srm_emailsno" : ''
						, "invoicedata" :  {}
						, "totalrowsamount" : ''
						, "totalgstamount" : ''
						, "totalrowsdiscount" : ''
						, "otherdiscount" : ''
						, "totalpayableamount" : ''
						, "sno" : $(tr).find('td:eq(8)').text()
          }
     	}
  });
	
	ArrayDataLIst = ArrayDataLIst;
	ArrayDataLIst.shift();
	ArrayDataLIst = JSON.stringify(ArrayDataLIst);

	form_data.append('voucherinfo', ArrayDataLIst);

  	$.ajax
    ({
      type: "POST",
      url: baseurl+"modal/request_modal.php?action=savetootherthanrequest",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
     	dataType:'JSON',
      success: function(res)
      {
      	// itax_
      	if(res.length>0){
      		for(var i=0; i<res.length; i++){
      			if(res[i].status=="Success"){
      				
      				updatestatusofcashexp(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,ArrayDataLIst,res[i].requestno);
		      		alertmessage('success',res[i].message);
		      		
		      		// setTimeout(function(){
							//    window.location.reload(1);
							// }, 3000);
		      	}else{
		      		alertmessage('error',res[i].message);
		      	}
      		}
      	}
      }
    });
}

function updatestatusofcashexp(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,ArrayDataLIst,requestno)
{
	var form_data = new FormData();
	var rooturl = $("#hidden_rooturl").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);
	form_data.append("requestno", requestno);
	form_data.append('expeninfo', ArrayDataLIst);
  $.ajax
  ({
      type: "POST",
      url: rooturl+"modal/request_modal.php?action=updatecashsmallexpencstatus",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
     	dataType:'JSON',
      success: function(res)
      {
      	 // itax_
      	if(res.length>0){
      		
      	}
    }
  });
}

function upload_paymentreceipt(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,receiptpath_eid,loginid_eid,uploadbtnid){
	var form_data = new FormData();
	var baseurl = $("#"+baseurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);

	var updwithrefno = $("#"+uploadbtnid+"").data("referenceno");
	form_data.append('updwithrefno', updwithrefno);
	var dataupdatewith = $("#"+uploadbtnid+"").data("dataupdatewith");
	form_data.append('dataupdatewith', dataupdatewith);

	var filepath = $("#"+receiptpath_eid+"")[0].files[0];
	if(filepath=="undefined" || filepath=="" || filepath==null){
		alertmessage('error','Receipt file missing, Please attached the receipt file');
		return false;
	}
	form_data.append('filepath', filepath);

	var loginid = $("#"+loginid_eid+"").val();
	form_data.append("loginid", loginid);

	var btnval = $("#"+uploadbtnid+"").text();
	$("#"+uploadbtnid+"").prop("disabled", true);
	$("#"+uploadbtnid+"").text("Please Wait data are saving...");

	var closetobook_bankdbsno = $("#htmlcontainerdiv").data("unrbankdbsno");
	form_data.append("closetobook_bankdbsno", closetobook_bankdbsno);
	
	$.ajax
  ({
    type: "POST",
    url: baseurl+"modal/upload_audio.php?action=upload_paymentreceipt",
    data: form_data,
    contentType: false,
    processData: false,
    cache: false,
   	dataType:'JSON',
    success: function(res)
    {
    	// itax_
    	$("#"+uploadbtnid+"").text(btnval);
			$("#"+uploadbtnid+"").prop("disabled", false);
    	if(res.length>0){
    		for(var i=0; i<res.length; i++){
    			if(res[i].status=="Success"){
	      		$("#"+receiptpath_eid+"").prop("src", '');
	      		alertmessage('success',res[i].message);
	      		$("#updpreceipt_modal").modal("hide");
						var alrdbankdbsno = $("#htmlcontainerdiv").data("unrbankdbsno");
						if(alrdbankdbsno){
							Swal.fire({
							  title: "Are you sure",
							  text: "Digitize another unknown entry",
							  icon: "warning",
							  showCancelButton: true,
							  confirmButtonColor: "#3085d6",
							  cancelButtonColor: "#d33",
							  confirmButtonText: "Yes"
							}).then((result) => {
							  if (result.isConfirmed) {
							  	$(".activerow").find("td.unknownenteriestd").text(parseInt($(".activerow").find("td.unknownenteriestd").text())-1);
							  	if(parseInt($(".activerow").find("td.unknownenteriestd").text())==0){
							  		$(".activerow").remove();
									$(".unknownrow").show();
							        $("#unknownlistdiv").hide();
							  	}else{
							  		$(".activerow").find(".unwbnkradio").prop("checked", false).trigger("change");
							  		$(".activerow").find(".unwbnkradio").prop("checked", true).trigger("change");
							  	}
							    $("#makebook_unknownentry_modal").modal("hide");
							  }else{
							  	window.location.reload();
							  }
							});
						}else{
		      		setTimeout(function(){
							   window.location.reload(1);
							}, 3000);
						}
	      	}else{
	      		alertmessage('error',res[i].message);
	      	}
    		}
    	}
    }
  });
}
function savetodb_postpaymententrypart1(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,subprojectid_eid,paymenttype_eidname,fromaccount_ename,ptransctiondate_eid,ptransctionno_eid,paymentreceiptfile_eid,ptamount_eid,ptvatamount_eid,vatinvoicefile_eid,paytoname_eid,purpose_eid,otherdocument_eid,description_pvoice_eid,voiceremark_eid,uploadbtnid,taxfilestatus=null){
	var form_data = new FormData();
	var baseurl = $("#"+baseurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);
	
	var subprojectid = $("#"+subprojectid_eid+"").val();
	if(subprojectid=="" || subprojectid==null || subprojectid=="undefined"){
		alertmessage('error','Sub-Project name missing');
		$("#"+subproject_eid+"").focus();
		return false;
	}
	form_data.append("subprojectid", subprojectid);

	var requesttypename = $("input[name='"+paymenttype_eidname+"']:checked").val();
	form_data.append("requesttypename", requesttypename);

	if(fromaccount_ename){
		var fromaccountid=$("input[name='"+fromaccount_ename+"']:checked").val();	
		form_data.append("fromaccountid", fromaccountid);
		if(fromaccountid=="" || fromaccountid==null || fromaccountid=="undefined"){
			alertmessage('error','The bank from which the amount was transferred is missing.');
			return false;
		}
	}else{
		form_data.append("fromaccountid", '');
	}

	var transctiondate = $("#"+ptransctiondate_eid+"").val();
	form_data.append("transctiondate", transctiondate);
	var transactionno = $("#"+ptransctionno_eid+"").val();
	form_data.append("transactionno", transactionno);
	if(transactionno=="" || transactionno==null || transactionno=="undefined"){
		alertmessage('error','Transaction no missing, Atleast enter the last 4 digits of transaction no');
		return false;
	}

	var paymentreceiptfile = $("#"+paymentreceiptfile_eid+"")[0].files[0];
	form_data.append('paymentreceiptfile', paymentreceiptfile);

	
	var requestamount = $("#"+ptamount_eid+"").val();
	if(requestamount=="" || requestamount==null || requestamount=="undefined"){
		alertmessage('error','The transferred amount is invalid or missing, and it is mandatory.');
		return false;
	}
	form_data.append("requestamount", requestamount);

	var taxamount = $("#"+ptvatamount_eid+"").val();
	form_data.append("taxamount", taxamount);

	var istaxreceiptup=$("#"+uploadbtnid+"").data("istaxreceiptupload");
	form_data.append("istaxreceiptup", istaxreceiptup);

	var vatinvoicefile = $("#"+vatinvoicefile_eid+"")[0].files[0];
	if (parseInt(taxamount) > 0) {
    if (istaxreceiptup === "now" || istaxreceiptup !== "later") {
        if (typeof vatinvoicefile === "undefined" || vatinvoicefile === "") {
            alertmessage('error', 'Tax reference file is missing');
            return false;
        }
    }
}

	form_data.append('vatinvoicefile', vatinvoicefile);

	if(paytoname_eid){
		var paytonametxt=$("#"+paytoname_eid+"").val();	
		form_data.append("paytonametxt", paytonametxt);
	}else{
		form_data.append("paytonametxt", '');
	}
	
	if(purpose_eid){
		var purposetxt=$("#"+purpose_eid+"").val();	
		form_data.append("purposetxt", purposetxt);
	}else{
		form_data.append("purposetxt", '');
	}
	var description = $("#"+description_pvoice_eid+"").val();
	form_data.append("description", description);

	var otherdocumentfile = $("#"+otherdocument_eid+"")[0].files[0];
	form_data.append('otherdocumentfile', otherdocumentfile);

	var voiceremark = $("#"+voiceremark_eid+"").data("audioFile");
	form_data.append('voiceremark', voiceremark);

	var loginid = $("body").data("loginid");
	form_data.append("loginid", loginid);

	var btnval = $("#"+uploadbtnid+"").text();
	$("#"+uploadbtnid+"").prop("disabled", true);
	$.ajax
  ({
    type: "POST",
    url: baseurl+"modal/upload_audio.php?action=postpaymentpart1",
    data: form_data,
    contentType: false,
    processData: false,
    cache: false,
   	dataType:'JSON',
    success: function(res)
    {
    	// itax_
    	$("#"+uploadbtnid+"").text(btnval);
			$("#"+uploadbtnid+"").prop("disabled", false);
    	if(res.length>0){
    		for(var i=0; i<res.length; i++){
    			if(res[i].status=="Success"){
	      		SweetAlertmessageFadein(res[i].status,res[i].message);
	      	}else{
	      		alertmessage('error',res[i].message);
	      	}
    		}
    	}
    }
  });
}

function upload_voicerecording(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,subprojectid_eid=null,paymenttype_eidname,voiceattpath_eid,attpath_eid,requestamount_eid,loginid_eid,uploadbtnid, paytoname_eid=null, paytonametxt_eid=null, purpose_eid=null, purposetxt_eid=null){
	var form_data = new FormData();
	var baseurl = $("#"+baseurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);

	var subprojectid = $("#"+subprojectid_eid+"").val();
	form_data.append("subprojectid", subprojectid);

	var requesttypename = $("input[name='"+paymenttype_eidname+"']:checked").val();
	form_data.append("requesttypename", requesttypename);
	
	var audioFile = $("#"+voiceattpath_eid+"").data("audioFile");
	form_data.append('audio', audioFile);
	if(audioFile=="undefined"){
		alertmessage('error','Audio recording missing');
		return false;
	}

	var filepath = $("#"+attpath_eid+"")[0].files[0];
	form_data.append('filepath', filepath);

	var requestamount = $("#"+requestamount_eid+"").val();
	if(requestamount=="" || requestamount==null || requestamount=="undefined"){
		alertmessage('error','Request Amount is missing, Which is mandatory');
		return false;
	}
	form_data.append("requestamount", requestamount);

	let paytoname_frmm='';
	let paytonametxt='';
	if(paytoname_eid){
		paytoname_frmm=$("#"+paytoname_eid+"").val();
	}
	if(paytonametxt_eid){
		paytonametxt=$("#"+paytonametxt_eid+"").val();	
	}

	let paytoname = paytoname_frmm !== "" ? paytoname_frmm : paytonametxt;
	form_data.append("paytonametxt", paytoname);

	let purpose_frmm='';
	let purposetxt='';
	if(purpose_eid){
		purpose_frmm=$("#"+purpose_eid+"").val();
	}
	if(purposetxt_eid){
		purposetxt=$("#"+purposetxt_eid+"").val();	
	}

	let purposename = purpose_frmm !== "" ? purpose_frmm : purposetxt;
	form_data.append("purposenametext", purposename);
	
	var loginid = $("#"+loginid_eid+"").val();
	form_data.append("loginid", loginid);

	var btnval = $("#"+uploadbtnid+"").text();
	$("#"+uploadbtnid+"").prop("disabled", true);
	$("#"+uploadbtnid+"").text("Please Wait data are saving...");
	
	$.ajax
  ({
    type: "POST",
    url: baseurl+"modal/upload_audio.php?action=upload_voicerecording",
    data: form_data,
    contentType: false,
    processData: false,
    cache: false,
   	dataType:'JSON',
    success: function(res)
    {
    	// itax_
    	$("#"+uploadbtnid+"").text(btnval);
			$("#"+uploadbtnid+"").prop("disabled", false);
    	if(res.length>0){
    		for(var i=0; i<res.length; i++){
    			if(res[i].status=="Success"){
	      		// $("#"+uploadbtnid+"").data("audioFile").empty();
	      		$("#audioPlayback").prop("src", '');
	      		SweetAlertmessageFadein(res[i].status,res[i].message);
	      		// messageAfterresult('Instruction id : '+res[i].instructionid,res[i].message);
	      		// setTimeout(function(){
						//    window.location.reload(1);
						// }, 2000);

	      	}else{
	      		alertmessage('error',res[i].message);
	      	}
    		}
    	}
    }
  });
}

function upload_voicerecording_post(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,subprojectid_eid,paymenttype_eidname,voiceattpath_eid,attpath_eid,attpath2_eid,requestamount_eid, paytoname_eid, paytonametxt_eid, purpose_eid, purposetxt_eid,accountid_eid,accountnametxt_eid,transactionno_eid,transactiondate_eid,remark_eid,loginid_eid,uploadbtnid){
	var form_data = new FormData();
	var baseurl = $("#"+baseurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val()??'';
	form_data.append("companyid", companyid);
	
	var subcompanyid = $("#"+subcompanyid_eid+"").val()??'';
	form_data.append("subcompanyid", subcompanyid);
	
	var projectid = $("#"+projectid_eid+"").val()??'';
	form_data.append("projectid", projectid);
	
	var subprojectid = $("#"+subprojectid_eid+"").val()??'';
	if(subprojectid=="" || subprojectid==null || subprojectid=="undefined"){
		alertmessage('error','Please provide the sub-project name.');
		$("#"+subproject_eid+"").focus();
		return false;
	}
	form_data.append("subprojectid", subprojectid);

	var requesttypename = $("input[name='"+paymenttype_eidname+"']:checked").val();
	form_data.append("requesttypename", requesttypename);
	var audioFile = $("#"+voiceattpath_eid+"_audioPlayback").data("audioFile");
	form_data.append('audio', audioFile);
	if(audioFile=="undefined"){
		alertmessage('error','Audio recording is missing. It is required for voice instruction.');
		return false;
	}
	var requestamount = $("#"+requestamount_eid+"").val();
	if(requestamount=="" || requestamount==null || requestamount=="undefined"){
		alertmessage('error','Please specify how much amount was transferred — it is mandatory.');
		return false;
	}
	form_data.append("requestamount", requestamount);

	var filepath = $("#"+attpath_eid+"")[0].files[0];
	form_data.append('filepath', filepath);

	var filepath2 = $("#"+attpath2_eid+"")[0].files[0];
	form_data.append('filepath2', filepath2);

	var paytofromdrop = $("#"+paytoname_eid+"").val()??'';
	if(paytofromdrop){
		var paytoid=$("#"+paytoname_eid+"").val()??'';
		var paytoname=$("#"+paytoname_eid+" option:selected").text()??'';
	}else{
		var paytoid="";
		var paytoname=$("#"+paytonametxt_eid+"").val()??'';
	}
	
	form_data.append("paytoid", paytoid);
	form_data.append("paytoname", paytoname);

	var purposefromdrop = $("#"+purpose_eid+"").val()??'';
	if(purposefromdrop){
		var purposecode=$("#"+purpose_eid+"").val()??'';
		var purposename=$("#"+purpose_eid+" option:selected").text()??'';
	}else{
		var purposecode="";
		var purposename=$("#"+purposetxt_eid+"").val()??'';
	}
	
	form_data.append("purposecode", purposecode);
	form_data.append("purposename", purposename);



	var accountidfromdrop = $("#"+accountid_eid+"").val()??'';
	if(accountidfromdrop){
		var accountid=$("#"+accountid_eid+"").val()??'';
		var bankname=$("#"+accountid_eid+" option:selected").text()??'';
	}else{
		var accountid="";
		var bankname=$("#"+accountnametxt_eid+"").val()??'';
	}
	
	if(bankname==null || bankname=='null' || bankname=="" || bankname=="undefined"){
		alertmessage('Specify the bank the amount was transferred from');
		return false;
	}
	form_data.append("fromaccountid", accountid);
	form_data.append("bankname", bankname);

	var transactionno  = $("#"+transactionno_eid+"").val();
	form_data.append("transactionno", transactionno);
	var transactiondate  = $("#"+transactiondate_eid+"").val();
	if(transactiondate==null || transactiondate=='null' || transactiondate=="" || transactiondate=="undefined"){
		alertmessage('Transaction date is missing, Please choose the transaction date');
		return false;
	}
	form_data.append("transactiondate", transactiondate);

	var textremark  = $("#"+remark_eid+"").val();
	form_data.append("textremark", textremark);

	var loginid = $("#"+loginid_eid+"").val();
	form_data.append("loginid", loginid);

	var btnval = $("#"+uploadbtnid+"").text();
	$("#"+uploadbtnid+"").prop("disabled", true);
	$.ajax
  ({
    type: "POST",
    url: baseurl+"modal/upload_audio.php?action=upload_voicerecordingpost",
    data: form_data,
    contentType: false,
    processData: false,
    cache: false,
   	dataType:'JSON',
    success: function(res)
    {
    	// console.log(res);
    	$("#"+uploadbtnid+"").text(btnval);
			$("#"+uploadbtnid+"").prop("disabled", false);
    	if(res.length>0){
    		for(var i=0; i<res.length; i++){
    			if(res[i].status=="Success"){
	      		$("#audioPlayback").prop("src", '');
	      		SweetAlertmessageFadein(res[i].status,res[i].message);
	      	}else{
	      		alertmessage('error',res[i].message);
	      	}
    		}
    	}
    }
  });
}

function upload_voicerecording_salarysheet(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,paymenttype_eidname,attpath_eid,loginid_eid,uploadbtnid){
	var form_data = new FormData();
	var baseurl = $("#"+baseurl_eid+"").val();
	
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);

	var requesttypename = $("input[name='"+paymenttype_eidname+"']:checked").val();
	form_data.append("requesttypename", requesttypename);
	

	var filepath = $("#"+attpath_eid+"")[0].files[0];
	form_data.append('filepath', filepath);

	var loginid = $("#"+loginid_eid+"").val();
	form_data.append("loginid", loginid);

	var btnval = $("#"+uploadbtnid+"").text();
	$("#"+uploadbtnid+"").prop("disabled", true);
	$("#"+uploadbtnid+"").text("Please Wait data are saving...");
	
	$.ajax
  ({
    type: "POST",
    url: baseurl+"modal/upload_audio.php?action=upload_voicerecordingsalarysheet",
    data: form_data,
    contentType: false,
    processData: false,
    cache: false,
   	dataType:'JSON',
    success: function(res)
    {
    	// itax_
    	$("#"+uploadbtnid+"").text(btnval);
			$("#"+uploadbtnid+"").prop("disabled", false);
    	if(res.length>0){
    		for(var i=0; i<res.length; i++){
    			if(res[i].status=="Success"){
	      		// $("#"+uploadbtnid+"").data("audioFile").empty();
	      		$("#audioPlayback").prop("src", '');
	      		SweetAlertmessageFadein(res[i].status,res[i].message);
	      		// messageAfterresult('Instruction id : '+res[i].instructionid,res[i].message);
	      		// setTimeout(function(){
						//    window.location.reload(1);
						// }, 2000);
	      	}else{
	      		alertmessage('error',res[i].message);
	      	}
    		}
    	}
    }
  });
}

function voicerecordingdetails(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,callback){
	var form_data = new FormData();
	var baseurl = $("#"+baseurl_eid+"").val();
	
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);
	$.ajax
  ({
    type: "POST",
    url: baseurl+"modal/upload_audio.php?action=voicerecordingdetails",
    data: form_data,
    contentType: false,
    processData: false,
    cache: false,
   	dataType:'JSON',
    success: function(res)
    {
    	// itax_
    	callback(res);
    	
    }
  });
}
function voiceinstructiondertails_withshortby(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,shortby,shortbyvalue,callback){

	var form_data = new FormData();
	var baseurl = $("#"+baseurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);
	form_data.append("shortby", shortby);
	form_data.append("shortbyvalue", shortbyvalue);
	$.ajax
  ({
    type: "POST",
    url: baseurl+"modal/upload_audio.php?action=voicerecordingdetailswithshortby",
    data: form_data,
    contentType: false,
    processData: false,
    cache: false,
   	dataType:'JSON',
    success: function(res)
    {
    	// itax_
    	callback(res);
    }
  });

}

function reject_voiceinstruction(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,voiceinstid_eid,rejectremark_eid,loginid_eid,updatebtn_eid){
	var form_data = new FormData();
	var baseurl = $("#"+routurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);

	var voiceinstid = $("#"+voiceinstid_eid+"").val();
	form_data.append("voiceinstid", voiceinstid);

	var rejectremark = $("#"+rejectremark_eid+"").val();
	form_data.append("rejectremark", rejectremark);

	var loginid = $("#"+loginid_eid+"").val();
	form_data.append("loginid", loginid);
	var btnval = $("#"+updatebtn_eid+"").val();
	$("#"+updatebtn_eid+"").prop('disabled', true);
	$("#"+updatebtn_eid+"").val('Please wait... ');
	$.ajax
    ({
      type: "POST",
      url: baseurl+"modal/upload_audio.php?action=rejectvoiceinstruction",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
     	dataType:'JSON',
      success: function(res)
      {
      	// itax_
      	if(res.status=="Success"){
      		$("#"+updatebtn_eid+"").prop('disabled', false);
					$("#"+updatebtn_eid+"").val(btnval);
					SweetAlertmessageFadein(res.status,res.message);
      		// alertmessage('success',res.message);
					// setTimeout(function() {
				  //   window.location.reload();
				  // }, 2000); // 2000 milliseconds = 2 seconds
      	}else{
      		$("#"+updatebtn_eid+"").prop('disabled', false);
					$("#"+updatebtn_eid+"").val(btnval);
      		alertmessage('error',res.message);
      	}
      }
    });
}

function savetodb_minimumdigitizedot3(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,paytoname_eid,amount_eid,purposetext_eid,remark_eid,uploadfile_eid,audiofile_eid,voiceinstid_eid,loginname_eid,updatebtn_eid){

	var form_data = new FormData();
	var baseurl = $("#"+routurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);
	
	var paytoname = $("#"+paytoname_eid+"").val();
	if(paytoname=="" || paytoname==null || paytoname=="undefined"){
		alertmessage('error','Enter the payto name');
		return false;
	}
	form_data.append("paytoname", paytoname);

	
	var amount = $("#"+amount_eid+"").val();
	if(amount=="" || amount==null || amount=="undefined"){
		alertmessage('error','Enter the amout value');
		return false;
	}
	form_data.append("amount", amount);
	
	var purposetext = $("#"+purposetext_eid+"").val();
	if(purposetext=="" || purposetext==null || purposetext=="undefined"){
		alertmessage('error','Enter the purpose of this amount');
		return false;
	}
	form_data.append("purposetext", purposetext);

	var remark = $("#"+remark_eid+"").val();
	form_data.append("remark", remark);

	var uploadfile = $("#"+uploadfile_eid+"")[0].files[0];
	form_data.append('uploadfile', uploadfile);

	var audioFile = $("#"+audiofile_eid+"").data("audioFile");
	form_data.append('audiofile', audioFile);

	var voiceinstid = $("#"+voiceinstid_eid+"").val();
	form_data.append("voiceinstid", voiceinstid);

	var loginname = $("#"+loginname_eid+"").val();
	form_data.append("loginname", loginname);

	var btnval = $("#"+updatebtn_eid+"").html();
	$("#"+updatebtn_eid+"").prop('disabled', true);
	$("#"+updatebtn_eid+"").html('Please wait... ');
	$.ajax
    ({
      type: "POST",
      url: baseurl+"modal/upload_audio.php?action=minimumdigitizedot3voiceinstruction",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
     	dataType:'JSON',
      success: function(res)
      {
      	// itax_
      	if(res.status=="Success"){
      		$("#"+updatebtn_eid+"").prop('disabled', false);
					$("#"+updatebtn_eid+"").html(btnval);
					SweetAlertmessageFadein(res.status,res.message);
      		// alertmessage('success',res.message);
					// setTimeout(function() {
				  //   window.location.reload();
				  // }, 2000); // 2000 milliseconds = 2 seconds
      	}else{
      		$("#"+updatebtn_eid+"").prop('disabled', false);
					$("#"+updatebtn_eid+"").html(btnval);
      		alertmessage('error',res.message);
      	}
      }
    });
}

function digitizedtoT3_voiceinstruction(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,voiceinstid_eid,remark_eid,uploadfile_eid,loginid_eid,updatebtn_eid){
	var form_data = new FormData();
	var baseurl = $("#"+routurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);

	var voiceinstid = $("#"+voiceinstid_eid+"").val();
	form_data.append("voiceinstid", voiceinstid);

	var remark = $("#"+remark_eid+"").val();
	form_data.append("remark", remark);

	var uploadfile = $("#"+uploadfile_eid+"")[0].files[0];
	form_data.append('uploadfile', uploadfile);

	var loginid = $("#"+loginid_eid+"").val();
	form_data.append("loginid", loginid);
	var btnval = $("#"+updatebtn_eid+"").val();
	$("#"+updatebtn_eid+"").prop('disabled', true);
	$("#"+updatebtn_eid+"").val('Please wait... ');
	$.ajax
    ({
      type: "POST",
      url: baseurl+"modal/upload_audio.php?action=digitizedtot3voiceinstruction",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
     	dataType:'JSON',
      success: function(res)
      {
      	// itax_
      	if(res.status=="Success"){
      		$("#"+updatebtn_eid+"").prop('disabled', false);
					$("#"+updatebtn_eid+"").val(btnval);
					SweetAlertmessageFadein(res.status,res.message);
      		// alertmessage('success',res.message);
					// setTimeout(function() {
				  //   window.location.reload();
				  // }, 2000); // 2000 milliseconds = 2 seconds
      	}else{
      		$("#"+updatebtn_eid+"").prop('disabled', false);
					$("#"+updatebtn_eid+"").val(btnval);
      		alertmessage('error',res.message);
      	}
      }
    });
}

function uploadsalarysheetforemail(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,attachedinput_eid,loginid_eid,uploadbtn_eid){
	var form_data = new FormData();
	// var baseurl = $("#"+baseurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);
	var uploadsheet = $("#"+attachedinput_eid+"")[0].files[0];
	form_data.append("uploadsheet", uploadsheet);
	var loginid = $("#"+loginid_eid+"").val();
	form_data.append("loginid", loginid);
	var btnval = $("#"+uploadbtn_eid+"").val();
	$("#"+uploadbtn_eid+"").prop('disabled', true);
	$("#"+uploadbtn_eid+"").val('Please wait file is uploding');
	$.ajax
    ({
      type: "POST",
      url: baseurl+"modal/request_modal.php?action=uploadsalarysheet",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
     	dataType:'JSON',
      success: function(res)
      {
      	// itax_
      	if(res.status=="Success"){
      		$("#salarysheetatt").val('');
      		$("#"+uploadbtn_eid+"").prop('disabled', false);
					$("#"+uploadbtn_eid+"").val(btnval);
      		alertmessage('success',res.message);
      	}else{
      		$("#"+uploadbtn_eid+"").prop('disabled', false);
					$("#"+uploadbtn_eid+"").val(btnval);
      		alertmessage('error',res.message);
      	}
      }
    });
}

function uploadsalarysheetforcreatebook(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,attachedinput_eid,loginname_eid,uploadbtn_eid){
	var form_data = new FormData();
	// var baseurl = $("#"+baseurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);
	var uploadsalarysheet = $("#"+attachedinput_eid+"")[0].files[0];
	if (!uploadsalarysheet) {
	    alertmessage("error", "Please attach a file before proceeding.");
	    return false;
	}
	form_data.append("uploadsalarysheet", uploadsalarysheet);

	var loginname = $("#"+loginname_eid+"").val();
	form_data.append("loginname", loginname);
	var btnval = $("#"+uploadbtn_eid+"").val();
	$("#"+uploadbtn_eid+"").prop('disabled', true);
	$("#"+uploadbtn_eid+"").val('Please wait file is uploding');
	$.ajax
  ({
    type: "POST",
    url: baseurl+"modal/request_modal.php?action=upldsalaryforbookreconstruct",
    data: form_data,
    contentType: false,
    processData: false,
    cache: false,
   	dataType:'JSON',
    success: function(res)
    {
    	// itax_
    	if(res.length>0){
    		$("#"+uploadbtn_eid+"").prop('disabled', false);
				$("#"+uploadbtn_eid+"").val(btnval);
    		for(var i=0; i<res.length; i++){
    			if(res[i].status=="Success"){
    				$("#"+attachedinput_eid+"").val('');
	    			// alertmessage('success',res[i].message);
	    			SweetAlertmessageFadeinwithoutrefresh(res[i].status,res[i].message);
	    			getdata_forreconsturctuctsalarydata("hidden_baseurl","hidden_companyid","hidden_subcompanyid","hidden_projectid","pendingbookofsalarytbl");
	    			// createbook_excelsheetdata('hidden_baseurl','hidden_companyid','hidden_subcompanyid','hidden_projectid','hidden_loginname','pendingbookofsalarytbl', 'salarydatabtn');
	    			// setTimeout(function() {
				    //   window.location.reload();
				    // }, 2000); // 2000 milliseconds = 2 seconds

    			}else if(res[i].status=="wrongfileformat"){
    				Swal.fire({
						  text: "Your attached excel sheet is not in a valid format. Are you sure you want to upload it?",
						  icon: "warning",
						  showCancelButton: true,
						  confirmButtonText: "Yes, upload it!",
						  cancelButtonText: "No, cancel!",
						  reverseButtons: true
						}).then((result) => {
						  if (result.isConfirmed) {
						    upload_voicerecording_salarysheet('hidden_rooturl','hidden_companyid','hidden_subcompanyid','hidden_projectid','paymenttype_voice','salarysheetatt','hidden_loginid','uploadsalarysheetbtn');
						  } 
						});
    			}else if(res[i].status=="missingfunction"){
    				Swal.fire({
						  // title: "Are you sure?",
						  text: "Your attached excel sheet is not valid format, Are you sure? You won't to upload it",
						  icon: "warning",
						  showCancelButton: true,
						  confirmButtonText: "Yes, upload it!",
						  cancelButtonText: "No, cancel!",
						  reverseButtons: true
						}).then((result) => {
						  if (result.isConfirmed) {
						    upload_voicerecording_salarysheet('hidden_rooturl','hidden_companyid','hidden_subcompanyid','hidden_projectid','paymenttype_voice','salarysheetatt','hidden_loginid','uploadsalarysheetbtn');
						  }
						});

    			}else{
    				// missingfunction
    				alertmessage('error',res[i].message);	
    			}
    		}
    	}
    }
  });
}

function uploadotherthan_salarysheetforcreatebook(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,attachedinput_eid,loginname_eid,uploadbtn_eid){
	var form_data = new FormData();
	// var baseurl = $("#"+baseurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);
	var uploadsalarysheet = $("#"+attachedinput_eid+"")[0].files[0];
	form_data.append("otherthansalarysheet", uploadsalarysheet);
	var loginname = $("#"+loginname_eid+"").val();
	form_data.append("loginname", loginname);
	var btnval = $("#"+uploadbtn_eid+"").val();
	$("#"+uploadbtn_eid+"").prop('disabled', true);
	$("#"+uploadbtn_eid+"").val('Please wait file is uploding');
	$.ajax
  ({
    type: "POST",
    url: baseurl+"modal/request_modal.php?action=upldotherthan_salaryforbookreconstruct",
    data: form_data,
    contentType: false,
    processData: false,
    cache: false,
   	dataType:'JSON',
    success: function(res)
    {
    	// console.log(res);
    	if(res.length>0){
    		$("#"+uploadbtn_eid+"").prop('disabled', false);
				$("#"+uploadbtn_eid+"").val(btnval);
    		for(var i=0; i<res.length; i++){
    			if(res[i].status=="Success"){
    				$("#"+attachedinput_eid+"").val('');
	    			// alertmessage('success',res[i].message);
	    			SweetAlertmessageFadeinwithoutrefresh(res[i].status,res[i].message);
	    			getdata_forreconsturctuct_otharthansalarydata("hidden_baseurl","hidden_companyid","hidden_subcompanyid","hidden_projectid","pendingbookofexpnsedatatbl");
	    			// setTimeout(function() {
				    //   window.location.reload();
				    // }, 2000); // 2000 milliseconds = 2 seconds

    			}else if(res[i].status=="wrongfileformat"){
    				Swal.fire({
						  text: "Your attached excel sheet is not in a valid format. Are you sure you want to upload it?",
						  icon: "warning",
						  showCancelButton: true,
						  confirmButtonText: "Yes, upload it!",
						  cancelButtonText: "No, cancel!",
						  reverseButtons: true
						}).then((result) => {
						  if (result.isConfirmed) {
						    upload_voicerecording_salarysheet('hidden_rooturl','hidden_companyid','hidden_subcompanyid','hidden_projectid','paymenttype_voice','salarysheetatt','hidden_loginid','uploadsalarysheetbtn');
						  } 
						});
    			}else if(res[i].status=="missingfunction"){
    				Swal.fire({
						  // title: "Are you sure?",
						  text: "Your attached excel sheet is not valid format or missing function of excel sheet, Are you sure? You won't to upload it",
						  icon: "warning",
						  showCancelButton: true,
						  confirmButtonText: "Yes, upload it!",
						  cancelButtonText: "No, cancel!",
						  reverseButtons: true
						}).then((result) => {
						  if (result.isConfirmed) {
						    upload_voicerecording_salarysheet('hidden_rooturl','hidden_companyid','hidden_subcompanyid','hidden_projectid','paymenttype_voice','salarysheetatt','hidden_loginid','uploadsalarysheetbtn');
						  }
						});

    			}else{
    				// missingfunction
    				alertmessage('error',res[i].message);	
    			}
    		}
    	}
    }
  });
}

function getdata_forreconsturctuctsalarydata(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,tableid){
	var form_data = new FormData();
	// var baseurl = $("#"+baseurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);
	
	var tableidr=$("#"+tableid+"");
	var details = tableidr.closest('details');
  var detailsId = details.attr('id');
  $("#"+detailsId+"").hide();
	$.ajax
  ({
    type: "POST",
    url: baseurl+"modal/request_modal.php?action=getsalarydataforreconstructbook",
    data: form_data,
    contentType: false,
    processData: false,
    cache: false,
   	// dataType:'JSON',
    success: function(res)
    {
    	// itax_
    	if(res){
    		$("#"+tableid+" tbody").empty();
    		$("#"+detailsId+"").show();
    		$("#"+tableid+" tbody").html(res);
    		// $("#crtsalarybookbtn").trigger("click");
    	}else{
				$("#"+detailsId+"").hide();    		
    	}
    	$(".select2").select2();
			$(".select2").css('margin-top', '5px');
			$('.select2-selection').css('border-radius','0px');
			$('.select2-container').children().css('border-radius','0px');
			$('.select2-selection').css('height','46px');
			$('.select2-selection__arrow').css('margin-top','10px');
    }
  });

}
function getdata_forreconsturctuct_otharthansalarydata(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,tableid){
	var form_data = new FormData();
	// var baseurl = $("#"+baseurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);
	
	var tableidr=$("#"+tableid+"");
	var details = tableidr.closest('details');
  var detailsId = details.attr('id');
  $("#"+detailsId+"").hide();
	$.ajax
  ({
    type: "POST",
    url: baseurl+"modal/request_modal.php?action=getuploadexceldata_otherthansalary",
    data: form_data,
    contentType: false,
    processData: false,
    cache: false,
   	// dataType:'JSON',
    success: function(res)
    {
    	// alert(res);
    	// itax_
    	if(res){
    		$("#"+tableid+" tbody").empty();
    		$("#"+detailsId+"").show();
    		$("#"+tableid+" tbody").html(res);
    		// $("#otherthansalarydatabtn").trigger("click");
    	}else{
				$("#"+detailsId+"").hide();    		
    	}
    	$(".select2").select2();
			$(".select2").css('margin-top', '5px');
			$('.select2-selection').css('border-radius','0px');
			$('.select2-container').children().css('border-radius','0px');
			$('.select2-selection').css('height','46px');
			$('.select2-selection__arrow').css('margin-top','10px');
    }
  });

}

function remove_exceldataentry(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,loginname_eid,dbsno,datatype){
	var form_data = new FormData();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);

	var loginname = $("#"+loginname_eid+"").val();
	form_data.append("loginname", loginname);

	form_data.append("dbsno", dbsno);
	$.ajax
  ({
    type: "POST",
    url: baseurl+"modal/request_modal.php?action=remove_exceldataentry",
    data: form_data,
    contentType: false,
    processData: false,
    cache: false,
   	dataType:'JSON',
    success: function(res)
    {
    	// itax_
    	if(res.status=="Success"){
    		alertmessage('success',res.message);
    		if(datatype=="removedsalarydata"){
    			getdata_forreconsturctuctsalarydata("hidden_baseurl","hidden_companyid","hidden_subcompanyid","hidden_projectid","pendingbookofsalarytbl");
    		}else if(datatype=="removedotherthansalarydata"){
    			getdata_forreconsturctuct_otharthansalarydata("hidden_baseurl","hidden_companyid","hidden_subcompanyid","hidden_projectid","pendingbookofexpnsedatatbl");
    		}
    	}else{
    		alertmessage('error',res.message);
    	}
    }
  });
}

function createbook_excelsheetdata(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,loginname_eid,tableid, clicktosavebtnid){
	var form_data = new FormData();
	// var baseurl = $("#"+baseurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);

	var loginname = $("#"+loginname_eid+"").val();
	form_data.append("loginname", loginname);
	
	var SalaryData2;
	SalaryData2 = storeTblValues2('#'+tableid+' tbody');
	SalaryData2 = JSON.stringify(SalaryData2);	
	function storeTblValues2(mytable)
	{
		var TableData_A2 = new Array();
		var mytableid = mytable + ' tr';
		$(mytableid).each(function(row, tr){
			TableData_A2[row]={
				"dbsno" :$(tr).find('input.sdbsno').val()
				, "paytoname" :$(tr).find('td.paytoname_sl').text()
				, "purposecode" :$(tr).find('select.slarypurpose').val()
				, "uploaddatatype" :$(tr).find('select.udatatype_sl').text()
			}
		});
		// TableData_A2.shift();  // first row will be empty - so remove
		return TableData_A2;
	}
	form_data.append('exceldata', SalaryData2);
	$.ajax
  ({
    type: "POST",
    url: baseurl+"modal/request_modal.php?action=createbookwith_exceldata",
    data: form_data,
    contentType: false,
    processData: false,
    cache: false,
   	dataType:'JSON',
    success: function(res)
    {
    	
    	if(res.length>0){
    		for(var i=0; i<res.length; i++){
    			if(res[i].status=="Success"){
    				alertmessage('success',res[i].message);
    				if(clicktosavebtnid=="salarydatabtn"){
    					// alert("11: "+clicktosavebtnid);
	    				getdata_forreconsturctuctsalarydata("hidden_baseurl","hidden_companyid","hidden_subcompanyid","hidden_projectid","pendingbookofsalarytbl");
    				}else if(clicktosavebtnid=="otherthansalarydatabtn"){
    					// alert("22: "+clicktosavebtnid);
	    				getdata_forreconsturctuct_otharthansalarydata("hidden_baseurl","hidden_companyid","hidden_subcompanyid","hidden_projectid","pendingbookofexpnsedatatbl");
    				}else{
    					// alert("33: "+clicktosavebtnid);
    					// location.reload();
    				}
    			}else{
    				alertmessage('error',res[i].message);
    			}
    		}
    	}
    }
  });

}



function payto_salaryfrommaster(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,paytoid_eid,viewdivid){
	var form_data = new FormData();
	// var baseurl = $("#"+baseurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);
	var paytoid = $("#"+paytoid_eid+"").val();
	form_data.append("paytoid", paytoid);
	$.ajax
    ({
      	type: "POST",
      	url: baseurl+"modal/request_modal.php?action=payto_salaryfrommaster",
      	data: form_data,
      	contentType: false,
      	processData: false,
      	cache: false,
     		dataType:'JSON',
      	success: function(res)
      	{
      		// itax_
      		$("#"+viewdivid+"").empty();
      		$("#"+viewdivid+"").html(res);
	        	        
	    	}
    });
}
function newemployeeregistration(baseurl_eid, companyid_eid, subcompanyid_eid, projectid_eid, employeetypename_ename, employeename_eid, empdob_eid, empgender_eid, uploadphoto_eid, empmaritalstatus_eid, empreligion_eid, contactnumber_eid, emernumber_eid, presaddress_eid, prmaddress_eid, nationality_eid, eemail_eid, adharnumber_eid, pannumber_eid, uploadadhar_eid, uploadpan_eid, uploadresume_eid, highest_qualification_eid, specialisation_eid, institution_eid, yearofpassing_eid, uploadeducation_eid, experience_eid, doj_eid, intime_eid, outtime_eid, paymentmode_eid, totalsalary_eid, bankname_eid, accountno_eid, ifsccode_eid, uploadbank_eid, fathername_eid, mothername_eid, loginname_eid, allcheckid_eid, submitbtn_eid){
		if($("#"+allcheckid_eid+"").is(":checked")){
		}else{
			alertmessage('error', 'Please checked the check box, if all details are correct.');
			return false;
		}
		var form_data = new FormData();
		// var baseurl = $("#"+baseurl_eid+"").val();
		var companyid = $("#"+companyid_eid+"").val();
		form_data.append("companyid", companyid);
		
		var subcompanyid = $("#"+subcompanyid_eid+"").val();
		form_data.append("subcompanyid", subcompanyid);
		
		var projectid = $("#"+projectid_eid+"").val();
		form_data.append("projectid", projectid);
		var employeetype = $("input[name='"+employeetypename_ename+"']:checked").val();
		form_data.append("employeetype", employeetype);
		if(employeetype=="" || employeetype==null || employeetype=="undefined"){
			alertmessage('error', 'Please choose the employee type');
			return false;
		}
		var employeename = $("#"+employeename_eid+"").val();
		form_data.append("employeename", employeename);
		if(employeename=="" || employeename==null || employeename=="undefined"){
			alertmessage('error', 'Please enter the emplyee name');
			$("#"+employeename_eid+"").focus();
			return false;
		}
		
		var empdob = $("#"+empdob_eid+"").val();
		form_data.append("empdob", empdob);
		if(empdob=="" || empdob==null || empdob=="undefined"){
			alertmessage('error', 'Please enter the emplyee date of birth');
			$("#"+empdob_eid+"").focus();
			return false;
		}
		var empgender = $("#"+empgender_eid+"").val();
		form_data.append("empgender", empgender);
		if(empgender=="" || empgender==null || empgender=="undefined"){
			alertmessage('error', 'Please choose the emplyee Gender');
			$("#"+empgender_eid+"").focus();
			return false;
		}
		
		var uploadphoto = $("#"+uploadphoto_eid+"")[0].files[0];
		form_data.append("uploadphoto", uploadphoto);
		
		var empmaritalstatus = $("#"+empmaritalstatus_eid+"").val();
		form_data.append("empmaritalstatus", empmaritalstatus);
		if(empmaritalstatus=="" || empmaritalstatus==null || empmaritalstatus=="undefined"){
			alertmessage('error', 'Please choose the marital status');
			$("#"+empmaritalstatus_eid+"").focus();
			return false;
		}
		
		var empreligion = $("#"+empreligion_eid+"").val();
		form_data.append("empreligion", empreligion);
		if(empreligion=="" || empreligion==null || empreligion=="undefined"){
			alertmessage('error', 'Please choose the emplyee religion');
			$("#"+empreligion_eid+"").focus();
			return false;
		}
		
		var contactnumber = $("#"+contactnumber_eid+"").val();
		form_data.append("contactnumber", contactnumber);
		if(contactnumber=="" || contactnumber==null || contactnumber=="undefined"){
			alertmessage('error', 'Please enter the emplyee contact number');
			$("#"+contactnumber_eid+"").focus();
			return false;
		}
		
		var emernumber = $("#"+emernumber_eid+"").val();
		form_data.append("emernumber", emernumber);
		
		var presaddress = $("#"+presaddress_eid+"").val();
		form_data.append("presaddress", presaddress);
		
		var prmaddress = $("#"+prmaddress_eid+"").val();
		form_data.append("prmaddress", prmaddress);
		
		var nationality = $("#"+nationality_eid+"").val();
		form_data.append("nationality", nationality);
		
		var eemail = $("#"+eemail_eid+"").val();
		form_data.append("eemail", eemail);
		
		var adharnumber = $("#"+adharnumber_eid+"").val();
		form_data.append("adharnumber", adharnumber);
		
		var pannumber = $("#"+pannumber_eid+"").val();
		form_data.append("pannumber", pannumber);
		
		var uploadadhar = $("#"+uploadadhar_eid+"")[0].files[0];
		form_data.append("uploadadhar", uploadadhar);
		
		var uploadpan = $("#"+uploadpan_eid+"")[0].files[0];
		form_data.append("uploadpan", uploadpan);
		
		var uploadresume = $("#"+uploadresume_eid+"")[0].files[0];
		form_data.append("uploadresume", uploadresume);
		
		var highest_qualification = $("#"+highest_qualification_eid+"").val();
		form_data.append("highest_qualification", highest_qualification);
		
		var specialisation = $("#"+specialisation_eid+"").val();
		form_data.append("specialisation", specialisation);
		
		var institution = $("#"+institution_eid+"").val();
		form_data.append("institution", institution);
		
		var yearofpassing = $("#"+yearofpassing_eid+"").val();
		form_data.append("yearofpassing", yearofpassing);
		
		var uploadeducation = $("#"+uploadeducation_eid+"")[0].files[0];
		form_data.append("uploadeducation", uploadeducation);
		
		var experience = $("#"+experience_eid+"").val();
		form_data.append("experience", experience);
		
		var doj = $("#"+doj_eid+"").val();
		form_data.append("doj", doj);
		
		var intime = $("#"+intime_eid+"").val();
		form_data.append("intime", intime);
		
		var outtime = $("#"+outtime_eid+"").val();
		form_data.append("outtime", outtime);
		
		var paymentmode = $("#"+paymentmode_eid+"").val();
		form_data.append("paymentmode", paymentmode);
		
		var totalsalary = $("#"+totalsalary_eid+"").val();
		form_data.append("totalsalary", totalsalary);
		if(totalsalary=="" || totalsalary==null || totalsalary=="undefined"){
			alertmessage('error', 'Please enter the emplyee salary');
			$("#"+totalsalary_eid+"").focus();
			return false;
		}
		
		var bankname = $("#"+bankname_eid+"").val();
		form_data.append("bankname", bankname);
		if(bankname=="" || bankname==null || bankname=="undefined"){
			alertmessage('error', 'Please enter the bank name of employee');
			$("#"+bankname_eid+"").focus();
			return false;
		}
		
		var accountno = $("#"+accountno_eid+"").val();
		form_data.append("accountno", accountno);
		if(accountno=="" || accountno==null || accountno=="undefined"){
			alertmessage('error', 'Please enter the bank account number');
			$("#"+accountno_eid+"").focus();
			return false;
		}
		
		var ifsccode = $("#"+ifsccode_eid+"").val();
		form_data.append("ifsccode", ifsccode);
		if(ifsccode=="" || ifsccode==null || ifsccode=="undefined"){
			alertmessage('error', 'Please enter the ifsc code of bank');
			$("#"+ifsccode_eid+"").focus();
			return false;
		}
		
		var uploadbank = $("#"+uploadbank_eid+"")[0].files[0];
		form_data.append("uploadbank", uploadbank);
		
		var fathername = $("#"+fathername_eid+"").val();
		form_data.append("fathername", fathername);
		
		var mothername = $("#"+mothername_eid+"").val();
		form_data.append("mothername", mothername);
		
		var loginname = $("#"+loginname_eid+"").val();
		form_data.append("loginname", loginname);
		var btnval = $("#"+submitbtn_eid+"").val();
		$("#"+submitbtn_eid+"").prop("disabled", true);
		$("#"+submitbtn_eid+"").val('Please wait, data saving...');
		$.ajax
	    ({
	      type: "POST",
	      url: baseurl+"modal/employee_modal.php?action=newemployeeregistration",
	      data: form_data,
	      contentType: false,
	      processData: false,
	      cache: false,
	     	dataType:'JSON',
	      success: function(res)
	      {
	      	// console.log();
	      	$("#"+submitbtn_eid+"").prop("disabled", false);
					$("#"+submitbtn_eid+"").val(btnval);
	      	if(res.status=="Success"){
	      		alertmessage('success',res.message);
	      		// location.reload();
	      		$("#newemployeedetails_modal").modal("hide");
	      		
	      	}else{
	      		alertmessage('error',res.message);
	      	}
	        // itax_
	        $(".waitloaderfooter").hide();
	      },beforeSend: function(res){
	        $(".waitloaderfooter").show();
	      }
	    });
}
function uploadfile_onchangefileinput(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,attachedinput_eid,iframidtoview,messageviewid){
	var form_data = new FormData();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);
	var attachedfile = $("#"+attachedinput_eid+"")[0].files[0];
	form_data.append("attachedfile", attachedfile);
	$("#"+messageviewid+"").text('Please wait file is uploding<br/>');
	var rooturl=$("#hidden_rooturl").val();
	$.ajax
    ({
      type: "POST",
      url: baseurl+"modal/request_modal.php?action=uploadrequest_attachement",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
     	dataType:'JSON',
      success: function(res)
      {
      	// itax_
      	if(res.status=="Success"){
      		$("#"+iframidtoview+"").prop('src', "");
      		$("#"+iframidtoview+"").prop('src', rooturl+res.filepath);
      		$("#"+messageviewid+"").text('');
      		alertmessage('success',res.message);
      	}else{
      		alertmessage('error',res.message);
      	}
      }
    });
}

function expensetypehtml(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,expensetypedivid,subaction=null){
	var form_data = new FormData();
	// var baseurl = $("#"+baseurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);
	form_data.append("subaction", subaction);
	// alert(subaction);
	$.ajax
    ({
      type: "POST",
      url: baseurl+"modal/request_modal.php?action=expensetypehtml",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
     	dataType:'html',
      success: function(res)
      {
      	// itax_
      	$("#"+expensetypedivid+"").html(res);
      }
    });
}

function pendingtransferpaymentlist(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,tableid){
	var form_data = new FormData();
	var baseurl = $("#"+routurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);

	$.ajax
    ({
      type: "POST",
      url: baseurl+"modal/transfer_modal.php?action=pendingtrasferlist",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
     	dataType:'JSON',
      success: function(res)
      {
      	// console.log(res);
      	$("#" + tableid + " tbody").empty();
				var ttr = "";
				if (res.length > 0) {
				    var zz = 1;
				    for (var i = 0; i < res.length; i++) {
				        ttr += "<tr data-tempcateid='"+res[i].temppurposecategoryid+"' data-tempsubcateid='"+res[i].temppurposesubcategoryid+"' data-purposestatus='"+res[i].temppurposename+"' data-isinternaltransfer='"+res[i].is_intermediate_transfer+"' data-payto='"+res[i].payto+"' data-paytoid='"+res[i].paytoid+"' data-referenceno='"+ res[i].reference_no +"'>";
				        ttr += "<td style='text-align:center;'>" + zz + "</td>";
				        ttr += "<td style='text-align:center;'><input type='checkbox' name='checkforupdutr' class='checkforupdutr' /></td>";
				        ttr += "<td class='refno_td' style='color:blue; cursor:pointer;'>" + res[i].reference_no + "</td>";
				        ttr += "<td class='booddate_td'>" + res[i].bookdatedsp + "</td>";
				        ttr += "<td class='paymentduedate_td'>" + res[i].paymentduedate + "</td>";
				        ttr += "<td class='payfrombnk_td' style='display:none;'>" + res[i].payfrombankname + " (" + res[i].lastfourdigitfrmacno + ")</td>";
				        ttr += "<td class='paytoname_td'>" + res[i].payto + "</td>";
				        ttr += "<td class='paytoaccno_td' style='display:none;'>" + res[i].paytoaccountno + "</td>";
				        ttr += "<td class='paytobank_td' style='display:none;'>" + res[i].paytobankname + "</td>";
				        ttr += "<td class='paytoifsc_td' style='display:none;'>" + res[i].paytoifsc + "</td>";
				        ttr += "<td class='tempurpose_td'>" + res[i].temppurposename + "</td>";
				        ttr += "<td class='bookamount_td' style='text-align:center;'>" + res[i].book_amount + "</td>";
				        ttr += "<td style='background-color: yellow; display:none;' contenteditable='true' class='utrno_td'></td>";
				        ttr += "<td style='display: none;' class='utrdate_td'><input type='date' class='form-control utrdate_input' value='" + res[i].todaydate + "'></td>";
				        ttr += "<td style='display: none;'><button class='btn btn-sm btn-info updutrnotd' disabled>Update</button><input type='hidden' class='dbsnot_td' value='" + res[i].sno + "'></td>";
				        ttr += "<td class='frmaccountid_td' style='display:none;'>" + res[i].payfromaccountid + "</td>";
				        ttr += "</tr>";
				        zz++;
				    }
				    $("#" + tableid + " tbody").append(ttr);
				}
				$(".waitloaderfooter").hide();
      },beforeSend: function(res){
        $(".waitloaderfooter").show();
      }
    });
}

function readytotransfersalarylist(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,tableid){
	var form_data = new FormData();
	var baseurl = $("#"+routurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);

	$.ajax
    ({
      type: "POST",
      url: baseurl+"modal/transfer_modal.php?action=readytotransfersalarylist",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
     	dataType:'JSON',
      success: function(res)
      {
      	// itax_
      	$("#" + tableid + " tbody").empty();
				var ttr = "";
				if (res.length > 0) {
				    var zz = 1;
				    
						
				    for (var i = 0; i < res.length; i++) {
				        ttr += "<tr data-tempcateid='"+res[i].temppurposecategoryid+"' data-tempsubcateid='"+res[i].temppurposesubcategoryid+"'>";
				        ttr += "<td style='text-align:center;'>" + zz + "</td>";
				        ttr += "<td style='text-align:center;'><input type='checkbox' name='checkforupdutr' class='checkforupdutr' /></td>";
				        ttr += "<td class='refno_td' style='color:blue; cursor:pointer;'>" + res[i].reference_no + "</td>";
				        ttr += "<td class='booddate_td'>" + res[i].bookdatedsp + "</td>";
				        ttr += "<td class='paymentduedate_td'>" + res[i].paymentduedate + "</td>";
				        ttr += "<td class='payfrombnk_td' style='display:none;'>" + res[i].payfrombankname + " (" + res[i].lastfourdigitfrmacno + ")</td>";
				        ttr += "<td class='paytoname_td'>" + res[i].payto + "</td>";
				        ttr += "<td class='paytoaccno_td' style='display:none;'>" + res[i].paytoaccountno + "</td>";
				        ttr += "<td class='paytobank_td' style='display:none;'>" + res[i].paytobankname + "</td>";
				        ttr += "<td class='paytoifsc_td' style='display:none;'>" + res[i].paytoifsc + "</td>";
				        ttr += "<td class='tempurpose_td'>" + res[i].temppurposename + "</td>";
				        ttr += "<td class='bookamount_td' style='text-align:center;'>" + res[i].book_amount + "</td>";
				        ttr += "<td style='background-color: yellow; display:none;' contenteditable='true' class='utrno_td'></td>";
				        ttr += "<td style='display: none;' class='utrdate_td'><input type='date' class='form-control utrdate_input' value='" + res[i].todaydate + "'></td>";
				        ttr += "<td style='display: none;'><button class='btn btn-sm btn-info updutrnotd' disabled>Update</button><input type='hidden' class='dbsnot_td' value='" + res[i].sno + "'></td>";
				        ttr += "<td class='frmaccountid_td' style='display:none;'>" + res[i].payfromaccountid + "</td>";
				        ttr += "</tr>";
				        zz++;
				    }
				    $("#" + tableid + " tbody").append(ttr);
				}
				$(".waitloaderfooter").hide();
      },beforeSend: function(res){
        $(".waitloaderfooter").show();
      }
    });
}

function maintransactionowndetails(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,reference_no,callback){
	var form_data = new FormData();
	var baseurl = $("#"+routurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);
	form_data.append("reference_no", reference_no);
	// alert(baseurl);
	$.ajax
    ({
      type: "POST",
      url: baseurl+"modal/transfer_modal.php?action=maintransactiondetails",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
     	dataType:'JSON',
      success: function(res)
      {
      	// itax_
      	callback(res);
      }
    });
}
function maintransactionbreakdowndetails(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,reference_no,callback){
	var form_data = new FormData();
	var baseurl = $("#"+routurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);
	form_data.append("reference_no", reference_no);
	$.ajax
    ({
      type: "POST",
      url: baseurl+"modal/transfer_modal.php?action=maintransactionbreakdowndetails",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
     	dataType:'JSON',
      success: function(res)
      {
      	// itax_
      	callback(res);
      }
    });
}

function updateutrnotoBookdata(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,utrno,utrdate,dbsno,btnclass, callback){
	var form_data = new FormData();
	var baseurl = $("#"+routurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);
	form_data.append("utrno", utrno);
	form_data.append("utrdate", utrdate);	
	form_data.append("dbsno", dbsno);	

	$.ajax
    ({
      type: "POST",
      url: baseurl+"modal/transfer_modal.php?action=updateutrno",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
     	dataType:'JSON',
      success: function(res)
      {
      	// itax_
      	callback(res);
      }
    });
	
}

function editupdateutrnotoBookdata(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,receiptpath_eid,utrno,utrdate,dbsno, callback){
	var form_data = new FormData();
	var baseurl = $("#"+routurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);

	var transferreceiptpath = $("#"+receiptpath_eid+"")[0].files[0];
	form_data.append("transferreceiptpath", transferreceiptpath);

	form_data.append("utrno", utrno);
	form_data.append("utrdate", utrdate);	
	form_data.append("dbsno", dbsno);

	$.ajax
    ({
      type: "POST",
      url: baseurl+"modal/reporting_modal.php?action=editupdate_utrno",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
     	dataType:'JSON',
      success: function(res)
      {
      	// itax_
      	callback(res);
      }
    });
	
}

function updateutrnoto_multipleBookentry(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,payfromaccountid_eid,entrytblid,transactiontype,utrno,utrdate,transferamount,transferreceipt_eid,clicktobtnid,issplittransaction='',callback){
	var form_data = new FormData();
	var baseurl = $("#"+routurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);

	var loginname = $("#hidden_loginname").val();
	form_data.append("loginname", loginname);
	form_data.append("issplittransaction", issplittransaction);


	var payfromaccountid = $("#"+payfromaccountid_eid+"").val();
	if(payfromaccountid=="" || payfromaccountid==null || payfromaccountid=="null" || payfromaccountid=="undefined"){
		alertmessage('error','Pay from bank is missing');
		return false;
	}
	form_data.append("payfromaccountid", payfromaccountid);
	// if(utrno=="" || utrno==null || utrno=="null" || utrno=="undefined"){
	// 	alertmessage('error','UTR/ Transaction No Missing or Wrong');
	// 	return false;
	// }
	form_data.append("utrno", utrno);
	if(utrdate=="" || utrdate==null || utrdate=="null" || utrdate=="undefined"){
		alertmessage('error','Amount transaction date is missing or Wrong');
		return false;
	}
	form_data.append("utrdate", utrdate);

	// transferamount
	if(transferamount=="" || transferamount==null || transferamount=="null" || transferamount=="undefined"){
		alertmessage('error','Transfered amount should be grater than zero');
		return false;
	}
	form_data.append("transferamount", transferamount);
	form_data.append('transactiontype', transactiontype);
	var transferreceiptpath = $("#"+transferreceipt_eid+"")[0].files[0];
	form_data.append("transferreceiptpath", transferreceiptpath);

	var TableData2;
	TableData2 = storeTblValues2('#'+entrytblid+' tbody');
	TableData2 = JSON.stringify(TableData2);	
	function storeTblValues2(mytable)
	{
			var TableData_A2 = new Array();
			var mytableid = mytable + ' tr';
			$(mytableid).each(function(row, tr){
				if($(tr).closest('tr').find('td').find('input[type="checkbox"]').is(":checked")){
					TableData_A2[row]={
						"referenceno" :$(tr).find('td.refno_ut').text()
						, "paytoname" :$(tr).find('td.payto_ut').text()
						, "bookamount" :$(tr).find('td.bookamount_ut').text()
						, "dbsno" :$(tr).find('td.dbsno_ut').text()
					}
				}
			});
			// TableData_A2.shift();  // first row will be empty - so remove
			return TableData_A2;
	}
	form_data.append('referencenodata', TableData2);
	var btnval=$("#"+clicktobtnid+"").html();
	$("#"+clicktobtnid+"").prop("disabled", true);
	$("#"+clicktobtnid+"").html('Please wait transaction updating..');
	$.ajax
  ({
    type: "POST",
    url: baseurl+"modal/transfer_modal.php?action=updateutrnowithmultipleentry",
    data: form_data,
    contentType: false,
    processData: false,
    cache: false,
   	dataType:'JSON',
    success: function(res)
    {
    	// itax_
    	callback(res);
    	$("#"+clicktobtnid+"").prop("disabled", false);
			$("#"+clicktobtnid+"").html(btnval);
    }
  });
}

function savetodb_invoicedata(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,invoiceno_eid,invoicedate_edit,invoicetype_org,invoiceno_org,invoicedate_org,invoiceamount_org,invoicetabledata_eid,totalamount_inpid,gsttotalamount_inpid,ttldiscountamount_inpid,otherdiscounttotalamount_inpid,grandtotalamount_inpid, loginname_eid,savetodbbtnid){
	$("#"+invorgval_date+"").val('');
	$("#"+invorgval_no+"").val('');
	$("#"+invorgval_amt+"").val(0);
	$("#"+invorgval_type+"").data("invoicedata", '');

	var form_data = new FormData();
	var baseurl = $("#"+baseurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);
	var invoiceno = $("#"+invoiceno_eid+"").val();
	if(invoiceno=="" || invoiceno==null || invoiceno=="undefined"){
		alertmessage('error','Invoice number is missing');
		return false;
	}
	form_data.append("invoiceno", invoiceno);
	
	var invoicedate = $("#"+invoicedate_edit+"").val();
	if(invoicedate=="" || invoicedate==null || invoicedate=="undefined"){
		alertmessage('error','Invoice date is missing');
		return false;
	}
	form_data.append("invoicedate", invoicedate);

	var totalamount = $("#"+totalamount_inpid+"").text();
	form_data.append("totalamount", totalamount);
	var gsttotalamount = $("#"+gsttotalamount_inpid+"").text();
	form_data.append("gsttotalamount", gsttotalamount);
	var ttldiscountamount = $("#"+ttldiscountamount_inpid+"").text();
	form_data.append("ttldiscountamount", ttldiscountamount);
	var otherdiscounttotalamount = $("#"+otherdiscounttotalamount_inpid+"").val();
	form_data.append("otherdiscounttotalamount", otherdiscounttotalamount);
	var grandtotalamount = $("#"+grandtotalamount_inpid+"").text();
	form_data.append("grandtotalamount", grandtotalamount);

	var loginname = $("#"+loginname_eid+"").val();
	form_data.append("loginname", loginname);
	
	var invoiceamount = 0;
	var TableData1;
	TableData1 = storeTblValues1('#'+invoicetabledata_eid+' tbody');
	TableData1 = JSON.stringify(TableData1);
	
		function storeTblValues1(mytable)
		{
			var TableData_A1 = new Array();
			var mytableid = mytable + ' tr';
			$(mytableid).each(function(row, tr){
				TableData_A1[row]={
					"description" :$(tr).find('td').find('input.description_td').val()
					, "hsbn" :$(tr).find('td').find('input.hsbn_td').val()
					, "mrp" :$(tr).find('td').find('input.mrp_td').val()
					, "rate" :$(tr).find('td').find('input.rate_td').val()
					, "qty" :$(tr).find('td').find('input.qty_td').val()
					, "cgstprt" :$(tr).find('td').find('input.cgstprt_td').val()
					,"cgstamt" : $(tr).find('td').find('input.cgstamt_td').val()
					,"sgstprt" : $(tr).find('td').find('input.sgstprt_td').val()
					,"sgstamt" : $(tr).find('td').find('input.sgstamt_td').val()
					,"igstprt" : $(tr).find('td').find('input.igstprt_td').val()
					,"igstamt" : $(tr).find('td').find('input.igstamt_td').val()
					,"distprt" : $(tr).find('td').find('input.distprt_td').val()
					,"distamt" : $(tr).find('td').find('input.distamt_td').val()
					,"famount" : $(tr).find('td').find('input.famount_td').val()
					,"txableamount" : $(tr).find('td').find('input.txableamount_td').val()
				}
				// invoiceamount = parseFloat(invoiceamount,2) + parseFloat($(tr).find('td:eq(14)').find('input.famount_td').val(),2);
			}); 

			// TableData_A1.shift();  // first row will be empty - so remove
			return TableData_A1;
		}
		form_data.append('invoicerowdata', TableData1);
		var invorgval_date = $("#"+invoicedate_org+"").val();
		var invorgval_no = $("#"+invoiceno_org+"").val();
		var invorgval_amt = $("#"+invoiceamount_org+"").val();
		var invorgval_type = $("#"+invoicetype_org+"").val();

		$("#"+invorgval_date+"").val(invoicedate);
		$("#"+invorgval_no+"").val(invoiceno);
		$("#"+invorgval_amt+"").val(parseFloat(grandtotalamount,2));
		$("#"+invorgval_type+"").data("invoicedata", TableData1);
		
		$("#"+invorgval_type+"").data("totalrowsamount", totalamount);
		$("#"+invorgval_type+"").data("totalgstamount", gsttotalamount);
		$("#"+invorgval_type+"").data("totalrowsdiscount", ttldiscountamount);
		$("#"+invorgval_type+"").data("otherdiscount", otherdiscounttotalamount);
		$("#"+invorgval_type+"").data("totalpayableamount", grandtotalamount);
		$("#gstinvoicedetailsmodal").modal("hide");
	
}

function updatepayfrombankdetails_inrequest(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,accountid_eid,requestdbsno,btnid){
	var form_data = new FormData();
	var baseurl = $("#"+baseurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);
	form_data.append("requestdbsno", requestdbsno);
	var accountid = $("#"+accountid_eid+"").val();
	form_data.append("accountid", accountid);
	$.ajax
    ({
      type: "POST",
      url: baseurl+"modal/savetodb.php?action=updatepayfrombank_inrequest",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
     	dataType:'JSON',
      success: function(res)
      {
      	// itax_
      	if(res.length>0){
      		for(var i=0; i<res.length; i++){
      			if(res[i].status="Success"){
      				alertmessage('success',res[i].message);
      				window.location.reload();
      			}else{
      				alertmessage('error',res[i].message);
      				return false;
      			}
      		}
      	}
      }
    });
}

function expensesheetdatat3(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,shortby,shortbyvalue,callback){

	var form_data = new FormData();
	var baseurl = $("#"+routurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);

	form_data.append("shortby", shortby);
	form_data.append("shortbyvalue", shortbyvalue);

	$.ajax
  ({
    type: "POST",
    url: baseurl+"modal/digitization_modal.php?action=expensesheetdata",
    data: form_data,
    contentType: false,
    processData: false,
    cache: false,
   	dataType:'JSON',
    success: function(res)
    {
    	// itax_
    	$(".waitloaderfooter").modal("hide");
    	callback(res);
    },beforeSend: function(res){
    	$(".waitloaderfooter").modal("show");
    }
  });

}
function depositsheetdatat3c(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,shortby,shortbyvalue,callback){

	var form_data = new FormData();
	var baseurl = $("#"+routurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);

	form_data.append("shortby", shortby);
	form_data.append("shortbyvalue", shortbyvalue);

	$.ajax
  ({
    type: "POST",
    url: baseurl+"modal/digitization_modal.php?action=depositsheetdata",
    data: form_data,
    contentType: false,
    processData: false,
    cache: false,
   	dataType:'JSON',
    success: function(res)
    {
    	// itax_
    	callback(res);
    }
  });

}
function checkdescriptioninhistory(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,accountid,description,callback){
	var form_data = new FormData();
	var routurl = $("#"+routurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);
	form_data.append("accountid", accountid);
	form_data.append("description", description);
	$.ajax
  ({
    type: "POST",
    url: routurl+"modal/digitization_modal.php?action=checkreconstructionhistory",
    data: form_data,
    contentType: false,
    processData: false,
    cache: false,
   	dataType:'JSON',
    success: function(res)
    {
    	callback(res);
    }
  });
}
function checkdescriptioninhistory_deposit(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,accountid,description,callback){
	var form_data = new FormData();
	var routurl = $("#"+routurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);
	form_data.append("accountid", accountid);
	form_data.append("description", description);
	$.ajax
  ({
    type: "POST",
    url: routurl+"modal/digitization_modal.php?action=checkreconstructionhistory_deposit",
    data: form_data,
    contentType: false,
    processData: false,
    cache: false,
   	dataType:'JSON',
    success: function(res)
    {
    	callback(res);
    }
  });
}
function getcompanycommunicationhistory(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,accountid,description,transactiontype="",callback){
	var form_data = new FormData();
	var routurl = $("#"+routurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);
	form_data.append("accountid", accountid);
	form_data.append("description", description);
	form_data.append("transactiontype", transactiontype);
	$.ajax
  ({
    type: "POST",
    url: routurl+"modal/digitization_modal.php?action=companycommunicationhistory",
    data: form_data,
    contentType: false,
    processData: false,
    cache: false,
   	dataType:'JSON',
    success: function(res)
    {
    	// itax_
    	callback(res);
    }
  });
}

function unreconciledclosetobookcompany(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,transactiontype,callback){
	var form_data = new FormData();
	var routurl = $("#"+routurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);
	form_data.append("transactiontype", transactiontype);
	$.ajax
  ({
    type: "POST",
    url: routurl+"modal/digitization_modal.php?action=unreconciledclosetobookdatacompany",
    data: form_data,
    contentType: false,
    processData: false,
    cache: false,
   	dataType:'JSON',
    success: function(res)
    {
    	// itax_
    	callback(res);
    }
  });
}

function unreconciledclosetobooksummary(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,transactiontype,callback){
	var form_data = new FormData();
	var routurl = $("#"+routurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);
	form_data.append("transactiontype", transactiontype);
	$.ajax
  ({
    type: "POST",
    url: routurl+"modal/digitization_modal.php?action=unreconciledclosetobookdatasummary",
    data: form_data,
    contentType: false,
    processData: false,
    cache: false,
   	dataType:'JSON',
    success: function(res)
    {
    	// itax_
    	callback(res);
    }
  });
}

function unreconciledclosetobooksummary_deposit(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,transactiontype,reporttype,callback){
	var form_data = new FormData();
	var routurl = $("#"+routurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("companyid", companyid);	
	form_data.append("subcompanyid", subcompanyid);	
	form_data.append("projectid", projectid);
	form_data.append("transactiontype", transactiontype);
	form_data.append("reporttype", reporttype);
	$.ajax
  ({
    type: "POST",
    url: routurl+"modal/digitization_modal.php?action=unreconciledclosetobookdatasummary_deposit",
    data: form_data,
    contentType: false,
    processData: false,
    cache: false,
   	dataType:'JSON',
    success: function(res)
    {
    	// itax_
    	callback(res);
    }
  });
}
function setbankentriesasunknown(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,account_id,callback){
	var form_data = new FormData();
	var baseurl = $("#"+routurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);

	form_data.append("accountid", account_id);

	$.ajax
  ({
    type: "POST",
    url: baseurl+"modal/digitization_modal.php?action=bankentriesasunknown",
    data: form_data,
    contentType: false,
    processData: false,
    cache: false,
   	//dataType:'JSON',
    success: function(res)
    {
    	callback(res);
    }
  });
}

function getbookdetails(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,account_id,callback){
	var form_data = new FormData();
	var baseurl = $("#"+routurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);

	form_data.append("accountid", account_id);

	$.ajax
  ({
    type: "POST",
    url: baseurl+"modal/digitization_modal.php?action=bookdetails",
    data: form_data,
    contentType: false,
    processData: false,
    cache: false,
   	dataType:'JSON',
    success: function(res)
    {
    	//itax_
    	callback(res);
    }
  });
}

function getT3details(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,callback){
	var form_data = new FormData();
	var baseurl = $("#"+routurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);

	$.ajax
  ({
    type: "POST",
    url: baseurl+"modal/digitization_modal.php?action=T3details",
    data: form_data,
    contentType: false,
    processData: false,
    cache: false,
   	dataType:'JSON',
    success: function(res)
    {
    	//itax_
    	callback(res);
    }
  });
}

function getcompanyreplydetails(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,account_id,callback){
	var form_data = new FormData();
	var baseurl = $("#"+routurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);

	form_data.append("accountid", account_id);

	$.ajax
  ({
    type: "POST",
    url: baseurl+"modal/digitization_modal.php?action=companyreplydetails",
    data: form_data,
    contentType: false,
    processData: false,
    cache: false,
   	dataType:'JSON',
    success: function(res)
    {
    	//itax_
    	callback(res);
    }
  });
}

function closetobookdata(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,transactiontype,shortby,shortbyvalue,callback){
	var form_data = new FormData();
	var baseurl = $("#"+routurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);

	form_data.append("transactiontype", transactiontype);
	form_data.append("shortby", shortby);
	form_data.append("shortbyvalue", shortbyvalue);
	// alert(shortby+'=='+shortbyvalue);
	$.ajax
  ({
    type: "POST",
    url: baseurl+"modal/digitization_modal.php?action=unreconciledclosetobookdata",
    data: form_data,
    contentType: false,
    processData: false,
    cache: false,
   	dataType:'JSON',
    success: function(res)
    {
    	// console.log(res);
    	callback(res);
    	// $(".waitloaderfooter").hide();
    },beforeSend: function(res){
      // $(".waitloaderfooter").show();
    }
  });
}



function creditcardsmallexpense(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,subproject_eid,paymenttype_eidname,creditcarduser_eid,creditcardno_eid,requestamount_eid,attpath_eid,purpose_eid,paidtowhom_eid,voiceattpath_eid,textremark_eid,loginid_eid,uploadbtnid,exptype,purposeradio,requestdate_crdp){
	var form_data = new FormData();
	var baseurl = $("#"+routurl_eid+"").val();
	
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);

	var subprojectid = $("#"+subproject_eid+"").val();
	form_data.append("subprojectid", subprojectid);

	var exptype=$("input[name='"+exptype+"']:checked").val();

	form_data.append("exptype", exptype);

	var requesttypename = $("input[name='"+paymenttype_eidname+"']:checked").val();
	form_data.append("requesttypename", requesttypename);

	var creditcarduser = $("#"+creditcarduser_eid+"").val();
	if(creditcarduser=="" || creditcarduser==null || creditcarduser=="undefined"){
		alertmessage('error','Credit card user is missing, Which is mandatory please choose him/her');
		return false;
	}
	form_data.append("creditcarduser", creditcarduser);

	var creditcardusername = $("#"+creditcarduser_eid+" option:selected").text();
	form_data.append("creditcardusername", creditcardusername);

	var creditcardno ="";
	if (exptype=='credit') {
		 creditcardno = $("#"+creditcardno_eid+"").val();
	if(creditcardno=="" || creditcardno==null || creditcardno=="undefined"){
		alertmessage('error','Credit card is missing, Which is mandatory');
		$("#"+creditcardno_eid+"").focus();
		return false;
	}
	}
	form_data.append("creditcardno", creditcardno);

	var requestamount = $("#"+requestamount_eid+"").val();
	if(requestamount=="" || requestamount==null || requestamount=="undefined"){
		alertmessage('error','Request Amount is missing, Which is mandatory');
		return false;
	}
	form_data.append("requestamount", requestamount);

	var filepath = $("#"+attpath_eid+"")[0].files[0];
	form_data.append('filepath', filepath);


	if(purpose_eid){
		var purposetxt=$("#"+purpose_eid+"").val();	
		form_data.append("purposetxt", purposetxt);
	}else{
		form_data.append("purposetxt", purposetxt);
	}
	var beneficiarynamextn="";
	var purposextn=$("input[name="+purposeradio+"]:checked").data("purposename");
	if (purposextn=='Taxi') {
		beneficiarynamextn='Local Transport';
	}else if (purposextn=='Food') {
		beneficiarynamextn='Local Shop';
	}else if (purposextn=='Travel') {
		beneficiarynamextn='Local Transport';
	}else{
		beneficiarynamextn='Local Person';
	}
	form_data.append("paidtowhom", beneficiarynamextn);
	// if(paidtowhom_eid){
	// 	var paidtowhom=$("#"+paidtowhom_eid+"").val();	
	// 	form_data.append("paidtowhom", paidtowhom);
	// }else{
		
	// 	form_data.append("paidtowhom", '');
	// }

	var audioFile = $("#"+voiceattpath_eid+"").data("audioFile");
	form_data.append('audio', audioFile);
	if(audioFile=="undefined"){
		alertmessage('error','Audio recording missing');
		return false;
	}

	var textremark = $("#"+textremark_eid+"").val();
	form_data.append("textremark", textremark);	
	
	var loginid = $("#"+loginid_eid+"").val();
	form_data.append("loginid", loginid);

	var transactiondate = $("#"+requestdate_crdp+"").val();
	form_data.append("transactiondate", transactiondate);

	var btnval = $("#"+uploadbtnid+"").text();
	$("#"+uploadbtnid+"").prop("disabled", true);
	$.ajax
  ({
    type: "POST",
    url: baseurl+"modal/creditcard_modal.php?action=creditcardsmallexpenseentry",
    data: form_data,
    contentType: false,
    processData: false,
    cache: false,
   	dataType:'JSON',
    success: function(res)
    {
    	// itax_
    	$("#"+uploadbtnid+"").text(btnval);
			$("#"+uploadbtnid+"").prop("disabled", false);
    	if(res.length>0){
    		for(var i=0; i<res.length; i++){
    			if(res[i].status=="Success"){
	      		$("#"+voiceattpath_eid+"").prop("src", '');
	      		SweetAlertmessageFadein(res[i].status,res[i].message);
	      	}else{
	      		alertmessage('error',res[i].message);
	      	}
    		}
    	}
    }
  });
}
function imprestinformationsmallexpense(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,subproject_eid,hidden_loginname,hidden_loginid,requestamount_eid,attpath_eid,purpose_eid,paidtowhom_eid,voiceattpath_eid,textremark_eid,loginid_eid,requestdate_crdp,uploadbtnid,creditcard_crdp){
	var form_data = new FormData();
	var baseurl = $("#"+routurl_eid+"").val();
	
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);

	var subprojectid = $("#"+subproject_eid+"").val();
	form_data.append("subprojectid", subprojectid);

	var accid = $("#"+creditcard_crdp+"").val();
	form_data.append("accid", accid);

	var requesttypename = "Imprest";
	form_data.append("requesttypename", requesttypename);

	 var imprestduser = $("#"+hidden_loginname+"").val();
	
	if(imprestduser=="" || imprestduser==null || imprestduser=="undefined"){
		alertmessage('error','Credit card user is missing, Which is mandatory please choose him/her');
		return false;
	}
	form_data.append("imprestduser", imprestduser);

	var imprestusername = $("#"+hidden_loginid+" option:selected").text();
	form_data.append("imprestusername", imprestusername);

	var requestamount = $("#"+requestamount_eid+"").val();
	if(requestamount=="" || requestamount==null || requestamount=="undefined"){
		alertmessage('error','Request Amount is missing, Which is mandatory');
		return false;
	}
	form_data.append("requestamount", requestamount);

	var filepath = $("#"+attpath_eid+"")[0].files[0];
	form_data.append('filepath', filepath);



		var purposetxt=$("#"+purpose_eid+"").val();	
		if(purposetxt=="" || purposetxt==null || purposetxt=="undefined"){
		alertmessage('error','Purpose is missing, Which is mandatory');
		return false;
	}
		form_data.append("purposetxt", purposetxt);
	


		var paidtowhom=$("#"+paidtowhom_eid+"").val();	
		if(paidtowhom=="" || paidtowhom==null || paidtowhom=="undefined"){
		alertmessage('error','Payto is missing, Which is mandatory');
		return false;
	}
		form_data.append("paidtowhom", paidtowhom);
	
	
	form_data.append("beneficiarynamextn", paidtowhom);
	

	var audioFile = $("#"+voiceattpath_eid+"").data("audioFile");
	form_data.append('audio', audioFile);
	if(audioFile=="undefined"){
		alertmessage('error','Audio recording missing');
		return false;
	}

	var textremark = $("#"+textremark_eid+"").val();
	form_data.append("textremark", textremark);	
	
	var loginid = $("#"+loginid_eid+"").val();
	form_data.append("loginid", loginid);

	var transactiondate = $("#"+requestdate_crdp+"").val();
	form_data.append("transactiondate", transactiondate);

	
	$.ajax
  ({
    type: "POST",
    url: baseurl+"modal/expencesinfoofotheraccount.php?action=imprestexpences",
    data: form_data,
    contentType: false,
    processData: false,
    cache: false,
   	dataType:'JSON',
    success: function(res)
    {
    	// itax_
    	//$("#"+uploadbtnid+"").text(btnval);
			$("#"+uploadbtnid+"").prop("disabled", false);
    	if(res.length>0){
    		for(var i=0; i<res.length; i++){
    			if(res[i].status=="Success"){
	      		$("#"+voiceattpath_eid+"").prop("src", '');
	      		SweetAlertmessageFadein(res[i].status,res[i].message);
	      	}else{
	      		alertmessage('error',res[i].message);
	      	}
    		}
    	}
    }
  });
}
function imprestinformationsmallexpenseAll(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,subproject_eid,paytoaccountid,requestamount,purposename,paidtowhom,textremark,requestdate,attpath_eid,savetobtnid){
	var form_data = new FormData();
	var baseurl = $("#"+routurl_eid+"").val();
	
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);

	var subprojectid = $("#"+subproject_eid+"").val();
	form_data.append("subprojectid", subprojectid);

	if(paytoaccountid=="" || paytoaccountid==null || paytoaccountid=="undefined"){
		alertmessage('error','Imprest Account is missing, Which is mandatory');
		return false;
	}
	form_data.append("paytoaccountid", paytoaccountid);
	var requesttypename = "Imprest";
	form_data.append("requesttypename", requesttypename);

	if(requestamount=="" || requestamount==null || requestamount=="undefined"){
		alertmessage('error','Request Amount is missing, Which is mandatory');
		return false;
	}
	form_data.append("requestamount", requestamount);

	if(purposename=="" || purposename==null || purposename=="undefined"){
		alertmessage('error','Purpose is missing, Which is mandatory');
		return false;
	}
	form_data.append("purposetxt", purposename);

	if(paidtowhom=="" || paidtowhom==null || paidtowhom=="undefined"){
		alertmessage('error','paidtowhom is missing, Which is mandatory');
		return false;
	}
	form_data.append("paidtowhom", paidtowhom);
	
	form_data.append("textremark", textremark);	
	

	var filepath = $("#"+attpath_eid+"")[0].files[0];
	form_data.append('filepath', filepath);
	
	form_data.append("requestdate", requestdate);
	
	var loginid = $("body").data("loginid");
	form_data.append("loginid", loginid);

	
	$.ajax
  ({
    type: "POST",
    url: baseurl+"modal/expencesinfoofotheraccount.php?action=imprestexpences",
    data: form_data,
    contentType: false,
    processData: false,
    cache: false,
   	dataType:'JSON',
    success: function(res)
    {
    	$(".waitloaderfooter").hide();
    	if(res.length>0){
    		for(var i=0; i<res.length; i++){
    			if(res[i].status=="Success"){
	      		SweetAlertmessageFadein(res[i].status,res[i].message);
	      	}else{
	      		alertmessage('error',res[i].message);
	      	}
    		}
    	}
    },beforeSend: function(res){
      $(".waitloaderfooter").show();
    }
  });
}
function cashinformationsmallexpense(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,subproject_eid,hidden_loginname,hidden_loginid,requestamount_eid,attpath_eid,purpose_eid,paidtowhom_eid,voiceattpath_eid,textremark_eid,loginid_eid,requestdate_crdp,uploadbtnid,creditcard_crdp){
	var form_data = new FormData();
	var baseurl = $("#"+routurl_eid+"").val();
	
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);

	var accid = $("#"+creditcard_crdp+"").val();
	form_data.append("accid", accid);
	
	var subprojectid = $("#"+subproject_eid+"").val();
	form_data.append("subprojectid", subprojectid);

	var requesttypename = "Cash";
	form_data.append("requesttypename", requesttypename);

	 var imprestduser = $("#"+hidden_loginname+"").val();
	
	if(imprestduser=="" || imprestduser==null || imprestduser=="undefined"){
		alertmessage('error','Credit card user is missing, Which is mandatory please choose him/her');
		return false;
	}
	form_data.append("imprestduser", imprestduser);

	var imprestusername = $("#"+hidden_loginid+" option:selected").text();
	form_data.append("imprestusername", imprestusername);

	var requestamount = $("#"+requestamount_eid+"").val();
	if(requestamount=="" || requestamount==null || requestamount=="undefined"){
		alertmessage('error','Request Amount is missing, Which is mandatory');
		return false;
	}
	form_data.append("requestamount", requestamount);

	var filepath = $("#"+attpath_eid+"")[0].files[0];
	form_data.append('filepath', filepath);



		var purposetxt=$("#"+purpose_eid+"").val();	
		if(purposetxt=="" || purposetxt==null || purposetxt=="undefined"){
		alertmessage('error','Purpose is missing, Which is mandatory');
		return false;
	}
		form_data.append("purposetxt", purposetxt);
	


		var paidtowhom=$("#"+paidtowhom_eid+"").val();	
		if(paidtowhom=="" || paidtowhom==null || paidtowhom=="undefined"){
		alertmessage('error','Payto is missing, Which is mandatory');
		return false;
	}
		form_data.append("paidtowhom", paidtowhom);
	
	
	form_data.append("beneficiarynamextn", paidtowhom);
	

	var audioFile = $("#"+voiceattpath_eid+"").data("audioFile");
	form_data.append('audio', audioFile);
	if(audioFile=="undefined"){
		alertmessage('error','Audio recording missing');
		return false;
	}

	var textremark = $("#"+textremark_eid+"").val();
	form_data.append("textremark", textremark);	
	
	var loginid = $("#"+loginid_eid+"").val();
	form_data.append("loginid", loginid);

	var transactiondate = $("#"+requestdate_crdp+"").val();
	form_data.append("transactiondate", transactiondate);

	
	$.ajax
  ({
    type: "POST",
    url: baseurl+"modal/expencesinfoofotheraccount.php?action=cashexpences",
    data: form_data,
    contentType: false,
    processData: false,
    cache: false,
   	dataType:'JSON',
    success: function(res)
    {
    	// itax_
    	//$("#"+uploadbtnid+"").text(btnval);
			$("#"+uploadbtnid+"").prop("disabled", false);
    	if(res.length>0){
    		for(var i=0; i<res.length; i++){
    			if(res[i].status=="Success"){
	      		$("#"+voiceattpath_eid+"").prop("src", '');
	      		SweetAlertmessageFadein(res[i].status,res[i].message);
	      	}else{
	      		alertmessage('error',res[i].message);
	      	}
    		}
    	}
    }
  });
}
function cashinformationsmallexpenseAll(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,subproject_eid,hidden_loginname,hidden_loginid,requestamount_eid,attpath_eid,purpose_eid,paidtowhom_eid,voiceattpath_eid,textremark_eid,loginid_eid,requestdate_crdp,uploadbtnid,creditcard_crdp){
	var form_data = new FormData();
	var baseurl = $("#"+routurl_eid+"").val();
	
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);

	var subprojectid = $("#"+subproject_eid+"").val();
	form_data.append("subprojectid", subprojectid);

	var accid = $("#"+creditcard_crdp+"").val();
	form_data.append("accid", accid);

	var requesttypename = "Cash";
	form_data.append("requesttypename", requesttypename);

	 var imprestduser = $("#"+hidden_loginname+"").val();
	
	if(imprestduser=="" || imprestduser==null || imprestduser=="undefined"){
		alertmessage('error','Credit card user is missing, Which is mandatory please choose him/her');
		return false;
	}
	form_data.append("imprestduser", imprestduser);

	var imprestusername = $("#"+hidden_loginid+" option:selected").text();
	form_data.append("imprestusername", imprestusername);

	// var requestamount = $("#"+requestamount_eid+"").val();
	var requestamount = requestamount_eid;
	if(requestamount=="" || requestamount==null || requestamount=="undefined"){
		alertmessage('error','Request Amount is missing, Which is mandatory');
		return false;
	}
	form_data.append("requestamount", requestamount);

	var filepath = $("#"+attpath_eid+"")[0].files[0];
	form_data.append('filepath', filepath);



		// var purposetxt=$("#"+purpose_eid+"").val();	
	var purposetxt=purpose_eid;	
		if(purposetxt=="" || purposetxt==null || purposetxt=="undefined"){
		alertmessage('error','Purpose is missing, Which is mandatory');
		return false;
	}
		form_data.append("purposetxt", purposetxt);
	


		// var paidtowhom=$("#"+paidtowhom_eid+"").val();
		var paidtowhom=paidtowhom_eid;		
		if(paidtowhom=="" || paidtowhom==null || paidtowhom=="undefined"){
		alertmessage('error','Payto is missing, Which is mandatory');
		return false;
	}
		form_data.append("paidtowhom", paidtowhom);
	
	
	form_data.append("beneficiarynamextn", paidtowhom);
	

	var audioFile = $("#"+voiceattpath_eid+"").data("audioFile");
	form_data.append('audio', audioFile);
	if(audioFile=="undefined"){
		alertmessage('error','Audio recording missing');
		return false;
	}

	// var textremark = $("#"+textremark_eid+"").val();
	var textremark = textremark_eid;
	form_data.append("textremark", textremark);	
	
	var loginid = $("#"+loginid_eid+"").val();
	form_data.append("loginid", loginid);

	// var transactiondate = $("#"+requestdate_crdp+"").val();
	var transactiondate = requestdate_crdp;
	form_data.append("transactiondate", transactiondate);

	
	$.ajax
  ({
    type: "POST",
    url: baseurl+"modal/expencesinfoofotheraccount.php?action=cashexpences",
    data: form_data,
    contentType: false,
    processData: false,
    cache: false,
   	dataType:'JSON',
    success: function(res)
    {
    	// itax_
    	//$("#"+uploadbtnid+"").text(btnval);
			$("#"+uploadbtnid+"").prop("disabled", false);
    	if(res.length>0){
    		for(var i=0; i<res.length; i++){
    			if(res[i].status=="Success"){
	      		$("#"+voiceattpath_eid+"").prop("src", '');
	      		SweetAlertmessageFadein(res[i].status,res[i].message);
	      	}else{
	      		//alertmessage('error',res[i].message);
	      	}
    		}
    	}
    }
  });
}
function creditcardinformationsmallexpense(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,subproject_eid,hidden_loginname,hidden_loginid,requestamount_eid,attpath_eid,purpose_eid,paidtowhom_eid,voiceattpath_eid,textremark_eid,loginid_eid,requestdate_crdp,uploadbtnid,creditcard_crdp){
	var form_data = new FormData();
	var baseurl = $("#"+routurl_eid+"").val();
	
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);

	var subprojectid = $("#"+subproject_eid+"").val();
	form_data.append("subprojectid", subprojectid);

	var requesttypename = "Imprest";
	form_data.append("requesttypename", requesttypename);

	 var imprestduser = $("#"+hidden_loginname+"").val();
	
	if(imprestduser=="" || imprestduser==null || imprestduser=="undefined"){
		alertmessage('error','Credit card user is missing, Which is mandatory please choose him/her');
		return false;
	}
	form_data.append("imprestduser", imprestduser);

	var imprestusername = $("#"+hidden_loginid+" option:selected").text();
	form_data.append("imprestusername", imprestusername);

	var requestamount = $("#"+requestamount_eid+"").val();
	if(requestamount=="" || requestamount==null || requestamount=="undefined"){
		alertmessage('error','Request Amount is missing, Which is mandatory');
		return false;
	}
	form_data.append("requestamount", requestamount);

	var filepath = $("#"+attpath_eid+"")[0].files[0];
	form_data.append('filepath', filepath);



		var purposetxt=$("#"+purpose_eid+"").val();	
		if(purposetxt=="" || purposetxt==null || purposetxt=="undefined"){
		alertmessage('error','Purpose is missing, Which is mandatory');
		return false;
	}
		form_data.append("purposetxt", purposetxt);
	


		var paidtowhom=$("#"+paidtowhom_eid+"").val();	
		if(paidtowhom=="" || paidtowhom==null || paidtowhom=="undefined"){
		alertmessage('error','Payto is missing, Which is mandatory');
		return false;
	}
		form_data.append("paidtowhom", paidtowhom);
	
	
	form_data.append("beneficiarynamextn", paidtowhom);

	var creditcard_id=$("#"+creditcard_crdp).val();
	var creditcard_no=$("#"+creditcard_crdp+ " option:selected").text();

	form_data.append("creditcard_id", creditcard_id);
	form_data.append("creditcard_no", creditcard_no);
	

	var audioFile = $("#"+voiceattpath_eid+"").data("audioFile");
	form_data.append('audio', audioFile);
	if(audioFile=="undefined"){
		alertmessage('error','Audio recording missing');
		return false;
	}

	var textremark = $("#"+textremark_eid+"").val();
	form_data.append("textremark", textremark);	
	
	var loginid = $("#"+loginid_eid+"").val();
	form_data.append("loginid", loginid);

	var transactiondate = $("#"+requestdate_crdp+"").val();
	form_data.append("transactiondate", transactiondate);
	$.ajax
  ({
    type: "POST",
    url: baseurl+"modal/expencesinfoofotheraccount.php?action=creditcardexpences",
    data: form_data,
    contentType: false,
    processData: false,
    cache: false,
   	dataType:'JSON',
    success: function(res)
    {
			$("#"+uploadbtnid+"").prop("disabled", false);
    	if(res.length>0){
    		for(var i=0; i<res.length; i++){
    			if(res[i].status=="Success"){
	      		$("#"+voiceattpath_eid+"").prop("src", '');
	      		SweetAlertmessageFadein(res[i].status,res[i].message);
	      	}else{
	      		alertmessage('error',res[i].message);
	      	}
    		}
    	}
    }
  });
}
function creditcardinformationsmallexpenseAll(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,subproject_eid,hidden_loginname,hidden_loginid,requestamount_eid,attpath_eid,purpose_eid,paidtowhom_eid,voiceattpath_eid,textremark_eid,loginid_eid,requestdate_crdp,uploadbtnid,creditcard_crdp){
	var form_data = new FormData();
	var baseurl = $("#"+routurl_eid+"").val();
	
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);

	var subprojectid = $("#"+subproject_eid+"").val();
	form_data.append("subprojectid", subprojectid);

	var accid = $("#"+creditcard_crdp+"").val();
	form_data.append("creditcard_id", accid);

	var requesttypename = "Credit Card";
	form_data.append("requesttypename", requesttypename);

	 var imprestduser = $("#"+hidden_loginname+"").val();
	
	if(imprestduser=="" || imprestduser==null || imprestduser=="undefined"){
		alertmessage('error','Credit card user is missing, Which is mandatory please choose him/her');
		return false;
	}
	form_data.append("imprestduser", imprestduser);

	var imprestusername = $("#"+hidden_loginid+" option:selected").text();
	form_data.append("imprestusername", imprestusername);

	// var requestamount = $("#"+requestamount_eid+"").val();
	var requestamount = requestamount_eid;
	if(requestamount=="" || requestamount==null || requestamount=="undefined"){
		alertmessage('error','Request Amount is missing, Which is mandatory');
		return false;
	}
	form_data.append("requestamount", requestamount);

	var filepath = $("#"+attpath_eid+"")[0].files[0];
	form_data.append('filepath', filepath);



		// var purposetxt=$("#"+purpose_eid+"").val();	
	var purposetxt=purpose_eid;	
		if(purposetxt=="" || purposetxt==null || purposetxt=="undefined"){
		alertmessage('error','Purpose is missing, Which is mandatory');
		return false;
	}
		form_data.append("purposetxt", purposetxt);
	


		// var paidtowhom=$("#"+paidtowhom_eid+"").val();
		var paidtowhom=paidtowhom_eid;		
		if(paidtowhom=="" || paidtowhom==null || paidtowhom=="undefined"){
		alertmessage('error','Payto is missing, Which is mandatory');
		return false;
	}
		form_data.append("paidtowhom", paidtowhom);
	
	
	form_data.append("beneficiarynamextn", paidtowhom);
	

	var audioFile = $("#"+voiceattpath_eid+"").data("audioFile");
	form_data.append('audio', audioFile);
	if(audioFile=="undefined"){
		alertmessage('error','Audio recording missing');
		return false;
	}

	// var textremark = $("#"+textremark_eid+"").val();
	var textremark = textremark_eid;
	form_data.append("textremark", textremark);	
	
	var loginid = $("#"+loginid_eid+"").val();
	form_data.append("loginid", loginid);

	// var transactiondate = $("#"+requestdate_crdp+"").val();
	var transactiondate = requestdate_crdp;
	form_data.append("transactiondate", transactiondate);

	
	$.ajax
  ({
    type: "POST",
    url: baseurl+"modal/expencesinfoofotheraccount.php?action=creditcardexpences",
    data: form_data,
    contentType: false,
    processData: false,
    cache: false,
   	dataType:'JSON',
    success: function(res)
    {
    	// itax_
    	//$("#"+uploadbtnid+"").text(btnval);
			$("#"+uploadbtnid+"").prop("disabled", false);
    	if(res.length>0){
    		for(var i=0; i<res.length; i++){
    			if(res[i].status=="Success"){
	      		$("#"+voiceattpath_eid+"").prop("src", '');
	      		SweetAlertmessageFadein(res[i].status,res[i].message);
	      	}else{
	      		//alertmessage('error',res[i].message);
	      	}
    		}
    	}
    }
  });
}
function getreportsofsmallexpenc(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,subproject_eid,selectedval,loginid,tblname,creditcarduser_crdp) {
	var form_data = new FormData();
	var baseurl = $("#"+routurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);
	var subprojectid = $("#"+subproject_eid+"").val();
	form_data.append("subprojectid", subprojectid);
	var exptype=selectedval;
	form_data.append("exptype", exptype);
	form_data.append("loginid", loginid);
	var userid=$("#"+creditcarduser_crdp+"").val();
	form_data.append("userid", userid);
	$.ajax
  ({
    type: "POST",
    url: baseurl+"modal/creditcard_modal.php?action=reportsmallexpenseentry",
    data: form_data,
    contentType: false,
    processData: false,
    cache: false,
   	dataType:'JSON',
    success: function(res)
    {
    	 // itax_
    
    	if(res.length>0){
    		$('#'+tblname+" tbody").empty();
    		var j=1;
    		for(var i=0; i<res.length; i++){
    			$('#'+tblname+" tbody").append("<tr><td data-label='Sno'>"+j+"</td><td data-label='Date' class='cashdate'>"+res[i].date+"</td><td data-label='Amount' class='cashamount'>"+res[i].amount+"</td><td data-label='Purpose' class='cashpurpose'>"+res[i].purpose+"</td><td data-label='Purpose Code' class='cashpurposecode'>"+res[i].purposecode+"</td><td data-label='Description' class='cashdescription'>"+res[i].description+"</td><td data-label='Attachment' style='display:none;' class='cashdocuments'>"+res[i].documentpath+"</td><td data-label='Action' class='tdhide'><input type='checkbox' name='cashcheckbox' class='chkinput cantrol-form'/></td><td data-label='Sno' style='display:none;'>"+res[i].sno+"</td><td data-label='Sno' style='display:none;'>"+res[i].beneficiaryname+"</td></tr>");
    			j++;
    		}
    		if (exptype=='credit') {
    			$('.tdhide').hide();
    		}else{
    			$('.tdhide').show();
    		}
    	}
    }
  });
}

//Start GST Calculation as per Rate
/*$(document).on("input", ".ratecal", function(event){
	event.target;
	var tableId = $(this).closest('table').attr('id'); // Get the ID of the closest table
	var row = $(this).closest("tr");
	var mrp = getCellValue(row.find("input.mrp_td").val());
	var qty = getCellValue(row.find("input.qty_td").val());
	var cgstprt = getCellValue(row.find("input.cgstprt_td").val());
	var sgstprt = getCellValue(row.find("input.sgstprt_td").val());
	var igstprt = getCellValue(row.find("input.igstprt_td").val());
	var distprt = getCellValue(row.find("input.distprt_td").val());
	var ttlmrp = (parseFloat(mrp)*parseFloat(qty)).toFixed(2);
	var rate = getCellValue(row.find("input.rate_td").val());
	var ttlrate = (parseFloat(rate)*parseFloat(qty)).toFixed(2);
	//discount percent and amount calculation
  var distamount = (getCellValue(ttlrate)*getCellValue(row.find("input.distprt_td").val())/100).toFixed(2);
 	getCellValue(row.find("input.distamt_td").val(distamount));
 	var ttltaxableamount =parseFloat(ttlrate) - parseFloat(distamount);
  
  //cgst percent and amount calculation
  var cgstamt = (ttltaxableamount*getCellValue(row.find("input.cgstprt_td").val())/100).toFixed(2);
  	getCellValue(row.find("input.cgstamt_td").val(cgstamt));
  //sgst percent and amount calculation
  var sgstamt = (ttltaxableamount*getCellValue(row.find("input.sgstprt_td").val())/100).toFixed(2);
  	getCellValue(row.find("input.sgstamt_td").val(sgstamt));
  //igst percent and amount calculation
  var igstamt = (ttltaxableamount*getCellValue(row.find("input.igstprt_td").val())/100).toFixed(2);
  	getCellValue(row.find("input.igstamt_td").val(igstamt));

  row.find("input.txableamount_td").val(ttltaxableamount);
  row.find("input.famount_td").val(ttltaxableamount);
  grandtotalgstamont('totalamount','gsttotalamount','ttldiscountamount','discounttotalamount','grandtotalamount','famount_td','cgstamt_td','sgstamt_td','igstamt_td','distamt_td',tableId);
});

$(document).on("input", ".qtycal", function(event){
	event.target;
	var tableId = $(this).closest('table').attr('id'); // Get the ID of the closest table
	var row = $(this).closest("tr");
	var mrp = getCellValue(row.find("input.mrp_td").val());
	var qty = getCellValue(row.find("input.qty_td").val());
	var cgstprt = getCellValue(row.find("input.cgstprt_td").val());
	var sgstprt = getCellValue(row.find("input.sgstprt_td").val());
	var igstprt = getCellValue(row.find("input.igstprt_td").val());
	var distprt = getCellValue(row.find("input.distprt_td").val());
	var ttlmrp = (parseFloat(mrp)*parseFloat(qty)).toFixed(2);
	var rate = getCellValue(row.find("input.rate_td").val());
	var ttlrate = (parseFloat(rate)*parseFloat(qty)).toFixed(2);
	
	//discount percent and amount calculation
  var distamount = (getCellValue(ttlrate)*getCellValue(row.find("input.distprt_td").val())/100).toFixed(2);
 	getCellValue(row.find("input.distamt_td").val(distamount));
 	var ttltaxableamount =parseFloat(ttlrate) -parseFloat(distamount);

  //cgst percent and amount calculation
  var cgstamt = (ttltaxableamount*getCellValue(row.find("input.cgstprt_td").val())/100).toFixed(2);
  	getCellValue(row.find("input.cgstamt_td").val(cgstamt));
  //sgst percent and amount calculation
  var sgstamt = (ttltaxableamount*getCellValue(row.find("input.sgstprt_td").val())/100).toFixed(2);
  	getCellValue(row.find("input.sgstamt_td").val(sgstamt));
  //igst percent and amount calculation
  var igstamt = (ttltaxableamount*getCellValue(row.find("input.igstprt_td").val())/100).toFixed(2);
  	getCellValue(row.find("input.igstamt_td").val(igstamt));

  row.find("input.txableamount_td").val(ttltaxableamount);
  row.find("input.famount_td").val(ttltaxableamount);
  grandtotalgstamont('totalamount','gsttotalamount','ttldiscountamount','discounttotalamount','grandtotalamount','famount_td','cgstamt_td','sgstamt_td','igstamt_td','distamt_td',tableId);
});

$(document).on("input", ".cgstcal", function(event){
	event.target;
	var tableId = $(this).closest('table').attr('id'); // Get the ID of the closest table
	var row = $(this).closest("tr");
	var mrp = getCellValue(row.find("input.mrp_td").val());
	var qty = getCellValue(row.find("input.qty_td").val());
	var ttlmrp = (parseFloat(mrp)*parseFloat(qty)).toFixed(2);
	var rate = getCellValue(row.find("input.rate_td").val());
	var ttlrate = (parseFloat(rate)*parseFloat(qty)).toFixed(2);
	//discount percent and amount calculation
	var distamount =getCellValue(row.find("input.distamt_td").val());
  var distprct =getCellValue(row.find("input.distamt_td").val()); 

  var ttltaxableamount =parseFloat(ttlrate) -parseFloat(distamount);
  row.find("input.txableamount_td").val(ttltaxableamount);
  //cgst percent and amount calculation
  var cgstamt =0.00;
  var sgstamt =0.00;
  var igstamt =0.00;
  var cgstprct =0;
  var sgstprct =0;
  var igstprct =0;
  if($(this).hasClass('cgstprt_td')){
  	if($(this).val()=="" || $(this).val()=="undefined" || $(this).val()=="null"){
  		row.find("input.cgstprt_td").val(0);
  	}
  	cgstamt = (getCellValue(row.find("input.txableamount_td").val())*getCellValue(row.find("input.cgstprt_td").val()||0)/100).toFixed(2);
  	getCellValue(row.find("input.cgstamt_td").val(cgstamt));
  	
  	getCellValue(row.find("input.sgstamt_td").val(cgstamt));
  	getCellValue(row.find("input.sgstprt_td").val(getCellValue(row.find("input.cgstprt_td").val()||0)));
		
		getCellValue(row.find("input.igstprt_td").val(0));
  	getCellValue(row.find("input.igstamt_td").val('0.00'));
  }

  if($(this).hasClass('cgstamt_td')){
  	if($(this).val()=="" || $(this).val()=="undefined" || $(this).val()=="null"){
  		row.find("input.cgstamt_td").val('0.00');
  	}
  	cgstprct = (100*getCellValue(row.find("input.cgstamt_td").val()||0)/getCellValue(row.find("input.txableamount_td").val())).toFixed(2);
  	getCellValue(row.find("input.cgstprt_td").val(cgstprct));

  	getCellValue(row.find("input.sgstprt_td").val(cgstprct));
  	getCellValue(row.find("input.sgstamt_td").val(getCellValue(row.find("input.cgstamt_td").val()||0)));

  	getCellValue(row.find("input.igstprt_td").val('0'));
  	getCellValue(row.find("input.igstamt_td").val('0.00'));
  }
  
  row.find("input.famount_td").val(ttltaxableamount);
  grandtotalgstamont('totalamount','gsttotalamount','ttldiscountamount','discounttotalamount','grandtotalamount','famount_td','cgstamt_td','sgstamt_td','igstamt_td','distamt_td',tableId);
});

$(document).on("input", ".sgstcal", function(event){
	event.target;
	var tableId = $(this).closest('table').attr('id'); // Get the ID of the closest table
	var row = $(this).closest("tr");
	var mrp = getCellValue(row.find("input.mrp_td").val());
	var qty = getCellValue(row.find("input.qty_td").val());
	var ttlmrp = (parseFloat(mrp)*parseFloat(qty)).toFixed(2);
	var rate = getCellValue(row.find("input.rate_td").val());
	var ttlrate = (parseFloat(rate)*parseFloat(qty)).toFixed(2);
	//discount percent and amount calculation
	var distamount =getCellValue(row.find("input.distamt_td").val());
  var distprct =getCellValue(row.find("input.distamt_td").val()); 

  var ttltaxableamount =parseFloat(ttlrate) -parseFloat(distamount);
  row.find("input.txableamount_td").val(ttltaxableamount);
  //cgst percent and amount calculation
  var cgstamt =0.00;
  var sgstamt =0.00;
  var igstamt =0.00;
  var cgstprct =0;
  var sgstprct =0;
  var igstprct =0;
  if($(this).hasClass('sgstprt_td')){
  	if($(this).val()=="" || $(this).val()=="undefined" || $(this).val()=="null"){
  		row.find("input.sgstprt_td").val(0);
  	}
  	sgstamt = (getCellValue(row.find("input.txableamount_td").val())*getCellValue(row.find("input.sgstprt_td").val()||0)/100).toFixed(2);
  	getCellValue(row.find("input.sgstamt_td").val(sgstamt));  	
  	getCellValue(row.find("input.cgstamt_td").val(sgstamt));

  	getCellValue(row.find("input.cgstprt_td").val(getCellValue(row.find("input.sgstprt_td").val())));
		
		getCellValue(row.find("input.igstprt_td").val(0));
  	getCellValue(row.find("input.igstamt_td").val('0.00'));
  }

  if($(this).hasClass('sgstamt_td')){
  	if($(this).val()=="" || $(this).val()=="undefined" || $(this).val()=="null"){
  		row.find("input.sgstamt_td").val('0.00');
  	}
  	sgstprct = (100*getCellValue(row.find("input.sgstamt_td").val()||'0.00')/getCellValue(row.find("input.txableamount_td").val())).toFixed(2);
  	getCellValue(row.find("input.sgstprt_td").val(sgstprct));

  	getCellValue(row.find("input.cgstprt_td").val(sgstprct));
  	getCellValue(row.find("input.cgstamt_td").val(getCellValue(row.find("input.sgstamt_td").val())));

  	getCellValue(row.find("input.igstprt_td").val('0'));
  	getCellValue(row.find("input.igstamt_td").val('0.00'));
  }
  
  row.find("input.famount_td").val(ttltaxableamount);
  grandtotalgstamont('totalamount','gsttotalamount','ttldiscountamount','discounttotalamount','grandtotalamount','famount_td','cgstamt_td','sgstamt_td','igstamt_td','distamt_td',tableId);
});

$(document).on("input", ".igstcal", function(event){
	event.target;
	var tableId = $(this).closest('table').attr('id'); // Get the ID of the closest table
	var row = $(this).closest("tr");
	var mrp = getCellValue(row.find("input.mrp_td").val());
	var qty = getCellValue(row.find("input.qty_td").val());
	var ttlmrp = (parseFloat(mrp)*parseFloat(qty)).toFixed(2);
	var rate = getCellValue(row.find("input.rate_td").val());
	var ttlrate = (parseFloat(rate)*parseFloat(qty)).toFixed(2);
	//discount percent and amount calculation
	var distamount =getCellValue(row.find("input.distamt_td").val());
  var distprct =getCellValue(row.find("input.distamt_td").val()); 

  var ttltaxableamount =parseFloat(ttlrate) -parseFloat(distamount);
  row.find("input.txableamount_td").val(ttltaxableamount);
  //cgst percent and amount calculation
  var cgstamt =0.00;
  var sgstamt =0.00;
  var igstamt =0.00;
  var cgstprct =0;
  var sgstprct =0;
  var igstprct =0;
  if($(this).hasClass('igstprt_td')){
  	if($(this).val()=="" || $(this).val()=="undefined" || $(this).val()=="null"){
  		row.find("input.igstprt_td").val(0);
  	}
  	igstamt = (getCellValue(row.find("input.txableamount_td").val())*getCellValue(row.find("input.igstprt_td").val()||0)/100).toFixed(2);
  	getCellValue(row.find("input.igstamt_td").val(igstamt));
  	
  	getCellValue(row.find("input.cgstprt_td").val('0'));
  	getCellValue(row.find("input.cgstamt_td").val('0.00'));
  	getCellValue(row.find("input.sgstprt_td").val('0'));
  	getCellValue(row.find("input.sgstamt_td").val('0.00'));
  }

  if($(this).hasClass('igstamt_td')){
  	if($(this).val()=="" || $(this).val()=="undefined" || $(this).val()=="null"){
  		row.find("input.igstamt_td").val('0.00');
  	}
  	igstprct = (100*getCellValue(row.find("input.igstamt_td").val())/getCellValue(row.find("input.txableamount_td").val()||'0.00')).toFixed(2);
  	getCellValue(row.find("input.igstprt_td").val(igstprct));
  	getCellValue(row.find("input.cgstprt_td").val('0'));
  	getCellValue(row.find("input.cgstamt_td").val('0.00'));
  	getCellValue(row.find("input.sgstprt_td").val('0'));
  	getCellValue(row.find("input.sgstamt_td").val('0.00'));
  }
  
  row.find("input.famount_td").val(ttltaxableamount);
  grandtotalgstamont('totalamount','gsttotalamount','ttldiscountamount','otherdiscounttotalamount','grandtotalamount','famount_td','cgstamt_td','sgstamt_td','igstamt_td','distamt_td',tableId);
});

$(document).on("input", ".distcal", function(event){
	event.target;
	var tableId = $(this).closest('table').attr('id'); // Get the ID of the closest table

	var row = $(this).closest("tr");
	var mrp = getCellValue(row.find("input.mrp_td").val());
	var qty = getCellValue(row.find("input.qty_td").val());
	var cgstprt = getCellValue(row.find("input.cgstprt_td").val());
	var sgstprt = getCellValue(row.find("input.sgstprt_td").val());
	var igstprt = getCellValue(row.find("input.igstprt_td").val());
	var distprt = getCellValue(row.find("input.distprt_td").val());
	var ttlmrp = (parseFloat(mrp)*parseFloat(qty)).toFixed(2);
	var rate = getCellValue(row.find("input.rate_td").val());
	var ttlrate = (parseFloat(rate)*parseFloat(qty)).toFixed(2);
	var ttltaxableamount =parseFloat(ttlrate) -parseFloat(getCellValue(row.find("input.distamt_td").val()));
  row.find("input.txableamount_td").val(ttltaxableamount);

	//discount percent and amount calculation
  if($(this).hasClass('distprt_td')){
  	if($(this).val()=="" || $(this).val()=="undefined" || $(this).val()=="null"){
  		row.find("input.distprt_td").val(0);
  	}
  	var distamount = (getCellValue(ttlrate)*getCellValue(row.find("input.distprt_td").val()||'0.00')/100).toFixed(2);
  	getCellValue(row.find("input.distamt_td").val(distamount));
  }
  if($(this).hasClass('distamt_td')){
  	if($(this).val()=="" || $(this).val()=="undefined" || $(this).val()=="null"){
  		row.find("input.distprt_td").val('0.00');
  	}
  	var distprct = (100*getCellValue(row.find("input.distamt_td").val()||0)/getCellValue(ttlrate)).toFixed(2);
  	getCellValue(row.find("input.distprt_td").val(distprct));
  	// $(".invrowcell").trigger("input");
  }


  getCellValue(row.find("input.cgstamt_td").val((getCellValue(row.find("input.txableamount_td").val())*getCellValue(row.find("input.cgstprt_td").val())/100).toFixed(2)));

  getCellValue(row.find("input.sgstamt_td").val((getCellValue(row.find("input.txableamount_td").val())*getCellValue(row.find("input.sgstprt_td").val())/100).toFixed(2)));
  getCellValue(row.find("input.igstamt_td").val((getCellValue(row.find("input.txableamount_td").val())*getCellValue(row.find("input.igstprt_td").val())/100).toFixed(2)));
  
  row.find("input.famount_td").val(ttltaxableamount);
  grandtotalgstamont('totalamount','gsttotalamount','ttldiscountamount','otherdiscounttotalamount','grandtotalamount','famount_td','cgstamt_td','sgstamt_td','igstamt_td','distamt_td',tableId);
});
*/
//End GST calculation as per Rate


// reporting
function requestdetails_callback(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,datefrom_eid,dateto_eid,callback){
	var form_data = new FormData();
	var baseurl = $("#"+routurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);	
	var datefrom = $("#"+datefrom_eid+"").val();
	form_data.append("datefrom", datefrom);
	var dateto = $("#"+dateto_eid+"").val();
	form_data.append("dateto", dateto);
	form_data.append("loginid", $("body").data('loginid'));
	form_data.append("loginname", $("body").data('loginname'));
	form_data.append("loginroleid", $("body").data('loginroleid'));
	$.ajax
    ({
      	type: "POST",
      	url: baseurl+"modal/reporting_modal.php?action=createdrequestdetailswithcallback",
      	data: form_data,
      	contentType: false,
      	processData: false,
      	cache: false,
     		dataType:'JSON',
      	success: function(res)
      	{
      		callback(res);
	    		$(".waitloaderfooter").hide();
		    },beforeSend: function(res){
		      $(".waitloaderfooter").show();
		    }
    });
}

function imprestsummarydetails_callback(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,datefrom_eid,dateto_eid,imprestpaytoid,callback){
	var form_data = new FormData();
	var baseurl = $("#"+routurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);	
	var datefrom = $("#"+datefrom_eid+"").val();
	form_data.append("datefrom", datefrom);
	var dateto = $("#"+dateto_eid+"").val();
	form_data.append("dateto", dateto);
	form_data.append("imprestpaytoid", imprestpaytoid);
	form_data.append("loginid", $("body").data('loginid'));
	form_data.append("loginname", $("body").data('loginname'));
	form_data.append("loginroleid", $("body").data('loginroleid'));
	$.ajax
    ({
      	type: "POST",
      	url: baseurl+"modal/reporting_modal.php?action=imprestsummarydetails",
      	data: form_data,
      	contentType: false,
      	processData: false,
      	cache: false,
     		dataType:'JSON',
      	success: function(res)
      	{
      		callback(res);
	    		$(".waitloaderfooter").hide();
		    },beforeSend: function(res){
		      $(".waitloaderfooter").show();
		    }
    });
}

function impreststatementdetails_callback(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,datefrom_eid,dateto_eid,imprestpaytoid,callback){
	var form_data = new FormData();
	var baseurl = $("#"+routurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);	
	var datefrom = $("#"+datefrom_eid+"").val();
	form_data.append("datefrom", datefrom);
	var dateto = $("#"+dateto_eid+"").val();
	form_data.append("dateto", dateto);
	form_data.append("imprestpaytoid", imprestpaytoid);
	form_data.append("loginid", $("body").data('loginid'));
	form_data.append("loginname", $("body").data('loginname'));
	form_data.append("loginroleid", $("body").data('loginroleid'));
	$.ajax
    ({
      	type: "POST",
      	url: baseurl+"modal/reporting_modal.php?action=impreststatementdetails",
      	data: form_data,
      	contentType: false,
      	processData: false,
      	cache: false,
     		dataType:'JSON',
      	success: function(res)
      	{
      		callback(res);
	    		$(".waitloaderfooter").hide();
		    },beforeSend: function(res){
		      $(".waitloaderfooter").show();
		    }
    });
}

function loanstatementdetails_callback(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,datefrom_eid,dateto_eid,paytoid,callback){
	var form_data = new FormData();
	var baseurl = $("#"+routurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);	
	var datefrom = $("#"+datefrom_eid+"").val();
	form_data.append("datefrom", datefrom);
	var dateto = $("#"+dateto_eid+"").val();
	form_data.append("dateto", dateto);
	form_data.append("paytoid", paytoid);
	form_data.append("loginid", $("body").data('loginid'));
	form_data.append("loginname", $("body").data('loginname'));
	form_data.append("loginroleid", $("body").data('loginroleid'));
	$.ajax
    ({
      	type: "POST",
      	url: baseurl+"modal/reporting_modal.php?action=loanstatementdetails",
      	data: form_data,
      	contentType: false,
      	processData: false,
      	cache: false,
     		dataType:'JSON',
      	success: function(res)
      	{
      		// console.log(res);
      		callback(res);
	    		$(".waitloaderfooter").hide();
		    },beforeSend: function(res){
		      $(".waitloaderfooter").show();
		    }
    });
}

function cashstatementdetails_callback(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,datefrom_eid,dateto_eid,imprestpaytoid,callback){
	var form_data = new FormData();
	var baseurl = $("#"+routurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);	
	var datefrom = $("#"+datefrom_eid+"").val();
	form_data.append("datefrom", datefrom);
	var dateto = $("#"+dateto_eid+"").val();
	form_data.append("dateto", dateto);
	form_data.append("cashpaytoid", imprestpaytoid);
	form_data.append("loginid", $("body").data('loginid'));
	form_data.append("loginname", $("body").data('loginname'));
	form_data.append("loginroleid", $("body").data('loginroleid'));
	$.ajax
    ({
      	type: "POST",
      	url: baseurl+"modal/reporting_modal.php?action=cashstatementdetails",
      	data: form_data,
      	contentType: false,
      	processData: false,
      	cache: false,
     		dataType:'JSON',
      	success: function(res)
      	{
      		callback(res);
	    		$(".waitloaderfooter").hide();
		    },beforeSend: function(res){
		      $(".waitloaderfooter").show();
		    }
    });
}

function requestbreakdowndetails_callback(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,requestnoval,callback){
	var form_data = new FormData();
	var baseurl = $("#"+routurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);
	form_data.append("requestnoval", requestnoval);
	form_data.append("loginid", $("body").data('loginid'));
	form_data.append("loginname", $("body").data('loginname'));
	form_data.append("loginroleid", $("body").data('loginroleid'));
	$.ajax
    ({
      	type: "POST",
      	url: baseurl+"modal/reporting_modal.php?action=requestbreakdowndetailscallback",
      	data: form_data,
      	contentType: false,
      	processData: false,
      	cache: false,
     		dataType:'JSON',
      	success: function(res)
      	{
      		// console.log(res);
      		callback(res);
	    		$(".waitloaderfooter").hide();
		    },beforeSend: function(res){
		      $(".waitloaderfooter").show();
		    }
    });
}

function maintransactionstatusdetails_callback(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,datefrom_eid,dateto_eid,datafilter_eid,callback){
	var form_data = new FormData();
	var baseurl = $("#"+routurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);	
	var datefrom = $("#"+datefrom_eid+"").val();
	form_data.append("datefrom", datefrom);
	var dateto = $("#"+dateto_eid+"").val();
	form_data.append("dateto", dateto);
	var datafiltertxt = $("#"+datafilter_eid+"").val();
	form_data.append("datafiltertxt", datafiltertxt);
	form_data.append("loginid", $("body").data('loginid'));
	form_data.append("loginname", $("body").data('loginname'));
	form_data.append("loginroleid", $("body").data('loginroleid'));
	$.ajax
    ({
      	type: "POST",
      	url: baseurl+"modal/reporting_modal.php?action=maintransactiondetailscallback",
      	data: form_data,
      	contentType: false,
      	processData: false,
      	cache: false,
     		dataType:'JSON',
      	success: function(res)
      	{
      		callback(res);
	    		$(".waitloaderfooter").hide();
		    },beforeSend: function(res){
		      $(".waitloaderfooter").show();
		    }
    });
}

function maintransactionreportdetails_callback(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,datefrom_eid,dateto_eid,datafilter_eid,callback){
	var form_data = new FormData();
	var baseurl = $("#"+routurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);	
	var datefrom = $("#"+datefrom_eid+"").val();
	form_data.append("datefrom", datefrom);
	var dateto = $("#"+dateto_eid+"").val();
	form_data.append("dateto", dateto);
	var datafiltertxt = $("#"+datafilter_eid+"").val();
	form_data.append("datafiltertxt", datafiltertxt);
	form_data.append("loginid", $("body").data('loginid'));
	form_data.append("loginname", $("body").data('loginname'));
	form_data.append("loginroleid", $("body").data('loginroleid'));
	$.ajax
    ({
      	type: "POST",
      	url: baseurl+"modal/reporting_modal.php?action=paymenttransferdetailscallback",
      	data: form_data,
      	contentType: false,
      	processData: false,
      	cache: false,
     		dataType:'JSON',
      	success: function(res)
      	{
      		callback(res);
	    	}
    });
}

function missingdocumentreferencedetails_callback(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,datefrom_eid,dateto_eid,callback){
	var form_data = new FormData();
	var baseurl = $("#"+routurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);	
	var datefrom = $("#"+datefrom_eid+"").val();
	form_data.append("datefrom", datefrom);
	var dateto = $("#"+dateto_eid+"").val();
	form_data.append("dateto", dateto);
	form_data.append("loginid", $("body").data('loginid'));
	form_data.append("loginname", $("body").data('loginname'));
	form_data.append("loginroleid", $("body").data('loginroleid'));
	$.ajax
    ({
      	type: "POST",
      	url: baseurl+"modal/reporting_modal.php?action=missingdocumentreferencedetails",
      	data: form_data,
      	contentType: false,
      	processData: false,
      	cache: false,
     		dataType:'JSON',
      	success: function(res)
      	{
      		callback(res);
	    	}
    });
}

function update_missingdocumentwithsno(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,dbsnoval,filepath_eid,clicktobt_eid){
	var form_data = new FormData();
	var baseurl = $("#"+routurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);
	var filepath = $("#"+filepath_eid+"")[0].files[0];
	form_data.append("filepath", filepath);
	form_data.append("dbsnoval", dbsnoval);
	form_data.append("loginid", $("body").data('loginid'));
	form_data.append("loginname", $("body").data('loginname'));
	form_data.append("loginroleid", $("body").data('loginroleid'));
	$.ajax
    ({
      	type: "POST",
      	url: baseurl+"modal/reporting_modal.php?action=updatemissingdocumentwithsno",
      	data: form_data,
      	contentType: false,
      	processData: false,
      	cache: false,
     		dataType:'JSON',
      	success: function(res)
      	{
      		// callback(res);
      		// itax_
      		if(res.status="Success"){
      			alertmessage('success', res.message);
    				setTimeout(function(){
						   window.location.reload(1);
						}, 2000);
      		}else{
      			alertmessage('error', res.message);
      		}
	    	}
    });
}

function missing_tdschalandocument(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,datefrom_eid,dateto_eid,tableid){
	var form_data = new FormData();
	var baseurl = $("#"+routurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);	
	var datefrom = $("#"+datefrom_eid+"").val();
	form_data.append("datefrom", datefrom);
	var dateto = $("#"+dateto_eid+"").val();
	form_data.append("dateto", dateto);
	form_data.append("loginid", $("body").data('loginid'));
	form_data.append("loginname", $("body").data('loginname'));
	form_data.append("loginroleid", $("body").data('loginroleid'));
	$.ajax
    ({
      	type: "POST",
      	url: baseurl+"modal/tds_modal.php?action=missing_tdschalandocument",
      	data: form_data,
      	contentType: false,
      	processData: false,
      	cache: false,
     		dataType:'html',
      	success: function(res)
      	{
      		$("#"+tableid+" tbody").empty();
      		$("#"+tableid+" tbody").html(res);
	    	}
    });
}

function update_tdschalandocument(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,chalanno_eid,chalandate_eid,filepath_eid,referenceno,clicktobtn_eid){
	var form_data = new FormData();
	var baseurl = $("#"+routurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);
	var chalanno = $("#"+chalanno_eid+"").val();
	if(chalanno=="" || chalanno==null || chalanno=="undefined"){
		alertmessage('error','chalan number is missing');
		return false;
	}
	form_data.append("chalanno", chalanno);
	var chalandate = $("#"+chalandate_eid+"").val();
	if(chalandate=="" || chalandate==null || chalandate=="undefined"){
		alertmessage('error','chalan date is missing');
		return false;
	}
	form_data.append("chalandate", chalandate);

	var filepath = $("#"+filepath_eid+"")[0].files[0];
	form_data.append("filepath", filepath);
	form_data.append("referenceno", referenceno);
	form_data.append("loginid", $("body").data('loginid'));
	form_data.append("loginname", $("body").data('loginname'));
	form_data.append("loginroleid", $("body").data('loginroleid'));
	$.ajax
    ({
      	type: "POST",
      	url: baseurl+"modal/tds_modal.php?action=update_tdschalandocument",
      	data: form_data,
      	contentType: false,
      	processData: false,
      	cache: false,
     		dataType:'JSON',
      	success: function(res)
      	{
      		// callback(res);
      		// itax_
      		if(res.status="Success"){
      			alertmessage('success', res.message);
    				// setTimeout(function(){
						//    window.location.reload(1);
						// }, 2000);
      		}else{
      			alertmessage('error', res.message);
      		}
	    	}
    });
}

function referencebreakdowndetails_callback(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,referencenoval,callback){
	var form_data = new FormData();
	var baseurl = $("#"+routurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);
	form_data.append("referencenoval", referencenoval);
	form_data.append("loginid", $("body").data('loginid'));
	form_data.append("loginname", $("body").data('loginname'));
	form_data.append("loginroleid", $("body").data('loginroleid'));
	$.ajax
    ({
      	type: "POST",
      	url: baseurl+"modal/reporting_modal.php?action=referencebreakdowndetailscallback",
      	data: form_data,
      	contentType: false,
      	processData: false,
      	cache: false,
     		dataType:'JSON',
      	success: function(res)
      	{
      		callback(res);
	    	}
    });
}

function RemoveEntryFromexpesnsedata(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,expense_tblid){
	var form_data = new FormData();
	var baseurl = $("#"+baseurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);

	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);
	
	var loginname = $("body").data("loginname");
	form_data.append("loginname", loginname);
	var ExpData;
	ExpData = storeTblValues1('#' + expense_tblid + '');
	ExpData = JSON.stringify(ExpData);
	function storeTblValues1(mytable) {
	    var TableData_Exp = [];
	    var mytableid = mytable + ' tr';
	    $(mytableid).each(function(row, tr) {
	        if ($(tr).find('input.checkboxradio').is(":checked")) {
	            var dbsno = $(tr).data('t3dbsno');  // Get the 't3dbsno' data attribute
	            if (dbsno) {  // Only add to array if dbsno is truthy (non-empty)
	                TableData_Exp.push({
	                    "dbsno": dbsno
	                });
	            }
	        }    
	    });

	    // Return the filtered array (empty rows will be excluded automatically)
	    return TableData_Exp;
	}
	// Append the data to form data
	form_data.append('expensedatadbsno', ExpData);
	$.ajax
  ({
    type: "POST",
    url: baseurl+"modal/digitization_modal.php?action=RemoveEntryFromexpesnsedata",
    data: form_data,
    contentType: false,
    processData: false,
    cache: false,
   	dataType:'JSON',
    success: function(res)
    {
      // console.log(res);
    	if(res.status=="Success"){
    		alertmessage('success',res.message);
    		location.reload();
    	}else{
    		alertmessage('error',res.message);
    	}
    	$(".waitloaderfooter").hide();
    },beforeSend: function(res){
      $(".waitloaderfooter").show();
    }
  });
}

function purpooseassign_toexpesnsedata(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,purposecode_eid,expense_tblid,clictobtnid){
	var form_data = new FormData();
	var baseurl = $("#"+baseurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);

	
	
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);

	var purposecode = "";
	$("#" + purposecode_eid + " tbody tr").each(function () {
    // Check if any radio button is selected in the current row
    if ($(this).find("td").find("input[type='radio']:checked").length > 0) {
        // Get the "upurposecode" data attribute of the row
        purposecode = $(this).data("upurposecode");
    }
	});
	form_data.append("purposecode", purposecode);
	if(purposecode=="" || purposecode==null || purposecode=="undefined"){
		alertmessage('error','Purpose code is missing');
		$("#"+purposecode_eid+"").focus();
		return false;
	}

	var loginname = $("body").data("loginname");
	form_data.append("loginname", loginname);
	var ExpData;
	ExpData = storeTblValues1('#' + expense_tblid + '');
	ExpData = JSON.stringify(ExpData);

	function storeTblValues1(mytable) {
	    var TableData_Exp = [];
	    var mytableid = mytable + ' tr';

	    $(mytableid).each(function(row, tr) {
	        // Check if the checkbox is checked
	        if ($(tr).find('input.checkboxradio').is(":checked")) {
	            var dbsno = $(tr).data('t3dbsno');  // Get the 't3dbsno' data attribute
	            if (dbsno) {  // Only add to array if dbsno is truthy (non-empty)
	                TableData_Exp.push({
	                    "dbsno": dbsno
	                });
	            }
	        }    
	    });

	    // Return the filtered array (empty rows will be excluded automatically)
	    return TableData_Exp;
	}
	// Append the data to form data
	form_data.append('expensedatadbsno', ExpData);
	var btnval = $("#"+clictobtnid+"").html();
	$("#"+clictobtnid+"").prop("disabled", true);
	$("#"+clictobtnid+"").html("Please wait data updating");

	$.ajax
  ({
    type: "POST",
    url: baseurl+"modal/digitization_modal.php?action=assignpurposetoexpensesheet",
    data: form_data,
    contentType: false,
    processData: false,
    cache: false,
   	dataType:'JSON',
    success: function(res)
    {
      // console.log(res);
    	if(res.status=="Success"){
    		alertmessage('success',res.message);
    		location.reload();
    	}else{
    		alertmessage('error',res.message);
    		$("#"+clictobtnid+"").prop("disabled", false);
				$("#"+clictobtnid+"").html(btnval);
    	}
    	$(".waitloaderfooter").hide();
    },beforeSend: function(res){
      $(".waitloaderfooter").show();
    }
  });
}

function purpooseassign_todepositdata(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,purposecode_eid,expense_tblid,clictobtnid,callback){
	var form_data = new FormData();
	var baseurl = $("#"+baseurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);

	var purposecode = $("#"+purposecode_eid+"").val();
	form_data.append("purposecode", purposecode);

	var loginname = $("body").data("loginname");
	form_data.append("loginname", loginname);
	if(purposecode=="" || purposecode==null || purposecode=="undefined"){
		alertmessage('error','Purpose code is missing');
		$("#"+purposecode_eid+"").focus();
		return false;
	}
	var strsnos = $("#"+clictobtnid+"").data("sno");
	var TableData_apj = strsnos.split("|").map(num => ({ dbsno: parseInt(num) }));
	var Authprj;
	Authprj = JSON.stringify(TableData_apj);
	form_data.append('expensedatadbsno', Authprj);
		 // console.log(Authprj);
		$.ajax
    ({
      type: "POST",
      url: baseurl+"modal/digitization_modal.php?action=assignpurposetodepositsheet",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
     dataType:'JSON',
      success: function(res)
      {
        // console.log(res);
        callback(res);
      	$(".waitloaderfooter").hide();
	    },beforeSend: function(res){
	      $(".waitloaderfooter").show();
	    }
    });
}
function paytoassign_todepositdata(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,payto_eid,expense_tblid,clictobtnid){
	var form_data = new FormData();
	var baseurl = $("#"+baseurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);

	var paytoid = $("#"+payto_eid+"").val();
	form_data.append("paytoid", paytoid);

	
	if(paytoid=="" || paytoid==null || paytoid=="undefined"){
		alertmessage('error','Payto name is missing');
		$("#"+purposecode_eid+"").focus();
		return false;
	}
	var Authprj;
		Authprj = storeTblValues1('#'+expense_tblid+'');
		Authprj = JSON.stringify(Authprj);
		function storeTblValues1(mytable)
		{
			var TableData_apj = new Array();
			var mytableid = mytable + ' tr';
			$(mytableid).each(function(row, tr){
				if($(tr).closest('tr').find('td').find('input.checkboxradio').is(":checked")){
					TableData_apj[row]={
						"dbsno" :$(tr).closest('tr').data('t3dbsno')
					}
				}    
			}); 
			TableData_apj.shift();  // first row will be empty - so remove
			return TableData_apj;
		}
		form_data.append('expensedatadbsno', Authprj);
		var loginname = $("body").data("loginname");
		form_data.append("loginname", loginname);
		// console.log(Authprj);
		$.ajax
    ({
      type: "POST",
      url: baseurl+"modal/digitization_modal.php?action=assignpaytotodepositsheet",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
     dataType:'JSON',
      success: function(res)
      {
        // console.log(res);
      	if(res.status=="Success"){
      		alertmessage('success',res.message);
      		location.reload();
      	}else{
      		alertmessage('error',res.message);
      	}
      	$(".waitloaderfooter").hide();
	    },beforeSend: function(res){
	      $(".waitloaderfooter").show();
	    }
    });
}

function paytoassign_toexpesnsedata(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,payto_eid,expense_tblid,clictobtnid){
	var form_data = new FormData();
	var baseurl = $("#"+baseurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);

	var paytoid = $("#"+payto_eid+"").val();
	form_data.append("paytoid", paytoid);

	
	if(paytoid=="" || paytoid==null || paytoid=="undefined"){
		alertmessage('error','Payto name is missing');
		$("#"+purposecode_eid+"").focus();
		return false;
	}
	var Authprj;
		Authprj = storeTblValues1('#'+expense_tblid+'');
		Authprj = JSON.stringify(Authprj);
		function storeTblValues1(mytable)
		{
			var TableData_apj = new Array();
			var mytableid = mytable + ' tr';
			$(mytableid).each(function(row, tr){
				if($(tr).closest('tr').find('td').find('input.checkboxradio').is(":checked")){
					TableData_apj[row]={
						"dbsno" :$(tr).closest('tr').data('t3dbsno')
					}
				}    
			}); 
			TableData_apj.shift();  // first row will be empty - so remove
			return TableData_apj;
		}
		form_data.append('expensedatadbsno', Authprj);
		var loginname = $("body").data("loginname");
		form_data.append("loginname", loginname);
		// console.log(Authprj);
		$.ajax
    ({
      type: "POST",
      url: baseurl+"modal/digitization_modal.php?action=assignpaytotoexpensesheet",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
     dataType:'JSON',
      success: function(res)
      {
        // console.log(res);
      	if(res.status=="Success"){
      		alertmessage('success',res.message);
      		location.reload();
      	}else{
      		alertmessage('error',res.message);
      	}
      	$(".waitloaderfooter").hide();
	    },beforeSend: function(res){
	      $(".waitloaderfooter").show();
	    }
    });
}

// created by Chatur singh
function verifyprovidepurposename_addifmissing(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,expense_tblid,clictobtnid){
	
	var baseurl = $("#"+baseurl_eid+"").val();
	var k=0;
	var companyid = $("#"+companyid_eid+"").val();
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	var projectid = $("#"+projectid_eid+"").val();
	var loginname = $("body").data("loginname");
	var Authprj;
		Authprj = storeTblValues1('#'+expense_tblid+' tbody');
		 
		Authprj = JSON.stringify(Authprj);
		function storeTblValues1(mytable)
		{
			var TableData_apj = new Array();
			var mytableid = mytable + ' tr';
			$(mytableid).each(function(row, tr){

				if($(tr).find('td.providedpurpose_td').text()!=""){
					TableData_apj[k]={
						"dbsno" :$(tr).closest('tr').data('t3dbsno'),
						"purpose" :$(tr).find('td.providedpurpose_td').text()
					}
					k++;
				}	    
			}); 
			return TableData_apj;
		}

	var form_data = new FormData();
	form_data.append("companyid", companyid);	
	form_data.append("subcompanyid", subcompanyid);		
	form_data.append("projectid", projectid);
	form_data.append('expensedatadbsno', Authprj);
	form_data.append("loginname", loginname);
		// console.log(Authprj);
		$.ajax
    ({
      type: "POST",
      url: baseurl+"modal/digitization_modal.php?action=verifyprovidepurposename_addifmissing",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
     dataType:'JSON',
      success: function(res)
      {
        // console.log(res);
      	if(res.status=="Success"){
      		alertmessage('success',res.message);
      		location.reload();
      	}else{
      		alertmessage('error',res.message);
      	}
	      $(".waitloaderfooter").hide();
		  },beforeSend: function(res){
		    $(".waitloaderfooter").show();
		  }
    });
}
// created by Chatur singh
function verifypaytowithexpesnsedata_addifmissing(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,expense_tblid,clictobtnid){
	var form_data = new FormData();
	var baseurl = $("#"+baseurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);
		var k=0;
		var Authprj;
		// alert(expense_tblid);
		Authprj = storeTblValues1('#'+expense_tblid+' tbody'); 
		Authprj = JSON.stringify(Authprj);
		function storeTblValues1(mytable)
		{
			var TableData_apj = new Array();
			var mytableid = mytable + ' tr';
			$(mytableid).each(function(row, tr){

				if($(tr).closest('tr').data('epaytoid')==""){
					TableData_apj[k]={
						"dbsno" :$(tr).closest('tr').data('t3dbsno'),
						// "purpose" :$(tr).find('td:eq(8)').text()
					}
					k++; 
				}   
			}); 
			return TableData_apj;
		}

		form_data.append('expensedatadbsno', Authprj);
		var loginname = $("body").data("loginname");
		form_data.append("loginname", loginname);
		// console.log(Authprj);
		$.ajax
    ({
      type: "POST",
      url: baseurl+"modal/digitization_modal.php?action=verifypaytotoexpensesheet_addifmissing",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
     dataType:'JSON',
      success: function(res)
      {
        // console.log(res);
      	if(res.status=="Success"){
      		alertmessage('success',res.message);
      		location.reload();
      	}else{
      		alertmessage('error',res.message);
      	}
      	$(".waitloaderfooter").hide();
	    },beforeSend: function(res){
	      $(".waitloaderfooter").show();
	    }
    });
}



function verifypaytotoexpensesheet_addifmissing_credit(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,expense_tblid,clictobtnid){
	var form_data = new FormData();
	var baseurl = $("#"+baseurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);
	var Authprj;
		Authprj = storeTblValues1('#'+expense_tblid+'');
		Authprj = JSON.stringify(Authprj);
		function storeTblValues1(mytable)
		{
			var TableData_apj = new Array();
			var mytableid = mytable + ' tr';
			$(mytableid).each(function(row, tr){
				TableData_apj[row]={
					"dbsno" :$(tr).closest('tr').data('t3dbsno')
				}    
			}); 
			TableData_apj.shift();  // first row will be empty - so remove
			return TableData_apj;
		}
		form_data.append('expensedatadbsno', Authprj);
		var loginname = $("body").data("loginname");
		form_data.append("loginname", loginname);
		// console.log(Authprj);
		$.ajax
    ({
      type: "POST",
      url: baseurl+"modal/digitization_modal.php?action=verifypaytotoexpensesheet_addifmissing_credit",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
     dataType:'JSON',
      success: function(res)
      {
        // console.log(res);
      	if(res.status=="Success"){
      		alertmessage('success',res.message);
      		location.reload();
      	}else{
      		alertmessage('error',res.message);
      	}
      }
    });
}

function verifypaytowithdepositdata_addifmissing(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,expense_tblid,clictobtnid){
	
	var baseurl = $("#"+baseurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	var projectid = $("#"+projectid_eid+"").val();
	var loginname = $("body").data("loginname");
	var k=0;
	var Authprj;
		Authprj = storeTblValues1('#'+expense_tblid+' tbody'); 
		Authprj = JSON.stringify(Authprj);
		function storeTblValues1(mytable)
		{
			var TableData_apj = new Array();
			var mytableid = mytable + ' tr';
			$(mytableid).each(function(row, tr){
				if(!$(tr).find('td:eq(3)').text()){
						TableData_apj[k]={
							"dbsno" :$(tr).closest('tr').data('t3dbsno'),
							"payeename" :$(tr).find('td:eq(4)').text()
						} 
					k++; 
				}		   
			}); 
			return TableData_apj;
		}

	var form_data = new FormData();
	form_data.append("companyid", companyid);	
	form_data.append("subcompanyid", subcompanyid);		
	form_data.append("projectid", projectid);
	form_data.append('expensedatadbsno', Authprj);
	form_data.append("loginname", loginname);
		  
		$.ajax
    ({
      type: "POST",
      url: baseurl+"modal/digitization_modal.php?action=check_automaticeupdate_payee_deposit",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType:'JSON',
      success: function(res)
      {
        // console.log(res);
      	if(res.status=="Success"){
      		alertmessage('success',res.message);
      		location.reload();
      	}else{
      		alertmessage('error',res.message);
      	}
      }
    }); 
}

function check_automaticeupdate_purpose_deposit(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,expense_tblid,clictobtnid){
	
	var baseurl = $("#"+baseurl_eid+"").val();
	var k=0;
	var companyid = $("#"+companyid_eid+"").val();
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	var projectid = $("#"+projectid_eid+"").val();
	var loginname = $("body").data("loginname");
	var Authprj;
		Authprj = storeTblValues1('#'+expense_tblid+' tbody');
		 
		Authprj = JSON.stringify(Authprj);
		function storeTblValues1(mytable)
		{
			var TableData_apj = new Array();
			var mytableid = mytable + ' tr';
			$(mytableid).each(function(row, tr){

				if(!$(tr).find('td.providedpurpose_td').text()){
					TableData_apj[k]={
						"dbsno" :$(tr).closest('tr').data('t3dbsno'),
						"purpose" :$(tr).find('td.providedpurpose_td').text()
					}
					k++;
				}	    
			}); 
			return TableData_apj;
		}

	var form_data = new FormData();
	form_data.append("companyid", companyid);	
	form_data.append("subcompanyid", subcompanyid);		
	form_data.append("projectid", projectid);
	form_data.append('expensedatadbsno', Authprj);
	form_data.append("loginname", loginname);
		// console.log(Authprj);
		$.ajax
    ({
      type: "POST",
      url: baseurl+"modal/digitization_modal.php?action=check_automaticeupdate_purpose_deposit",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
     // dataType:'JSON',
      success: function(res)
      {
        // console.log(res);
      	if(res.status=="Success"){
      		alertmessage('success',res.message);
      		location.reload();
      	}else{
      		alertmessage('error',res.message);
      	}
      }
    });
}

// function createbookfromexpensedataT3(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,expense_tblid,clictobtnid,actionfrom=""){
	
// 	var baseurl = $("#"+baseurl_eid+"").val();
// 	var companyid = $("#"+companyid_eid+"").val();
// 	var subcompanyid = $("#"+subcompanyid_eid+"").val();
// 	var projectid = $("#"+projectid_eid+"").val();
// 	var loginname = $("body").data("loginname");
// 	var k=0;
// 	var Authprj;
// 	Authprj = storeTblValues1('#'+expense_tblid+' tbody'); 
// 	Authprj = JSON.stringify(Authprj);
// 	function storeTblValues1(mytable)
// 	{
// 		var TableData_apj = new Array();
// 		var mytableid = mytable + ' tr';
// 		$(mytableid).each(function(row, tr){ 
// 				if(actionfrom!="sale invoice"){
// 					if($(tr).closest('tr').data('epaytoid') && $(tr).find('td:eq(6)').text()){	
// 							TableData_apj[k]={ 
// 								"dbsno" :$(tr).closest('tr').data('t3dbsno'),
// 								"clientid" :$(tr).closest('tr').data('epaytoid'),
// 								"date" :$(tr).find('td:eq(1)').text(),
// 								"chequeno" :$(tr).find('td:eq(2)').text(),
// 								"invoiceno" :"",
// 								"payeename" :$(tr).find('td:eq(3)').text(),
// 								"amountwithouttax" :$(tr).find('td:eq(5)').text(),
// 								"tax" :"0",
// 								"totalamount" :$(tr).find('td:eq(5)').text(),
// 								"purposecode" :$(tr).find('td:eq(6)').text(),
// 								"description" :$(tr).find('td:eq(9)').text()
// 							} 
// 						k++; 
// 					}
// 				}	else if(actionfrom=="sale invoice"){
// 					if($(tr).closest('tr').data('epaytoid') && $(tr).find('td:eq(8)').text()){	
// 							TableData_apj[k]={ 
// 								"dbsno" :$(tr).closest('tr').data('t3dbsno'),
// 								"clientid" :$(tr).closest('tr').data('epaytoid'),
// 								"date" :$(tr).find('td:eq(1)').text(),
// 								"chequeno" : "",
// 								"invoiceno" :$(tr).find('td:eq(2)').text(),
// 								"payeename" :$(tr).find('td:eq(3)').text(),
// 								"amountwithouttax" :$(tr).find('td:eq(5)').text(),
// 								"tax" :$(tr).find('td:eq(6)').text(),
// 								"totalamount" :$(tr).find('td:eq(7)').text(),
// 								"purposecode" :$(tr).find('td:eq(8)').text(),
// 								"description" : "Sale Invoice " +$(tr).find('td:eq(10)').text()
// 							} 
// 						k++; 
// 					}
// 				}		   
// 		}); 
// 		return TableData_apj;
// 	}

// 	var form_data = new FormData();
// 	form_data.append("companyid", companyid);	
// 	form_data.append("subcompanyid", subcompanyid);		
// 	form_data.append("projectid", projectid);
// 	form_data.append('expensedatadbsno', Authprj);
// 	form_data.append("loginname", loginname);
// 	form_data.append("actionfrom", actionfrom);
		  
// 		$.ajax
//     ({
//       type: "POST",
//       url: baseurl+"modal/updates_reconsile_deposit.php?action=tbl_creditadvice_c1",
//       data: form_data,
//       contentType: false,
//       processData: false,
//       cache: false,
//       dataType:'JSON',
//       success: function(res)
//       {
//         // console.log(res);
//       	if(res.status=="Success"){
//       		alertmessage('success',res.message);
//       		location.reload();
//       	}else{
//       		alertmessage('error',res.message);
//       	}
//       }
//     }); 
// } 


function createbookfromexpensedataT3(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,expense_tblid,clictobtnid){
	var form_data = new FormData();
	var baseurl = $("#"+baseurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);
	var Authprj;
		Authprj = storeTblValues1('#'+expense_tblid+'');
		Authprj = JSON.stringify(Authprj);
		function storeTblValues1(mytable)
		{
			var TableData_apj = new Array();
			var mytableid = mytable + ' tr';
			$(mytableid).each(function(row, tr){
				TableData_apj[row]={
					"dbsno" :$(tr).closest('tr').data('t3dbsno')
				}    
			}); 
			TableData_apj.shift();  // first row will be empty - so remove
			return TableData_apj;
		}
		form_data.append('expensedatadbsno', Authprj);
		var loginname = $("body").data("loginname");
		form_data.append("loginname", loginname);
		// console.log(Authprj);
		$.ajax
    ({
      type: "POST",
      url: baseurl+"modal/digitization_modal.php?action=createbookfromexpensedataT3",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
     dataType:'JSON',
      success: function(res)
      {
        // console.log(res);
      	if(res.status=="Success"){
      		alertmessage('success',res.message);
      		location.reload();
      	}else{
      		alertmessage('error',res.message);
      	}
      	$(".waitloaderfooter").hide();
      },beforeSend: function(res){
        $(".waitloaderfooter").show();
      }
    });
}
// ---------------------
function unverifyedbookdata_credit(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,callback){
	var form_data = new FormData();
	var baseurl = $("#"+routurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);	
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);	
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);
	$.ajax
  ({
    type: "POST",
    url: baseurl+"modal/digitization_modal.php?action=unverifyedbookdata_credit",
    data: form_data,
    contentType: false,
    processData: false,
    cache: false,
   	dataType:'JSON',
    success: function(res)
    {
    	// console.log(res);
    	callback(res);
    }
  });
}
function verifiedpurpose_tobookdata_credit(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,isconfirm_eid,purposecode_eid,bookdata_tblid,clictobtnid){
	var form_data = new FormData();
	var baseurl = $("#"+baseurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);

	var purposecode = $("#"+purposecode_eid+"").val();
	form_data.append("purposecode", purposecode);

	if(!$("#"+isconfirm_eid+"").is(":checked")){
		alertmessage('error','Please check the checkbox to confirm and verified it');
		$("#"+isconfirm_eid+"").focus();
		return false;
	}

	var loginname = $("body").data("loginname");
	form_data.append("loginname", loginname);

	if(purposecode=="" || purposecode==null || purposecode=="undefined"){
		alertmessage('error','Purpose code is missing');
		$("#"+purposecode_eid+"").focus();
		return false;
	}
	var Authprj;
		Authprj = storeTblValues1('#'+bookdata_tblid+'');
		Authprj = JSON.stringify(Authprj);
		function storeTblValues1(mytable)
		{
			var TableData_apj = new Array();
			var mytableid = mytable + ' tr';
			$(mytableid).each(function(row, tr){
				if($(tr).closest('tr').find('td').find('input.checkbox_tdradio').is(":checked")){
					TableData_apj[row]={
						"brdbsno" :$(tr).closest('tr').data('brdbsno')
						, "maindbsno" :$(tr).closest('tr').data('maindbsno')
					}
				}    
			}); 
			TableData_apj.shift();  // first row will be empty - so remove
			return TableData_apj;
		}
		form_data.append('unverifyedbookdata', Authprj);
		// console.log(Authprj);
		var clicktobtnval = $("#"+clictobtnid+"").html();
		$("#"+clictobtnid+"").prop("disabled", true);
		$.ajax
    ({
      type: "POST",
      url: baseurl+"modal/digitization_modal.php?action=verifiedpurpose_tobookdata_credit",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
     dataType:'JSON',
      success: function(res)
      {
        // console.log(res);
      	if(res.status=="Success"){
      		alertmessage('success',res.message);
      		location.reload();
      	}else{
      		alertmessage('error',res.message);
      	}
      	$(".waitloaderfooter").hide();
      },beforeSend: function(res){
        $(".waitloaderfooter").show();
      }
    });
}

function verifiedpayto_tobookdata_credit(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,isconfirm_eid,payto_eid,expense_tblid,clictobtnid){
	var form_data = new FormData();
	var baseurl = $("#"+baseurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);

	var paytoid = $("#"+payto_eid+"").val();
	form_data.append("paytoid", paytoid);
	if(paytoid=="" || paytoid==null || paytoid=="undefined"){
		alertmessage('error','Payto name is missing');
		$("#"+purposecode_eid+"").focus();
		return false;
	}
	alert(isconfirm_eid);
	if(!$("#"+isconfirm_eid+"").is(":checked")){
		alertmessage('error','Please check the checkbox to confirm and verified it');
		$("#"+isconfirm_eid+"").focus();
		return false;
	}	
	
	var Authprj;
		Authprj = storeTblValues1('#'+expense_tblid+'');
		Authprj = JSON.stringify(Authprj);
		function storeTblValues1(mytable)
		{
			var TableData_apj = new Array();
			var mytableid = mytable + ' tr';
			$(mytableid).each(function(row, tr){
				if($(tr).closest('tr').find('td').find('input.checkbox_tdradio').is(":checked")){
					TableData_apj[row]={
						"brdbsno" :$(tr).closest('tr').data('brdbsno')
						, "maindbsno" :$(tr).closest('tr').data('maindbsno')
					}
				}    
			}); 
			TableData_apj.shift();  // first row will be empty - so remove
			return TableData_apj;
		}
		form_data.append('unverifyedbookdata', Authprj);
		var loginname = $("body").data("loginname");
		form_data.append("loginname", loginname);
		// console.log(Authprj);
		$.ajax
    ({
      type: "POST",
      url: baseurl+"modal/digitization_modal.php?action=verifiedpayto_tobookdata_credit",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
     dataType:'JSON',
      success: function(res)
      {
        // console.log(res);
      	if(res.status=="Success"){
      		alertmessage('success',res.message);
      		location.reload();
      	}else{
      		alertmessage('error',res.message);
      	}
      }
    });
}

function verifiedsubproject_tobookdata_credit(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,subprojectid_eid,isconfirm_eid,expense_tblid,clictobtnid){
	var form_data = new FormData();
	var baseurl = $("#"+baseurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);

	var subprojectid = $("#"+subprojectid_eid+"").val();
	form_data.append("subprojectid", subprojectid);
	if(subprojectid=="" || subprojectid==null || subprojectid=="undefined"){
		alertmessage('error','Sub-Project name is missing');
		$("#"+subprojectid_eid+"").focus();
		return false;
	}

	if(!$("#"+isconfirm_eid+"").is(":checked")){
		alertmessage('error','Please check the checkbox to confirm and verified it');
		$("#"+isconfirm_eid+"").focus();
		return false;
	}	
	
	var Authprj;
		Authprj = storeTblValues1('#'+expense_tblid+'');
		Authprj = JSON.stringify(Authprj);
		function storeTblValues1(mytable)
		{
			var TableData_apj = new Array();
			var mytableid = mytable + ' tr';
			$(mytableid).each(function(row, tr){
				if($(tr).closest('tr').find('td').find('input.checkbox_tdradio').is(":checked")){
					TableData_apj[row]={
						"brdbsno" :$(tr).closest('tr').data('brdbsno')
						, "maindbsno" :$(tr).closest('tr').data('maindbsno')
					}
				}    
			}); 
			TableData_apj.shift();  // first row will be empty - so remove
			return TableData_apj;
		}
		form_data.append('unverifyedbookdata', Authprj);
		var loginname = $("body").data("loginname");
		form_data.append("loginname", loginname);
		// console.log(Authprj);
		$.ajax
    ({
      type: "POST",
      url: baseurl+"modal/digitization_modal.php?action=verifiedsubproject_tobookdata_credit",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
     dataType:'JSON',
      success: function(res)
      {
        // console.log(res);
      	if(res.status=="Success"){
      		alertmessage('success',res.message);
      		location.reload();
      	}else{
      		alertmessage('error',res.message);
      	}
      }
    });
}

function verifiedpaytoandpurpose_tobookdata_credit(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,unverified_tblid){
	var form_data = new FormData();
	var baseurl = $("#"+baseurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);

	var Authprj;
		Authprj = storeTblValues1('#'+unverified_tblid+'');
		Authprj = JSON.stringify(Authprj);
		function storeTblValues1(mytable)
		{
			var TableData_apj = new Array();
			var mytableid = mytable + ' tr';
			$(mytableid).each(function(row, tr){
				if($(tr).closest('tr').find('td').find('input.checkbox_tdradio').is(":checked")){
					TableData_apj[row]={
						"brdbsno" :$(tr).closest('tr').data('brdbsno')
						, "maindbsno" :$(tr).closest('tr').data('maindbsno')
					}
				}    
			}); 
			TableData_apj.shift();  // first row will be empty - so remove
			return TableData_apj;
		}
		form_data.append('unverifyedbookdata', Authprj);
		var loginname = $("body").data("loginname");
		form_data.append("loginname", loginname);
		// console.log(Authprj);
		$.ajax
    ({
      type: "POST",
      url: baseurl+"modal/digitization_modal.php?action=verifiedpaytoandpurpose_tobookdata_credit",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
     dataType:'JSON',
      success: function(res)
      {
        // console.log(res);
      	if(res.status=="Success"){
      		alertmessage('success',res.message);
      		location.reload();
      	}else{
      		alertmessage('error',res.message);
      	}
      }
    });
}


// -------------------
function getUnreconciledFinancialYears(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,transactiontype,dropdownid){
  var form_data = new FormData();
  var routurl = $("#"+routurl_eid+"").val();
 	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);	
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);	
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid); 
	form_data.append("transactiontype", transactiontype);
  $.ajax
    ({
      type: "POST",
      url: routurl+"modal/digitization_modal.php?action=getUnreconciledFinancialYears",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType:'JSON',
      success: function(res)
      {
        // console.log(res);
        $("#"+dropdownid+"").empty();
        
        if(res.length>0){
          $("#"+dropdownid+"").append("<option value='' selected disabled>Select</option>");
          for(var i=0; i<res.length; i++){
            $("#"+dropdownid+"").append("<option value='"+res[i]+"'>"+res[i]+"</option>");
            if(res.length==1){
              $("#"+dropdownid+"").val(res[i]).trigger("change");              
            }
          }
          $("#"+dropdownid+"").append("<option value='custom'>Custom</option>");         
        }        
      }
    });
}

function unreconciledaccountlist(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,datefrom_eid,dateto_eid,transactiontype,dropdownid){
  var form_data = new FormData();
  var routurl = $("#"+routurl_eid+"").val();
 	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);	
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);	
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid); 
	var datefrom = $("#"+datefrom_eid+"").val();
	form_data.append("datefrom", datefrom);
	var dateto = $("#"+dateto_eid+"").val();
	form_data.append("dateto", dateto);
	form_data.append("transactiontype", transactiontype);
  $.ajax
    ({
      type: "POST",
      url: routurl+"modal/digitization_modal.php?action=unreconciledaccountlist",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType:'JSON',
      success: function(res)
      {
        // console.log(res);
        $("#"+dropdownid+"").empty();
        
        if(res.length>0){
          $("#"+dropdownid+"").append("<option value='' selected disabled>Select</option>");
          for(var i=0; i<res.length; i++){
            $("#"+dropdownid+"").append("<option value='"+res[i].account_id+"'>"+res[i].bankname+"-"+res[i].accountno+"</option>");
            if(res.length==1){
              $("#"+dropdownid+"").val(res[i].account_id).trigger("change");              
            }
          }         
        }else{
          $("#"+dropdownid+"").append("<option value='' disabled>Account not available</option>");
        }
        
      }
    });
}

function unreconciledclosetobookdata(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,callback){
	var form_data = new FormData();
	var baseurl = $("#"+routurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);	
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);	
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);
	$.ajax
  ({
    type: "POST",
    url: baseurl+"modal/digitization_modal.php?action=unverifyedbookdata",
    data: form_data,
    contentType: false,
    processData: false,
    cache: false,
   	dataType:'JSON',
    success: function(res)
    {
    	// console.log(res);
    	callback(res);
    }
  });
}

function unverifyedbookdata(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,callback){
	var form_data = new FormData();
	var baseurl = $("#"+routurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);	
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);	
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);
	$.ajax
  ({
    type: "POST",
    url: baseurl+"modal/digitization_modal.php?action=unverifyedbookdata",
    data: form_data,
    contentType: false,
    processData: false,
    cache: false,
   	dataType:'JSON',
    success: function(res)
    {
    	// console.log(res);
    	callback(res);
    }
  });
}

function verifiedpurpose_tobookdata(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,isconfirm_eid,purposecode_eid,bookdata_tblid,clictobtnid){
	var form_data = new FormData();
	var baseurl = $("#"+baseurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);

	var purposecode = $("#"+purposecode_eid+"").val();
	form_data.append("purposecode", purposecode);

	if(!$("#"+isconfirm_eid+"").is(":checked")){
		alertmessage('error','Please check the checkbox to confirm and verified it');
		$("#"+isconfirm_eid+"").focus();
		return false;
	}

	var loginname = $("body").data("loginname");
	form_data.append("loginname", loginname);

	if(purposecode=="" || purposecode==null || purposecode=="undefined"){
		alertmessage('error','Purpose code is missing');
		$("#"+purposecode_eid+"").focus();
		return false;
	}
	var Authprj;
		Authprj = storeTblValues1('#'+bookdata_tblid+'');
		Authprj = JSON.stringify(Authprj);
		function storeTblValues1(mytable)
		{
			var TableData_apj = new Array();
			var mytableid = mytable + ' tr';
			$(mytableid).each(function(row, tr){
				if($(tr).closest('tr').find('td').find('input.checkbox_tdradio').is(":checked")){
					TableData_apj[row]={
						"brdbsno" :$(tr).closest('tr').data('brdbsno')
						, "maindbsno" :$(tr).closest('tr').data('maindbsno')
					}
				}    
			}); 
			TableData_apj.shift();  // first row will be empty - so remove
			return TableData_apj;
		}
		form_data.append('unverifyedbookdata', Authprj);
		// console.log(Authprj);
		var clicktobtnval = $("#"+clictobtnid+"").html();
		$("#"+clictobtnid+"").prop("disabled", true);
		$.ajax
    ({
      type: "POST",
      url: baseurl+"modal/digitization_modal.php?action=verifiedpurpose_tobookdata",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
     dataType:'JSON',
      success: function(res)
      {
        // console.log(res);
      	if(res.status=="Success"){
      		alertmessage('success',res.message);
      		location.reload();
      	}else{
      		alertmessage('error',res.message);
      	}
      }
    });
}

function verifiedpayto_tobookdata(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,isconfirm_eid,payto_eid,expense_tblid,clictobtnid){
	var form_data = new FormData();
	var baseurl = $("#"+baseurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);

	var paytoid = $("#"+payto_eid+"").val();
	form_data.append("paytoid", paytoid);
	if(paytoid=="" || paytoid==null || paytoid=="undefined"){
		alertmessage('error','Payto name is missing');
		$("#"+purposecode_eid+"").focus();
		return false;
	}

	if(!$("#"+isconfirm_eid+"").is(":checked")){
		alertmessage('error','Please check the checkbox to confirm and verified it');
		$("#"+isconfirm_eid+"").focus();
		return false;
	}	
	
	var Authprj;
		Authprj = storeTblValues1('#'+expense_tblid+'');
		Authprj = JSON.stringify(Authprj);
		function storeTblValues1(mytable)
		{
			var TableData_apj = new Array();
			var mytableid = mytable + ' tr';
			$(mytableid).each(function(row, tr){
				if($(tr).closest('tr').find('td').find('input.checkbox_tdradio').is(":checked")){
					TableData_apj[row]={
						"brdbsno" :$(tr).closest('tr').data('brdbsno')
						, "maindbsno" :$(tr).closest('tr').data('maindbsno')
					}
				}    
			}); 
			TableData_apj.shift();  // first row will be empty - so remove
			return TableData_apj;
		}
		form_data.append('unverifyedbookdata', Authprj);
		var loginname = $("body").data("loginname");
		form_data.append("loginname", loginname);
		// console.log(Authprj);
		$.ajax
    ({
      type: "POST",
      url: baseurl+"modal/digitization_modal.php?action=verifiedpayto_tobookdata",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
     dataType:'JSON',
      success: function(res)
      {
        // console.log(res);
      	if(res.status=="Success"){
      		alertmessage('success',res.message);
      		location.reload();
      	}else{
      		alertmessage('error',res.message);
      	}
      }
    });
}

function verifiedsubproject_tobookdata(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,subprojectid_eid,isconfirm_eid,expense_tblid,clictobtnid){
	var form_data = new FormData();
	var baseurl = $("#"+baseurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);

	var subprojectid = $("#"+subprojectid_eid+"").val();
	form_data.append("subprojectid", subprojectid);
	if(subprojectid=="" || subprojectid==null || subprojectid=="undefined"){
		alertmessage('error','Sub-Project name is missing');
		$("#"+subprojectid_eid+"").focus();
		return false;
	}

	if(!$("#"+isconfirm_eid+"").is(":checked")){
		alertmessage('error','Please check the checkbox to confirm and verified it');
		$("#"+isconfirm_eid+"").focus();
		return false;
	}	
	
	var Authprj;
		Authprj = storeTblValues1('#'+expense_tblid+'');
		Authprj = JSON.stringify(Authprj);
		function storeTblValues1(mytable)
		{
			var TableData_apj = new Array();
			var mytableid = mytable + ' tr';
			$(mytableid).each(function(row, tr){
				if($(tr).closest('tr').find('td').find('input.checkbox_tdradio').is(":checked")){
					TableData_apj[row]={
						"brdbsno" :$(tr).closest('tr').data('brdbsno')
						, "maindbsno" :$(tr).closest('tr').data('maindbsno')
					}
				}    
			}); 
			TableData_apj.shift();  // first row will be empty - so remove
			return TableData_apj;
		}
		form_data.append('unverifyedbookdata', Authprj);
		var loginname = $("body").data("loginname");
		form_data.append("loginname", loginname);
		// console.log(Authprj);
		$.ajax
    ({
      type: "POST",
      url: baseurl+"modal/digitization_modal.php?action=verifiedsubproject_tobookdata",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
     dataType:'JSON',
      success: function(res)
      {
        // console.log(res);
      	if(res.status=="Success"){
      		alertmessage('success',res.message);
      		location.reload();
      	}else{
      		alertmessage('error',res.message);
      	}
      }
    });
}

function verifiedpaytoandpurpose_tobookdata(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,unverified_tblid){
	var form_data = new FormData();
	var baseurl = $("#"+baseurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);

	var Authprj;
		Authprj = storeTblValues1('#'+unverified_tblid+'');
		Authprj = JSON.stringify(Authprj);
		function storeTblValues1(mytable)
		{
			var TableData_apj = new Array();
			var mytableid = mytable + ' tr';
			$(mytableid).each(function(row, tr){
				if($(tr).closest('tr').find('td').find('input.checkbox_tdradio').is(":checked")){
					TableData_apj[row]={
						"brdbsno" :$(tr).closest('tr').data('brdbsno')
						, "maindbsno" :$(tr).closest('tr').data('maindbsno')
					}
				}    
			}); 
			TableData_apj.shift();  // first row will be empty - so remove
			return TableData_apj;
		}
		form_data.append('unverifyedbookdata', Authprj);
		var loginname = $("body").data("loginname");
		form_data.append("loginname", loginname);
		// console.log(Authprj);
		$.ajax
    ({
      type: "POST",
      url: baseurl+"modal/digitization_modal.php?action=verifiedpaytoandpurpose_tobookdata",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
     dataType:'JSON',
      success: function(res)
      {
        // console.log(res);
      	if(res.status=="Success"){
      		alertmessage('success',res.message);
      		location.reload();
      	}else{
      		alertmessage('error',res.message);
      	}
      }
    });
}

function reconstructiontobook_manuallyfrombankdata(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,subprojectid_eid,payto_eid,purposecode_eid,accountid_eid,remarks_eid,clicktosavetobtn){
	var baseurl = $("#"+routurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	var projectid = $("#"+projectid_eid+"").val();
	var subprojectid = $("#"+subprojectid_eid+"").val();
	var paytoid = $("#"+payto_eid+"").val();
	var purposecode = $("#"+purposecode_eid+"").val();
	var accountid = $("#"+accountid_eid+"").val();
	var remarks = $("#"+remarks_eid+"").val().trim()||"";
	var banksno = $("#"+clicktosavetobtn+"").data("banksno");
	if(subprojectid=="" || subprojectid==null || subprojectid=="null" || subprojectid=="undefined"){
		alert('Sub-Project name missing, Please choose the sub-project name');
		return false;
	}else if(paytoid=="" || paytoid==null || paytoid=="null" || paytoid=="undefined"){
		alert( 'Payto name missing, Please choose the payto name');
		return false;
	}else if(purposecode=="" || purposecode==null || purposecode=="null" || purposecode=="undefined"){
		alert('Purpose missing/wrong of this book entry');
		return false;
	}	else if(accountid=="" || accountid==null || accountid=="null" || accountid=="undefined"){
		alert('Pay from bank detail is missing, Please choose the pay from bank');
		return false;
	}	else if(remarks=="" || remarks==null || remarks=="null" || remarks=="undefined"){
		alert('remark is missing, Please enter the remark / description of book entry');
		return false;
	}
	// if(!$("#"+isconfirm_eid+"").is(":checked")){
	// 	alert('Please check the checkbox to confirm and verified it');
	// 	$("#"+isconfirm_eid+"").focus();
	// 	return false;
	// }	
	
	var form_data = new FormData();
	form_data.append("companyid", companyid);
	form_data.append("subcompanyid", subcompanyid);
	form_data.append("projectid", projectid);
	form_data.append("subprojectid", subprojectid);
	form_data.append("paytoid", paytoid);
	form_data.append("accountid", accountid);
	form_data.append("purposecode", purposecode);
	form_data.append("remarks", remarks);
	form_data.append("banksno", banksno);

	
	var loginid = $("body").data("loginid");
	form_data.append("loginid", loginid);
	var loginname = $("body").data("loginname");
	form_data.append("loginname", loginname);

	$.ajax({
      	type: "POST",
      	url: baseurl+"modal/digitization_modal.php?action=reconstructiontobook_manuallyfrombankdata",
      	data: form_data,
      	contentType: false,
      	processData: false,
      	cache: false,
     		dataType:'JSON',
      	success: function(res)
      	{
        	// console.log(res);
      		if(res.status=="Success"){
      			alert(res.message);
      			 location.reload();
      		}else{
      			alert(res.message);
      		}
      	}
    });
}

function reconstructiontobook_unknownentry(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,subprojectid_eid,payto_eid,beneficiaryname_eid,purposecode_eid,remarks_eid,isconfirm_eid,ishistorydata_eid,isautoassign_eid,unknowndata_tblid,clicktosavetobtn){
	var baseurl = $("#"+routurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	var projectid = $("#"+projectid_eid+"").val();
	var subprojectid = $("#"+subprojectid_eid+"").val();
	var paytoid = $("#"+payto_eid+"").val();
	var beneficiaryname = $("#"+beneficiaryname_eid+"").val();
	var purposecode = $("#"+purposecode_eid+"").val();
	var remarks = $("#"+remarks_eid+"").val().trim()||"";
	if(subprojectid=="" || subprojectid==null || subprojectid=="null" || subprojectid=="undefined"){
		alert('Sub-Project name missing, Please choose the sub-project name');
		return false;
	}	
	if(paytoid=="" || paytoid==null || paytoid=="null" || paytoid=="undefined"){
		alert( 'Payto name missing, Please choose the payto name');
		return false;
	}
	if(beneficiaryname=="" || beneficiaryname==null || beneficiaryname=="null" || beneficiaryname=="undefined"){
		alert('Beneficiary name missing, Please choose the beneficiary name');
		return false;
	}
	if(purposecode=="" || purposecode==null || purposecode=="null" || purposecode=="undefined"){
		alert('Purpose missing/wrong');
		return false;
	}	
	if(!$("#"+isconfirm_eid+"").is(":checked")){
		alert('Please check the checkbox to confirm and verified it');
		$("#"+isconfirm_eid+"").focus();
		return false;
	}	
	var Authprj;
	Authprj = storeTblValues1('#'+unknowndata_tblid+'');
	function storeTblValues1(mytable)
	{
		var TableData_apj = new Array();
		var mytableid = mytable + ' tbody tr';
		var j=0;
			$(mytableid).each(function(row, tr){
				if($(tr).closest('tr').find('td').find('input[type="checkbox"]').is(":checked")){
					TableData_apj[j]={
						"unraccountno" :$(tr).closest('tr').data('unraccountno'),
						"unraccountid" :$(tr).closest('tr').data('unraccountid'),
						"unvdate" :$(tr).closest('tr').data('unvdate'),
						"untransactionno" :$(tr).closest('tr').data('unchequeno'),
						"unbankamount" :$(tr).closest('tr').data('unbankamount'),
						"unbankdescription" :$(tr).closest('tr').data('unbankdescription'),
						"undbsno" :$(tr).closest('tr').data('undbsno'),
						"bookreference_no" : $(tr).closest('tr').data('bookreference_no') 						
					}
					j++;
				}    
			});
		return TableData_apj;
	}

	if(	Authprj.length<=0)
	{
		alert("No Data Found");
		return false;
	} 
	Authprj = JSON.stringify(Authprj);
	var form_data = new FormData();
	form_data.append("companyid", companyid);
	form_data.append("subcompanyid", subcompanyid);
	form_data.append("projectid", projectid);
	form_data.append("subprojectid", subprojectid);
	form_data.append("paytoid", paytoid);
	form_data.append("beneficiaryname", beneficiaryname);
	form_data.append("purposecode", purposecode);
	form_data.append("remarks", remarks);
	if($("#"+ishistorydata_eid+"").is(":checked")){
		form_data.append("savehistory", 1);
	}
	else{
		form_data.append("savehistory", 0);
	}

	if($("#"+isautoassign_eid+"").is(":checked")){
		form_data.append("autoassign", 1);
	}
	else{
		form_data.append("autoassign", 0);
	}
	var seldesc = ($("#seldesc").text()).replace(/\s+/g, ' ').trim();
	form_data.append("seldescription",seldesc);
	var groupaccountid = $("#seldesc").data("account_id");
	form_data.append("groupaccountid",groupaccountid);
	var averageamountofgroup = $("#selavgamt").text();
	form_data.append("averageamountofgroup",averageamountofgroup);
	var count = $("#selent").text();
	form_data.append("countofgroup",count);
	var minamt = $("#selminamt").text();
	form_data.append("minamtofgroup",minamt);
	var maxamt = $("#selmaxamt").text();
	form_data.append("maxamtofgroup",maxamt);

	form_data.append('unknowbankndata', Authprj);
	var loginid = $("body").data("loginid");
	form_data.append("loginid", loginid);
	var loginname = $("body").data("loginname");
	form_data.append("loginname", loginname);

	$.ajax({
      	type: "POST",
      	url: baseurl+"modal/digitization_modal.php?action=reconstruct_unknown",
      	data: form_data,
      	contentType: false,
      	processData: false,
      	cache: false,
     		dataType:'JSON',
      	success: function(res)
      	{
        	// console.log(res);
      		if(res.status=="Success"){
      			alert(res.message);
      			 location.reload();
      		}else{
      			alert(res.message);
      		}
      	}
    });
}
function reconstructiontobook_unknownentry_deposit(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,subprojectid_eid,payto_eid,beneficiaryname_eid,purposecode_eid,remarks_eid,isconfirm_eid,ishistorydata_eid,isautoassign_eid,unknowndata_tblid,clicktosavetobtn){

	var baseurl = $("#"+routurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();	
	var subcompanyid = $("#"+subcompanyid_eid+"").val();	
	var projectid = $("#"+projectid_eid+"").val();
	var subprojectid = $("#"+subprojectid_eid+"").val();
	var paytoid = $("#"+payto_eid+"").val();
	var paidbyname= $("#"+payto_eid+" option:selected").text();
	//var beneficiaryname = $("#"+beneficiaryname_eid+"").val();
	var actualpayeeid = $("#"+beneficiaryname_eid+"").val();
	var purposecode = $("#"+purposecode_eid+"").val();
	var beneficiaryname  = $("#"+beneficiaryname_eid+" option:selected").text()

	if(subprojectid=="" || subprojectid==null || subprojectid=="null" || subprojectid=="undefined"){
		alert('Sub-Project name missing, Please choose the sub-project name');
		return false;
	}
	if(paytoid=="" || paytoid==null || paytoid=="null" || paytoid=="undefined"){
		alert( 'Pay by name missing, Please choose the payto name');
		return false;
	}
	if(beneficiaryname=="" || beneficiaryname==null || beneficiaryname=="null" || beneficiaryname=="undefined"){
		alert( 'Paid On Behalf name missing, Please choose the Paid on Behalf');
		return false;
	}
	if(purposecode=="" || purposecode==null || purposecode=="null" || purposecode=="undefined"){
		alert( 'Purpose missing/wrong');
		return false;
	}
	
	if(!$("#"+isconfirm_eid+"").is(":checked")){
		alert( 'Please check the checkbox to confirm and verified it');
		$("#"+isconfirm_eid+"").focus();
		return false;
	}
	var Authprj;
	Authprj = storeTblValues1('#'+unknowndata_tblid+'');

	function storeTblValues1(mytable)
	{
		var TableData_apj = new Array();
		var mytableid = mytable + ' tbody tr';
		var j=0;
			$(mytableid).each(function(row, tr){
				if($(tr).closest('tr').find('td').find('input[type="checkbox"]').is(":checked")){
					TableData_apj[j]={ 
						"accountno" :$(tr).closest('tr').data('unraccountno'),
						"accountid" :$(tr).closest('tr').data('unraccountid'),
						"bank_date" :$(tr).closest('tr').data('unvdate'),
						"chequeno" :$(tr).closest('tr').data('unchequeno'),
						"bankamount" :$(tr).closest('tr').data('unbankamount'),
						"bank_descript" :$(tr).closest('tr').data('unbankdescription'),
						"dbsno" :$(tr).closest('tr').data('undbsno'),
						"bookreference_no" : $(tr).closest('tr').data('bookreference_no'),
						"bankid" : $(tr).closest('tr').data('bankid'),
						"bank_sno" :$(tr).closest('tr').data('banksno') 
					}
					j++;
				}    
			});
		return TableData_apj;
	}
	if(	Authprj.length<=0)
	{
		alert("No Data Found");
		return false;
	}
	Authprj = JSON.stringify(Authprj);
	var clientType = $("#recfrompayto").find("option:selected").data("clienttype");
	var remarks = $("#"+remarks_eid+"").val().trim()||"";
	
	var form_data = new FormData();
	form_data.append("companyid", companyid);
	form_data.append("subcompanyid", subcompanyid);
	form_data.append("projectid", projectid);
	form_data.append("subprojectid", subprojectid);
	form_data.append("paytoid", paytoid);
	form_data.append('paidbyname', paidbyname);   
	form_data.append("purposecode", purposecode);
	form_data.append("remarks", remarks);
	form_data.append('clienttype', clientType); 
	form_data.append("actualpayee", beneficiaryname); 
	form_data.append('actualpayee_id', actualpayeeid); 
	form_data.append('reconcilefrom',"Bank Statement");
	form_data.append('reconciletype',"completed");   
	if($("#"+ishistorydata_eid+"").is(":checked")){
		form_data.append("savehistory", 1);
	}
	else{
		form_data.append("savehistory", 0);
	}

	if($("#"+isautoassign_eid+"").is(":checked")){
		form_data.append("autoassign", 1);
	}
	else{
		form_data.append("autoassign", 0);
	}

	var seldesc = ($("#seldesc").text()).replace(/\s+/g, ' ').trim();
	form_data.append("seldescription",seldesc);
	var groupaccountid = $("#seldesc").data("account_id");
	form_data.append("groupaccountid",groupaccountid);
	var averageamountofgroup = $("#selavgamt").text();
	form_data.append("averageamountofgroup",averageamountofgroup);
	var count = $("#selent").text();
	form_data.append("countofgroup",count);
	var minamt = $("#selminamt").text();
	form_data.append("minamtofgroup",minamt);
	var maxamt = $("#selmaxamt").text();
	form_data.append("maxamtofgroup",maxamt);

	form_data.append('unknowbankndata', Authprj);
	var loginid = $("body").data("loginid");
	form_data.append("loginid", loginid);
	var loginname = $("body").data("loginname");
	form_data.append("loginname", loginname);

	$.ajax({
      	type: "POST",
      	url: baseurl+"reconcilation_desposit/modal/savetodb_reconsile_deposit_new.php?action=unknow_directbankstmt",
      	data: form_data,
      	contentType: false,
      	processData: false,
      	cache: false,
     		//dataType:'JSON',
      	success: function(res)
      	{
        	resp=res.trim();
        	alert(resp);
      		if(resp=="done"){ 
      			 location.reload();
      		} 
      	}
    });
}
function savereconstructionfromhistoryauto(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,subprojectid_eid,paytoid,purposecode,remarks,unknowndata_tblid,callback){
	var form_data = new FormData();
	var baseurl = $("#"+routurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);

	var subprojectid = $("#recgrpdesc").data("subprojectid");
	
	form_data.append("subprojectid", subprojectid);

	form_data.append("paytoid", paytoid);

	form_data.append("purposecode", purposecode);
	
	form_data.append("remarks", remarks);

	form_data.append("savehistory", 0);

	var Authprj;
	Authprj = storeTblValues1('#'+unknowndata_tblid+'');
	Authprj = JSON.stringify(Authprj);
	function storeTblValues1(mytable)
	{
		var TableData_apj = new Array();
		var mytableid = mytable + ' tbody tr';
		var j=0;
			$(mytableid).each(function(row, tr){
				if($(tr).closest('tr').find('td').find('input[type="checkbox"]').is(":checked")){
					TableData_apj[j]={
						"unraccountno" :$(tr).closest('tr').data('unraccountno')
						, "unraccountid" :$(tr).closest('tr').data('unraccountid')
						, "untransactiondate" :$(tr).closest('tr').data('unvdate')
						, "untransactionno" :$(tr).closest('tr').data('unchequeno')
						, "unbankamount" :$(tr).closest('tr').data('unbankamount')
						, "unbankdescription" :$(tr).closest('tr').data('unbankdescription')
						, "undbsno" :$(tr).closest('tr').data('undbsno')						
					}
					j++;
				}    
			});
		return TableData_apj;
	}

	form_data.append('unknowbankndata', Authprj);
	var loginid = $("body").data("loginid");
	form_data.append("loginid", loginid);
	var loginname = $("body").data("loginname");
	form_data.append("loginname", loginname);

	$.ajax({
      	type: "POST",
      	url: baseurl+"modal/digitization_modal.php?action=reconstructionunknowndata_tobook",
      	data: form_data,
      	contentType: false,
      	processData: false,
      	cache: false,
     		dataType:'JSON',
      	success: function(res)
      	{
        	console.log(res);
        	callback(res);
      	}
    });
}
function savereconstructionfromhistory(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,subprojectid_eid,payto_eid,purposecode_eid,remarks,unknowndata_tblid){
	var form_data = new FormData();
	var baseurl = $("#"+routurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);

	var subprojectid = $("#recgrpdesc").data("subprojectid");
	
	form_data.append("subprojectid", subprojectid);

	var paytoid = $("#"+payto_eid+"").data("paytoid");
	form_data.append("paytoid", paytoid);
	
	var paytoname = $("#"+payto_eid+"").text();
	form_data.append("payername", paytoname);

	form_data.append("beneficiaryname", paytoname);

	var purposecode = $("#"+purposecode_eid+"").data("purposecode");
	form_data.append("purposecode", purposecode);
	
	form_data.append("remarks", remarks);

	form_data.append("savehistory", 0);

	var Authprj;
	Authprj = storeTblValues1('#'+unknowndata_tblid+'');
	Authprj = JSON.stringify(Authprj);
	function storeTblValues1(mytable)
	{
		var TableData_apj = new Array();
		var mytableid = mytable + ' tbody tr';
		var j=0;
			$(mytableid).each(function(row, tr){
				if($(tr).closest('tr').find('td').find('input[type="checkbox"]').is(":checked")){
					TableData_apj[j]={
						"unraccountno" :$(tr).closest('tr').data('unraccountno')
						, "unraccountid" :$(tr).closest('tr').data('unraccountid')
						, "untransactiondate" :$(tr).closest('tr').data('unvdate')
						, "untransactionno" :$(tr).closest('tr').data('unchequeno')
						, "unbankamount" :$(tr).closest('tr').data('unbankamount')
						, "unbankdescription" :$(tr).closest('tr').data('unbankdescription')
						, "undbsno" :$(tr).closest('tr').data('undbsno')						
					}
					j++;
				}    
			});
		return TableData_apj;
	}

	form_data.append('unknowbankndata', Authprj);
	var loginid = $("body").data("loginid");
	form_data.append("loginid", loginid);
	var loginname = $("body").data("loginname");
	form_data.append("loginname", loginname);

	$.ajax({
      	type: "POST",
      	url: baseurl+"modal/digitization_modal.php?action=reconstructionunknowndata_tobook",
      	data: form_data,
      	contentType: false,
      	processData: false,
      	cache: false,
     		dataType:'JSON',
      	success: function(res)
      	{
        	console.log(res);
      		if(res.status=="Success"){
      			alertmessage('success',res.message);
      			location.reload();
      		}else{
      			alertmessage('error',res.message);
      		}
      	}
    });
}
/*function reconstructiontobook_unknownentry_deposit(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,subprojectid_eid,payer_eid,beneficiaryname_eid,purposecode_eid,isconfirm_eid,unknowndata_tblid,saveoption_eid,modal_eid,clicktosavetobtn){
	var form_data = new FormData();
	var baseurl = $("#"+routurl_eid+"").val();
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);

	var subprojectid = $("#"+subprojectid_eid+"").val();
	if(subprojectid=="" || subprojectid==null || subprojectid=="null" || subprojectid=="undefined"){
		alertmessage('error','Sub-Project name missing, Please choose the sub-project name');
		return false;
	}
	form_data.append("subprojectid", subprojectid);

	var payerid = $("#"+payer_eid+"").val();
	if(payerid=="" || payerid==null || payerid=="null" || payerid=="undefined"){
		alertmessage('error','Payto name missing, Please choose the payto name');
		return false;
	}
	form_data.append("payerid", payerid);

	var payername = $("#"+payer_eid+"").find("option:selected").text();
	form_data.append("payername", payername);

	var beneficiaryname = $("#"+beneficiaryname_eid+"").val();
	form_data.append("beneficiaryname", beneficiaryname);

	var purposecode = $("#"+purposecode_eid+"").val();
	if(purposecode=="" || purposecode==null || purposecode=="null" || purposecode=="undefined"){
		alertmessage('error','Purpose missing/wrong');
		return false;
	}
	form_data.append("purposecode", purposecode);
	
	var selectedgroupclass = $("#"+modal_eid+"").data("group");
	var savemethod = $("input[name='"+saveoption_eid+"']:checked").val();

	if(!$("#"+isconfirm_eid+"").is(":checked")){
		alertmessage('error','Please check the checkbox to confirm and verified it');
		$("#"+isconfirm_eid+"").focus();
		return false;
	}

	var Authprj;
	Authprj = storeTblValues1('#'+unknowndata_tblid+'',savemethod,selectedgroupclass);
	Authprj = JSON.stringify(Authprj);
	function storeTblValues1(mytable,savemethod,groupclass)
	{
		var TableData_apj = new Array();
		var mytableid = mytable + 'tbody tr';
		if(savemethod=="singlesave"){
			$(mytableid).each(function(row, tr){
				if($(tr).closest('tr').find('td').find('input[type="checkbox"]').is(":checked")){
					TableData_apj[row]={
						"unraccountno" :$(tr).closest('tr').data('unraccountno')
						, "unraccountid" :$(tr).closest('tr').data('unraccountid')
						, "untransactiondate" :$(tr).closest('tr').data('unvdate')
						, "untransactionno" :$(tr).closest('tr').data('unchequeno')
						, "unbankamount" :$(tr).closest('tr').data('unbankamount')
						, "unbankdescription" :$(tr).closest('tr').data('unbankdescription')
						, "undbsno" :$(tr).closest('tr').data('undbsno')						
					}
				}    
			});
		}
		else if(savemethod=="groupsave"){
			$(mytableid).each(function(row, tr){
				if($(tr).hasClass(groupclass)){
					TableData_apj[row]={
						"unraccountno" :$(tr).closest('tr').data('unraccountno')
						, "unraccountid" :$(tr).closest('tr').data('unraccountid')
						, "untransactiondate" :$(tr).closest('tr').data('unvdate')
						, "untransactionno" :$(tr).closest('tr').data('unchequeno')
						, "unbankamount" :$(tr).closest('tr').data('unbankamount')
						, "unbankdescription" :$(tr).closest('tr').data('unbankdescription')
						, "undbsno" :$(tr).closest('tr').data('undbsno')						
					}
				}    
			});
		}
		return TableData_apj;
	}
	form_data.append('unknowbankndata', Authprj);
	var loginid = $("body").data("loginid");
	form_data.append("loginid", loginid);
	var loginname = $("body").data("loginname");
	form_data.append("loginname", loginname);

	// console.log(Authprj);
	$.ajax
    ({
      type: "POST",
      url: baseurl+"modal/digitization_modal.php?action=reconstructionunknowndata_tobookdeposit",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType:'JSON',
      success: function(res)
      {
        // console.log(res);
      	if(res.status=="Success"){
      		alertmessage('success',res.message);
      		location.reload();
      	}else{
      		alertmessage('error',res.message);
      	}
      }
    });
}*/



function sendtoemail_unknownentiry_html(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid){
	var form_data = new FormData();
	var baseurl = $("#"+routurl_eid+"").val();
	form_data.append("routurl", baseurl);
	
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);
		$.ajax
    ({
      type: "POST",
      url: baseurl+"modal/sendingemail_modal.php?action=sendtoemail_personlist",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
     dataType:'JSON',
      success: function(res)
      {
      	// console.log(res);
				var sendEmailHtml = "";
				// Clear the content of the webpage body div
				$("#webpagebodydiv").html('');
				if (res.length > 0) {
				    // Start building the HTML for the modal content
				    sendEmailHtml += `
				        <div class="row">
				            <div class="col-md-12">
				                <label>Send Email Person Name</label>
				                <select class="form-control" id="sendEmailLoginId">`;
										    // Populate the select options with data from `res`
										    for (var i = 0; i < res.length; i++) {
										        sendEmailHtml += `<option value="${res[i].loginid}">${res[i].loginname}</option>`;
										    }
				    						sendEmailHtml += `
				                </select>
				            </div>
				        </div>
				        <div class="row mt-4">
				            <div class="col-md-4"></div>
				            <div class="col-md-4 text-center">
				                <button 
				                    class="btn btn-success" 
				                    id="sendEmailBtn" 
				                    onclick="sendEmailUnknownEntityForReconstruction('${routurl_eid}', '${companyid_eid}', '${subcompanyid_eid}', '${projectid_eid}', $('#sendEmailLoginId').val())">
				                    Send Email
				                </button>
				            </div>
				            <div class="col-md-4"></div>
				        </div>`;
				} else {
				    // Alert the user if no authorized person exists for the project
				    alertmessage('error', 'No one authorized for login of this project');
				    return false;
				}
				// If the HTML was generated, show the modal and set its content
				if (sendEmailHtml) {
				    $("#webpagebodyview_modal").modal("show");
				    $("#modalheader").html("Sending Email For Reconstruction of Unknown Entry");
				    $("#webpagebodydiv").html(sendEmailHtml);
				}

      }
    });
}

function sendEmailUnknownEntityForReconstruction(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,sendtoemailloginid){
	var form_data = new FormData();
	var baseurl = $("#"+routurl_eid+"").val();
	form_data.append("routurl", baseurl);
	
	var companyid = $("#"+companyid_eid+"").val();
	form_data.append("companyid", companyid);
	
	var subcompanyid = $("#"+subcompanyid_eid+"").val();
	form_data.append("subcompanyid", subcompanyid);
	
	var projectid = $("#"+projectid_eid+"").val();
	form_data.append("projectid", projectid);
	
	var loginname = $("body").data("loginname");
	form_data.append("loginname", loginname);
	form_data.append("sendtoemailloginid", sendtoemailloginid);
		// console.log(Authprj);
		$.ajax
    ({
      type: "POST",
      url: baseurl+"modal/sendingemail_modal.php?action=sendemail_unknownentiry_forreconstruction",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
     dataType:'JSON',
      success: function(res)
      {
        // console.log(res);
      	if(res.status=="Success"){
      		alertmessage('success',res.message);
      		location.reload();
      	}else{
      		alertmessage('error',res.message);
      	}
      	$(".waitloaderfooter").hide();
      },beforeSend: function(res){
	      $(".waitloaderfooter").show();
	    }
    });
}




function addRowinvoice(tableid){
	$("#"+tableid+" tbody").append('<tr><td style="padding: .02rem; text-align: center; width:50px;"><center><i class="fa fa-trash delete-btn" style="cursor:pointer; color: red;" onclick="deleteRowNew(\''+tableid+'\');"></i></center></td><td style="padding: .02rem; text-align: left; width:300px;"><input type="text" class="form-control description_td" /></td><td style="padding: .02rem; text-align: center; width:100px;"><input type="text" class="form-control hsbn_td" /></td><td style="padding: .02rem; text-align: center; width:100px; display: none;"><input type="text" class="form-control invrowcell_rm mrpcal mrp_td" onKeyPress="return isFloatNumber(event,this);" /></td><td style="padding: .02rem; text-align: center; width:100px;"><input type="text" class="form-control invrowcell_rm ratecal rate_td" onKeyPress="return isFloatNumber(event,this);" /></td><td style="padding: .02rem; text-align: center; width:100px;"><input type="text" class="form-control invrowcell_rm qtycal qty_td" onKeyPress="return isFloatNumber(event,this);" /></td><td style="padding: .02rem; text-align: center; width:100px;"><input type="text" class="form-control txableamount_td" onKeyPress="return isFloatNumber(event,this);" disabled /></td><td style="padding: .02rem; text-align: center; width:90px;"><input type="text" class="form-control cgstcal cgstprt_td" onKeyPress="return isFloatNumber(event,this);" /></td><td style="padding: .02rem; text-align: center; width:90px;"><input type="text" class="form-control cgstcal cgstamt_td" onKeyPress="return isFloatNumber(event,this);" /></td><td style="padding: .02rem; text-align: center; width:90px;"><input type="text" class="form-control sgstcal sgstprt_td" onKeyPress="return isFloatNumber(event,this);" /></td><td style="padding: .02rem; text-align: center; width:90px;"><input type="text" class="form-control sgstcal sgstamt_td" onKeyPress="return isFloatNumber(event,this);" /></td><td style="padding: .02rem; text-align: center; width:90px;"><input type="text" class="form-control igstcal igstprt_td" onKeyPress="return isFloatNumber(event,this);" /></td><td style="padding: .02rem; text-align: center; width:90px;"><input type="text" class="form-control igstcal igstamt_td" onKeyPress="return isFloatNumber(event,this);" /></td><td style="padding: .02rem; text-align: center; width:90px;"><input type="text" class="form-control distcal distprt_td" onKeyPress="return isFloatNumber(event,this);" /></td><td style="padding: .02rem; text-align: center; width:90px;"><input type="text" class="form-control distcal distamt_td" onKeyPress="return isFloatNumber(event,this);" /></td><td style="padding: .02rem; text-align: center; width:150px;"><input type="text" class="form-control famount_td" onKeyPress="return isFloatNumber(event,this);" /></td></tr>');		
}

function addRowinvoiceNew(tableid) {
    // Calculate the row count
    let trcount = $("#" + tableid + " tbody tr").length + 1;

    // Create the new row HTML
    const newRow = `
        <tr>
            <td style="padding: .02rem; text-align: center; width:50px;">
                <center>
                    <i class="fa fa-trash delete-btn" style="cursor:pointer; color: red;" 
                       onclick="deleteRowNew('${tableid}', this);"></i>
                </center>
            </td>
            <td style="padding: .02rem; text-align: left; width:300px;">
                <input type="text" class="form-control description_td" />
            </td>
            <td style="padding: .02rem; text-align: left; width:300px;">
                <select class="form-control purpose_td" id="tblprj_${trcount}"></select>
            </td>
            <td style="padding: .02rem; text-align: center; width:100px;">
                <input type="checkbox" class="isforstock_td" />
            </td>
            <td style="padding: .02rem; text-align: center; width:100px;">
                <input type="text" class="form-control hsbn_td" />
            </td>
            <td style="padding: .02rem; text-align: center; width:100px; display: none;">
                <input type="text" class="form-control invrowcell_rm mrpcal mrp_td" 
                       onKeyPress="return isFloatNumber(event, this);" />
            </td>
            <td style="padding: .02rem; text-align: center; width:100px;">
                <input type="text" class="form-control invrowcell_rm ratecal rate_td" 
                       onKeyPress="return isFloatNumber(event, this);" />
            </td>
            <td style="padding: .02rem; text-align: center; width:100px;">
                <input type="text" class="form-control invrowcell_rm qtycal qty_td" 
                       onKeyPress="return isFloatNumber(event, this);" />
            </td>
            <td style="padding: .02rem; text-align: center; width:100px;">
                <input type="text" class="form-control txableamount_td" 
                       onKeyPress="return isFloatNumber(event, this);" disabled />
            </td>
            <td style="padding: .02rem; text-align: center; width:90px;">
                <input type="text" class="form-control cgstcal cgstprt_td" 
                       onKeyPress="return isFloatNumber(event, this);" />
            </td>
            <td style="padding: .02rem; text-align: center; width:90px; display:none;">
                <input type="text" class="form-control cgstcal cgstamt_td" 
                       onKeyPress="return isFloatNumber(event, this);" />
            </td>
            <td style="padding: .02rem; text-align: center; width:90px;">
                <input type="text" class="form-control sgstcal sgstprt_td" 
                       onKeyPress="return isFloatNumber(event, this);" />
            </td>
            <td style="padding: .02rem; text-align: center; width:90px; display:none;">
                <input type="text" class="form-control sgstcal sgstamt_td" 
                       onKeyPress="return isFloatNumber(event, this);" />
            </td>
            <td style="padding: .02rem; text-align: center; width:90px;">
                <input type="text" class="form-control igstcal igstprt_td" 
                       onKeyPress="return isFloatNumber(event, this);" />
            </td>
            <td style="padding: .02rem; text-align: center; width:90px; display:none;">
                <input type="text" class="form-control igstcal igstamt_td" 
                       onKeyPress="return isFloatNumber(event, this);" />
            </td>
            <td style="padding: .02rem; text-align: center; width:90px;">
                <input type="text" class="form-control distcal distprt_td" 
                       onKeyPress="return isFloatNumber(event, this);" />
            </td>
            <td style="padding: .02rem; text-align: center; width:90px;">
                <input type="text" class="form-control distcal distamt_td" 
                       onKeyPress="return isFloatNumber(event, this);" />
            </td>
            <td style="padding: .02rem; text-align: center; width:150px;">
                <input type="text" class="form-control famount_td" 
                       onKeyPress="return isFloatNumber(event, this);" />
            </td>
        </tr>
    `;

    // Append the new row to the table body
    $("#" + tableid + " tbody").append(newRow);

    // Populate the purpose dropdown dynamically
    getallpurposewithprojectidlist(
        "hidden_rooturl",
        "hidden_companyid",
        "hidden_subcompanyid",
        "hidden_projectid",
        function (res) {
            const selectElement = $("#tblprj_" + trcount);
            selectElement.empty(); // Clear any existing options
            if (res.length > 1) {
                selectElement.append("<option value='' selected>Purpose name</option>");
            }
            res.forEach(item => {
                selectElement.append(`<option value='${item.purposecode}'>${item.provided_code}-${item.purpose}</option>`);
            });
        }
    );
}


$(document).on("click", ".dohavegstinvoice", function(){
	var parentContainer = $(this).closest("body");
	var invtype = parentContainer.find("select.invoicetype").val();
	var dohavegstinvoiceval = parentContainer.find("input[name='dohavegstinvoice']:checked ").val();
	$('#gstinvoicedetailsmodal').find('table').find('input').val('');
	$("#totalamount").text('');
	$("#gsttotalamount").text('');
	$("#ttldiscountamount").text('');
	$("#grandtotalamount").text('');
	$("#otherdiscounttotalamount").val(''); //input

	if($(this).val()=="yes" && (invtype=="Invoice" || invtype=="Cash Memo")){
		$("#gstinvoicedetailsmodal").modal("show");
	}else if($(this).val()=="no"){
		$("#gstinvoicedetailsmodal").modal("hide");
	}
});


function changelabelonchange(inputname,labelid){
	var inputnameval = $("input[name='"+inputname+"']:checked").val();
	if(inputnameval=="cheque"){
		$("#"+labelid+"").html('cheque number?');
	}else if(inputnameval=="online"){
		$("#"+labelid+"").html('online transaction number?');
	}
}
function ispaytoasbeneficiary(paytodropid, beneficiarydropid, checkboxkid) {
    var paytoname = $("#" + paytodropid + " option:selected").text();
    var paytoid = $("#" + paytodropid).val();
    if ($("#" + checkboxkid).is(":checked")) {
    	 $("#"+beneficiarydropid+"").empty();
    	 if(paytoid){
    	 	 $("#"+beneficiarydropid+"").append("<option value='"+paytoid+"'>"+paytoname+"</option>");
    	 	}	        
        //$("#" + beneficiarydropid).val(paytoname).trigger("change"); // Update and trigger change for select2
    } else {

   		$("#"+beneficiarydropid+"").empty();
    	request_beneficiaryfrommaster("hidden_rooturl","hidden_companyid","hidden_subcompanyid","hidden_projectid",beneficiarydropid,"");
    }
}
function ispaytoasbeneficiary_deposit(beneficiarydropid) {
   		$("#"+beneficiarydropid+"").empty();
   
    	request_clientlistfrommaster("hidden_rooturl","hidden_companyid","hidden_subcompanyid","hidden_projectid",beneficiarydropid,"");
     
}


function balanceamount_inputvalfill(ttlamountinputid,inputclass,balanceinputid){
	var ttlexpamount =0;
	var ttlbalanceamount=0;
	if($("."+inputclass+"").length>0 && $("#"+ttlamountinputid+"").val()>0){
		$('.'+inputclass+'').each(function(){
			ttlexpamount = parseInt(ttlexpamount,10) + parseInt($(this).val()!=""?$(this).val():0);
		});
		ttlbalanceamount = parseInt($("#"+ttlamountinputid+"").val(),10) - parseInt(ttlexpamount,10);
		if(ttlbalanceamount>0){
			$("#"+balanceinputid+"").html("<span style='color:green;'>Balance Amount (Rs): "+ttlbalanceamount+" for Receipt Required</span>");
		}else{
			$("#"+balanceinputid+"").html("<span style='color:red;'>Balance Amount (Rs): "+ttlbalanceamount+" for Receipt Required</span>");
		}
	}else{
		ttlbalanceamount = $("#"+ttlamountinputid+"").val();
		$("#"+balanceinputid+"").html("<span style='color:green;'>Balance Amount (Rs): "+ttlbalanceamount+" for Receipt Required</span>");
	}
}

function yearlistwithlastyear_dropdown(dropdownid){
	var currentYear = new Date().getFullYear();
	// Loop to generate the last 5 years
	for (var i = 0; i < 2; i++) {
	    $('#'+dropdownid+'').append($('<option>').text((currentYear - i)).attr('value', (currentYear - i)));
	}   
    
}
function monthlistofyear_dropdown(dropdownid){
		// Create a new Date object
	var currentDate = new Date();
	// Get the current month (0-indexed, so January is 0)
	var currentMonth = currentDate.getMonth();
	// Add 1 to convert to 1-indexed month
	currentMonth += 1;
	// Array of month names
    var months = [
        "January", "February", "March", "April", "May", "June", 
        "July", "August", "September", "October", "November", "December"
    ];
    
    // Loop through the months array and append options to select element
    // $('#'+dropdownid+'').append($('<option disabled selected>').text('Choose month').attr('value', ''));
    $.each(months, function(index, value) {
    	if(currentMonth==(index + 1)){
        $('#'+dropdownid+'').append($('<option selected>').text(value).attr('value', index + 1));
    	}else{
        $('#'+dropdownid+'').append($('<option>').text(value).attr('value', index + 1));
    	}
    });
}
function getvalforText(mySelect,text) {
  var $select = $('#'+mySelect);
  var $options = $select.find('option').filter(function() {
    return $(this).text() === text;
  });

  if ($options.length) {
    var value = $options.val();
    return value;
  } else {
    return null;
  }
}

function getDaysInMonth(year, month) {
    // JavaScript months are zero-indexed, so we subtract 1 from the month
    return new Date(year, month, 0).getDate();
}

function gettaxableamount(mrpamount,discountamaount,taxpercent){
    return parseFloat(((mrpamount - discountamaount)*100)/(100 + parseFloat(taxpercent))).toFixed(2);
}
function gettaxableamountwithrate(rateamount,discountamaount,taxpercent){
    return parseFloat(rateamount - discountamaount).toFixed(2);
}


function totalamount(ttlamountid,totalamt_class,tableid){
    var ttlamt = 0;
    $("#"+tableid+" tbody tr").each(function(){
        var amt = $(this).find("input."+totalamt_class+"").val();
        ttlamt = parseFloat(ttlamt) + parseFloat(amt);
        $('#'+ttlamountid+"").text(ttlamt.toFixed(2));
    });
}

function grandtotalgstamont(totalamountid, totalgstamontid, totaldiscountamount, discountamaountid, grandttlamountid, totalamt_class, cgstamt_class, sgstamt_class, igstamt_class, discountamt_class, tableid) {
    var spdistamount = 0; 
    var ttlamt = 0;
    var ttldistamt = 0;
    var ttlcgstamt = 0;
    var ttlsgstamt = 0;
    var ttligstamt = 0;
    var ttlgst = 0;
    var grandttlamt = 0;
    var parentdivid = $("#" + tableid).data("parentdivid");
    $("#" + tableid + " tbody tr").each(function() {
        // Get the total amount from the row
        var amt = $(this).find("input." + totalamt_class).val();
        ttlamt += parseFloat(amt) || 0; // Safely add the value, default to 0 if invalid

        // Get the discount amount from the row
        var distamt = $(this).find("input." + discountamt_class).val();
        ttldistamt += parseFloat(distamt) || 0; // Safely add the value, default to 0 if invalid

        // Get the CGST amount from the row
        var cgstamt = $(this).find("input." + cgstamt_class).val();
        ttlcgstamt += parseFloat(cgstamt) || 0; // Safely add the value, default to 0 if invalid

        // Get the SGST amount from the row
        var sgstamt = $(this).find("input." + sgstamt_class).val();
        ttlsgstamt += parseFloat(sgstamt) || 0; // Safely add the value, default to 0 if invalid

        // Get the IGST amount from the row
        var igstamt = $(this).find("input." + igstamt_class).val();
        ttligstamt += parseFloat(igstamt) || 0; // Safely add the value, default to 0 if invalid
    });

    // Calculate the total GST
    ttlgst = ttlcgstamt + ttlsgstamt + ttligstamt;

    // Handle the discount amount
    $("#" + parentdivid + " #" + discountamaountid).each(function() {
        var discountValue = $(this).val(); // Get the discount value from each element
        spdistamount += parseFloat(discountValue) || 0; // Sum up the discount amounts
    });
    spdistamount = spdistamount.toFixed(2); // Fix to two decimal places

    // Calculate the grand total
    grandttlamt = (parseFloat(ttlamt) + parseFloat(ttlgst) - parseFloat(spdistamount)).toFixed(2);
    // Update the DOM elements with the calculated values
    $("#" + parentdivid + " #" + totalamountid).text(ttlamt.toFixed(2));
    $("#" + parentdivid + " #" + totalgstamontid).text(ttlgst.toFixed(2));
    $("#" + parentdivid + " #" + totaldiscountamount).text(ttldistamt.toFixed(2));
    $("#" + parentdivid + " #" + grandttlamountid).text(grandttlamt);

    $("#" + tableid).data("ttlrows-amount", ttlamt.toFixed(2));
    $("#" + tableid).data("ttlgst-amount", ttlgst.toFixed(2));
    $("#" + tableid).data("ttldiscount-amount", ttldistamt.toFixed(2));
    $("#" + tableid).data("ttlpayable-amount", grandttlamt);
}



function getCellValue(val) {
    return val !== undefined && val !== null && val !== '' ? parseFloat(val) : 0;
}

function refreshWindow() {
  window.location.reload();
}

// function getFinancialYear(date = new Date(),finyeartype="Apr-Mar") {
//     const month = date.getMonth() + 1;  // getMonth() is zero-indexed (0 = January, 11 = December)
//     const year = date.getFullYear();

//     let startYear;
//     let endYear;

//     if(finyeartype="Apr-Mar"){
// 	    if (month >= 4) {  // Financial year starts in April
// 	        startYear = year;
// 	        endYear = year + 1;
// 	    } else {
// 	        startYear = year - 1;
// 	        endYear = year;
// 	    }
// 	     return `${startYear}-${endYear}`;
// 	  }
// 	  else if(finyeartype="Jan-Dec"){
// 	  	 return `${startYear}`;
// 	  } 
// }

function formatDate(dateString) {
    const date = new Date(dateString);
    let day = date.getDate();
    let month = date.getMonth() + 1; 
    let year = date.getFullYear();

    if (day < 10) {
        day = '0' + day;
    }
    if (month < 10) {
        month = '0' + month;
    }

    return `${day}-${month}-${year}`;
}

function formatDateymd(dateString) {
    const [day, month, year] = dateString.split("-");
    return `${year}-${month}-${day}`;
}

let sortOrder = {}; // Object to store sort order for each column

function sortTable(table,columnIndex) {
    const rows = Array.from(document.getElementById(table).getElementsByTagName("tbody")[0].getElementsByTagName("tr"));
	var datatype = detectColumnType(table,columnIndex);
    sortOrder[columnIndex] = !sortOrder[columnIndex];

    rows.sort((rowA, rowB) => {
    	var cellA;
    	var cellB;
    	if(datatype=="date"){
    		cellA = parseFloat(formatDateymd(rowA.getElementsByTagName("td")[columnIndex].textContent).replaceAll("-",""));
        	cellB = parseFloat(formatDateymd(rowB.getElementsByTagName("td")[columnIndex].textContent).replaceAll("-",""));
    	}
    	else if(datatype=="number"){
    		cellA = parseInt(rowA.getElementsByTagName("td")[columnIndex].textContent.toLowerCase());
        	cellB = parseInt(rowB.getElementsByTagName("td")[columnIndex].textContent.toLowerCase());
    	}
    	else{
        	cellA = rowA.getElementsByTagName("td")[columnIndex].textContent.toLowerCase();
        	cellB = rowB.getElementsByTagName("td")[columnIndex].textContent.toLowerCase();
    	}

        if (cellA < cellB) return sortOrder[columnIndex] ? -1 : 1;
        if (cellA > cellB) return sortOrder[columnIndex] ? 1 : -1;
        return 0;
    });

    const tbody = document.getElementById(table).getElementsByTagName("tbody")[0];
    tbody.innerHTML = ""; 
    rows.forEach(row => tbody.appendChild(row));
}
function detectColumnType(table,columnIndex) {
    const rows = Array.from(document.getElementById(table).getElementsByTagName("tbody")[0].getElementsByTagName("tr"));
    const sampleValues = rows.map(row => row.getElementsByTagName("td")[columnIndex].textContent.trim());

    // Flags to determine data type
    let isNumber = true;
    let isDate = true;

    sampleValues.forEach(value => {
        if (isNumber) isNumber = !isNaN(value) && value !== "";

        // Check for date in the format dd-mm-yyyy
        if (isDate) {
            const dateParts = value.split("-");
            isDate = dateParts.length === 3 &&
                     !isNaN(dateParts[0]) && !isNaN(dateParts[1]) && !isNaN(dateParts[2]) &&
                     dateParts[0].length === 2 && dateParts[1].length === 2 && dateParts[2].length === 4;

            // Further check: Make sure it's a valid date
            if (isDate) {
                const day = parseInt(dateParts[0], 10);
                const month = parseInt(dateParts[1], 10) - 1; // Month is zero-based in JavaScript
                const year = parseInt(dateParts[2], 10);
                const dateObj = new Date(year, month, day);

                // Validate the created date object
                isDate = dateObj.getFullYear() === year &&
                         dateObj.getMonth() === month &&
                         dateObj.getDate() === day;
            }
        }
    });

    if (isNumber) return "number";
    if (isDate) return "date";
    return "string";
}

function getaccinfobyprojectinfo(routurl_eid,companyid,subcompanyid,projectid, callback){
	var form_data = new FormData();
	var baseurl = $("#"+routurl_eid+"").val();
	form_data.append("routurl", baseurl);
	form_data.append("companyid", companyid);
	form_data.append("subcompanyid", subcompanyid);
	form_data.append("projectid", projectid);
	// alert(projectid);
	$.ajax({
		type: "POST",	
		url: baseurl+"modal/request_modal.php?action=getaccountinfobyproject",
		data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType:'JSON',
		success: function(res)
		{
			if(res.length>0){
				callback(res);
			}
		}
	});
}

function getaccinfoandgetstatusofstatement(routurl_eid,companyid,subcompanyid,projectid,fromdate,todate, callback){
	var form_data = new FormData();
	var baseurl = $("#"+routurl_eid+"").val();
	form_data.append("routurl", baseurl);
	form_data.append("companyid", companyid);
	form_data.append("subcompanyid", subcompanyid);
	form_data.append("projectid", projectid);
	form_data.append("fromdate", fromdate);
	form_data.append("todate", todate);
	$.ajax({
		type: "POST",	
		url: baseurl+"modal/request_modal.php?action=getaccinfoandgetstatusofstatement",
		data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType:'JSON',
		success: function(res)
		{
			if(res.length>0){
				callback(res);
			}
		}
	});
}

function getaccinfoandgetstatusofstatementpdf(routurl_eid,companyid,subcompanyid,projectid,fromdate,todate,accountid, callback){
	var form_data = new FormData();
	var baseurl = $("#"+routurl_eid+"").val();
	form_data.append("routurl", baseurl);
	form_data.append("companyid", companyid);
	form_data.append("subcompanyid", subcompanyid);
	form_data.append("projectid", projectid);
	form_data.append("fromdate", fromdate);
	form_data.append("todate", todate);
	form_data.append("accountid", accountid);
	$.ajax({
		type: "POST",	
		url: baseurl+"modal/request_modal.php?action=getaccinfoandgetstatusofstatementpdf",
		data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType:'JSON',
		success: function(res)
		{
			if(res.length>0){
				callback(res);
			}
		}
	});
}

function getaccinfoandgetstatusofstatementpdfandexcel(routurl_eid,companyid,subcompanyid,projectid,filetype, callback){
	var form_data = new FormData();
	var baseurl = $("#"+routurl_eid+"").val();
	form_data.append("routurl", baseurl);
	form_data.append("companyid", companyid);
	form_data.append("subcompanyid", subcompanyid);
	form_data.append("projectid", projectid);
	form_data.append("filetype", filetype);
	$.ajax({
		type: "POST",	
		url: baseurl+"modal/request_modal.php?action=getaccinfoandgetstatusofstatementpdfexcel",
		data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType:'JSON',
		success: function(res)
		{
			if(res.length>0){
				callback(res);
			}
		}
	});
}

function getuncheckedbankdata(routurl_eid,companyid,subcompanyid,projectid,callback) {
  var form_data = new FormData();
  var baseurl = $("#"+routurl_eid+"").val();
	form_data.append("routurl", baseurl);
  form_data.append('companyid', companyid);
  form_data.append('subcompanyid', subcompanyid);
  form_data.append('projectid', projectid);
  $.ajax({
    type: "POST",
    url: baseurl+"modal/request_modal.php?action=getbankstatementuncheck",
    data: form_data,
    contentType: false,
    processData: false,
    cache:  false,
    dataType: "json",
    success: function(res){
    	// console.log(res);
    	callback(res);
    }
  });
}

function get_bankstatement(routurl_eid,companyid,subcompanyid,projectid,accountid,transactiontype="",callback) {
  var form_data = new FormData();
  var baseurl = $("#"+routurl_eid+"").val();
	form_data.append("routurl", baseurl);
  form_data.append('companyid', companyid);
  form_data.append('subcompanyid', subcompanyid);
  form_data.append('projectid', projectid);
  form_data.append('accountid', accountid);
  form_data.append('transactiontype', transactiontype);
  $.ajax({
    type: "POST",
    url: baseurl+"modal/request_modal.php?action=getunreconcileclosebook",
    data: form_data,
    contentType: false,
    processData: false,
    cache:  false,
    dataType: "json",
    success: function(res){
    	 // console.log(res);
    	callback(res);
    }
  });
}

function get_bankstatementorig(routurl_eid,companyid,subcompanyid,projectid,accountid,callback) {
  var form_data = new FormData();
  var baseurl = $("#"+routurl_eid+"").val();
	form_data.append("routurl", baseurl);
  form_data.append('companyid', companyid);
  form_data.append('subcompanyid', subcompanyid);
  form_data.append('projectid', projectid);
  form_data.append('accountid', accountid);
  $.ajax({
    type: "POST",
    url: baseurl+"modal/request_modal.php?action=getbankstatement",
    data: form_data,
    contentType: false,
    processData: false,
    cache:  false,
    dataType: "json",
    success: function(res){
    	 // console.log(res);
    	callback(res);
    }
  });
}

function get_bankstatementpdf(routurl_eid,companyid,subcompanyid,projectid,accountid,datefrom,dateto,callback) {
  var form_data = new FormData();
  var baseurl = $("#"+routurl_eid+"").val();
	form_data.append("routurl", baseurl);
  form_data.append('companyid', companyid);
  form_data.append('subcompanyid', subcompanyid);
  form_data.append('projectid', projectid);
  form_data.append('accountid', accountid);
  form_data.append('datefrom', datefrom);
  form_data.append('dateto', dateto);
 
  $.ajax({
    type: "POST",
    url: baseurl+"modal/request_modal.php?action=getbankstatementpdf",
    data: form_data,
    contentType: false,
    processData: false,
    cache:  false,
    success: function(res){
    	// console.log(res);
    	callback(res);
    }
  });
}