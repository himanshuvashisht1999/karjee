<?php
include('header.php');
if($loginroleid==0 || $loginroleid<=2){

?>
<div class="content">	
	<div class="row mt-4 g-0 text-white">
		<div class="kpi-row kpi-row-compact">
			<div class="kpi-card compact d-flex align-items-center gap-3" data-view="activeemployeedata">
				<span class="avatar avatar-lg bg-success rounded-circle">
					<i class="ti ti-user-share"></i>
				</span>
				<div class="kpi-content">
					<span class="kpi-label">Active</span>
					<span class="counter" data-key="activeemployeecount">0</span>
				</div>
			</div>
			<div class="kpi-card compact d-flex align-items-center gap-3" data-view="inactiveemployeedata">
				<span class="avatar avatar-lg bg-danger rounded-circle">
					<i class="ti ti-user-x"></i>
				</span>
				<div class="kpi-content">
					<span class="kpi-label">Resigned</span>
					<span class="counter" data-key="inactiveemployeecount">0</span>
				</div>
			</div>
			<div class="kpi-card compact d-flex align-items-center gap-3" data-view="newjoineremployeedata">
				<span class="avatar avatar-lg bg-info rounded-circle">
					<i class="ti ti-user-plus"></i>
				</span>
				<div class="kpi-content">
					<span class="kpi-label">New Joiner</span>
					<span class="counter" data-key="newjoineremployeecount">0</span>
				</div>
			</div>
			<div class="kpi-card compact d-flex align-items-center gap-3" data-view="totalemployeedata">
				<span class="avatar avatar-lg bg-primary rounded-circle">
					<i class="ti ti-users"></i>
				</span>
				<div class="kpi-content">
					<span class="kpi-label">Total</span>
					<span class="counter" data-key="totalemployeecount">0</span>
				</div>
			</div>
		</div>
	</div>
	<div class="row d-none">
		<div class="col-lg-3 col-md-6 d-flex">
			<div class="card flex-fill">
				<div class="card-body d-flex align-items-center justify-content-between">
					<div class="d-flex align-items-center overflow-hidden">
						<div>
							<span class="avatar avatar-lg bg-success rounded-circle"><i
									class="ti ti-user-share"></i></span>
						</div>
						<div class="ms-2 overflow-hidden">
							<p class="fs-12 fw-medium mb-1 text-truncate">Active</p>
							<h4 id="">1007</h4>
						</div>
					</div>
					<div>
						<span class="badge badge-soft-primary badge-sm fw-normal">
							<i class="ti ti-arrow-wave-right-down"></i>
							+19.01%
						</span>
					</div>
				</div>
			</div>
		</div>
		<div class="col-lg-3 col-md-6 d-flex">
			<div class="card flex-fill">
				<div class="card-body d-flex align-items-center justify-content-between">
					<div class="d-flex align-items-center overflow-hidden">
						<div>
							<span class="avatar avatar-lg bg-danger rounded-circle"><i
									class="ti ti-user-pause"></i></span>
						</div>
						<div class="ms-2 overflow-hidden">
							<p class="fs-12 fw-medium mb-1 text-truncate">InActive</p>
							<h4>1007</h4>
						</div>
					</div>
					<div>
						<span class="badge badge-soft-dark badge-sm fw-normal">
							<i class="ti ti-arrow-wave-right-down"></i>
							+19.01%
						</span>
					</div>
				</div>
			</div>
		</div>
		<div class="col-lg-3 col-md-6 d-flex">
			<div class="card flex-fill">
				<div class="card-body d-flex align-items-center justify-content-between">
					<div class="d-flex align-items-center overflow-hidden">
						<div>
							<span class="avatar avatar-lg bg-info rounded-circle"><i class="ti ti-user-plus"></i></span>
						</div>
						<div class="ms-2 overflow-hidden">
							<p class="fs-12 fw-medium mb-1 text-truncate">New Joiners</p>
							<h4>67</h4>
						</div>
					</div>
					<div>
						<span class="badge badge-soft-secondary badge-sm fw-normal">
							<i class="ti ti-arrow-wave-right-down"></i>
							+19.01%
						</span>
					</div>
				</div>
			</div>
		</div>
		<div class="col-lg-3 col-md-6 d-flex">
			<div class="card flex-fill">
				<div class="card-body d-flex align-items-center justify-content-between">
					<div class="d-flex align-items-center overflow-hidden">
						<div>
							<span class="avatar avatar-lg bg-dark rounded-circle"><i class="ti ti-users"></i></span>
						</div>
						<div class="ms-2 overflow-hidden">
							<p class="fs-12 fw-medium mb-1 text-truncate">Total Employee</p>
							<h4>1007</h4>
						</div>
					</div>
					<div>
						<span class="badge badge-soft-purple badge-sm fw-normal">
							<i class="ti ti-arrow-wave-right-down"></i>
							+19.01%
						</span>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="card">
		<div class="card-header d-flex align-items-center justify-content-between flex-wrap row-gap-3">
			<h3 class="mb-2">Employee List</h3>

			<div class="d-flex my-xl-auto right-content align-items-center flex-wrap row-gap-3">
				<div class="me-3">
					<a href="<?php echo $rooturl; ?>employeeResigtration.php?k=<?php echo $key; ?>"
						class="btn btn-primary d-flex align-items-center"><i class="ti ti-circle-plus me-2"></i>Add
						Employee</a>
				</div>
				<div class="dropdown">
					<a href="javascript:void(0);"
						class="dropdown-toggle btn btn-primary d-inline-flex align-items-center"
						data-bs-toggle="dropdown">
						<i class="ti ti-file-export me-1"></i>Export
					</a>
					<ul class="dropdown-menu  dropdown-menu-end p-3">
						<li>
							<button class="dropdown-item rounded-1" onclick="exportEmployeePDF()"><i
									class="ti ti-file-type-pdf me-1"></i>Export as PDF</button>
						</li>
						<li>
							<button class="dropdown-item rounded-1" onclick="exportEmployeeExcel('Employee Of Karjee')"><i
									class="ti ti-file-type-xls me-1"></i>Export as Excel </button>
						</li>
					</ul>
				</div>
				<div class="dropdown me-3 d-none">
					<a href="javascript:void(0);" class="dropdown-toggle btn btn-white d-inline-flex align-items-center"
						data-bs-toggle="dropdown">
						Designation
					</a>
					<ul class="dropdown-menu  dropdown-menu-end p-3">
						<li>
							<a href="javascript:void(0);" class="dropdown-item rounded-1">Finance</a>
						</li>
						<li>
							<a href="javascript:void(0);" class="dropdown-item rounded-1">Developer</a>
						</li>
						<li>
							<a href="javascript:void(0);" class="dropdown-item rounded-1">Executive</a>
						</li>
					</ul>
				</div>
			</div>
		</div>
		<div class="card-body p-0">
			<div class="row p-2 g-3 align-items-end">
				<div class="col-md-6">
					<label class="form-label fw-semibold mb-1">Search to Filter</label>
					<input type="search" class="form-control" placeholder="Search...." id="searchempmastertbl"
						oninput="filterEmployeeTable();">
				</div>
				<div class="col-md-6">
					<label class="form-label fw-semibold mb-1">Working Location</label>
					<select class="form-select" id="filterWorkingLocation" onchange="filterEmployeeTable();">
						<option value="all" selected>All Locations</option>
					</select>
				</div>
			</div>
				<div class="col-md-12 mt-2">
					<div class=" table-responsive">
						<table class="table table-bordered" id="emplistPayrolltbl" style="font-size: .8em;">
							<thead class="thead-light">
								<tr>
									<th class="d-none">Employee Id</th>
									<th class="d-none">Employee Name</th>
									<th style="min-width: 280px;" class="noexport">Name</th>
									<th>Working Location / Hours</th>
									<th>Joining Date</th>
									<th>Net Salary</th>
									<th class="noexport d-none">Status</th>
									<th class="noexport">Action</th>
								</tr>
							</thead>
							<tbody></tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<?php 
}else{
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
	let countdown = 30;
	Swal.fire({
		icon: 'error',
		title: 'Access Denied',
		html: 'You are not authorized to access this page.<br>Redirecting to logout in <b>' + countdown + '</b> seconds...',
		allowOutsideClick: false,
		allowEscapeKey: false,
		showConfirmButton: false,
		timer: 30000,
		timerProgressBar: true,
		didOpen: function() {
			var timerInterval = setInterval(function() {
				countdown--;
				if(countdown <= 0) {
					clearInterval(timerInterval);
				} else {
					Swal.getHtmlContainer().querySelector('b').textContent = countdown;
				}
			}, 1000);
		},
		willClose: function() {
			window.location.href = '<?php echo $rooturl; ?>logout.php';
		}
	});
});
</script>
<?php	
}
?>
<!-- Add Employee -->
<div class="modal fade" id="add_employee">
	<div class="modal-dialog modal-dialog-centered modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<div class="d-flex align-items-center">
					<h4 class="modal-title me-2">Add New Employee</h4><span>Employee ID : EMP -<b
							id="empid_elbl"></b></span>
				</div>
				<button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
					<i class="ti ti-x"></i>
				</button>
			</div>
			<form action="#">
				<div class="contact-grids-tab">
					<ul class="nav nav-underline" id="myTab" role="tablist">
						<li class="nav-item" role="presentation">
							<button class="nav-link active" id="info-tab" data-bs-toggle="tab"
								data-bs-target="#basic-info" type="button" role="tab" aria-selected="true">Basic
								Information</button>
						</li>
						<li class="nav-item" role="presentation">
							<button class="nav-link" id="address-tab" data-bs-toggle="tab" data-bs-target="#address"
								type="button" role="tab" aria-selected="false">Permissions</button>
						</li>
					</ul>
				</div>
				<div class="tab-content" id="myTabContent">
					<div class="tab-pane fade show active" id="basic-info" role="tabpanel" aria-labelledby="info-tab"
						tabindex="0">
						<div class="modal-body pb-0 ">
							<div class="row">
								<div class="col-md-12">
									<div
										class="d-flex align-items-center flex-wrap row-gap-3 bg-light w-100 rounded p-3 mb-4">
										<div
											class="d-flex align-items-center justify-content-center avatar avatar-xxl rounded-circle border border-dashed me-2 flex-shrink-0 text-dark frames">
											<i class="ti ti-photo text-gray-2 fs-16"></i>
										</div>
										<div class="profile-upload">
											<div class="mb-2">
												<h6 class="mb-1">Upload Profile Image</h6>
												<p class="fs-12">Image should be below 4 mb</p>
											</div>
											<div class="profile-uploader d-flex align-items-center">
												<div class="drag-upload-btn btn btn-sm btn-primary me-2">
													Upload
													<input type="file" class="form-control image-sign" multiple="">
												</div>
												<a href="javascript:void(0);" class="btn btn-light btn-sm">Cancel</a>
											</div>

										</div>
									</div>
								</div>
								<div class="col-md-6">
									<div class="mb-3">
										<label class="form-label">First Name <span class="text-danger"> *</span></label>
										<input type="text" name="firsname_elbl" id="firsname_elbl" class="form-control">
									</div>
								</div>
								<div class="col-md-6">
									<div class="mb-3">
										<label class="form-label">Middle Name</label>
										<input type="text" name="middlename_elbl" id="middlename_elbl"
											class="form-control">
									</div>
								</div>
								<div class="col-md-6">
									<div class="mb-3">
										<label class="form-label">Last Name</label>
										<input type="text" name="lastname_elbl" id="lastname_elbl" class="form-control">
									</div>
								</div>
								<div class="col-md-6">
									<div class="mb-3">
										<label class="form-label">Joining Date <span class="text-danger">
												*</span></label>
										<div class="input-icon-end position-relative">
											<input type="date" name="joiningdate_elbl" id="joiningdate_elbl"
												class="form-control datetimepicker" placeholder="dd/mm/yyyy">
											<span class="input-icon-addon">
												<i class="ti ti-calendar text-gray-7"></i>
											</span>
										</div>
									</div>
								</div>
								<div class="col-md-6">
									<div class="mb-3">
										<label class="form-label">Email <span class="text-danger"> *</span></label>
										<input type="email" name="email_elbl" id="email_elbl" class="form-control">
									</div>
								</div>
								<div class="col-md-6">
									<div class="mb-3">
										<label class="form-label">Phone Number <span class="text-danger">
												*</span></label>
										<input type="text" name="phoneno_elbl" id="phoneno_elbl" class="form-control">
									</div>
								</div>
								<div class="col-md-6">
									<div class="mb-3">
										<label class="form-label">Department</label>
										<select class="select" name="department_elbl" id="department_elbl">
											<option>Select</option>
											<option>All Department</option>
											<option>Finance</option>
											<option>Developer</option>
											<option>Executive</option>
										</select>
									</div>
								</div>
								<div class="col-md-6">
									<div class="mb-3">
										<label class="form-label">Designation</label>
										<select class="select" name="designation_elbl" id="designation_elbl">
											<option>Select</option>
											<option>Finance</option>
											<option>Developer</option>
											<option>Executive</option>
										</select>
									</div>
								</div>
							</div>
						</div>
						<div class="modal-footer">
							<button type="button" class="btn btn-outline-light border me-2"
								data-bs-dismiss="modal">Cancel</button>
							<button type="submit" class="btn btn-primary">UPDATE </button>
						</div>
					</div>
					<div class="tab-pane fade" id="address" role="tabpanel" aria-labelledby="address-tab" tabindex="0">
						<div class="modal-body">
							<div class="card bg-light-500 shadow-none">
								<div
									class="card-body d-flex align-items-center justify-content-between flex-wrap row-gap-3">
									<h6>Enable Options</h6>
									<div class="d-flex align-items-center justify-content-end">
										<div class="form-check form-switch me-2">
											<label class="form-check-label mt-0">
												<input class="form-check-input me-2" type="checkbox" role="switch">
												Enable all Module
											</label>
										</div>
										<div class="form-check d-flex align-items-center">
											<label class="form-check-label mt-0">
												<input class="form-check-input" type="checkbox" checked="">
												Select All
											</label>
										</div>
									</div>
								</div>
							</div>
							<div class="table-responsive border rounded">
								<table class="table">
									<tbody>
										<tr>
											<td>
												<div class="form-check form-switch me-2">
													<label class="form-check-label mt-0">
														<input class="form-check-input me-2" type="checkbox"
															role="switch" checked>
														Holidays
													</label>
												</div>
											</td>
											<td>
												<div class="form-check d-flex align-items-center">
													<label class="form-check-label mt-0">
														<input class="form-check-input" type="checkbox" checked="">
														Read
													</label>
												</div>
											</td>
											<td>
												<div class="form-check d-flex align-items-center">
													<label class="form-check-label mt-0">
														<input class="form-check-input" type="checkbox">
														Write
													</label>
												</div>
											</td>
											<td>
												<div class="form-check d-flex align-items-center">
													<label class="form-check-label mt-0">
														<input class="form-check-input" type="checkbox">
														Create
													</label>
												</div>
											</td>
											<td>
												<div class="form-check d-flex align-items-center">
													<label class="form-check-label mt-0">
														<input class="form-check-input" type="checkbox" checked="">
														Delete
													</label>
												</div>
											</td>
											<td>
												<div class="form-check d-flex align-items-center">
													<label class="form-check-label mt-0">
														<input class="form-check-input" type="checkbox">
														Import
													</label>
												</div>
											</td>
											<td>
												<div class="form-check d-flex align-items-center">
													<label class="form-check-label mt-0">
														<input class="form-check-input" type="checkbox">
														Export
													</label>
												</div>
											</td>
										</tr>
										<tr>
											<td>
												<div class="form-check form-switch me-2">
													<label class="form-check-label mt-0">
														<input class="form-check-input me-2" type="checkbox"
															role="switch">
														Leaves
													</label>
												</div>
											</td>
											<td>
												<div class="form-check d-flex align-items-center">
													<label class="form-check-label mt-0">
														<input class="form-check-input" type="checkbox">
														Read
													</label>
												</div>
											</td>
											<td>
												<div class="form-check d-flex align-items-center">
													<label class="form-check-label mt-0">
														<input class="form-check-input" type="checkbox">
														Write
													</label>
												</div>
											</td>
											<td>
												<div class="form-check d-flex align-items-center">
													<label class="form-check-label mt-0">
														<input class="form-check-input" type="checkbox">
														Create
													</label>
												</div>
											</td>
											<td>
												<div class="form-check d-flex align-items-center">
													<label class="form-check-label mt-0">
														<input class="form-check-input" type="checkbox">
														Delete
													</label>
												</div>
											</td>
											<td>
												<div class="form-check d-flex align-items-center">
													<label class="form-check-label mt-0">
														<input class="form-check-input" type="checkbox">
														Import
													</label>
												</div>
											</td>
											<td>
												<div class="form-check d-flex align-items-center">
													<label class="form-check-label mt-0">
														<input class="form-check-input" type="checkbox">
														Export
													</label>
												</div>
											</td>
										</tr>
										<tr>
											<td>
												<div class="form-check form-switch me-2">
													<label class="form-check-label mt-0">
														<input class="form-check-input me-2" type="checkbox"
															role="switch">
														Clients
													</label>
												</div>
											</td>
											<td>
												<div class="form-check d-flex align-items-center">
													<label class="form-check-label mt-0">
														<input class="form-check-input" type="checkbox">
														Read
													</label>
												</div>
											</td>
											<td>
												<div class="form-check d-flex align-items-center">
													<label class="form-check-label mt-0">
														<input class="form-check-input" type="checkbox">
														Write
													</label>
												</div>
											</td>
											<td>
												<div class="form-check d-flex align-items-center">
													<label class="form-check-label mt-0">
														<input class="form-check-input" type="checkbox">
														Create
													</label>
												</div>
											</td>
											<td>
												<div class="form-check d-flex align-items-center">
													<label class="form-check-label mt-0">
														<input class="form-check-input" type="checkbox">
														Delete
													</label>
												</div>
											</td>
											<td>
												<div class="form-check d-flex align-items-center">
													<label class="form-check-label mt-0">
														<input class="form-check-input" type="checkbox">
														Import
													</label>
												</div>
											</td>
											<td>
												<div class="form-check d-flex align-items-center">
													<label class="form-check-label mt-0">
														<input class="form-check-input" type="checkbox">
														Export
													</label>
												</div>
											</td>
										</tr>
										<tr>
											<td>
												<div class="form-check form-switch me-2">
													<label class="form-check-label mt-0">
														<input class="form-check-input me-2" type="checkbox"
															role="switch">
														Projects
													</label>
												</div>
											</td>
											<td>
												<div class="form-check d-flex align-items-center">
													<label class="form-check-label mt-0">
														<input class="form-check-input" type="checkbox">
														Read
													</label>
												</div>
											</td>
											<td>
												<div class="form-check d-flex align-items-center">
													<label class="form-check-label mt-0">
														<input class="form-check-input" type="checkbox">
														Write
													</label>
												</div>
											</td>
											<td>
												<div class="form-check d-flex align-items-center">
													<label class="form-check-label mt-0">
														<input class="form-check-input" type="checkbox">
														Create
													</label>
												</div>
											</td>
											<td>
												<div class="form-check d-flex align-items-center">
													<label class="form-check-label mt-0">
														<input class="form-check-input" type="checkbox">
														Delete
													</label>
												</div>
											</td>
											<td>
												<div class="form-check d-flex align-items-center">
													<label class="form-check-label mt-0">
														<input class="form-check-input" type="checkbox">
														Import
													</label>
												</div>
											</td>
											<td>
												<div class="form-check d-flex align-items-center">
													<label class="form-check-label mt-0">
														<input class="form-check-input" type="checkbox">
														Export
													</label>
												</div>
											</td>
										</tr>
										<tr>
											<td>
												<div class="form-check form-switch me-2">
													<label class="form-check-label mt-0">
														<input class="form-check-input me-2" type="checkbox"
															role="switch">
														Tasks
													</label>
												</div>
											</td>
											<td>
												<div class="form-check d-flex align-items-center">
													<label class="form-check-label mt-0">
														<input class="form-check-input" type="checkbox">
														Read
													</label>
												</div>
											</td>
											<td>
												<div class="form-check d-flex align-items-center">
													<label class="form-check-label mt-0">
														<input class="form-check-input" type="checkbox">
														Write
													</label>
												</div>
											</td>
											<td>
												<div class="form-check d-flex align-items-center">
													<label class="form-check-label mt-0">
														<input class="form-check-input" type="checkbox">
														Create
													</label>
												</div>
											</td>
											<td>
												<div class="form-check d-flex align-items-center">
													<label class="form-check-label mt-0">
														<input class="form-check-input" type="checkbox">
														Delete
													</label>
												</div>
											</td>
											<td>
												<div class="form-check d-flex align-items-center">
													<label class="form-check-label mt-0">
														<input class="form-check-input" type="checkbox">
														Import
													</label>
												</div>
											</td>
											<td>
												<div class="form-check d-flex align-items-center">
													<label class="form-check-label mt-0">
														<input class="form-check-input" type="checkbox">
														Export
													</label>
												</div>
											</td>
										</tr>
										<tr>
											<td>
												<div class="form-check form-switch me-2">
													<label class="form-check-label mt-0">
														<input class="form-check-input me-2" type="checkbox"
															role="switch">
														Chats
													</label>
												</div>
											</td>
											<td>
												<div class="form-check d-flex align-items-center">
													<label class="form-check-label mt-0">
														<input class="form-check-input" type="checkbox">
														Read
													</label>
												</div>
											</td>
											<td>
												<div class="form-check d-flex align-items-center">
													<label class="form-check-label mt-0">
														<input class="form-check-input" type="checkbox">
														Write
													</label>
												</div>
											</td>
											<td>
												<div class="form-check d-flex align-items-center">
													<label class="form-check-label mt-0">
														<input class="form-check-input" type="checkbox">
														Create
													</label>
												</div>
											</td>
											<td>
												<div class="form-check d-flex align-items-center">
													<label class="form-check-label mt-0">
														<input class="form-check-input" type="checkbox">
														Delete
													</label>
												</div>
											</td>
											<td>
												<div class="form-check d-flex align-items-center">
													<label class="form-check-label mt-0">
														<input class="form-check-input" type="checkbox">
														Import
													</label>
												</div>
											</td>
											<td>
												<div class="form-check d-flex align-items-center">
													<label class="form-check-label mt-0">
														<input class="form-check-input" type="checkbox">
														Export
													</label>
												</div>
											</td>
										</tr>
										<tr>
											<td>
												<div class="form-check form-switch me-2">
													<label class="form-check-label mt-0">
														<input class="form-check-input me-2" type="checkbox"
															role="switch" checked>
														Assets
													</label>
												</div>
											</td>
											<td>
												<div class="form-check d-flex align-items-center">
													<label class="form-check-label mt-0">
														<input class="form-check-input" type="checkbox">
														Read
													</label>
												</div>
											</td>
											<td>
												<div class="form-check d-flex align-items-center">
													<label class="form-check-label mt-0">
														<input class="form-check-input" type="checkbox">
														Write
													</label>
												</div>
											</td>
											<td>
												<div class="form-check d-flex align-items-center">
													<label class="form-check-label mt-0">
														<input class="form-check-input" type="checkbox" checked="">
														Create
													</label>
												</div>
											</td>
											<td>
												<div class="form-check d-flex align-items-center">
													<label class="form-check-label mt-0">
														<input class="form-check-input" type="checkbox">
														Delete
													</label>
												</div>
											</td>
											<td>
												<div class="form-check d-flex align-items-center">
													<label class="form-check-label mt-0">
														<input class="form-check-input" type="checkbox" checked="">
														Import
													</label>
												</div>
											</td>
											<td>
												<div class="form-check d-flex align-items-center">
													<label class="form-check-label mt-0">
														<input class="form-check-input" type="checkbox">
														Export
													</label>
												</div>
											</td>
										</tr>
										<tr>
											<td>
												<div class="form-check form-switch me-2">
													<label class="form-check-label mt-0">
														<input class="form-check-input me-2" type="checkbox"
															role="switch">
														Timing Sheets
													</label>
												</div>
											</td>
											<td>
												<div class="form-check d-flex align-items-center">
													<label class="form-check-label mt-0">
														<input class="form-check-input" type="checkbox">
														Read
													</label>
												</div>
											</td>
											<td>
												<div class="form-check d-flex align-items-center">
													<label class="form-check-label mt-0">
														<input class="form-check-input" type="checkbox">
														Write
													</label>
												</div>
											</td>
											<td>
												<div class="form-check d-flex align-items-center">
													<label class="form-check-label mt-0">
														<input class="form-check-input" type="checkbox">
														Create
													</label>
												</div>
											</td>
											<td>
												<div class="form-check d-flex align-items-center">
													<label class="form-check-label mt-0">
														<input class="form-check-input" type="checkbox">
														Delete
													</label>
												</div>
											</td>
											<td>
												<div class="form-check d-flex align-items-center">
													<label class="form-check-label mt-0">
														<input class="form-check-input" type="checkbox">
														Import
													</label>
												</div>
											</td>
											<td>
												<div class="form-check d-flex align-items-center">
													<label class="form-check-label mt-0">
														<input class="form-check-input" type="checkbox">
														Export
													</label>
												</div>
											</td>
										</tr>
									</tbody>
								</table>
							</div>
						</div>
						<div class="modal-footer">
							<button type="button" class="btn btn-outline-light border me-2"
								data-bs-dismiss="modal">Cancel</button>
							<button type="button" class="btn btn-primary" data-bs-toggle="modal"
								data-bs-target="#success_modal">Save </button>
						</div>
					</div>
				</div>
			</form>
		</div>
	</div>
