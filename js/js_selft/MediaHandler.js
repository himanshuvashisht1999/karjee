class MediaHandler {
    constructor(config = {}) {
        this.videoElement = $(config.videoElement || "#video")[0];
        this.canvasElement = $(config.canvasElement || "#canvas")[0];
        this.capturedImageElement = $(config.capturedImageElement || "#capturedImage")[0];
        this.fileInputElement = $(config.fileInputElement || "#filenew");
        this.previewContainer = $(config.previewContainer || "#filepreview_maindiv");
        this.buttonContainer = $(config.buttonContainer || ".previewbtndiv");
        this.captureCountLabel = $(config.captureCountLabel || "#lblcapturecount");
        this.uploadCountLabel = $(config.uploadCountLabel || "#lbluploadcount");

        // Custom event IDs for buttons (optional)
        this.captureButton = config.captureButton || "#capture";
        this.nextCaptureButton = config.nextCaptureButton || "#btn_next";
        this.prevCaptureButton = config.prevCaptureButton || "#btn_prev";
        this.deleteCaptureButton = config.deleteCaptureButton || "#btn_delete";
        this.startCameraButton = config.startCameraButton || "#showcameradiv";
        this.stopCameraButton = config.stopCameraButton || "#viewuploadeddiv";
        this.nextPreviewButton = config.nextPreviewButton || "#preview_next";
        this.prevPreviewButton = config.prevPreviewButton || "#preview_prev";
        this.deletePreviewButton = config.deletePreviewButton || "#preview_delete";

        this.cameradiv = config.cameradiv || "#activecamera";
        this.priviewdiv = config.priviewdiv || "#priviewview";

        this.videoStream = null;
        this.currentIndex = -1;
        this.previewIndex = 0;
        this.packageImages = [];
        this.fileInput = [];

        this.initEventListeners();
    }
    initEventListeners() {
        $(document).on("click", this.captureButton, () => this.captureImage());
        $(document).on("click", this.nextCaptureButton, () => this.nextCapturedImage());
        $(document).on("click", this.prevCaptureButton, () => this.prevCapturedImage());
        $(document).on("click", this.deleteCaptureButton, () => this.deleteCapturedImage());
        $(document).on("click", this.startCameraButton, () => this.startCamera());
        $(document).on("click", this.stopCameraButton, () => this.stopCamera());
        this.fileInputElement.on("change", (event) => this.handleFileUpload(event));
        $(document).on("click", this.nextPreviewButton, () => this.nextPreviewImage());
        $(document).on("click", this.prevPreviewButton, () => this.prevPreviewImage());
        $(document).on("click", this.deletePreviewButton, () => this.deletePreviewImage());
    }
    captureImage() {
        if (!this.videoStream) return;
        const context = this.canvasElement.getContext("2d");
        this.canvasElement.width = this.videoElement.videoWidth;
        this.canvasElement.height = this.videoElement.videoHeight;
        context.drawImage(this.videoElement, 0, 0, this.canvasElement.width, this.canvasElement.height);
        this.packageImages.push(this.canvasElement.toDataURL("image/png"));
        this.currentIndex = this.packageImages.length - 1;
        this.updateCapturedImage();
    }
    nextCapturedImage() {
        if (this.currentIndex < this.packageImages.length - 1) {
            this.currentIndex++;
            this.updateCapturedImage();
        }
    }
    prevCapturedImage() {
        if (this.currentIndex > 0) {
            this.currentIndex--;
            this.updateCapturedImage();
        }
    }
    deleteCapturedImage() {
        if (this.packageImages.length > 0) {
            this.packageImages.splice(this.currentIndex, 1);
            this.currentIndex = Math.max(0, this.packageImages.length - 1);
            this.updateCapturedImage();
        }
    }
    updateCapturedImage() {
        if (this.packageImages.length > 0) {
            this.capturedImageElement.src = this.packageImages[this.currentIndex];
            this.captureCountLabel.text(`Image No: ${this.currentIndex + 1} of ${this.packageImages.length}`);
        } else {
            this.capturedImageElement.src = "";
            this.captureCountLabel.text("No images available");
        }
    }
    startCamera() { 
        $(this.cameradiv).show();
        $(this.priviewdiv).hide();

        if (!this.videoStream) {
            navigator.mediaDevices.getUserMedia({ video: true })
                .then((stream) => {
                    this.videoStream = stream;
                    this.videoElement.srcObject = stream;
                })
                .catch((error) => {
                    alert("Camera access denied or not available.");
                    console.error("Error accessing webcam:", error);
                });
        }
    }
    stopCamera() {
        $(this.cameradiv).hide();
        $(this.priviewdiv).show();
        this.buttonContainer.hide();
        if (this.fileInput.length > 0) {
            this.buttonContainer.show();
        }

        if (this.videoStream) {
            this.videoStream.getTracks().forEach(track => track.stop());
            this.videoStream = null;
        }
    }
    handleFileUpload(event) {
        $(this.cameradiv).hide();
        $(this.priviewdiv).show();
        let files = Array.from(event.target.files);
        if (files.length > 0) {
            this.fileInput.push(...files);
            this.previewIndex = this.fileInput.length - files.length;
            this.updatePreview(); 
            $(this.priviewdiv).show();
        }
    }
    nextPreviewImage() {
        if (this.previewIndex < this.fileInput.length - 1) {
            this.previewIndex++;
            this.updatePreview();
        }
    }
    prevPreviewImage() {
        if (this.previewIndex > 0) {
            this.previewIndex--;
            this.updatePreview();
        }
    }
    deletePreviewImage() {
        if (this.fileInput.length > 0) {
            this.fileInput.splice(this.previewIndex, 1);
            this.previewIndex = Math.max(0, this.previewIndex - 1);
            this.updatePreview();
        }
    }
    updatePreview() {
        this.uploadCountLabel.text("");
        this.buttonContainer.hide();
        // $("#priviewview").hide();
        $(this.priviewdiv).hide();
        this.previewContainer.empty();

        if (this.fileInput.length === 0) return;

        this.uploadCountLabel.text(`Image No: ${this.previewIndex + 1} of ${this.fileInput.length}`);
        // $("#priviewview").show();
        $(this.priviewdiv).show();
        this.buttonContainer.show();

        let file = this.fileInput[this.previewIndex];
        let fileType = file.type.toLowerCase();
        let reader = new FileReader();

        reader.onload = (e) => {
            if (fileType.includes("image")) {
                this.previewContainer.append(`<img src="${e.target.result}" style="max-width: 100%; margin-bottom: 10px;" />`);
            } else if (fileType === "application/pdf") {
                this.previewContainer.append(`<embed src="${URL.createObjectURL(file)}" type="application/pdf" width="100%" style="height: 65vh; margin-bottom: 10px;" />`);
            } else {
                this.previewContainer.html("<p>Unsupported file type.</p>");
            }
        };
        reader.readAsDataURL(file);
    }
}
