<div class="modal fade  bd-example-modal-sm" id="beforeSend" tabindex="-1" role="dialog">
	<div class="modal-dialog modal-dialog-centered modal-sm" style="width: 150px; height: auto;" role="document">
		<div class="modal-content">
			<div class="modal-body">
				<img src="<?php echo $prefix; ?>img/loading.gif" />
			</div>
		</div>
	</div>
</div>

<div class="modal" id="replacement_purposedetails_modal" role="dialog">
	<div class="modal-dialog modal-lg" style="max-width: 98%;">
		<div class="modal-content">
			<div class="modal-header bg-success">
				<h5 class="modal-title" id="replacement_purposeheader">Replacement of Purpose</h5>
				<button type="button" class="btn close" data-dismiss="modal" aria-label="Close">X</button>
			</div>
			<div class="modal-body">
				<div id="replacement_purposedivid"></div>
			</div>
		</div>
	</div>
</div>

<div class="modal fade" id="newpurposedetails_modal" role="dialog">
	<div class="modal-dialog modal-lg" style="max-width: 98%;">
		<div class="modal-content">
			<div class="modal-header bg-info">
				<h5 class="modal-title" id="purposeheader">Add New Purpose</h5>
				<button type="button" class="btn close" data-dismiss="modal" aria-label="Close">X</button>
			</div>
			<div class="modal-body" style="background-color:rgb(150, 203, 208, 0.8);">
				<div id="newpurposedivid"></div>
			</div>
		</div>
	</div>
</div>
<div class="modal fade" id="webpagebodyview_modal" role="dialog">
	<div class="modal-dialog modal-lg" style="max-width: 98%;">
		<div class="modal-content">
			<div class="modal-header bg-info">
				<h5 class="modal-title" id="webmodalheader">Details</h5>
				<button type="button" class="btn close" data-dismiss="modal" aria-label="Close">X</button>
			</div>
			<div class="modal-body" style="background-color:rgb(200, 213, 228, 0.8);">
				<div id="webpagebodydiv"></div>
			</div>
		</div>
	</div>
</div>

<div class="modal" id="webpagebodyview1_modal" role="dialog">
	<div class="modal-dialog modal-lg" style="max-width: 98%;">
		<div class="modal-content">
			<div class="modal-header bg-info">
				<h5 class="modal-title" id="modalheader1">Details</h5>
				<button type="button" class="btn close" data-dismiss="modal" aria-label="Close">X</button>
			</div>
			<div class="modal-body" style="background-color:rgb(200, 213, 228, 0.8);">
				<div id="webpagebodydiv1"></div>
			</div>
		</div>
	</div>
</div>

<div class="modal" id="webpagebodyview2_modal" role="dialog">
	<div class="modal-dialog modal-lg" style="max-width: 98%;">
		<div class="modal-content">
			<div class="modal-header bg-info">
				<h5 class="modal-title" id="modalheader2">Details</h5>
				<button type="button" class="btn close" data-dismiss="modal" aria-label="Close">X</button>
			</div>
			<div class="modal-body" style="background-color:rgb(200, 213, 228, 0.8);">
				<div id="webpagebodydiv2"></div>
			</div>
		</div>
	</div>
</div>

<div class="modal fade" id="webpagebodyview_mdmodal" role="dialog">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header bg-info">
				<h5 class="modal-title" id="modalheadermd">Details</h5>
				<button type="button" class="btn close" data-dismiss="modal" aria-label="Close">X</button>
			</div>
			<div class="modal-body" style="background-color:rgb(200, 213, 228, 0.8);">
				<div id="webpagebodydivmd"></div>
			</div>
		</div>
	</div>
</div>

