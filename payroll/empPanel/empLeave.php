<?php include('header.php'); ?>
<div class="content">
	<div class="d-flex align-items-center justify-content-between page-breadcrumb mb-4">
		<div class="my-auto mb-2">
			<h2 class="mb-1 fw-bold">Leave Management</h2>
			<nav>
				<ol class="breadcrumb mb-0">
					<li class="breadcrumb-item">
						<a
							href="<?php echo $rooturl; ?>empPanel/index.php?k=<?php echo $key; ?>&empid=<?php echo $empid; ?>&accessby=<?php echo $accessby; ?>"><i
								class="ti ti-smart-home"></i></a>
					</li>
					<li class="breadcrumb-item">Employee</li>
					<li class="breadcrumb-item active text-primary" aria-current="page">Leaves</li>
				</ol>
			</nav>
		</div>
		<?php if (!$isResigned) { ?>
		<div class="d-flex my-xl-auto right-content align-items-center flex-wrap gap-2">
			<button data-bs-toggle="modal" data-bs-target="#applyLeavemodal"
				class="btn btn-apply-leave d-flex align-items-center shadow-sm">
				<i class="ti ti-circle-plus"></i> Apply Leave
			</button>
		</div>
		<?php } ?>
	</div>

	<!-- Leave Statistics Cards -->
	<div class="row g-2 g-md-3 mb-4 flex-nowrap">
		<!-- Applied Leaves Card -->
		<div class="col-md-4 col-4">
			<div class="card leave-card bg-applied-leaves active-filter" onclick="filterLeaveHistory('all')">
				<div class="card-body">
					<div class="d-flex align-items-center justify-content-between">
						<div class="leave-info text-start">
							<h3 class="mb-0 fw-bold count-text" id="totalAppliedLeavelbl">0</h3>
							<p class="mb-0 text-uppercase fw-bold label-text">Applied Leaves</p>
						</div>
						<div class="icon-wrapper">
							<i class="ti ti-file-description"></i>
						</div>
					</div>
				</div>
			</div>
		</div>
		<!-- Approved Leaves Card -->
		<div class="col-md-4 col-4">
			<div class="card leave-card bg-approved-leaves" onclick="filterLeaveHistory('Approved')">
				<div class="card-body">
					<div class="d-flex align-items-center justify-content-between">
						<div class="leave-info text-start">
							<h3 class="mb-0 fw-bold count-text" id="totalApprovedLeavelbl">0</h3>
							<p class="mb-0 text-uppercase fw-bold label-text">Approved Leaves</p>
						</div>
						<div class="icon-wrapper">
							<i class="ti ti-circle-check"></i>
						</div>
					</div>
				</div>
			</div>
		</div>
		<!-- Pending For Approval Card -->
		<div class="col-md-4 col-4">
			<div class="card leave-card bg-pending-leaves" onclick="filterLeaveHistory('Pending')">
				<div class="card-body">
					<div class="d-flex align-items-center justify-content-between">
						<div class="leave-info text-start">
							<h3 class="mb-0 fw-bold count-text" id="totalPendingLeavelbl">0</h3>
							<p class="mb-0 text-uppercase fw-bold label-text">Pending For Approval</p>
						</div>
						<div class="icon-wrapper">
							<i class="ti ti-clock"></i>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>


	<!-- Leave List Section -->
	<div class="mt-4">
		<div class="d-flex align-items-center justify-content-between mb-3">
			<h4 class="mb-0 fw-bold">Leave History</h4>
			<span class="badge bg-soft-primary text-primary px-3 py-2 border rounded-pill" onclick="filterLeaveHistory('all')" style="cursor: pointer;">View All Leaves</span>
		</div>

		<!-- Leave Strips Container -->
		<div id="empLeaveStripsWrapper" class="info-strip-container">
			<!-- Data populated via AJAX in hrm.js -->
			<div class="text-center py-4">
				<div class="spinner-border text-primary spinner-border-sm"></div>
				<p class="small text-muted mt-2">Loading history...</p>
			</div>
		</div>
	</div>

</div>


