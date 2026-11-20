<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Payment Request Form</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
  <h4 class="mb-3">Create Payment Request</h4>
  <form id="paymentRequestForm" enctype="multipart/form-data">

    <!-- Request Amount -->
    <div class="mb-3">
      <label for="requestAmount" class="form-label">Request Amount</label>
      <input type="number" class="form-control" id="requestAmount" name="requestAmount" placeholder="Enter requested amount" required>
    </div>

    <!-- Who is Payto -->
    <div class="mb-3">
      <label for="payTo" class="form-label">Who is Payto</label>
      <input type="text" class="form-control" id="payTo" name="payTo" placeholder="Enter payee name" required>
    </div>

    <!-- Who is Beneficiary -->
    <div class="mb-3">
      <label for="beneficiaryName" class="form-label">Who is Beneficiary Name</label>
      <input type="text" class="form-control" id="beneficiaryName" name="beneficiaryName" placeholder="Enter beneficiary name" required>
    </div>

    <!-- Purpose -->
    <div class="mb-3">
      <label for="purpose" class="form-label">Purpose of Requested Amount</label>
      <textarea class="form-control" id="purpose" name="purpose" rows="2" placeholder="Enter purpose" required></textarea>
    </div>

    <!-- Remark -->
    <div class="mb-3">
      <label for="remark" class="form-label">Remark</label>
      <textarea class="form-control" id="remark" name="remark" rows="2" placeholder="Enter remark"></textarea>
    </div>

    <!-- Do you have any document -->
    <div class="mb-3">
      <label class="form-label">Do you have any document?</label>
      <div>
        <div class="form-check form-check-inline">
          <input class="form-check-input" type="radio" name="hasDocument" id="docYes" value="yes">
          <label class="form-check-label" for="docYes">Yes</label>
        </div>
        <div class="form-check form-check-inline">
          <input class="form-check-input" type="radio" name="hasDocument" id="docNo" value="no" checked>
          <label class="form-check-label" for="docNo">No</label>
        </div>
      </div>
    </div>

    <!-- Document Upload -->
    <div class="mb-3 d-none" id="documentUploadDiv">
      <label for="documentFile" class="form-label">Choose Document</label>
      <input class="form-control" type="file" id="documentFile" name="documentFile">
    </div>

    <!-- Invoice Type -->
    <div class="mb-3">
      <label for="invoiceType" class="form-label">Invoice Type</label>
      <select class="form-select" id="invoiceType" name="invoiceType" required>
        <option value="">Select Invoice Type</option>
        <option value="purchase">Purchase</option>
        <option value="sales">Sales</option>
        <option value="service">Service</option>
        <option value="other">Other</option>
      </select>
    </div>

    <!-- Invoice No -->
    <div class="mb-3">
      <label for="invoiceNo" class="form-label">Invoice No</label>
      <input type="text" class="form-control" id="invoiceNo" name="invoiceNo" placeholder="Enter invoice number" required>
    </div>

    <!-- Invoice Date -->
    <div class="mb-3">
      <label for="invoiceDate" class="form-label">Invoice Date</label>
      <input type="date" class="form-control" id="invoiceDate" name="invoiceDate" required>
    </div>

    <!-- Taxable Amount -->
    <div class="mb-3">
      <label for="taxableAmount" class="form-label">Taxable Amount</label>
      <input type="number" class="form-control" id="taxableAmount" name="taxableAmount" placeholder="Enter taxable amount" required>
    </div>

    <!-- Payment Due Date -->
    <div class="mb-3">
      <label for="dueDate" class="form-label">Payment Due Date</label>
      <input type="date" class="form-control" id="dueDate" name="dueDate" required>
    </div>

    <!-- Submit -->
    <button type="submit" class="btn btn-primary">Submit Request</button>
  </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Toggle file upload visibility
  document.querySelectorAll('input[name="hasDocument"]').forEach((el) => {
    el.addEventListener('change', function() {
      document.getElementById('documentUploadDiv').classList.toggle('d-none', this.value !== 'yes');
    });
  });
</script>
</body>
</html>