<div class="modal" id="updpreceipt_modal" tabindex="-1" role="dialog">
	<div class="modal-dialog modal-lg" style="max-width: 98%;">
		<div class="modal-content">
			<div class="modal-header bg-success">
				<h5 class="modal-title">Upload Payment Receipt</h5>
				<button type="button" class="btn close" data-dismiss="modal" aria-label="Close">X</button>
			</div>
			<div class="modal-body">
				<div class="container">
					<div class="row">
						<div class="col-md-12 p-4">
							<span id="outputmsg" class="text-success"></span>
						</div>
						<div class="col-md-12">
							<p>Would you like to upload the payment receipt?</p>
							<label class="mt-2"><input type="radio" name="uppreceiptradio" value="yes" /> YES</label>
							<label class="mt-2"><input type="radio" name="uppreceiptradio" value="no" /> NO</label>
						</div>
					</div>
					<div class="row mt-4" id="uppdiv" style="display: none;">
						<div class="col-md-12">
							<input type="file" class="form-control" name="uppdreceiptfile" id="uppdreceiptfile" />
						</div>
						<div class="col-md-12 text-center mt-4">
							<button type="button" class="btn btn-sm btn-success" id="uploadreceiptfilebtn"
								onclick="upload_paymentreceipt('hidden_rooturl','hidden_companyid','hidden_subcompanyid','hidden_projectid','uppdreceiptfile','hidden_loginname','uploadreceiptfilebtn');">Upload
								Payment Receipt</button>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="modal" id="billpayment_modal" role="dialog">
	<div class="modal-dialog modal-lg" style="max-width: 98%;">
		<div class="modal-content">
			<div class="modal-header bg-success">
				<h5 class="modal-title" id="billpaymentheader">Details</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">X</button>
			</div>
			<div class="modal-body" style="background-color:rgb(193, 232, 212, 0.8);">
				<div class="row">
					<div class="col-md-6">
						<div class="row">
							<div class="col-md-12">
								<label>Bill Pay To Name</label>
								<input type="text" class="form-control" name="billpaymentpaytoname"
									id="billpaymentpaytoname" readonly />
							</div>
						</div>
						<div class="row">
							<div class="col-md-12">
								<label>Bill Payment on Behalf Of</label>
								<input type="text" class="form-control" name="billpaymentbeneficiaryname"
									id="billpaymentbeneficiaryname" readonly />
							</div>
						</div>
						<div class="row">
							<div class="col-md-12">
								<label>What is the Bill Amount?</label>
								<input type="number" class="form-control" name="billpaymentamount"
									id="billpaymentamount" />
							</div>
						</div>
						<div class="row" style="display: none;">
							<div class="col-md-12">
								<label>What is the Bill Type?</label>
								<select id="billinvoicetype" class="form-control"></select>
							</div>
						</div>
						<div class="row">
							<div class="col-md-12">
								<label>What is the Bill Number?</label>
								<input type="text" class="form-control" name="billpaymentnumber" id="billpaymentnumber"
									onblur="checkinvoicenumberexist('hidden_rooturl','hidden_companyid','hidden_subcompanyid','hidden_projectid',this.value);" />
							</div>
						</div>

						<div class="row">
							<div class="col-md-12">
								<label>What is the Bill Date?</label>
								<input type="date" class="form-control" name="billpaymentdate" id="billpaymentdate" />
							</div>
						</div>
						<div class="row">
							<div class="col-md-12">
								<label>What is the Bill Payment Due Date?</label>
								<input type="date" class="form-control" name="billpaymentduedate"
									id="billpaymentduedate" />
							</div>
						</div>
						<div class="row">
							<div class="col-md-12">
								<label>Remark</label>
								<textarea class="form-control" rows="4" name="billpaymentremark"
									id="billpaymentremark"></textarea>
							</div>
						</div>
					</div>
					<div class="col-md-6">
						<label>Attachement of File</label>
						<input type="file" class="form-control" name="billpaymentattachement"
							onchange="filepreview_Onchange(event, 'billpaymentattachement_div');"
							id="billpaymentattachement" />
						<div id="billpaymentattachement_div" class="mt-4">
							<h2 class="text-center">No Document</h2>
						</div>
					</div>
				</div>
				<div class="row mt-4">
					<div class="col-md-12 text-center">
						<button id="billpaymentsavetodb" class="btn btn-success"
							onclick='savetodb_billpaymentrequest("hidden_baseurl","hidden_companyid","hidden_subcompanyid","hidden_projectid", "billpaymentamount","billinvoicetype","billpaymentnumber","billpaymentdate","billpaymentduedate", "billpaymentremark","billpaymentattachement","hidden_loginname","billpaymentsavetodb");'>Create
							Request of Bill Payment</button>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="modal" id="newimprestpaytodetails_modal" role="dialog">
	<div class="modal-dialog modal-lg" style="max-width: 98%;">
		<div class="modal-content">
			<div class="modal-header bg-success">
				<h5 class="modal-title">Imprest Payto Details</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">X</button>
			</div>
			<div class="modal-body" style="background-color:rgb(193, 232, 212, 0.8);">
				<div id="newimprespaytodivid"></div>
			</div>
		</div>
	</div>