<style>
.btn-apply-leave {
    background: linear-gradient(135deg, #fa5a28, #ea580c) !important;
    border: none !important;
    color: #ffffff !important;
    padding: 8px 14px !important;
    font-size: 0.85rem !important;
    font-weight: 600 !important;
    border-radius: 8px !important;
    transition: all 0.2s ease-in-out !important;
}
.btn-apply-leave i {
    font-size: 1.05rem !important;
    margin-right: 4px !important;
}
.btn-apply-leave:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(234, 88, 12, 0.35) !important;
    background: linear-gradient(135deg, #fb713b, #f97316) !important;
}
.btn-apply-leave:active {
    transform: translateY(0);
}
.leave-card {
    background-color: #f8fafc !important;
    border: 1px solid #cbd5e1 !important; /* Lighter clean border */
    border-radius: 12px !important;
    color: #1e293b;
    cursor: pointer;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 1px 3px rgba(0,0,0,0.05) !important;
}
.leave-card .card-body {
    padding: 10px 12px !important;
}
.leave-card .label-text {
    color: #64748b !important;
    font-size: 0.65rem !important; /* Decreased size */
    font-weight: 700 !important;
    letter-spacing: 0.2px;
    margin-top: 2px; /* Margin at the top since it is below the number */
}
.leave-card .count-text {
    color: #0f172a !important;
    font-size: 1.3rem !important; /* Decreased size */
    margin-top: 0px; /* No margin at the top */
    margin-bottom: 0px;
    line-height: 1.2;
}
.leave-card .icon-wrapper {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background-color: #f1f5f9;
    border: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background-color 0.2s, border-color 0.2s;
}
.leave-card .icon-wrapper i {
    color: #475569 !important;
    font-size: 1rem !important;
}
.leave-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.08) !important;
    border-color: #94a3b8 !important;
}

/* Active Filter States with unique animated pulsing border and color */
.leave-card.active-filter.bg-applied-leaves {
    background: linear-gradient(135deg, #2563eb, #1d4ed8) !important;
    border-color: #2563eb !important;
    color: #ffffff;
    transform: translateY(-3px);
    animation: activePulseBlue 2s infinite;
}
.leave-card.active-filter.bg-approved-leaves {
    background: linear-gradient(135deg, #10b981, #047857) !important;
    border-color: #10b981 !important;
    color: #ffffff;
    transform: translateY(-3px);
    animation: activePulseGreen 2s infinite;
}
.leave-card.active-filter.bg-pending-leaves {
    background: linear-gradient(135deg, #f59e0b, #b45309) !important;
    border-color: #f59e0b !important;
    color: #ffffff;
    transform: translateY(-3px);
    animation: activePulseOrange 2s infinite;
}

.leave-card.active-filter .label-text {
    color: rgba(255, 255, 255, 0.8) !important;
}
.leave-card.active-filter .count-text {
    color: #ffffff !important;
}
.leave-card.active-filter .icon-wrapper {
    background-color: rgba(255, 255, 255, 0.2) !important;
    border-color: rgba(255, 255, 255, 0.1) !important;
}
.leave-card.active-filter .icon-wrapper i {
    color: #ffffff !important;
}

/* Pulse Keyframes */
@keyframes activePulseBlue {
    0% {
        box-shadow: 0 0 0 0 rgba(37, 99, 235, 0.5);
    }
    70% {
        box-shadow: 0 0 0 8px rgba(37, 99, 235, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(37, 99, 235, 0);
    }
}
@keyframes activePulseGreen {
    0% {
        box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.5);
    }
    70% {
        box-shadow: 0 0 0 8px rgba(16, 185, 129, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(16, 185, 129, 0);
    }
}
@keyframes activePulseOrange {
    0% {
        box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.5);
    }
    70% {
        box-shadow: 0 0 0 8px rgba(245, 158, 11, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(245, 158, 11, 0);
    }
}

/* Responsive adjustments */
@media (max-width: 575px) {
    .btn-apply-leave {
        padding: 5px 8px !important;
        font-size: 0.7rem !important;
        border-radius: 6px !important;
    }
    .btn-apply-leave i {
        font-size: 0.85rem !important;
        margin-right: 2px !important;
    }
    h2.mb-1.fw-bold {
        font-size: 1.15rem !important;
        margin-bottom: 0px !important;
    }
    .breadcrumb-item {
        font-size: 0.65rem !important;
    }
    .leave-card .card-body {
        padding: 4px 6px !important;
    }
    .leave-card .label-text {
        font-size: 0.46rem !important; /* Extra small on mobile */
        letter-spacing: -0.1px;
        margin-top: 1px;
    }
    .leave-card .count-text {
        font-size: 0.95rem !important; /* Extra small on mobile */
        margin-top: 0px;
    }
    .leave-card .icon-wrapper {
        width: 20px;
        height: 20px;
    }
    .leave-card .icon-wrapper i {
        font-size: 0.65rem !important;
    }
}
.bg-unpaid-leave {
    background: linear-gradient(-45deg, #c2410c, #f97316, #fdba74, #ea580c);
    color: #fff;
}
/* Custom Redesigned Leave Modal Styles */
#applyLeavemodal .modal-content {
    border-radius: 12px;
    overflow: hidden;
    border: none;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
}

#applyLeavemodal .modal-header {
    background-color: #fa5a28 !important; /* Orange-red matching screenshot */
    border-bottom: none;
    padding: 18px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

#applyLeavemodal .modal-title {
    font-size: 1.25rem;
    font-weight: 700;
    letter-spacing: -0.5px;
    color: #ffffff !important;
    display: flex;
    align-items: center;
}

#applyLeavemodal .modal-title i {
    font-size: 1.4rem;
}

#applyLeavemodal .modal-body {
    padding: 24px !important;
    background-color: #ffffff;
}

#applyLeavemodal .form-label {
    font-size: 0.95rem;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 8px;
}

