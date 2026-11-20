		<div class="footer d-sm-flex align-items-center justify-content-between border-top bg-white p-3">
			<p class="mb-0"><?php date('Y'); ?> &copy; HRMplus.</p>
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

	<!-- Quick Labour Registration Floating Button -->
	<button type="button" class="btn btn-primary rounded-pill shadow-lg position-fixed bottom-0 end-0 m-4 border-0 d-flex align-items-center gap-2" 
	        style="z-index: 1050; padding: 12px 24px; background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%); transition: all 0.3s ease; box-shadow: 0 8px 16px rgba(13, 110, 253, 0.25) !important;" 
	        data-bs-toggle="modal" data-bs-target="#QuickLabourRegistrationModal"
	        onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 12px 24px rgba(13, 110, 253, 0.3)';"
	        onmouseout="this.style.transform='none'; this.style.boxShadow='0 8px 16px rgba(13, 110, 253, 0.25)';"
	        id="quick_reg_btn">
	    <i class="ti ti-user-plus fs-18"></i> <span class="fw-bold">Quick Register</span>
	</button>

</div>
<!-- /Main Wrapper -->

<!-- Quick Labour Registration Modal -->
<div class="modal fade" id="QuickLabourRegistrationModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-4">
      <div class="modal-header border-0 px-4 pt-4">
        <h5 class="modal-title fw-bold text-dark d-flex align-items-center">
          <span class="bg-primary-subtle text-primary rounded-2 p-1 me-2"><i class="ti ti-user-plus fs-20"></i></span>
          Quick Labour Registration
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="QuickLabourRegistrationForm" novalidate>
        <div class="modal-body px-4 pb-4">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label small fw-bold text-muted">Working Location <span class="text-danger">*</span></label>
              <select name="workingLocation" id="quick_workingLocation" class="form-select bg-light border-0" required>
                <option value="">Choose Location</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold text-muted">Suggested ID <span class="text-danger">*</span></label>
              <input type="text" inputmode="numeric" name="seriesid" id="quick_seriesid" class="form-control bg-light border-0" required placeholder="Suggested ID">
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold text-muted">Labour Name <span class="text-danger">*</span></label>
              <input type="text" name="labourName" id="quick_labourName" class="form-control bg-light border-0" required placeholder="Enter Full Name">
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold text-muted">Contact No <span class="text-danger">*</span></label>
              <input type="text" name="contactNo" id="quick_contactNo" class="form-control bg-light border-0" required placeholder="10-digit mobile number" maxlength="10">
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold text-muted d-block">Gender <span class="text-danger">*</span></label>
              <div class="d-flex gap-4 align-items-center mt-2">
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="gender" id="quick_gender_male" value="Male" checked required>
                  <label class="form-check-label fw-bold" for="quick_gender_male"><i class="ti ti-man text-primary me-1"></i> Male</label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="gender" id="quick_gender_female" value="Female" required>
                  <label class="form-check-label fw-bold" for="quick_gender_female"><i class="ti ti-woman text-danger me-1"></i> Female</label>
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold text-muted d-block">Is Grafted Skill? <span class="text-danger">*</span></label>
              <div class="d-flex gap-4 align-items-center mt-2">
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="grafting_skill" id="quick_skill_yes" value="yes" required>
                  <label class="form-check-label fw-bold" for="quick_skill_yes">Yes, Skilled</label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="grafting_skill" id="quick_skill_no" value="no" checked>
                  <label class="form-check-label fw-bold" for="quick_skill_no">No</label>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer border-0 px-4 pb-4 pt-0 d-flex gap-2 justify-content-end">
          <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Discard</button>
          <button type="submit" id="submitQuickRegistration" class="btn btn-primary rounded-pill px-4 shadow-sm">
            <i class="ti ti-check me-1"></i> Register
          </button>
        </div>
      </form>
    </div>
  </div>
</div>


	<!-- Bootstrap Core JS -->
	<script src="assets/js/bootstrap.bundle.min.js"></script>

	<!-- Feather Icon JS -->
	<script src="assets/js/feather.min.js"></script>

	<!-- Slimscroll JS -->
	<script src="assets/js/jquery.slimscroll.min.js"></script>

	<!-- Chart JS -->
	<script src="assets/plugins/apexchart/apexcharts.min.js"></script>
	<script src="assets/plugins/apexchart/chart-data.js"></script>

	<!-- Chart JS -->
	<script src="assets/plugins/chartjs/chart.min.js"></script>
	<script src="assets/plugins/chartjs/chart-data.js"></script>

	<!-- Datetimepicker JS -->
	<script src="assets/js/moment.min.js"></script>
	<script src="assets/js/bootstrap-datetimepicker.min.js"></script>

	<!-- Daterangepikcer JS -->
	<script src="assets/plugins/daterangepicker/daterangepicker.js"></script>

	<!-- Summernote JS -->
	<script src="assets/plugins/summernote/summernote-lite.min.js"></script>

	<!-- Bootstrap Tagsinput JS -->
	<script src="assets/plugins/bootstrap-tagsinput/bootstrap-tagsinput.js"></script>

	<!-- Select2 JS -->
	<script src="assets/plugins/select2/js/select2.min.js"></script>

	<!-- Color Picker JS -->
	<!-- <script src="assets/plugins/%40simonwep/pickr/pickr.es5.min.js"></script> -->

	<!-- Datatable JS -->
	<script src="assets/js/jquery.dataTables.min.js"></script>
	<script src="assets/js/dataTables.bootstrap5.min.js"></script>

	<!-- Custom JS -->
	<script src="assets/js/todo.js"></script>
	<script src="assets/js/theme-colorpicker.js"></script>
	<script src="assets/js/script.js"></script>
	<script src="assets/js/iziToast.js"></script>

	<!-- Self JS -->
	<script src="assets/js/actioncontrol.js"></script>
	<script src="assets/js/hrm.js"></script>
	<script src="assets/js/quick_labour.js?v=<?php echo time(); ?>"></script>
	<script src="assets/js/exceljs.min.js"></script>
	<script src="assets/js/export_grid_excel.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.22.2/dist/sweetalert2.all.min.js"></script>
	<!-- Must include these -->
	<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js"></script>
</body>
</html>