</div>

<div class="modal" id="newpaytodetails_modal" tabindex="-1" role="dialog">
	<div class="modal-dialog modal-lg" style="max-width: 98%;">
		<div class="modal-content">
			<div class="modal-header bg-success">
				<h5 class="modal-title" id="paytohead">Payto Details</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">X</button>
			</div>
			<div class="modal-body" style="background-color: #d1f2c8;">
				<div id="newpaytodivid"></div>
			</div>
		</div>
	</div>
</div>

<div class="modal waitloaderfooter">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-body loader-container">
				<div class="loader"></div>
				<label class="loader-text">Processing...<br>Please Wait!</label>
			</div>
		</div>
	</div>
</div>
<div class="modal" id="newbeneficiarydetails_modal">
	<div class="modal-dialog modal-lg" style="max-width: 98%;">
		<div class="modal-content">
			<div class="modal-header bg-success">
				<h5 class="modal-title">New Beneficiary Details</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">X</button>
			</div>
			<div class="modal-body">
				<div class="row">
					<div class="col-md-12">
						<label>New Beneficiary Name</label>
						<input type="text" class="form-control" name="newbeneficiaryname" id="newbeneficiaryname">
					</div>
				</div>
				<div class="row mt-4">
					<div class="col-md-12 text-center">
						<button class="btn btn-success" id="savetodbbnfbtn"
							onclick="savetodb_newbeneficiarydetails('hidden_baseurl','hidden_companyid','hidden_subcompanyid','hidden_projectid','newbeneficiaryname','hidden_loginname','savetodbbnfbtn');">Save
							Beneficiary Details</button>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<div class="modal" id="newemployeedetails_modal" tabindex="-1" role="dialog">
	<div class="modal-dialog modal-lg" style="max-width: 98%;">
		<div class="modal-content">
			<div class="modal-header bg-success">
				<h5 class="modal-title">EMPLOYEE REGISTRATION FORM</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">X</button>
			</div>
			<div class="modal-body">
				<div id="newemployeedivid"></div>
			</div>
		</div>
	</div>
</div>

<div class="modal" id="makebook_unknownentry_modal" role="dialog">
	<div class="modal-dialog modal-lg modal-dialog-scrollable" style="max-width: 98%;" role="document">
		<div class="modal-content">
			<div class="modal-header bg-success">
				<h5 class="modal-title">Digitization of Unknown Entry </h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">X</button>
			</div>
			<div class="modal-body">
				<div class="row mt-2">
					<div class="col-md-12">
						<div id="bankstatementdetailsdiv"></div>
					</div>
					<div class="col-md-12">
						<div id="unknownentryrequesthtmlview"></div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="modal" id="makebook_asperinst_modal" role="dialog">
	<div class="modal-dialog modal-lg modal-dialog-scrollable" style="max-width: 98%;" role="document">
		<div class="modal-content">
			<div class="modal-header bg-success">
				<h5 class="modal-title">Digitization of voice instruction </h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">X</button>
			</div>
			<div class="modal-body">
				<div class="row mt-2">
					<div class="col-md-12">
						<details id="videtail" open>
							<summary>Voice Instruction Details</summary>
							<div class="p-2">
								<div id="viewvoiceinstruction"></div>
							</div>
						</details>
					</div>
				</div>
				<div class="row">
					<div class="col-md-12">
						<div id="requesthtmlview"></div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="modal" id="viewvoiceinstruction_modal" tabindex="-1" role="dialog">
	<div class="modal-dialog modal-lg" style="max-width: 98%;">
		<div class="modal-content">
			<div class="modal-header bg-success">
				<h5 class="modal-title">Voice instruction </h5>
				<button type="button" class="btn close" data-dismiss="modal" aria-label="Close">&#10006;</button>
			</div>
			<div class="modal-body">
				<div class="row mt-2">
					<div class="col-md-12">
						<div id="voiceinstructionviewhtml"></div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="modal" id="modelviewnote4detailspurpose" tabindex="-1" role="dialog">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header bg-success">
				<button type="button" class="close" data-dismiss="modal">&#10006;</button>
				<input type="text" id="txt_purposeid" name="" style="display:none;">
				<input type="text" id="txt_providedpurposeid" name="" style="display:none;">
			</div>
			<div class="modal-body btn-deepblue" id="iddetails">
			</div>
		</div>
	</div>
