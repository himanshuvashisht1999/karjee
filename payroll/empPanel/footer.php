		<div class="footer d-sm-flex align-items-center justify-content-between border-top bg-white p-3">
			<p class="mb-0"><?php echo date('Y'); ?> &copy; HRMplus.</p>
			<p>Designed &amp; Developed By <a href="javascript:void(0);" class="text-primary">AmanMovement.org</a></p>
		</div>
	</div>
	<!-- /Page Wrapper -->
	<!-- Modal 0 -->
	<div class="modal fade" id="webpagebodyview_modal" tabindex="-1" aria-labelledby="modalheader" aria-hidden="true">
	    <div class="modal-dialog modal-lg" style="max-width: 98%;">
	        <div class="modal-content">
	            <div class="modal-header bg-info text-white">
	                <h5 class="modal-title" id="modalheader">Details</h5>
	                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
	            </div>
	            <div class="modal-body" style="background-color: rgba(200, 213, 228, 0.8);">
	                <div id="webpagebodydiv"></div>
	            </div>
	        </div>
	    </div>
	</div>

	<!-- Modal 1 -->
	<div class="modal fade" id="webpagebodyview1_modal" tabindex="-1" aria-labelledby="modalheader1" aria-hidden="true">
	    <div class="modal-dialog modal-lg" style="max-width: 98%;">
	        <div class="modal-content">
	            <div class="modal-header bg-info text-white">
	                <h5 class="modal-title" id="modalheader1">Details</h5>
	                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
	            </div>
	            <div class="modal-body" style="background-color: rgba(200, 213, 228, 0.8);">
	                <div id="webpagebodydiv1"></div>
	            </div>
	        </div>
	    </div>
	</div>

	<!-- Modal 2 -->
	<div class="modal fade" id="webpagebodyview2_modal" tabindex="-1" aria-labelledby="modalheader2" aria-hidden="true">
	    <div class="modal-dialog modal-lg" style="max-width: 98%;">
	        <div class="modal-content">
	            <div class="modal-header bg-info text-white">
	                <h5 class="modal-title" id="modalheader2">Details</h5>
	                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
	            </div>
	            <div class="modal-body" style="background-color: rgba(200, 213, 228, 0.8);">
	                <div id="webpagebodydiv2"></div>
	            </div>
	        </div>
	    </div>
	</div>

	<!-- MD Modal -->
	<div class="modal fade" id="webpagebodyview_mdmodal" tabindex="-1" aria-labelledby="modalheadermd" aria-hidden="true">
	    <div class="modal-dialog modal-lg">
	        <div class="modal-content">
	            <div class="modal-header bg-info text-white">
	                <h5 class="modal-title" id="modalheadermd">Details</h5>
	                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
	            </div>
	            <div class="modal-body" style="background-color: rgba(200, 213, 228, 0.8);">
	                <div id="webpagebodydivmd"></div>
	            </div>
	        </div>
	    </div>
	</div>
	<!-- Add Leaves -->
	<div class="modal fade" id="add_leaves">
		<div class="modal-dialog modal-dialog-centered modal-md">
			<div class="modal-content">
				<div class="modal-header">
					<h4 class="modal-title">Add Leave Request</h4>
					<button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
						<i class="ti ti-x"></i>
					</button>
				</div>
				<form action="https://smarthr.co.in/demo/html/template/index.html">
					<div class="modal-body pb-0">
						<div class="row">
							<div class="col-md-12">
								<div class="mb-3">
									<label class="form-label">Employee Name</label>
									<select class="select">
										<option>Select</option>
										<option>Anthony Lewis</option>
										<option>Brian Villalobos</option>
										<option>Harvey Smith</option>
									</select>
								</div>	
							</div>
							<div class="col-md-12">
								<div class="mb-3">
									<label class="form-label">Leave Type</label>
									<select class="select">
										<option>Select</option>
										<option>Medical Leave</option>
										<option>Casual Leave</option>
										<option>Annual Leave</option>
									</select>
								</div>	
							</div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">From </label>
                                    <div class="input-icon-end position-relative">
                                        <input type="text" class="form-control datetimepicker" placeholder="dd/mm/yyyy">
                                        <span class="input-icon-addon">
                                            <i class="ti ti-calendar text-gray-7"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">To </label>
                                    <div class="input-icon-end position-relative">
                                        <input type="text" class="form-control datetimepicker" placeholder="dd/mm/yyyy">
                                        <span class="input-icon-addon">
                                            <i class="ti ti-calendar text-gray-7"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>   
							<div class="col-md-6">
								<div class="mb-3">
									<label class="form-label">No of Days</label>
									<input type="text" class="form-control" disabled>
								</div>
							</div>
							<div class="col-md-6">
								<div class="mb-3">
									<label class="form-label">Remaining Days</label>
									<input type="text" class="form-control" disabled>
								</div>
							</div>
							<div class="col-md-12">
								<div class="mb-3">
									<label class="form-label">Reason</label>
									<textarea class="form-control" rows="3"></textarea>
								</div>
							</div>
						</div>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</button>
						<button type="submit" class="btn btn-primary">Add Leaves</button>
					</div>
				</form>
			</div>
		</div>
	</div>
	<!-- /Add Leaves -->