#applyLeavemodal .form-control, 
#applyLeavemodal .form-select, 
#applyLeavemodal .input-group-text {
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    padding: 10px 14px;
    font-size: 0.95rem;
    transition: all 0.2s ease;
    background-color: #ffffff;
}

#applyLeavemodal .form-control:focus, 
#applyLeavemodal .form-select:focus {
    border-color: #fa5a28;
    box-shadow: 0 0 0 3px rgba(250, 90, 40, 0.15);
}

/* Specific styling for readonly total days to match screenshot */
#applyLeavemodal #totalDays {
    background-color: #f8fafc;
    border-left: none;
    font-weight: 700;
    color: #1e293b;
    opacity: 1; /* Override bootstrap opacity for disabled/readonly */
}

#applyLeavemodal .input-group-text {
    background-color: #f8fafc;
    color: #64748b;
    border-right: none;
}

/* Custom File Upload Styling to match screenshot */
.custom-file-upload {
    display: flex;
    align-items: center;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    background-color: #fff;
    overflow: hidden;
}

.custom-file-upload .upload-btn {
    background-color: #f1f5f9;
    border: none;
    border-right: 1px solid #e2e8f0;
    padding: 10px 20px;
    color: #475569;
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    transition: background-color 0.2s;
    user-select: none;
}

.custom-file-upload .upload-btn:hover {
    background-color: #e2e8f0;
}

.custom-file-upload .file-name {
    flex-grow: 1;
    padding: 10px 16px;
    color: #94a3b8;
    font-size: 0.95rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    user-select: none;
    cursor: pointer;
}

.custom-file-upload .upload-icon {
    background-color: #f8fafc;
    border: none;
    border-left: 1px solid #e2e8f0;
    padding: 10px 16px;
    color: #94a3b8;
    cursor: pointer;
    transition: background-color 0.2s;
}

.custom-file-upload .upload-icon:hover {
    background-color: #f1f5f9;
    color: #475569;
}

/* Modal Footer Buttons */
#applyLeavemodal .modal-footer {
    background-color: #f8fafc !important;
    border-top: 1px solid #f1f5f9;
    padding: 16px 24px;
    display: flex;
    justify-content: flex-end;
    gap: 12px;
}

#applyLeavemodal .btn-cancel {
    background-color: #ffffff;
    border: 1px solid #cbd5e1;
    color: #334155;
    font-weight: 600;
    padding: 10px 24px;
    border-radius: 8px;
    transition: all 0.2s;
}

#applyLeavemodal .btn-cancel:hover {
    background-color: #f1f5f9;
    color: #1e293b;
}

#applyLeavemodal .btn-reset {
    background-color: #3d5a80; /* Slate-teal matching screenshot */
    border: none;
    color: #ffffff;
    font-weight: 600;
    padding: 10px 24px;
    border-radius: 8px;
    transition: all 0.2s;
}

#applyLeavemodal .btn-reset:hover {
    background-color: #293f56;
}