</div>

<div class="modal" id="modelviewnote4detailspurposeaccount" role="dialog">
	<div class="modal-dialog modal-lg" style="max-width: 96%;" role="document">
		<div class="modal-content">
			<div class="modal-header bg-primary">
				<button type="button" class="close" data-dismiss="modal">&#10006;</button>
			</div>
			<div class="modal-body bg-light" id="iddetailsaccount">
			</div>
		</div>
	</div>
</div>

<div class="modal" id="attachpreviewdetails" tabindex="-1" role="dialog">
	<div class="modal-dialog" style="max-width: 80%;" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h4>Document Preview</h4>
				<button type="button" class="btn close" data-dismiss="modal">&#10006;</button>
			</div>
			<div class="modal-body">
				<div class="row">
					<iframe class="col-md-12 form-control" id="preview" style="min-height:400px;"></iframe>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="modal" id="editpurposedetails" role="dialog">
	<div class="modal-dialog" style="max-width: 95%;" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h3 class="col-md-12 modal-title text-center font-weight-bold">Edit Purpose Details
					<button type="button" class="btn close" data-dismiss="modal">&#10006;</button>
				</h3>
			</div>
			<div class="modal-body">
				<div class="row table-responsive mx-1">
					<table id="notreconciled_exp_tblX" class="table table-striped table-bordered"
						style="font-size: 14px !important;">
						<thead>
							<tr>
								<th class="bg-info">&nbsp;</th>
								<th class="bg-info">Category Name</th>
								<th class="bg-info">Sub Category Name</th>
								<th class="bg-info">Purpose Name</th>
								<th class="bg-info">Purpose Code</th>
								<th class="bg-info">Provided Code</th>
								<th class="bg-info clamounthead">Amount</th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td class="bg-info font-weight-bold">Existing Details</td>
								<td class="font-weight-bold text-primary clcat"></td>
								<td class="font-weight-bold text-primary clsubcat"></td>
								<td class="font-weight-bold text-primary clpurpose"></td>
								<td class="font-weight-bold text-primary clpurposecode"></td>
								<td class="font-weight-bold text-primary clpcod"></td>
								<td class="font-weight-bold text-primary clamount"></td>
							</tr>
							<tr class="changedrow" data-ctype="nochange">
								<td class="bg-info font-weight-bold">Changed Details</td>
								<td class="text-danger clcatnew"></td>
								<td class="text-danger clsubcatnew"></td>
								<td class="text-danger clpurposenew"></td>
								<td class="text-danger clpurposecodenew"></td>
								<td class="text-danger clpcodnew"></td>
								<td class="text-danger clamount"></td>
							</tr>
						</tbody>
					</table>
				</div>
				<div class="row mt-1">
					<div class="col-12">
						<label class="text-black mb-0">Purpose Name</label>
						<select id="cmbpurpose" class="form-control select2 select2Mannual " style="margin-top: 0px">
						</select>
					</div>
				</div>
				<div class="row mt-1">
					<div class="col-12">
						<label class="text-black mb-0">Category Name</label>
						<select id="cmbcategort" class="form-control select2 select2Mannual " style="margin-top: 0px">
						</select>
					</div>
				</div>
				<div class="row mt-1">
					<div class="col-12">
						<label class="text-black mb-0">Sub Category Name</label>
						<select id="cmbsubcategort" class="form-control select2 select2Mannual "
							style="margin-top: 0px">
						</select>
					</div>
				</div>
				<div class="row mt-1">
					<div class="col-12">
						<label class="text-black mb-0">Type New Provided Code</label>
						<input type="number" class="form-control" name="" id="textnewprovidedcode" disabled>
					</div>
				</div>
				<div class="row mt-2">
					<div class="col-12">
						<input type="checkbox" class="mr-2" name="spelling" id="spelling">
						<label for="spelling" class="text-primary">Only spelling correction for 'Purpose Name'.</label>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<div class="col-md-12 text-center">
					<button class="btn btn-info" id="edit">Update Purpose Detail</button>
					<button class="btn btn-info" id="editsno">Update Purpose Detail</button>
				</div>
			</div>
		</div>
	</div>