</div>
<!-- /Main Wrapper -->
	<!-- jQuery -->
	<!-- <script data-cfasync="false" src="../assets/js/email-decode.min.js"></script> -->
	<script src="../assets/js/jquery-3.7.1.min.js"></script>

	<!-- Bootstrap Core JS -->
	<script src="../assets/js/bootstrap.bundle.min.js"></script>

	<!-- Feather Icon JS -->
	<script src="../assets/js/feather.min.js"></script>

	<!-- Slimscroll JS -->
	<script src="../assets/js/jquery.slimscroll.min.js"></script>

	<!-- Chart JS -->
	<script src="../assets/plugins/apexchart/apexcharts.min.js"></script>
	<script src="../assets/plugins/apexchart/chart-data.js"></script>

	<!-- Chart JS -->
	<script src="../assets/plugins/chartjs/chart.min.js"></script>
	<script src="../assets/plugins/chartjs/chart-data.js"></script>

	<!-- Datetimepicker JS -->
	<script src="../assets/js/moment.min.js"></script>
	<script src="../assets/js/bootstrap-datetimepicker.min.js"></script>

	<!-- Daterangepikcer JS -->
	<script src="../assets/plugins/daterangepicker/daterangepicker.js"></script>

	<!-- Summernote JS -->
	<script src="../assets/plugins/summernote/summernote-lite.min.js"></script>

	<!-- Bootstrap Tagsinput JS -->
	<script src="../assets/plugins/bootstrap-tagsinput/bootstrap-tagsinput.js"></script>

	<!-- Select2 JS -->
	<script src="../assets/plugins/select2/js/select2.min.js"></script>

	<!-- Color Picker JS -->
	<!-- <script src="../assets/plugins/%40simonwep/pickr/pickr.es5.min.js"></script> -->

	<!-- Datatable JS -->
	<script src="../assets/js/jquery.dataTables.min.js"></script>
	<script src="../assets/js/dataTables.bootstrap5.min.js"></script>

	<!-- Custom JS -->
	<script src="../assets/js/todo.js?v=<?= time(); ?>"></script>
	<script src="../assets/js/theme-colorpicker.js?v=<?= time(); ?>"></script>
	<script src="../assets/js/script.js?v=<?= time(); ?>"></script>
	<script src="../assets/js/iziToast.js"></script>

	<!-- Self JS -->
	<script src="../assets/js/actioncontrol.js?v=<?= time(); ?>"></script>
	<script src="../assets/js/hrm.js?v=<?= time(); ?>"></script>
	<script src="../assets/js/exceljs.min.js"></script>
	<script src="../assets/js/export_grid_excel.js?v=<?= time(); ?>"></script>
	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.22.2/dist/sweetalert2.all.min.js"></script>
	<!-- Must include these -->
	<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js"></script>
</body>
</html>