#applyLeavemodal .btn-submit {
    background-color: #fa5a28; /* Matching header orange-red */
    border: none;
    color: #ffffff;
    font-weight: 600;
    padding: 10px 28px;
    border-radius: 8px;
    box-shadow: 0 4px 6px -1px rgba(250, 90, 40, 0.2), 0 2px 4px -2px rgba(250, 90, 40, 0.2);
    transition: all 0.2s;
}

#applyLeavemodal .btn-submit:hover {
    background-color: #e04a16;
    box-shadow: 0 10px 15px -3px rgba(250, 90, 40, 0.3);
}

/* Select2 Custom Styling to look uniform within the modal */
#applyLeavemodal .select2-container--default .select2-selection--single {
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    height: 45px;
    padding: 8px 12px;
    font-size: 0.95rem;
}

#applyLeavemodal .select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 43px;
    right: 12px;
}

#applyLeavemodal .select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 27px;
    color: #334155;
    padding: 0;
}

/* Responsiveness adjustments */
@media (max-width: 767px) {
    #applyLeavemodal .modal-dialog {
        margin: 16px;
    }
    #applyLeavemodal .modal-body {
        padding: 16px !important;
    }
    #applyLeavemodal .modal-footer {
        flex-direction: column-reverse;
        gap: 10px;
        padding: 16px !important;
    }
    #applyLeavemodal .modal-footer .btn {
        width: 100%;
        margin: 0 !important;
    }
}
</style>

<!-- Apply Leave Modal -->
<div class="modal fade" id="applyLeavemodal" role="dialog">
	<div class="modal-dialog modal-dialog-centered modal-lg">
		<div class="modal-content border-0 shadow-lg">
			<div class="modal-header py-3">
				<h5 class="modal-title fw-bold">
					<i class="ti ti-file-description me-2"></i>New Leave Application
				</h5>
				<button type="button" class="btn-close-custom" data-bs-dismiss="modal" aria-label="Close" style="background: none; border: none; color: #fff; font-size: 1.5rem; line-height: 1; cursor: pointer; transition: opacity 0.2s; opacity: 0.8;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.8'">&times;</button>
			</div>
			<div class="modal-body p-4">
				<form id="leaveForm">
					<div class="row g-3">
						<!-- Employee List (Hidden but required for logic) -->
						<div class="col-12 d-none">
							<label class="form-label fw-semibold text-dark">Employee</label>
							<select class="form-select select2" name="EmployeeList" id="EmployeeList" required></select>
						</div>

						<!-- Leave Type & Balance -->
						<div class="col-12 col-md-6">
							<label class="form-label fw-semibold text-dark">Leave Type <span class="text-danger">*</span></label>
							<select class="form-select select2" id="leaveapplyleaveType" required></select>
							<div id="balancelbl" class="mt-2 small"></div>
						</div>

						<!-- Total Days (Readonly) -->
						<div class="col-12 col-md-6">
							<label class="form-label fw-semibold text-dark">Total Days</label>
							<div class="input-group">
								<span class="input-group-text bg-light border-end-0"><i
										class="ti ti-calendar-event"></i></span>
								<input type="number" class="form-control bg-light fw-bold border-start-0" id="totalDays"
									placeholder="0" readonly>
							</div>
						</div>

						<!-- Date Range -->
						<div class="col-12 col-md-6">
							<label class="form-label fw-semibold text-dark">From Date <span class="text-danger">*</span></label>
							<input type="date" class="form-control" id="leaveapplyfromDate" onclick="this.showPicker()" required>
						</div>
						<div class="col-12 col-md-6">
							<label class="form-label fw-semibold text-dark">To Date <span class="text-danger">*</span></label>
							<input type="date" class="form-control" id="leaveapplytoDate" onclick="this.showPicker()" required>
						</div>

						<!-- Reason -->
						<div class="col-12">
							<label class="form-label fw-semibold text-dark">Reason for Leave <span class="text-danger">*</span></label>
							<textarea class="form-control" id="reason" rows="4" placeholder="Detailed reason..."
								required></textarea>
						</div>

						<!-- Attachment -->
						<div class="col-12">
							<label class="form-label fw-semibold text-dark">Attachment <span class="text-muted fw-normal" style="color: #7f8c8d !important;">(Optional)</span></label>
							<div class="custom-file-upload" id="customFileUploadContainer">
								<div class="upload-btn" id="fakeUploadBtn">Choose File</div>
								<div class="file-name" id="fileNameDisplay" style="color: #94a3b8;">No file chosen</div>
								<div class="upload-icon" id="fakeUploadIcon"><i class="ti ti-paperclip"></i></div>
								<input type="file" id="attachment" name="Attachment" accept=".jpg,.png,.pdf" style="display: none;">
							</div>
						</div>
					</div>
				</form>
			</div>
			<div class="modal-footer py-3">
				<button type="button" class="btn btn-cancel px-4" data-bs-dismiss="modal">Cancel</button>
				<button type="reset" class="btn btn-reset px-4"
					onclick="clearFormElementsInDiv('leaveForm'); $('#fileNameDisplay').text('No file chosen').css('color', '#94a3b8');">Reset</button>
				<button type="button" id="savedappliyedleavebtn"
					onclick="SaveOrUpdateAppliedLeave('EmployeeList', 'leaveapplyleaveType', 'leaveapplyfromDate', 'leaveapplytoDate', 'reason', 'attachment', 'savedappliyedleavebtn');"
					class="btn btn-submit px-4 fw-bold shadow-sm">
					Submit Application
				</button>
			</div>
		</div>
	</div>