</div>
<div class="modal" id="editpurposedetailscredit" role="dialog">
	<div class="modal-dialog" style="max-width: 95%;" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h3 class="col-md-12 modal-title text-center font-weight-bold">Edit Purpose Details
					<button type="button" class="btn close" data-dismiss="modal">&#10006;</button>
				</h3>
			</div>
			<div class="modal-body">
				<div class="row table-responsive mx-1">
					<table id="notreconciled_exp_tblX" class="table table-striped table-bordered"
						style="font-size: 14px !important;">
						<thead>
							<tr>
								<th class="bg-info">&nbsp;</th>
								<th class="bg-info">Category Name</th>
								<th class="bg-info">Sub Category Name</th>
								<th class="bg-info">Purpose Name</th>
								<th class="bg-info">Purpose Code</th>
								<th class="bg-info">Provided Code</th>
								<th class="bg-info clamounthead">Amount</th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td class="bg-info font-weight-bold">Existing Details</td>
								<td class="font-weight-bold text-primary clcat"></td>
								<td class="font-weight-bold text-primary clsubcat"></td>
								<td class="font-weight-bold text-primary clpurpose"></td>
								<td class="font-weight-bold text-primary clpurposecode"></td>
								<td class="font-weight-bold text-primary clpcod"></td>
								<td class="font-weight-bold text-primary clamount"></td>
							</tr>
							<tr class="changedrow" data-ctype="nochange">
								<td class="bg-info font-weight-bold">Changed Details</td>
								<td class="text-danger clcatnew"></td>
								<td class="text-danger clsubcatnew"></td>
								<td class="text-danger clpurposenew"></td>
								<td class="text-danger clpurposecodenew"></td>
								<td class="text-danger clpcodnew"></td>
								<td class="text-danger clamount"></td>
							</tr>
						</tbody>
					</table>
				</div>
				<div class="row mt-1">
					<div class="col-12">
						<label class="text-black mb-0">Purpose Name</label>
						<select id="cmbcreditpurpose" class="form-control select2 select2Mannual "
							style="margin-top: 0px">
						</select>
					</div>
				</div>
				<div class="row mt-1">
					<div class="col-12">
						<label class="text-black mb-0">Category Name</label>
						<select id="cmbcreditcategort" class="form-control select2 select2Mannual "
							style="margin-top: 0px">
						</select>
					</div>
				</div>
				<div class="row mt-1">
					<div class="col-12">
						<label class="text-black mb-0">Sub Category Name</label>
						<select id="cmbcreditsubcategort" class="form-control select2 select2Mannual "
							style="margin-top: 0px">
						</select>
					</div>
				</div>
				<div class="row mt-1">
					<div class="col-12">
						<label class="text-black mb-0">Type New Provided Code</label>
						<input type="number" class="form-control" name="" id="textnewcreditprovidedcode" disabled>
					</div>
				</div>
				<div class="row mt-2">
					<div class="col-12">
						<input type="checkbox" class="mr-2" name="creditspelling" id="creditspelling">
						<label for="creditspelling" class="text-primary">Only spelling correction for 'Purpose
							Name'.</label>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<div class="col-md-12 text-center">
					<button class="btn btn-info" id="creditedit">Update Purpose Detail</button>
					<button class="btn btn-info" id="crediteditsno">Update Purpose Detail</button>
				</div>
			</div>
		</div>
	</div>