</div>
<!-- /Add Employee -->

<!-- Edit Employee -->
<div class="modal fade" id="edit_employee">
	<div class="modal-dialog modal-dialog-centered modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<div class="d-flex align-items-center">
					<h4 class="modal-title me-2">Edit Employee</h4><span>Employee ID : EMP -<b
							id="empid_edit"></b></span>
				</div>
				<button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
					<i class="ti ti-x"></i>
				</button>
			</div>
			<!-- <form action="#"> -->
			<div class="contact-grids-tab">
				<ul class="nav nav-underline" id="myTab" role="tablist">
					<li class="nav-item" role="presentation">
						<button class="nav-link active" id="info-tab" data-bs-toggle="tab" data-bs-target="#basic-info"
							type="button" role="tab" aria-selected="true">Basic Information</button>
					</li>

				</ul>
			</div>
			<div class="tab-content" id="myTabContent">
				<div class="tab-pane fade show active" id="basic-info" role="tabpanel" aria-labelledby="info-tab"
					tabindex="0">
					<div class="modal-body pb-0 ">
						<div class="row">
							<!-- <div class="col-md-12">
									<div class="d-flex align-items-center flex-wrap row-gap-3 bg-light w-100 rounded p-3 mb-4">                                              
										<div class="d-flex align-items-center justify-content-center avatar avatar-xxl rounded-circle border border-dashed me-2 flex-shrink-0 text-dark frames">
											<i class="ti ti-photo text-gray-2 fs-16"></i>
										</div>                                              
										<div class="profile-upload">
											<div class="mb-2">
												<h6 class="mb-1">Upload Profile Image</h6>
												<p class="fs-12">Image should be below 4 mb</p>
											</div>
											<div class="profile-uploader d-flex align-items-center">
												<div class="drag-upload-btn btn btn-sm btn-primary me-2">
													Upload
													<input type="file" class="form-control image-sign" multiple="" id="imagesign_edit">
												</div>
												<a href="javascript:void(0);" class="btn btn-light btn-sm">Cancel</a>
											</div>												
										</div>
									</div>
								</div> -->
						</div>
						<div class="row">
							<div class="col-md-2">
								<label for="titlename_edit" class="form-label">Title <sup><i
											class="fa fa-star text-danger small"></i></sup></label>
								<select class="form-select" id="titlename_edit" name="titlename_edit">
									<option value="Mr.">Mr.</option>
									<option value="Ms.">Ms.</option>
									<option value="Mrs.">Mrs.</option>
									<option value="Miss">Miss</option>
								</select>
							</div>
							<div class="col-md-10">
								<div class="mb-3">
									<label class="form-label">First Name <span class="text-danger"> *</span></label>
									<input type="text" name="firsname_edit" id="firsname_edit" class="form-control">
								</div>
							</div>
						</div>
						<div class="row">
							<div class="col-md-6">
								<div class="mb-3">
									<label class="form-label">Middle Name</label>
									<input type="text" name="middlename_edit" id="middlename_edit" class="form-control">
								</div>
							</div>
							<div class="col-md-6">
								<div class="mb-3">
									<label class="form-label">Last Name</label>
									<input type="text" name="lastname_edit" id="lastname_edit" class="form-control">
								</div>
							</div>
						</div>
						<div class="row">
							<div class="col-md-6">
								<div class="mb-3">
									<label class="form-label">Date of Birth</label>
									<div class="input-icon-end position-relative">
										<input type="date" name="dateofbirth_edit" id="dateofbirth_edit"
											class="form-control datetimepicker" placeholder="dd/mm/yyyy">
										<span class="input-icon-addon">
											<i class="ti ti-calendar text-gray-7"></i>
										</span>
									</div>
								</div>
							</div>
							<div class="col-md-6">
								<div class="mb-3">
									<label class="form-label">Gender</label>
									<select class="select" name="gender_edit" id="gender_edit">
										<option value="" selected disabled>Select</option>
										<option>Male</option>
										<option>Female</option>
										<option>Other</option>
									</select>
								</div>
							</div>

							<div class="col-md-4">
								<div class="mb-3">
									<label class="form-label">Email</label>
									<input type="email" name="email_edit" id="email_edit" class="form-control">
								</div>
							</div>
							<div class="col-md-4">
								<div class="mb-3">
									<label class="form-label">Contact Number</label>
									<input type="text" name="phoneno_edit" id="phoneno_edit" class="form-control">
								</div>
							</div>
							<div class="col-md-4">
								<div class="mb-3">
									<label class="form-label">Joining Date</label>
									<div class="input-icon-end position-relative">
										<input type="text" name="joiningdate_edit" id="joiningdate_edit"
											class="form-control datetimepicker" placeholder="dd/mm/yyyy">
										<span class="input-icon-addon">
											<i class="ti ti-calendar text-gray-7"></i>
										</span>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-outline-light border me-2"
							data-bs-dismiss="modal">Cancel</button>
						<button type="button" id="updateEmpProfile" data-editempid=""
							onclick="UpdateEmployeeProfileDetails('updateEmpProfile')" class="btn btn-primary">UPDATE
						</button>
					</div>
				</div>
			</div>
			<!-- </form> -->
		</div>
	</div>
