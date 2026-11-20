<div style="background-color: #F0F8FF">
	<div class="container" id="p_info">
		<div class="row">
			<div class="col-md-12 text-center mt-4 bg-success">
				<h6>PERSONAL INFORMATION</h6>
			</div>
		</div><hr>
		<div class="row">
			<div class="col-md-8">
				<div class="row">
					<div class="col-md-12">
						<p>What is the Employment Type?</p>
						<label><input type="radio" class="m-2" name="employeetypename" value="regular" /> Regular Employee</label><br/>
						<label><input type="radio" class="m-2" name="employeetypename" value="parttime" /> Part Time Employee</label><br/>
						<label><input type="radio" class="m-2" name="employeetypename" value="trainee" /> Trainee Employee</label><br/>
					</div>
				</div>
				<div class="row"> 
					<div class="col-md-12">
						<label>Employee Name </label><span class="text-red">*</span> 
						<input type="text" id="employeename" name="employeename" placeholder="Enter First Name" onKeyPress="return ValidateAlpha(event);" class="form-control"  />
						<i><label class="text-red FirstName"></label></i>
					</div>
				</div>
				<div class="row"> 
					<div class="col-md-12">
						<label>Date Of Birth (DD-MM-YYYY)</label><span class="text-red">*</span>
						<input type="date" name="empdob" id="empdob" placeholder="Enter  DOB" class="form-control"  />
						<i><label class="text-red DOB"></label></i>
					</div>				 
					<div class="col-md-12">
					   <label>Gender </label><span class="text-red">*</span>					 
						<select class="form-control" name="empgender" id="empgender" >
						  <option value="" selected disabled>Select Gender</option>
						  <option value="Female">Female</option>
						  <option value="Male">Male</option>
						</select>
						<i><label class="text-red SltGender"></label></i>
					</div>
				</div>
			</div>
			<div class="col-md-4" style="margin-top: 25px; padding-left: 40px" >
				<div class="upload-button" style="cursor: pointer;">
					<img class="profile-pic" src="../img/Placeholder.jpg" style="margin-bottom: 5px; position: center;"/>
				</div>
				<input class="file-upload" id="uploadphoto" type="file" accept="image/*" style="display: none;"/>
			</div>			
		</div> 
		<div class="row row-padding">
			<div class="col-md-6">
				<label>Marital Status</label><span class="text-red">*</span>			 
				<select class="form-control" id="empmaritalstatus" name="empmaritalstatus" >
					<option value="" selected disabled>Select Status</option>
					<option value="Married">Married</option>
					<option value="UnMarried">UnMarried</option>
					<option value="Separated/Divorced">Separated/Divorced</option>
					<option value="Widowed">Widowed</option>
				</select>
				<i><label class="text-red MStatus"></label></i>
			</div>		 
			<div class="col-md-6">
				<label>Religion </label><span class="text-red">*</span>			 
				<select type="text" id="empreligion"  class="form-control ">
					<option selected disabled>Select empreligion</option>
					<option value="Hindu">Hindu</option>
					<option value="Muslim">Muslim</option>
					<option value="Christian">Christian</option>
					<option value="Sikhism">Sikhism</option>
				</select>
				<i><label class="text-red empreligion "></label></i>
			</div>
		</div> 
		<div class="row row-padding">
			<div class="col-md-6">
				<label>Mobile Number </label><span class="text-red">*</span>			 
				<input type="text" name="contactnumber" id="contactnumber" placeholder="Enter Contact Number" class="form-control phone_no" maxlength="10" onkeypress="return isNumber(event)"  />
				<i><label class="text-red PhoneNo"></label></i>
			</div>
			<div class="col-md-6">
				<label>Emergency Number </label>			 
				<input type="text" name="emernumber" id="emernumber" placeholder="Enter Emergency Number" class="form-control phone_no" maxlength="10"/>
				<i><label class="text-red EPhoneNo"></label></i>
		    </div>
		</div>
		<div class="row row-padding">
			<div class="col-md-6">
				<label>Present Address </label><span class="text-red">*</span>			 
				<textarea name="presaddress" id="presaddress" placeholder="Enter Your Present Address" class="form-control"  rows="2" style="resize: none;"></textarea>
				<i><label class="text-red Address"></label></i>
			</div>
		 
			<div class="col-md-6">
				<label>Permanent Address</label>
				<i class="text-blue margin-left"><input type="checkbox" id="prmaddraspres" class="" /> Same as Present</i>			 
				<textarea name="" id="prmaddress" placeholder="Enter Your Permanent Address" class="form-control" rows="2" style="resize: none;"></textarea>
			</div>
		</div>
		<div class="row row-padding">
			<div class="col-md-6">
				<label>Nationality </label>			 
				<input type="text" value="INDIAN" id="nationality" placeholder="Enter Nationality" class="form-control " readonly />
				<i><label class="text-red nationality "></label></i>
			</div>
		 
			<div class="col-md-6">
				<label>Email </label><span class="text-red">*</span>			 
				<input type="text" id="eemail" name="eemail" placeholder="Enter Email" class="form-control" required />
				<i><label class="text-red WEmail eemail"></label></i>
			</div>
		</div> 
		<div class="row row-padding">
			<div class="col-md-6" >
				<label>Adhar Number </label>
				<input type="text" name="adharnumber" id="adharnumber" placeholder="Enter Adhar Number" class="form-control adhar_number" maxlength="12" style="margin-top: 10px;"/>
				<i><label class="text-red Adhar"></label></i>
			</div>
			
			<div class="col-md-6">
				<label>PAN Number</label>
				<input type="text" name="pannumber" pattern="[A-Z]{5}[0-9]{4}[A-Z]{1}" id="pannumber" placeholder="Enter PAN Number" class="form-control" maxlength="10" style="margin-top: 10px;"/>
			<i><label class="text-red Pan"></label></i>
			</div>
		</div>		
		
		<div class="row mt-2">
			<div class="col-md-4" >
				<label>Upload Adhar Card</label> 
				<input type="file" class="form-control" id="uploadadhar" accept="application/pdf"/>
				<i><label class="text-red uploadadhar"></label></i>
			</div>
			
			<div class="col-md-4" style="padding-left:20px;">
				<label>Upload Pan Card</label> 
				<input type="file" class="form-control" id="uploadpan" accept="application/pdf"/>
				<i><label class="text-red uploadpan"></label></i>
			</div>
			
			<div class="col-md-4" style="padding-left:20px;" >
				<label>Upload Employee's Resume</label><span class="text-red">*</span>
				<input type="file" class="form-control" id="uploadresume" accept="application/pdf"/>
				<i><label class="text-red uploadresume"></label></i>
			</div>
		</div>
		<!-- <div class="row mt-2 mb-2" style="padding:8px 24px";>
			<input type="button" id="nextbtn" class="btn btn-blue float-right" value="Next"/>
		</div> -->
	</div>
	<div class="container" id="e_info">
		<div class="row">
			<div class="col-md-12 text-center mt-4 bg-success">
				<h6>EDUCATIONAL QUALIFICATIONS</h6>
			</div>
		</div><hr>				
		<div class="row row-padding">
			<div class="col-md-6">
			   <label>Highest Qualification</label><span class="text-red">*</span>
				<select class="form-control" id="highest_qualification" name="highest_qualification" >
					<option value="" selected>Select Qualification</option>
					<option value="Master Degree">Master Degree</option>
					<option value="Bachelor Degree">Bachelor Degree</option>
					<option value="12th Pass">HSC/ 12th</option>
					<option value="10th Pass">SSC/ 10th</option>
					<option value="Other">Other</option>
				 </select>
				 <i><label class="text-red highest_qualification"></label></i>
			</div>
			<div class="col-md-6" >
				<label>Specialisation</label><span class="text-red">*</span>
				<i class="text-blue text-small">(MA, MBA, BA, BBA, Diploma Any)</i>
				<input type="text" name="specialisation" id="specialisation" class="form-control" placeholder="Enter Specialisation"/>
				<i><label class="text-red specialisation"></label></i>
			</div>
		</div>
		<div class="row row-padding">
			<div class="col-md-6" >
				<label>Board/ University</label><span class="text-red">*</span>
				<input type="text" name="institution" id="institution" class="form-control" placeholder="Enter Board/ University Name"/>
				<i><label class="text-red institution"></label></i>
			</div>
			<div class="col-md-6" >
				<label>Year Of Passing</label><span class="text-red">*</span>
			<input type="text" name="yearofpassing" id="yearofpassing" class="form-control year" maxlength="4" placeholder="Enter Year of Passing" />
			<i><label class="text-red yearofpassing"></label></i>
			</div>
		</div>
		<div class="row mt-2">
			<div class="col-md-6">
				<label style="margin-top: 10px;">Upload Qualification Documents </label>
				<span class="text-red">*</span><br><span class="text-center"><i class="text-small">(SSC /10th, HSC /12th, Graduation, Post Graduation, Any Other)</i></span>
			</div>
			<div class="col-md-6">
				<input type="file" class="form-control" id="uploadeducation" accept="application/pdf"/>
				<i><label class="text-red uploadeducation"></label></i>
			</div>
		</div>
		<!-- <div class="row" style="padding:8px 24px";>
			<input type="button" id="btn1" class="btn btn-blue float-right" style="float:right" value="Next"/>
			<input type="button" id="back1" class="btn btn-blue" style="float:left" value="Back"/>
		</div> -->
	</div>
	<div class="container" id="o_info">
		<div class="row">
			<div class="col-md-12 text-center mt-4 bg-success">
				<h6>OFFICIAL INFORMATION</h6>
			</div>
		</div><hr> 
		<div class="row row-padding">
			<div class="col-md-6">
				<label>Experience(if any)</label><span class="text-red">*</span>
			 
				<input type="text" maxlength="2" name="experience" id="experience" class="form-control year" placeholder="Enter Your Experience" />
				<i><label class="text-red Experience"></label></i>
			</div>
			<div class="col-md-6">
				<label>Date Of Joining (YYYY-MM-DD)</label><span class="text-red">*</span>
				<input type="date" name="doj" id="doj" class="form-control" placeholder="Enter Joining Date"  />
				<i><label class="text-red Appointment"></label></i>
			</div>
		</div>
		<div class="row row-padding"> 
			<div class="col-md-6">
				<label>In Time  (24 Hr format)</label>
				<input type="time" name="" id="intime" class="form-control"  />
				<i><label class="text-red intime"></label></i>
			</div>
			<div class="col-md-6">
				<label>Out Time (24 Hr format)</label>
				<input type="time" name="" id="outtime" placeholder="" class="form-control"  />
				<i><label class="text-red outtime"></label></i>
			</div>
		</div>
		<!-- <div class="row" style="padding:8px 24px";>
			<input type="button" id="btn3" class="btn btn-blue float-right" style="float:right" value="Next"/>
			<input type="button" id="back3" class="btn btn-blue" style="float:left" value="Back"/>
		</div> -->
	</div>	
	
	<div class="container" id="s_info">
		<div class="row">
			<div class="col-md-12 text-center mt-4 bg-success">
				<h6>SALARY DETAILS</h6>
			</div>
		</div><hr>
		<div class="row row-padding">
			<div class="col-md-6">
				<label>Payment Mode</label><span class="text-red">*</span>
				<select name="" id="paymentmode" class="form-control">
					<option selected Disabled>Select payment Mode</option>
					<option value="eTransfer">Electronic Transfer</option>
					<option value="cash">Cash</option>
				</select>
				<i><label class="text-red paymentmode"></label></i>
			</div>
		
			<div class="col-md-6">
				<label>salary </label><span class="text-red">*</span>
				<input type="" name="" id="totalsalary" class="form-control" onkeypress="return isNumber(event)" maxlength="6" placeholder="Enter salary" />
				<i><label class="text-red totalsalary"></label></i>
			</div>
		</div>
		<!-- <div class="row" style="padding:8px 24px";>
			<input type="button" id="btn4" class="btn btn-blue float-right" style="float:right" value="Next"/>
			<input type="button" id="back4" class="btn btn-blue" style="float:left" value="Back"/>
		</div> -->
	</div>
	
	<div class="container" id="bnk_info">
		<div class="row">
			<div class="col-md-12 text-center mt-4 bg-success">
				<h6>BANK DETAILS</h6>
			</div>
		</div><hr>
		
		<div class="row row-padding">
			<div class="col-md-4">
				<label>Bank Name </label><span class="text-red">*</span>
				<input type="" name="bankname" id="bankname" onKeyPress="return ValidateAlpha(event);" class="form-control" placeholder="Enter your Bank Name" />
				<i><label class="text-red bank_name"></label></i>
			</div>
			<div class="col-md-4">
				<label>Account No </label><span class="text-red">*</span>
				<input type="" name="accountno" id="accountno" placeholder="Enter Account No." class="form-control" maxlength="16" onkeypress="return isNumber(event)"  />
				<i><label class="text-red account_no"></label></i>
			</div>
			
			<div class="col-md-4">
				<label>IFSC code</label><span class="text-red">*</span>
				<input type="" name="ifsccode" id="ifsccode" class="form-control" placeholder="Enter IFSC Code" maxlength="11"/>
				<i><label class="text-red ifsc"></label></i>
			</div>
			
		</div>
		
		<div class="row">
			<div class="col-md-6">
				<label style="margin-top: 10px;">Upload Bank Documents </label>
				<span class="text-red">*</span><br><span class="text-small">(Only Bank Passbook, Cencal Cheque)</span>
			</div>
			<div class="col-md-6">
				<input type="file" class="form-control" id="uploadbank" accept="application/pdf"/>
				<i><label class="text-red uploadbank"></label></i>
			</div>
		</div>
		
		<!-- <div class="row" style="padding:8px 24px";>
			<input type="button" id="btn5" class="btn btn-blue float-right" style="float:right" value="Next"/>
			<input type="button" id="back5" class="btn btn-blue" style="float:left" value="Back"/>
		</div> -->
	</div>
	
	<div class="container" id="f_info">
		<div class="row">
			<div class="col-md-12 text-center mt-4 bg-success">
				<h6>FAMILY DETAILS</h6>
			</div>
		</div><hr>
		<div class="row">
			<div class="col-md-6">
				<label>Father Name </label><span class="text-red">*</span>
				<input type="text" name="fathername" id="fathername" placeholder="Enter Father Name" onKeyPress="return ValidateAlpha(event);" class="form-control"/>
				 <i><label class="text-red fathername"></label></i>
			</div>
			<div class="col-md-6">
				<label>Mother Name </label><span class="text-red">*</span>
				<input type="text" name="mothername" id="mothername" placeholder="Enter Mother Name" onKeyPress="return ValidateAlpha(event);" class="form-control"/>
				 <i><label class="text-red mothername"></label></i>
			</div>
		</div>
		<hr>
		<div class="row">
			<div class="col-md-12">
				<label><input type="checkbox" id="chkSelect"/>&nbsp;<b>I declare all the information given in this form are true and correct.</b>
					<i class="text-red text-small chkSelectlable"></i></label>
			</div>
		</div>
		<div class="row mt-4">
			<div class="col-md-12 text-center">
				<!-- <input type="button" id="back6" class="btn btn-blue" value="Back"/> -->
				<input type="button" id="submitempregistrationbtn" onclick="newemployeeregistration('hidden_baseurl','hidden_companyid','hidden_subcompanyid','hidden_projectid','employeetypename', 'employeename', 'empdob', 'empgender', 'uploadphoto', 'empmaritalstatus', 'empreligion', 'contactnumber', 'emernumber', 'presaddress', 'prmaddress', 'nationality', 'eemail', 'adharnumber', 'pannumber', 'uploadadhar', 'uploadpan', 'uploadresume', 'highest_qualification', 'specialisation', 'institution', 'yearofpassing', 'uploadeducation', 'experience', 'doj', 'intime', 'outtime', 'paymentmode', 'totalsalary', 'bankname', 'accountno', 'ifsccode', 'uploadbank', 'fathername', 'mothername', 'hidden_loginname', 'chkSelect', 'submitempregistrationbtn');" class="btn btn-success" style="cursor:pointer;" value="Submit" />
			</div>			 
		</div>
	</div>	
	 
	<div id="test_modal" class="w3-modal">
		<div class="w3-modal-content text-center-64" style="background-color : rgb(20,14,15,0.5);">
			<p class=" w3-xlarge" id="diplay_msg"></p>
			<input type="button" id="ok_btn" class="btn mt-2 text-center btn-blue" style="display: none;" value="O K"/>
		</div>
	</div>