</div>
<?php
$hide_footer_final = (isset($hide_footer) && $hide_footer) || (isset($_GET['iframe']) && $_GET['iframe'] == 'true');
if (!$hide_footer_final):
	?>
	<footer class="premium-footer mt-auto">
		<div class="footer-gradient-bar"></div>
		<div class="container-fluid py-5 d-none">
			<div class="row px-md-4">
				<div class="col-lg-4 col-md-6 mb-4 mb-lg-0">
					<h4 class="footer-brand mb-3">Aman Accounting <span>System</span></h4>
					<p class="text-muted pr-lg-5">A comprehensive and professional solution for modern accounting and order
						management. Built for precision and efficiency.</p>
					<div class="social-links mt-4">
						<a href="#" class="social-btn"><i class="fab fa-facebook-f"></i></a>
						<a href="#" class="social-btn"><i class="fab fa-twitter"></i></a>
						<a href="#" class="social-btn"><i class="fab fa-linkedin-in"></i></a>
					</div>
				</div>
				<div class="col-lg-2 col-md-6 mb-4 mb-lg-0">
					<h6 class="text-uppercase fw-bold mb-3 footer-heading">Quick Links</h6>
					<ul class="list-unstyled footer-links">
						<li><a href="#"><i class="fas fa-chevron-right mr-2"></i> Dashboard</a></li>
						<li><a href="#"><i class="fas fa-chevron-right mr-2"></i> Reports</a></li>
						<li><a href="#"><i class="fas fa-chevron-right mr-2"></i> Invoice List</a></li>
						<li><a href="#"><i class="fas fa-chevron-right mr-2"></i> User Settings</a></li>
					</ul>
				</div>
				<div class="col-lg-3 col-md-6 mb-4 mb-lg-0">
					<h6 class="text-uppercase fw-bold mb-3 footer-heading">Recent Modules</h6>
					<ul class="list-unstyled footer-links">
						<li><a href="#"><i class="fas fa-chevron-right mr-2"></i> Production Module</a></li>
						<li><a href="#"><i class="fas fa-chevron-right mr-2"></i> Inventory Tracking</a></li>
						<li><a href="#"><i class="fas fa-chevron-right mr-2"></i> HR Management</a></li>
						<li><a href="#"><i class="fas fa-chevron-right mr-2"></i> Sales Analytics</a></li>
					</ul>
				</div>
				<div class="col-lg-3 col-md-6">
					<h6 class="text-uppercase fw-bold mb-3 footer-heading">Contact Support</h6>
					<p class="text-muted small"><i class="fas fa-envelope mr-2"></i> support@amanaccounting.com</p>
					<p class="text-muted small"><i class="fas fa-phone mr-2"></i> +91 999 000 1122</p>
					<div class="mt-4">
						<button class="btn btn-outline-primary btn-sm rounded-pill px-4">Get Help</button>
					</div>
				</div>
			</div>
		</div>
		<div class="footer-bottom py-3">
			<div class="container-fluid px-md-5">
				<div class="row align-items-center">
					<div class="col-md-6 text-center text-md-left">
						<p class="mb-0 text-muted small">&copy; <?php echo date('Y'); ?> Aman Accounting. All rights
							reserved.</p>
					</div>
					<div class="col-md-6 text-center text-md-right mt-2 mt-md-0">
						<p class="mb-0 text-muted small">Designed with <i class="fas fa-heart text-danger mx-1"></i> for
							Professionalism</p>
					</div>
				</div>
			</div>
		</div>
	</footer>
<?php endif; ?>

<style>
	.premium-footer {
		background: #fff;
		border-top: 1px solid #edf2f7;
		position: relative;
		color: #4a5568;
	}

	.footer-gradient-bar {
		height: 4px;
		background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
	}

	.footer-brand {
		font-weight: 800;
		color: #2d3748;
		letter-spacing: -0.5px;
	}

	.footer-brand span {
		background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
		-webkit-background-clip: text;
		-webkit-text-fill-color: transparent;
	}

	.footer-heading {
		color: #1a202c;
		font-size: 0.9rem;
		letter-spacing: 1px;
	}

	.footer-links li {
		margin-bottom: 12px;
	}

	.footer-links a {
		color: #718096;
		text-decoration: none;
		transition: all 0.3s;
		font-size: 0.9rem;
	}

	.footer-links a:hover {
		color: #667eea;
		padding-left: 5px;
	}

	.social-links {
		display: flex;
		gap: 12px;
	}

	.social-btn {
		width: 36px;
		height: 36px;
		border-radius: 50%;
		background: #f7fafc;
		display: flex;
		align-items: center;
		justify-content: center;
		color: #a0aec0;
		transition: all 0.3s;
		text-decoration: none !important;
	}

	.social-btn:hover {
		background: #667eea;
		color: #fff;
		transform: translateY(-3px);
	}

	.footer-bottom {
		background: #f8fafc;
		border-top: 1px solid #edf2f7;
	}

	@media (max-width: 767.98px) {
		.premium-footer .row>div {
			text-align: center;
		}

		.footer-links a:hover {
			padding-left: 0;
		}

		.social-links {
			justify-content: center;
		}
	}