</div>
<!-- /Edit Employee -->

<!-- Add Employee Success -->
<div class="modal fade" id="success_modal" role="dialog">
	<div class="modal-dialog modal-dialog-centered modal-sm">
		<div class="modal-content">
			<div class="modal-body">
				<div class="text-center p-3">
					<span class="avatar avatar-lg avatar-rounded bg-success mb-3"><i
							class="ti ti-check fs-24"></i></span>
					<h5 class="mb-2">Employee Added Successfully</h5>
					<p class="mb-3">Stephan Peralt has been added with Client ID : <span class="text-primary">#EMP -
							0001</span>
					</p>
					<div>
						<div class="row g-2">
							<div class="col-6">
								<a href="https://smarthr.co.in/demo/html/template/employees.html"
									class="btn btn-dark w-100">Back to List</a>
							</div>
							<div class="col-6">
								<a href="https://smarthr.co.in/demo/html/template/employee-details.html"
									class="btn btn-primary w-100">Detail Page</a>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<!-- /Add Client Success -->

<!-- Delete Modal -->
<div class="modal fade" id="delete_modal">
	<div class="modal-dialog modal-dialog-centered modal-md">
		<div class="modal-content">
			<div class="modal-body">
				<div class="row">
					<div class="col-md-12 text-center">
						<span class="avatar avatar-xl bg-transparent-danger text-danger mb-3">
							<i class="ti ti-trash-x fs-36"></i>
						</span>
						<h4 class="mb-1">
							Mark Employee as Resigned<br>
							<b class="empNamelbl"></b>
						</h4>
						<p class="mb-3">
							This action will move the employee to the resigned list and deactivate their account.
							Their data will remain available for future reference.
						</p>
					</div>
				</div>
				<div class="row">
					<div class="col-md-12">
						<label>Resignation/Remove Date</label>
						<input type="date" class="form-control" id="dordate" />
					</div>
					<div class="col-md-12">
						<label>Remark <sup class="text-danger">*</sup></label>
						<textarea id="empremoveremark" rows="3" class="form-control"></textarea>
					</div>
				</div>
				<div class="d-flex justify-content-center mt-4">
					<a href="javascript:void(0);" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</a>
					<button class="btn btn-danger" id="removeEmployeebtn">Yes, Remove From Active Master</button>
				</div>
			</div>
		</div>
	</div>