</div>
<script>
	function validateEmail__(email) {
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

//messageAfterresult();

function isNumber(evt) {
	var charCode = (evt.which) ? evt.which : evt.keyCode;
	if (charCode != 46 && charCode > 31 &&
		(charCode < 48 || charCode > 57))
		return false;
	return true;
}
function ValidateAlpha(evt)
{
    var keyCode = (evt.which) ? evt.which : evt.keyCode
    if ((keyCode < 65 || keyCode > 90) && (keyCode < 97 || keyCode > 123) && keyCode != 32)
     
    return false;
        return true;
}

function setintimeouttime(category,intime,outtime){
	var categoryval = $("#"+category).val();
	if(categoryval=="1"){
		$("#"+intime).val('09:00');
		$("#"+outtime).val('17:30');
	}else if(categoryval=="2"){
		$("#"+intime).val('08:00');
		$("#"+outtime).val('14:00');
	}else if(categoryval=="3"){
		$("#"+intime).val('09:00');
		$("#"+outtime).val('17:30');
	}else{
		$("#"+intime).va('00:00');
		$("#"+outtime).val('00:00');
	}
}
$(document).ready(function() {    
    var readURL = function(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();

            reader.onload = function (e) {
                $('.profile-pic').attr('src', e.target.result);
            }
    
            reader.readAsDataURL(input.files[0]);
        }
    }    

    $(".file-upload").on('change', function(){
        readURL(this);
    });
    
    $(".upload-button").on('click', function() {
       $(".file-upload").click();
    });
});