</style>
</body>

</html>
<!-- <script type="text/javascript"  src="<?php echo $prefix; ?>js/jquery.min.js"></script> -->
<!-- <script defer src="https://use.fontawesome.com/releases/v5.15.4/js/solid.js"></script> -->
<script defer src="https://use.fontawesome.com/releases/v5.15.4/js/fontawesome.js"></script>
<script src="https://cdn.jsdelivr.net/npm/wavefile"></script>

<script type="text/javascript" src="<?php echo $prefix; ?>js/js_selft/request.js"></script>
<script type="text/javascript" src="<?php echo $prefix; ?>js/js_selft/financialreport.js"></script>
<script type="text/javascript" src="<?php echo $prefix; ?>js/js_selft/masters.js"></script>
<script type="text/javascript" src="<?php echo $prefix; ?>js/js_selft/utilitybill.js"></script>
<script type="text/javascript" src="<?php echo $prefix; ?>js/js_selft/admindashboard.js"></script>
<script type="text/javascript" src="<?php echo $prefix; ?>js/js_selft/book_reconstruction.js"></script>
<script type="text/javascript" src="<?php echo $prefix; ?>js/js_selft/MediaHandler.js"></script>

<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<script type="text/javascript" src="<?php echo $prefix; ?>js/accordian.js"></script>
<script type="text/javascript" src="<?php echo $prefix; ?>js/colortoner.js"></script>
<script type="text/javascript" src="<?php echo $prefix; ?>js/iziToast.js"></script>
<script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/pdf.js/2.14.305/pdf.min.js"></script>

<script type="text/javascript" src="<?php echo $prefix; ?>js/jspdf.debug.js"></script>
<script type="text/javascript" src="<?php echo $prefix; ?>js/jspdf.plugin.autotable.js"></script>
<script type="text/javascript" src="<?php echo $prefix; ?>js/exceljs.min.js"></script>
<script type="text/javascript" src="<?php echo $prefix; ?>js/export_grid_excel.js"></script>
<script type="text/javascript" src="<?php echo $prefix; ?>js/print.js"></script>

<!-- ajay by -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js" defer></script>

<!-- jsPDF AutoTable plugin -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.29/jspdf.plugin.autotable.min.js"
	defer></script>

<script type="text/javascript" src="<?php echo $prefix; ?>js/export_grid_pdf_ajay.js"></script>
<!-- end -->
<script type="text/javascript" src="<?php echo $prefix; ?>js/export_grid_pdf.js"></script>

<!-- <script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js"></script>
<script type="text/javascript" src="//maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
 -->


<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.min.js"></script>


<script type="text/javascript" src="//cdn.datatables.net/1.10.20/js/jquery.dataTables.js"></script>

<script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/js/select2.min.js"></script>
<script type="text/javascript"
	src="https://cdn.jsdelivr.net/npm/sweetalert2@11.12.4/dist/sweetalert2.all.min.js"></script>
<script type="text/javascript" src="<?php echo $prefix; ?>js/actioncontrol.js"></script>
<script type="text/javascript" src="<?php echo $prefix; ?>js/js_selft/voicerec.js"></script>
<script type="text/javascript" src="//cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<script type="text/javascript" src="//cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<script type="text/javascript" src="<?php echo $prefix; ?>js/daterangecontrol.js"></script>

<script type="text/javascript" src="<?php echo $prefix; ?>modulesCell/js/modules.js"></script>
<script type="text/javascript" src="<?php echo $prefix; ?>loginCell/js/login.js"></script>
<script type="text/javascript" src="<?php echo $prefix; ?>whatsappnotification\whatsappaction.js"></script>