</div>
<!-- /Delete Modal -->
<?php include('footer.php'); ?>
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.29/jspdf.plugin.autotable.min.js"></script>

<script type="text/javascript">
	var routurl = $("body").data("routurl");
	var key = $("body").data("key");

	function populateWorkingLocations(list) {
		const $sel = $("#filterWorkingLocation");
		const currentVal = $sel.val() || "all";
		const locSet = new Set();

		if (Array.isArray(list)) {
			list.forEach(function (emp) {
				if (emp.LocationName && emp.LocationName.trim() && emp.LocationName !== "-") {
					locSet.add(emp.LocationName.trim());
				}
			});
		}

		if (typeof AttendanceLocationMaster_callback === "function") {
			AttendanceLocationMaster_callback("all", "all", function (res) {
				if (Array.isArray(res)) {
					res.forEach(function (loc) {
						if (loc.LocationName && loc.LocationName.trim()) {
							locSet.add(loc.LocationName.trim());
						}
					});
				}
				updateDropdown();
			});
		} else {
			updateDropdown();
		}

		function updateDropdown() {
			$sel.empty().append('<option value="all">All Locations</option>');
			Array.from(locSet).sort().forEach(function (locName) {
				const isSelected = (currentVal.toLowerCase() === locName.toLowerCase()) ? "selected" : "";
				$sel.append('<option value="' + locName.replace(/"/g, '&quot;') + '" ' + isSelected + '>' + locName + '</option>');
			});
			if (!currentVal || currentVal === "all") {
				$sel.val("all");
			}
		}
	}

	function filterEmployeeTable() {
		var searchVal = ($("#searchempmastertbl").val() || "").toLowerCase().trim();
		var selectedLoc = ($("#filterWorkingLocation").val() || "all").toLowerCase().trim();

		$("#emplistPayrolltbl tbody tr").each(function () {
			var $tr = $(this);
			
			if ($tr.find("td").length <= 1 || $tr.text().indexOf("No employee data available") > -1) {
				return;
			}

			var empData = $tr.data("empData");
			
			var locName = "";
			if (empData && empData.LocationName) {
				locName = empData.LocationName.toLowerCase().trim();
			} else {
				var locHtml = $tr.find(".emp_worklocation").html() || "";
				var locParts = locHtml.split("<br");
				locName = locParts[0] ? locParts[0].replace(/<[^>]*>/g, "").toLowerCase().trim() : "";
			}

			var rowText = $tr.text().toLowerCase();
			if (empData) {
				rowText += " " + (empData.EmployeeID || "") + " " + (empData.FullName || "") + " " + (empData.LocationName || "");
			}

			var matchesSearch = (!searchVal || rowText.indexOf(searchVal) > -1);
			var matchesLocation = (selectedLoc === "all" || locName === selectedLoc || locName.indexOf(selectedLoc) > -1);

			if (matchesSearch && matchesLocation) {
				$tr.show();
			} else {
				$tr.hide();
			}
		});
	}

	function getExportData() {
		const exportData = [];
		let sno = 1;
		$("#emplistPayrolltbl tbody tr:visible").each(function () {
			const $tr = $(this);
			const emp = $tr.data("empData");

			let empid = "";
			let empName = "";
			let workLocation = "";
			let workingHours = "";
			let joinDate = "";
			let netSalary = 0;

			if (emp) {
				empid = emp.EmployeeID || "-";
				empName = emp.FullName || "-";
				workLocation = emp.LocationName || "-";
				workingHours = (emp.InTime && emp.OutTime)
					? (typeof formatTime_Hi === "function" ? formatTime_Hi(emp.InTime) + " - " + formatTime_Hi(emp.OutTime) : emp.InTime + " - " + emp.OutTime)
					: "-";
				const isEmpResigned = (String(emp.Status).toLowerCase() === "resigned" || (emp.DateOfResignation && emp.DateOfResignation !== "0000-00-00" && emp.DateOfResignation !== ""));
				const jDate = emp.DateOfJoining
					? new Date(emp.DateOfJoining).toLocaleDateString("en-IN")
					: "-";
				if (isEmpResigned) {
					const rDate = emp.DateOfResignation ? new Date(emp.DateOfResignation).toLocaleDateString("en-IN") : "-";
					joinDate = jDate + " (Resigned: " + rDate + (emp.remark ? ", " + emp.remark : "") + ")";
				} else {
					joinDate = jDate;
				}
				netSalary = parseFloat(emp.NetSalary || 0);
			} else {
				empid = $tr.find("td").eq(0).text().trim() || $tr.data("empid") || "-";
				empName = $tr.find("td").eq(1).text().trim() || $tr.data("empfullname") || "-";
				
				const locTdHtml = $tr.find(".emp_worklocation").html() || "";
				const locParts = locTdHtml.split("<br");
				workLocation = locParts[0] ? locParts[0].replace(/<[^>]*>/g, "").trim() : "-";
				workingHours = $tr.find(".emp_worklocation small").text().trim() || "-";
				joinDate = $tr.find(".emp_doj").text().trim() || "-";

				const salaryTxt = $tr.find("td").eq(5).text().replace(/[^0-9.]/g, "").trim();
				netSalary = salaryTxt ? parseFloat(salaryTxt) : 0;
			}

			exportData.push({
				sno: sno++,
				empid: empid,
				empName: empName,
				workLocation: workLocation,
				workingHours: workingHours,
				joinDate: joinDate,
				netSalary: netSalary
			});
		});
		return exportData;
	}

	function exportEmployeePDF(titleText = "Employee Payroll Report") {
		const data = getExportData();
		if (!data.length) {
			if (typeof Swal !== "undefined") {
				Swal.fire("Warning", "No employee data to export", "warning");
			} else {
				alert("No employee data to export");
			}
			return;
		}

		const { jsPDF } = window.jspdf;
		const doc = new jsPDF("l", "mm", "a4");

		const headers = [["S.No", "EmployeeID", "Employee Name", "Working Location", "Working Hourse", "Joining Date", "Net Salary"]];

		const body = data.map(item => [
			item.sno,
			item.empid,
			item.empName,
			item.workLocation,
			item.workingHours,
			item.joinDate,
			"₹ " + item.netSalary.toLocaleString("en-IN")
		]);

		doc.setFontSize(14);
		doc.text(titleText, 14, 15);
		doc.autoTable({
			head: headers,
			body: body,
			startY: 22,
			theme: "grid",
			styles: {
				fontSize: 9,
				cellPadding: 3
			},
			headStyles: {
				fillColor: [41, 128, 185],
				textColor: 255,
				fontStyle: "bold"
			},
			columnStyles: {
				0: { cellWidth: 15, halign: "center" },
				1: { cellWidth: 30 },
				2: { cellWidth: 50 },
				3: { cellWidth: 45 },
				4: { cellWidth: 40 },
				5: { cellWidth: 30 },
				6: { cellWidth: 35, halign: "right" }
			}
		});

		doc.save("Employee_List.pdf");
	}

	function exportEmployeeExcel(titleText = "Employee List") {
		const data = getExportData();
		if (!data.length) {
			if (typeof Swal !== "undefined") {
				Swal.fire("Warning", "No employee data to export", "warning");
			} else {
				alert("No employee data to export");
			}
			return;
		}

		const headers = ["S.No", "EmployeeID", "Employee Name", "Working Location", "Working Hourse", "Joining Date", "Net Salary"];

		const aoa = [
			[titleText],
			[],
			headers
		];

		data.forEach(item => {
			aoa.push([
				item.sno,
				item.empid,
				item.empName,
				item.workLocation,
				item.workingHours,
				item.joinDate,
				item.netSalary
			]);
		});

		const ws = XLSX.utils.aoa_to_sheet(aoa);

		ws['!cols'] = [
			{ wch: 8 },  // S.No
			{ wch: 15 }, // EmployeeID
			{ wch: 25 }, // Employee Name
			{ wch: 20 }, // Working Location
			{ wch: 20 }, // Working Hourse
			{ wch: 15 }, // Joining Date
			{ wch: 15 }  // Net Salary
		];

		const wb = XLSX.utils.book_new();
		XLSX.utils.book_append_sheet(wb, ws, "Employees");

		XLSX.writeFile(wb, "Employee_List.xlsx");
	}



	/*
	fetchEmployeeDatawithOtherifno_callback('allactive', 'allactive', function(res) {
	  // console.log(res);
	  if (res.length > 0) {
		var emplist = ``;
		for (var i = 0; i < res.length; i++) {
			// empPhotopath
			if(res[i].empPhotopath){
				var profilepath = routurl+res[i].empPhotopath;
			}else{
				var profilepath = routurl+'assets/img/users/profile.jpg';
			}
			// <a href="${routurl}employeeProfile.php?k=${key}&empid=${res[i].EmployeeID}" class="avatar avatar-md" data-bs-toggle="modal" data-bs-target="#view_details">
				emplist += `<tr data-empid="${res[i].EmployeeID}" data-empfullname="${res[i].FullName}" data-dob="${res[i].DateOfBirth}">
			<td class="emp_name">
			  <div class="d-flex align-items-center">
				
				<a href="${routurl}employeeProfile.php?k=${key}&empid=${res[i].EmployeeID}" class="avatar avatar-md">
				  <img src="${profilepath}" class="img-fluid rounded-circle" alt="img">
				</a>
				<div class="ms-2">
				  <p class="text-dark mb-0">
					<a href="${routurl}empPanel/index.php?k=${key}&empid=${res[i].EmployeeID}&accessby=adminu">
					  ${res[i].FirstName} ${res[i].LastName}
					</a>
				  </p>
				  <span class="fs-12">${res[i].DesignationName}</span>
				</div>
			  </div>
			</td>
			<td class="emp_worklocation">${res[i].WorkLocationName}</td>
			<td class="emp_workinghourse">InTime: ${res[i].InTime}<br/>OutTime: ${res[i].OutTime}</td>
			<td class="emp_designation">${res[i].DesignationName}</td>
			<td class="emp_doj">${res[i].DateOfJoining}</td>
			<td><span class="badge badge-success d-inline-flex align-items-center badge-xs"><i class="ti ti-point-filled me-1"></i>${res[i].basiccompletestatus}</span></td>
			<td>
			  <div class="action-icon d-inline-flex">
				<a href="#" class="me-2 d-none" data-bs-toggle="modal" data-bs-target="#edit_employee"><i class="ti ti-edit"></i></a>
				<i class="btn ti ti-trash text-danger deletemodal" style="font-size: 24px;"></i>
			  </div>
			</td>
		  </tr>`;
		}
		$("#emplistPayrolltbl tbody").html(emplist);
	  } else {
		$("#emplistPayrolltbl tbody").html('<tr><td colspan="6" class="text-center">No data available</td></tr>');
	  }
	});
	*/

	// EmployeeSummaryListwithDetails(function(res){
	// 	console.log(res);
	// });

	$(document).ready(function () {
		EmployeeSummaryListwithDetails();
	});
	$(document).on("click", ".kpi-card", function () {
		$(".kpi-card").removeClass("active");
		$(this).addClass("active");
		let view = $(this).data("view");
		if (view === "activeemployeedata") renderPayrollEmployeeTable("Active");
		if (view === "inactiveemployeedata") renderPayrollEmployeeTable("Resigned");
		if (view === "newjoineremployeedata") renderPayrollEmployeeTable("New Joiner");
		if (view === "totalemployeedata") renderPayrollEmployeeTable("Total");
	});


	function animateCounter(el, target) {
		let start = 0;
		let duration = 800;
		let increment = target / (duration / 16);
		let counter = setInterval(() => {
			start += increment;
			if (start >= target) {
				el.textContent = target;
				clearInterval(counter);
			} else {
				el.textContent = Math.floor(start);
			}
		}, 16);
	}

	$(document).on("click", ".deletemodal", function () {
		let empid = $(this).closest("tr").data("empid");
		let empfullname = $(this).closest("tr").data("empfullname");
		$("#removeEmployeebtn").data("employeeiddata", empid);
		$("#removeEmployeebtn").data("empfullname", empfullname);
		$(".empNamelbl").html(empfullname);
		$("#delete_modal").modal("show");
	});

	$(document).on("click", "#removeEmployeebtn", function () {
		const $btn = $(this);
		// ✅ Correct way to fetch data-attribute
		let rmempid = $btn.data("employeeiddata"); // data-employeeid="123"
		let rmremark = $("#empremoveremark").val().trim();
		let dor = $("#dordate").val();
		// 🔒 Validation
		if (!rmempid) {
			Swal.fire("Error", "Employee not selected", "error");
			return;
		}
		if (!rmremark) {
			Swal.fire("Warning", "Please enter resignation remark", "warning");
			return;
		}
		if (!dor) {
			Swal.fire("Warning", "Please select resignation date", "warning");
			return;
		}
		$btn.prop("disabled", true).text("Processing...");
		removEmployeeFromMaster(rmempid, rmremark, dor, "removeEmployeebtn", function (res) {
			$btn.prop("disabled", false).text("Remove Employee");
			if (typeof res === "string") {
				res = JSON.parse(res);
			}
			// 🔔 SweetAlert message
			if (res.status === "success") {
				Swal.fire({
					icon: "success",
					title: "Employee Removed",
					text: res.message || "Employee resigned successfully",
					confirmButtonText: "OK"
				}).then(() => {
					// Optional: reload / redirect
					location.reload();
				});

			} else {
				Swal.fire({
					icon: "error",
					title: "Failed",
					text: res.message || "Unable to remove employee"
				});
			}
		}
		);
	});
</script>