</div>

</div>
<!-- /Apply Leave -->
<div class="d-none" id="hiddenLeaveBalances">
	<span id="remainEarnleavslbl">0</span>
	<span id="remainSickleavelbl">0</span>
	<span id="remainCasualleavelbl">0</span>
	<span id="totalEarnleavelbl">0</span>
	<span id="totalSickleavelbl">0</span>
	<span id="totalCasualleavelbl">0</span>
</div>
<?php include('footer.php'); ?>
<script src="../assets/js/empleave.js?v=<?= time(); ?>"></script>
<script>
	let empid = '<?php echo $empid; ?>';
	let leaveyear = '<?php echo date('Y'); ?>';
	EmployeeLeaveCountDetails(empid, leaveyear);

	fetchEmployeeData_callback('allactive', '', (res) => {
		const $dropdown = $("#EmployeeList");
		const EmployeeId = empid; // You can set a default or preselected ID if needed
		$dropdown.empty(); // Clear existing options
		if (Array.isArray(res) && res.length > 0) {
			if (res.length > 1) {
				$dropdown.append(`<option value="">Select</option>`);
			}
			res.forEach(emp => {
				const { EmployeeID, FullName } = emp;
				$dropdown.append(`<option value="${EmployeeID}">${FullName}</option>`);
			});
			// ✅ If only one employee, select automatically
			if (res.length === 1) {
				$dropdown.val(res[0].EmployeeID).trigger("change");
			} else {
				// ✅ Otherwise select saved EmployeeId if available
				if (EmployeeId) {
					$dropdown.val(EmployeeId).trigger("change");
				} else {
					$dropdown.trigger("change");
				}
			}
		} else {
			// ✅ Fallback if no data received
			$dropdown.append(`<option value="">No data available</option>`);
			$dropdown.trigger("change");
		}
	});
	LeaveTypeMaster_callback('all', '', function (res) {
		const $dropdown = $("#leaveapplyleaveType");
		const leave_code = ""; // You can set a default or preselected ID if needed
		$dropdown.empty(); // Clear existing options
		if (Array.isArray(res) && res.length > 0) {
			const activeLeaves = res.filter(lytype => (lytype.status || '').toLowerCase() !== 'inactive');
			if (activeLeaves.length > 0) {
				if (activeLeaves.length > 1) {
					$dropdown.append(`<option value="">Select</option>`);
				}
				activeLeaves.forEach(lytype => {
					const { leave_code, leave_name, leave_type } = lytype;
					$dropdown.append(`<option value="${leave_code}" data-type="${leave_type}">${leave_name}</option>`);
				});
				// ✅ If only one employee, select automatically
				if (activeLeaves.length === 1) {
					$dropdown.val(activeLeaves[0].leave_code).trigger("change");
				} else {
					// ✅ Otherwise select saved EmployeeId if available
					if (leave_code) {
						$dropdown.val(leave_code).trigger("change");
					} else {
						$dropdown.trigger("change");
					}
				}
			} else {
				$dropdown.append(`<option value="">No active leave types available</option>`);
				$dropdown.trigger("change");
			}
		} else {
			// ✅ Fallback if no data received
			$dropdown.append(`<option value="">No data available</option>`);
			$dropdown.trigger("change");
		}
	});
</script>