<script type="text/javascript">
	$(document).ready(function () {
		controlDevTools($("body").data("loginroleid"));
		
		bindUpperCaseInput('ucaseinput');
		$(document).on("click", ".openinvoicemodal", function () {
			$("#gstinvoicedetailsmodal").modal("show");
			$("#gstinvoicedetailsmodal").css("z-index", "99100");
			// Ensure it has btn class if added dynamically (though it shouldn't here)
		});


		$('.select2').select2({
			width: '100%' // need to override the changed default
		});
		$('.select2-selection').css('border-radius', '2px');
		$('.select2-container').children().css('border-radius', '0px');
		$('.select2-selection').css('height', '38px');

		$(".select2Mannual").select2({
			tags: true,
			width: '100%'
		});
	});
	var rooturl = $("#hidden_rooturl").val();
	$(document).on("change", ".invoicetype", function () {
		$("#invoicetype_org").val('');
		$("#invoiceno_org").val('');
		$("#invoicedate_org").val('');
		$("#invoiceamount_org").val('');
		$('#gstinvoicedetailsmodal').find('table').find('input').val('');
		$("#totalamount").text('');
		$("#gsttotalamount").text('');
		$("#ttldiscountamount").text('');
		$("#grandtotalamount").text('');
		$("#otherdiscounttotalamount").val(''); //input
		var parentContainer = $(this).closest("body");
		parentContainer.find('.openinvoicemodal').html('');
		var invtype = parentContainer.find("select.invoicetype").val();
		var dohavegstinvoiceval = parentContainer.find("input[name='dohavegstinvoice']:checked ").val();
		$("#invoicetype_org").val($(this).prop("id"));
		var invtypidno = parseInt($(this).prop("id").split("_")[1]) || 0;
		$("#invoiceno_org").val(parentContainer.find("input.invoiceno:eq(" + (invtypidno ? invtypidno - 1 : 0) + ")").prop("id"));
		$("#invoicedate_org").val(parentContainer.find("input.invoicedate:eq(" + (invtypidno ? invtypidno - 1 : 0) + ")").prop("id"));
		$("#invoiceamount_org").val(parentContainer.find("input.invoiceamount:eq(" + (invtypidno ? invtypidno - 1 : 0) + ")").prop("id"));

		$(".invoicetypetxt").html($(this).val());
		if ($(this).val() == "Invoice") {
			$(".invoicenodiv").show();
			$(".invno_lbl").html("Invoice/Voucher No");
			$(".invdate_lbl").html("Invoice/Voucher Date");
		} else if ($(this).val() == "Cash Memo") {
			$(".invoicenodiv").show();
			$(".invno_lbl").html("Cash Memo No");
			$(".invdate_lbl").html("Cash Memo Date");
		} else if ($(this).val() == "Purchase Order (PO)") {
			$(".invoicenodiv").show();
			// $(".dohavegstinvoicediv").hide();
			$(".invno_lbl").html("Purchase Order No");
			$(".invdate_lbl").html("Purchase Order Date");
		} else if ($(this).val() == "Delivery Note/Chalan") {
			$(".invoicenodiv").show();
			$(".invno_lbl").html("Delivery Note/Chalan No");
			$(".invdate_lbl").html("Delivery Note/Chalan Date");
		} else if ($(this).val() == "Packing List") {
			$(".invoicenodiv").show();
			// $(".dohavegstinvoicediv").hide();
			$(".invno_lbl").html("Packing List No");
			$(".invdate_lbl").html("Packing List Date");
		} else {
			$(".invoicenodiv").hide();
			// $(".dohavegstinvoicediv").hide();
			$(".invno_lbl").html("Invoice/Voucher No");
			$(".invdate_lbl").html("Invoice/Voucher Date");
		}
	});

	$('.modal').on('hidden.bs.modal', function () {
		$('body').removeClass('modal-open');
		$('.modal-backdrop').remove();
	});

	$('.modal').on('hidden.bs.modal', function () {
		$('body').css('overflow', 'auto');
	});
</script>
<?php //require __DIR__ . '/krf_order_followup/followup_reminders_include.php'; ?>