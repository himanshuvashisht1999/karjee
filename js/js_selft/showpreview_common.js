function showPreview(filetarget, file_passed_as = "") {
    let file, fileType;

    if (file_passed_as === "blob" && filetarget.files && filetarget.files[0]) {
        file = filetarget.files[0];
        fileType = file.type.toLowerCase();
        if (!file || file.size === 0) {
            alert("The file is empty or not selected.");
            return false;
        }
    } else if (typeof filetarget === "string") {
        if (!filetarget) {
            return false;
        }
        fileType = filetarget.toLowerCase(); // assuming `filetarget` is a file path or URL in this case
    } else {
        alert("Invalid file target or type.");
        return false;
    }

    // Create modal structure if it doesn’t exist
    if (!document.getElementById("filePreviewModal")) {
        const modal = document.createElement("div");
        modal.id = "filePreviewModal";
        modal.className = "modal fade";
        modal.setAttribute("tabindex", "-1");
        modal.setAttribute("aria-labelledby", "filePreviewModalLabel");
        modal.setAttribute("aria-hidden", "true");

        modal.innerHTML = `
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="filePreviewModalLabel">File Preview</h5>
                    </div>
                    <div class="modal-body" id="modalPreviewContent">
                        <!-- Preview content will go here -->
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="button" id="deleteFileBtn" class="btn btn-danger" style="display: none;">Delete</button>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(modal); // Append modal to the body
    }
    // Show delete button only for blob files
    const deleteFileBtn = document.getElementById("deleteFileBtn");
    if (file_passed_as === "blob") {
        deleteFileBtn.style.display = "inline-block";
    } else {
        deleteFileBtn.style.display = "none";
    }

    // Get the modal content area
    const modalContent = document.getElementById("modalPreviewContent");
    modalContent.innerHTML = ""; // Clear any previous content
    const container = document.createElement("div");
    container.className = "preview-item";

    // Check file type and display accordingly
    if (file && fileType.startsWith("image/")) {
        const reader = new FileReader();
        reader.onload = function (e) {
            const img = document.createElement("img");
            img.src = e.target.result;
            img.style.maxWidth = "100%";
            container.appendChild(img);
        };
        reader.readAsDataURL(file); // Read file as Data URL for images
    } else if (file && fileType === "application/pdf") {
        const pdfURL = URL.createObjectURL(file);
        const iframe = document.createElement("iframe");
        iframe.src = pdfURL;
        iframe.style.width = "100%";
        iframe.style.height = "500px";
        container.appendChild(iframe);
        iframe.onload = () => URL.revokeObjectURL(pdfURL); // Revoke URL after load
    } else if (typeof filetarget === "string" && (fileType.endsWith(".jpg") || fileType.endsWith(".png") || fileType.endsWith(".gif"))) {
        // For direct URL/image path, assuming `filetarget` is a URL or path string
        const img = document.createElement("img");
        img.src = filetarget;
        img.style.maxWidth = "100%";
        container.appendChild(img);
    } else if (typeof filetarget === "string" && fileType.endsWith(".pdf")) {
        const iframe = document.createElement("iframe");
        iframe.src = filetarget;
        iframe.style.width = "100%";
        iframe.style.height = "500px";
        container.appendChild(iframe);
    } else {
        container.innerHTML = "<p>Unsupported file type.</p>";
    }

    modalContent.appendChild(container);

    // Show the modal
    const filePreviewModal = new bootstrap.Modal(document.getElementById("filePreviewModal"));
    filePreviewModal.show();

    deleteFileBtn.onclick = function() {
        modalContent.innerHTML = ""; // Clear modal preview content
        filetarget.value = ""; // Clear file input if it's a blob
        filePreviewModal.hide(); // Hide modal
    };
}