$(document).ready(function()
{
	$('#submit_btn').prop('disabled', true);
	$(".phone_no").keydown(function (e)
	{
		// Allow: backspace, delete, tab, escape, enter and .
		if ($.inArray(e.keyCode, [46, 8, 9, 27, 13, 110, 190]) !== -1 ||
		// Allow: Ctrl+A, Command+A
		(e.keyCode === 65 && (e.ctrlKey === true || e.metaKey === true)) || 
		// Allow: home, end, left, right, down, up
		(e.keyCode >= 35 && e.keyCode <= 40))
		{
			// let it happen, don't do anything
			return;
		}
		// Ensure that it is a number and stop the keypress
		if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105))
		{
			e.preventDefault();
		}
	});
	$(".age").keydown(function (e)
	{
		// Allow: backspace, delete, tab, escape, enter and .
		if ($.inArray(e.keyCode, [46, 8, 9, 27, 13, 110, 190]) !== -1 ||
		// Allow: Ctrl+A, Command+A
		(e.keyCode === 65 && (e.ctrlKey === true || e.metaKey === true)) || 
		// Allow: home, end, left, right, down, up
		(e.keyCode >= 35 && e.keyCode <= 40))
		{
			// let it happen, don't do anything
			return;
		}
		// Ensure that it is a number and stop the keypress
		if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105))
		{
			e.preventDefault();
		}
	});
	$(".year").keydown(function (e)
	{
		// Allow: backspace, delete, tab, escape, enter and .
		if ($.inArray(e.keyCode, [46, 8, 9, 27, 13, 110, 190]) !== -1 ||
		// Allow: Ctrl+A, Command+A
		(e.keyCode === 65 && (e.ctrlKey === true || e.metaKey === true)) || 
		// Allow: home, end, left, right, down, up
		(e.keyCode >= 35 && e.keyCode <= 40))
		{
			// let it happen, don't do anything
			return;
		}
		// Ensure that it is a number and stop the keypress
		if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105))
		{
			e.preventDefault();
		}
	});
	$(".adhar_number").keydown(function (e)
	{
		// Allow: backspace, delete, tab, escape, enter and .
		if ($.inArray(e.keyCode, [46, 8, 9, 27, 13, 110, 190]) !== -1 ||
		// Allow: Ctrl+A, Command+A
		(e.keyCode === 65 && (e.ctrlKey === true || e.metaKey === true)) || 
		// Allow: home, end, left, right, down, up
		(e.keyCode >= 35 && e.keyCode <= 40))
		{
			// let it happen, don't do anything
			return;
		}
		// Ensure that it is a number and stop the keypress
		if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105))
		{
			e.preventDefault();
		}
	});
	 // $('#formfilledbydiv').hide();
	 // $('#e_info').hide();
	 // $('#o_info').hide();
	 // $('#s_info').hide();
	 // $('#bnk_info').hide(); 
	 // $('#f_info').hide();
	 
	$('#prmaddraspres').change(function ()
	{
		var address = $('#address').val();
		if($('#prmaddraspres').is(":checked"))
		{
		  $('#prmaddress').val(address);
		}else{
			$('#prmaddress').val(' ');
		}
	});
	
	/*$("#back1").click(function()
	{
		
		$('#p_info').show();
		$('#e_info').hide();
		$('#o_info').hide();
		$('#nextbtn').show();
		$('#f_info').hide();
		$('#back3').hide();
		$('#btn3').hide();
	});
	$('#back3').click(function()
	{
	  $('#p_info').hide();
	  $('#e_info').show();
	  $('#f_info').hide();
	  $('#btn3').hide()
	  $('#o_info').hide();
	  $('#back1').show();
	  $('#btn').show();
	});
	$("#nextbtn").click(function()
	{
		var first_name = $('#first_name').val();
		var empdob = $('#empdob').val();
		var eemail = $('#eemail').val();
		var empgender = $('#empgender').val();
		var empmaritalstatus = $('#empmaritalstatus').val();
		var address = $('#address').val();
		var pm_address = $('#pm_address').val();
		var empreligion = $('#empreligion').val();
		var nationality = $('#nationality').val();
		var contact_number = $('#contact_number').val();
		var emer_number = $('#emer_number').val();
		var adhar = $('#adhar').val();
		var pan = $('#pan').val();
		
		
		if(first_name=='' || first_name==null)
		{
			$('.FirstName').text('Please enter name.');
			return false;
		}
		else
		{
			$('.FirstName').text();
			$('.FirstName').hide();
		}
		
		if(empdob=='' || empdob==null)
		{
			$('.DOB').text('Please enter D.O.B.');
			return false;
		}
		else
		{
			$('.DOB').text();
			$('.DOB').hide();
		}
		
		if(empgender=='' || empgender==null)
		{
			$('.SltGender').text('Please select atleast one gender name.');
			return false;
		}
		else
		{
			$('.SltGender').text();
			$('.SltGender').hide();
		}
		
		if($('#upload_photo').val()=='')
		{
			alert('Please upload photo.');
			return false;
		}else{
			
		}
		
		if(empmaritalstatus=='' || empmaritalstatus==null)
		{
			$('.MStatus').text('Please select one option.');
			return false;
		}
		else
		{
			$('.MStatus').text();
			$('.MStatus').hide();
		}
		
		
		if(empreligion=='' || empreligion==null)
		{
			$('.empreligion').text('Please fill empreligion.');
			return false;
		}
		else
		{
			$('.empreligion').text();
			$('.empreligion').hide();
		}
		
		if(contact_number=="" || contact_number==null)
		{
			$('.PhoneNo').text('Please enter Mobile No');
				return false;
		}
		else
		{
			if(contact_number.length<=9)
			{
				$('.PhoneNo').text('Please enter a valid Mobile No');
				return false;
			}
			else
			{
				$('.PhoneNo').text();
				$('.PhoneNo').hide();
				 
			}
		}
		
		 
		if(address=='' || address==null)
		{
			$('.Address').text('Please fill address.');
			return false;
		}
		else
		{
			$('.Address').text();
			$('.Address').hide();
		}
		
		
		if(nationality=='' || nationality==null)
		{
			$('.nationality').text('Please fill Nationality.');
			return false;
		}
		else
		{
			$('.nationality').text();
			$('.nationality').hide();
		}
		
		if(eemail=='' || eemail==null)
		{
			$('.WEmail').text('Please enter Email');
			return false;
			
		}
		else
		{

			var emailRegex =  /^([\w-\.]+@([\w-]+\.)+[\w-]{2,4})?$/;
			if(!emailRegex.test(eemail))
			{
				$('.WEmail').text('Please enter valid Email');
				return false;
			}
			else
			{
				$('.WEmail').text();
				$('.WEmail').hide();
			}
		}
		

		

		if($('#upload_resume').val()=='')
		{
			alert('Please upload Resume.');
			return false;
		}else{
			
		}
		$('#p_info').hide();
		$('#e_info').show();
		$('#nextbtn').hide();

	});


	$("#btn1").click(function()
	{
		var highest_qualification = $('#highest_qualification').val();
		var specialisation = $('#specialisation').val();
		var institution = $('#institution').val();
		var year_earned = $('#year_earned').val();
		
		 
		if(highest_qualification=='' || highest_qualification==null)
		{
			$('.highest_qualification').text('Please Select Highest Qualification.');
			return false;
		}
		else
		{
			$('.highest_qualification').text();
			$('.highest_qualification').hide();
		}
		
		if(specialisation=='' || specialisation==null)
		{
			$('.specialisation').text('Please fill specialisation name.');
			return false;
		}
		else
		{
			$('.specialisation').text();
			$('.specialisation').hide();
		}
		
		if(institution=='' || institution==null)
		{
			$('.institution').text('Please fill Institution name.');
			return false;
		}
		else
		{
			$('.institution').text();
			$('.institution').hide();
		}
		
		if(year_earned=='' || year_earned==null)
		{
			$('.year_earned').text('Please fill Year.');
			return false;
		}
		else
		{
			$('.year_earned').text();
			$('.year_earned').hide();
		}
		 
		 
		if($('#upload_education').val()=='')
		{
			alert('Please upload Qualification Documents.');
			return false;
		}else{
			
		} 
		
		$('#p_info').hide();
		$('#o_info').show();
		$('#e_info').hide();
		$('#btn').hide();
		$('#f_info').hide();
		$('#btn3').show();
		$('#back3').show();
		$('#submit').prop('disabled', false);<!--->
		$('#formfilledbydiv').hide();
	});
	$("#btn3").click(function()
	{
		
		var experience = $('#experience').val();
		var d_o_a = $('#d_o_a').val();
		var designation = $('#designation').val();
		var category = $('#category').val();
		var weekend = $('#weekend').val();
		var overtime = $('#overtime').val();
		var relax_hr = $('#relax_hr').val();
		var working_sunday = $('#working_sunday').val();
		var paid_leaves = $('#paid_leaves').val();
		var in_time = $('#in_time').val();
		var out_time = $('#out_time').val();
		var branch = $('#branch').val();
		//var dept = $('#dept').val();
		
		if(experience=='' || experience==null)
		{
			$('.Experience').text('Please enter experience.');
			return false;
		}
		else
		{
			$('.Experience').text();
			$('.Experience').hide();
		}
		if(d_o_a=='' || d_o_a==null)
		{
			$('.Appointment').text('Please enter joining date.');
			return false;
		}
		else
		{
			$('.Appointment').text();
			$('.Appointment').hide();
		}
		
		if(branch=='' || branch==null)
		{
			$('.branch').text('Please enter Branch.');
			return false;
		}
		else
		{
			$('.branch').text();
			$('.branch').hide();
		}
		
		if(designation=='' || designation==null)
		{
			$('.Designation').text('Please enter designation.');
			return false;
		}
		else
		{
			$('.Designation').text();
			$('.Designation').hide();
		}
		
		if(category=='' || category==null)
		{
			$('.category').text('Please enter category.');
			return false;
		}
		else
		{
			$('.category').text();
			$('.category').hide();
		}
		
		if(weekend=='' || weekend==null)
		{
			$('.weekend').text('Please enter weekend.');
			return false;
		}
		else
		{
			$('.weekend').text();
			$('.weekend').hide();
		}
		
			
		if(overtime=='' || overtime==null)
		{
			$('.overtime').text('Please enter overtime.');
			return false;
		}
		else
		{
			$('.overtime').text();
			$('.overtime').hide();
		}
		
		if(paid_leaves=='' || paid_leaves==null)
		{
			$('.paid_leaves').text('Please enter No. of Paid Leaves.');
			return false;
		}
		else
		{
			$('.paid_leaves').text();
			$('.paid_leaves').hide();
		}
		
		$('#s_info').show();
		$('#e_info').hide();
		$('#f_info').hide();
		$('#o_info').hide();
		$('#back3').hide();
		$('#btn3').hide();
		$('#btn4').show();
		$('#submit_btn').prop('disabled', false);
		$('#formfilledbydiv').hide();
		
		
	});
	$("#btn4").click(function()
	{
		var total_salary = $('#total_salary').val();
		var payment_mode = $('#payment_mode').val();
		
		if(payment_mode=='' || payment_mode==null)
		{
			$('.payment_mode').text('Please Select Mode of payment.');
			return false;
		}
		
		if(total_salary=='' || total_salary==null)
		{
			$('.total_salary').text('Please enter Basic Salary.');
			return false;
		}
		else
		{
			$('.total_salary').text();
			$('.total_salary').hide();
		}
		
		$('.payment_mode').text();
		$('.payment_mode').hide();
		$('#bnk_info').show();
		$('#btn5').show();
		$('#back5').show();
		$('#e_info').hide();
		$('#o_info').hide();
		$('#s_info').hide();
		$('#p_info').hide();
		$('#f_info').hide();
		//$('#upload_div').hide();
		$('#formfilledbydiv').hide();
		 
	});
	$("#back4").click(function()
	{
		$('#bnk_info_info').hide();
		$('#e_info').hide();
		$('#o_info').show();
		$('#s_info').hide();
		$('#p_info').hide();
		$('#f_info').hide();
		$('#btn4').hide();
		//$('#upload_div').hide();
		$('#formfilledbydiv').hide();
		$('#btn3').show();
		$('#back3').show();
	});
	
	$("#btn5").click(function()
	{
		var bank_name = $('#bank_name').val();
		var ifsc = $('#ifsc').val();
		var account_no = $('#account_no').val();
		var bank_branch = $('#bank_branch').val();
		
		if(bank_name=='' || bank_name==null)
		{
			$('.bank_name').text('Please Enter Bank Name.');
			return false;
		}
		else
		{
			$('.bank_name').text();
			$('.bank_name').hide();
		}
		if(bank_branch=='' || bank_branch==null)
		{
			$('.bank_branch').text('Please Enter Bank Branch.');
			return false;
		}
		else
		{
			$('.bank_branch').text();
			$('.bank_branch').hide();
		}
		
		
		if(account_no=='' || account_no==null)
		{
			$('.account_no').text('Please fill Account no.');
			return false;
		}
		else
		{
			$('.account_no').text();
			$('.account_no').hide();
		}
		if(ifsc=='' || ifsc==null)
		{
			$('.ifsc').text('Please fill IFSC Code.');
			return false;
		}
		else
		{
			$('.ifsc').text();
			$('.ifsc').hide();
		} 
		
		 
		if($('#upload_bank').val()=='')
		{
			alert('Please upload bank Documents.');
			return false;
		}else{
			
		}  
		
		
		$('#bnk_info').hide();
		$('#btn6').show();
		$('#back6').show();
		$('#e_info').hide();
		$('#o_info').hide();
		$('#s_info').hide();
		$('#p_info').hide();
		$('#f_info').show();
		//$('#upload_div').hide();
		$('#formfilledbydiv').hide();
	});
	$("#back5").click(function()
	{
		$('#bnk_info').hide();
		$('#e_info').hide();
		$('#o_info').hide();
		$('#s_info').show();
		$('#p_info').hide();
		$('#f_info').hide();
		$('#btn4').show();
		//$('#upload_div').hide();
		$('#formfilledbydiv').hide();
		$('#btn3').show();
		$('#back4').show();
	});
	
	$("#btn6").click(function()
	{
		$('#bnk_info').hide();
		$('#btn7').show();
		$('#back6').hide();
		$('#e_info').hide();
		$('#o_info').hide();
		$('#f_info').hide();
		$('#s_info').hide();
		$('#p_info').hide();
		//$('#upload_div').hide();
		$('#formfilledbydiv').hide();
	});
	
	$("#back6").click(function()
	{
		$('#p_info').hide();
		$('#e_info').hide();
		$('#o_info').hide();
		$('#s_info').hide();
		$('#bnk_info').show();
		$('#f_info').hide();
		$('#nextbtn').hide();
		$('#btn5').show();
		$('#back5').show();
		$('#formfilledbydiv').hide();
	});
	
	
	$("#back7").click(function()
	{
		$('#p_info').hide();
		$('#e_info').hide();
		$('#o_info').hide();
		$('#s_info').hide();
		$('#bnk_info').hide();
		$('#f_info').show();
		$('#nextbtn').hide();
		$('#btn6').show();
		$('#back6').show();
		$('#formfilledbydiv').hide();
		
	});*/
	
	$("#submit_btn").click(function()
	{ 
		var form_data = new FormData();
		
		var submit_btn_id = $(this).attr('id');
		form_data.append('submit_btn_id', submit_btn_id); 
		
		var employee_name = $('#employeename').val();
		form_data.append('employee_name', employee_name);
		
		var empdob = $('#empdob').val();
		form_data.append('empdob', empdob);
		 
		var eemail = $('#eemail').val();
		form_data.append('eemail', eemail);
		
		var empgender = $('#empgender').val();
		form_data.append('empgender', empgender);
		
		var empmaritalstatus = $('#empmaritalstatus').val();
		form_data.append('empmaritalstatus', empmaritalstatus);
		
		var address = $('#address').val();
		form_data.append('address', address);
		
		var pm_address = $('#pm_address').val();
		form_data.append('pm_address', pm_address);
		
		var empreligion = $('#empreligion').val();
		form_data.append('empreligion', empreligion);
		
		var nationality = $('#nationality').val();
		form_data.append('nationality', nationality);
		
		var contact_number = $('#contact_number').val();
		form_data.append('contact_number', contact_number);
		
		var emer_number = $('#emer_number').val();
		form_data.append('emer_number', emer_number);
		
		var adhar = $('#adhar').val();
		form_data.append('adhar', adhar);
		
		var pan = $('#pan').val();
		form_data.append('pan', pan);
		
		var highest_qualification = $('#highest_qualification').val();
		form_data.append('highest_qualification', highest_qualification);
		
		var specialisation = $('#specialisation').val();
		form_data.append('specialisation', specialisation);
		
		var institution = $('#institution').val();
		form_data.append('institution', institution);
		
		var year_earned = $('#year_earned').val();
		form_data.append('year_earned', year_earned);
		
		var experience = $('#experience').val();
		form_data.append('experience', experience);
		
		var d_o_a = $('#d_o_a').val();
		form_data.append('d_o_a', d_o_a);
		
		var designation = $('#designation').val();
		form_data.append('designation', designation);
	
		var category = $('#category').val();
		form_data.append('category', category);
		
		var weekend = $('#weekend').val();
		form_data.append('weekend', weekend);
		
		var overtime = $('#overtime').val();
		form_data.append('overtime', overtime);
		
		var relax_hr = $('#relax_hr').val();
		form_data.append('relax_hr', relax_hr);
		
		var working_sunday = $('#working_sunday').val();
		form_data.append('working_sunday', working_sunday);
		
		var paid_leaves = $('#paid_leaves').val();
		form_data.append('paid_leaves', paid_leaves);
		
		var in_time = $('#in_time').val();
		form_data.append('in_time', in_time);
		
		var out_time = $('#out_time').val();
		form_data.append('out_time', out_time);
		
		var branch = $('#branch').val();
		form_data.append('branch', branch);
		 
		var total_salary = $('#total_salary').val();
		form_data.append('total_salary', total_salary);
		 
		var payment_mode = $('#payment_mode').val();
		form_data.append('payment_mode', payment_mode);
		
		var bank_name = $('#bank_name').val();
		form_data.append('bank_name', bank_name);
		
		var ifsc = $('#ifsc').val();
		if(ifsc!=""){
			var reg = /[A-Z|a-z]{4}[0][a-zA-Z0-9]{6}$/;
			if (ifsc.match(reg)) {
            } else {
                alert("You entered invalid IFSC code");
                document.getElementById("ifsc").focus();    
                return false;    
            } 
		}
		form_data.append('ifsc', ifsc);
		
		var account_no = $('#account_no').val();
		form_data.append('account_no', account_no);
		
		var bank_branch = $('#bank_branch').val();
		form_data.append('bank_branch', bank_branch);
		
		var father_name = $('#father_name').val();
		form_data.append('father_name', father_name);
		
		var fage = $('#fage').val();
		form_data.append('fage', fage);
	
		var mother_name = $('#mother_name').val();
		form_data.append('mother_name', mother_name);
		
		var mage = $('#mage').val();
		form_data.append('mage', mage);
		
		var husband_name = $('#husband_name').val();
		form_data.append('husband_name', husband_name);
		
		var hage = $('#hage').val();
		form_data.append('hage', hage);
		
		var spouse_name = $('#spouse_name').val();
		form_data.append('spouse_name', spouse_name);
		
		var sage = $('#sage').val();
		form_data.append('sage', sage);
		
		var file_data_img = $('#upload_photo').prop('files')[0];   
		form_data.append('photo', file_data_img);
		
		var file_data = $('#upload_resume').prop('files')[0];   
		form_data.append('resumedoc', file_data);
		
		var file_data = $('#upload_adhar').prop('files')[0];   
		form_data.append('adhardoc', file_data);
		
		var file_data = $('#upload_pan').prop('files')[0];   
		form_data.append('pandoc', file_data);
		
		var file_data = $('#upload_bank').prop('files')[0];   
		form_data.append('bankdoc', file_data);
		
		var file_data = $('#upload_education').prop('files')[0];   
		form_data.append('educationdoc', file_data);
		
		var formfilledby = $('#formfilledby').val();
		form_data.append('formfilledby', formfilledby);
		 
		if(empmaritalstatus=="Single")
		{
			if(father_name=='' || father_name==null)
			  {
				$('.FatherName').text('Please enter Fathers name.');
				 return false;
			  }
			   else
			  {
			    $('.FatherName').text('');
			    $('.FatherName').hide();
			  }
			
			 
			 
			if(mother_name=='' || mother_name==null)
			{
				$('.MName').text('Please fill Mothers name.');
				return false;
			}
			else
			{
				$('.MName').text();
				$('.MName').hide();
			}
			 
			}
		else if(empmaritalstatus=="Married")
		{
			if(empgender=="Female")
			{
				if(husband_name=='' || husband_name==null)
				{
					$('.HName').text('Please enter name.');
					return false;
				}
				else
				{
					$('.HName').text();
					$('.HName').hide();
				}
				 
			}
			else if(empgender=="Male")
			{
					if(father_name=='' || father_name==null)
				{
					$('.FatherName').text('Please enter Fathers name.');
					 return false;
				}
				else
				{
					$('.FatherName').text('');
					$('.FatherName').hide();
				}
				if(mother_name=='' || mother_name==null)
				{
					$('.MName').text('Please fill Mothers name.');
					return false;
				}
				else
				{
					$('.MName').text();
					$('.MName').hide();
				}
				
				if(spouse_name=='' || spouse_name==null)
				{
					$('.SName').text('Please fill name.');
					return false;
				}
				else
				{
					$('.SName').text();
					$('.SName').hide();
				}
				 
			}
		}
		if(formfilledby=='' || formfilledby==null)
		{
			$('.FilledBy').text('Please enter name.');
			return false;
		}
		else
		{
			$('.FilledBy').text();
			$('.FilledBy').hide();
		}
		
		if($('#chkSelect').is(":checked"))
		{
		  
		}else{
			$('.chkSelectlable').text('Please checke this box.');
			return false;
		}
		$.ajax
		({
			type: "POST",
			url: "savetoregistrationform.php",
			data: form_data,
			enctype: 'multipart/form-data',
			contentType: false,
			processData: false,
			cache: false,
			success: function(html)
			{	$("#diplay_msg").html(html);
				$('#test_modal').show();
				$('#ok_btn').show();
			},
			beforeSend: function(html)
			{
				$('#test_modal').show();
				$("#diplay_msg").html('<img src="../img/loading.gif" alt="Load-Image" style="width:50px; height:50px; margin:20px;" /><br><b style="color:red;">Please Wait...</b>');
			}
		});
		
		$('#ok_btn').click(function ()
		{
			$('#test_modal').hide();
			location.reload();
		});
		
	});
});
</script>