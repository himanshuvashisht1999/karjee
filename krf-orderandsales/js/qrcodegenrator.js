function BoxLayoutForQRcode(viewQrCodeDivid, qrCodearray, columnsInRow) {
    const $container = $("#" + viewQrCodeDivid);
    $container.empty();
    if (!Array.isArray(qrCodearray) || qrCodearray.length === 0) return;
    columnsInRow = parseInt(columnsInRow, 10);
    if (isNaN(columnsInRow) || columnsInRow < 1) {
        columnsInRow = 1;
    }

    // Exact width per column (no overflow)
    const labelWidth = 100 / columnsInRow;
    let j = 1;
    qrCodearray.forEach(item => {
        const qrDiv = `
            <div class="barcode-box"
                style="
                    width:${labelWidth}%;
                    display:inline-block;
                    vertical-align:top;
                    padding:6px;
                    box-sizing:border-box;
                ">
                <div style="
                    display:flex;
                    width:100%;
                    align-items:center;
                ">
                    <!-- LEFT : QR CODE -->
                    <div style="
                        width:30%;
                        display:flex;
                        justify-content:center;
                        align-items:center;
                    ">
                        <div id="layoutqr_${j}" style="width:100%; text-align:center;"></div>
                    </div>
                    <!-- RIGHT : TEXT -->
                    <div style="
                        width:70%;
                        padding-left:10px;
                        text-align:left;
                        word-break:break-word;
                    ">
                        <div><label id="gby_${j}" style="font-size:0.9em;font-weight:bold;"></label></div>
                        <div><label id="prdname_${j}" style="font-size:0.75em;"></label></div>
                        <div><label id="locname_${j}" style="font-size:0.7em;"></label></div>
                        <div><label id="aldate_${j}" style="font-size:0.75em;"></label></div>
                    </div>

                </div>
            </div>
        `;
        $container.append(qrDiv);
        j++;
    });
}

function printQRCode(qrCodearray, starting = 1, variable1, variable2, variable3, variable4) {
    if (!Array.isArray(qrCodearray) || qrCodearray.length === 0) return;
    let s = parseInt(starting, 10);
    if (isNaN(s) || s < 1) s = 1;
    // Small delay to ensure layout elements exist
    setTimeout(() => {
        qrCodearray.forEach(item => {
            if (!item || !item.qrcode) {
                s++;
                return;
            }
            const cid = `layoutqr_${s}`;
            // Ensure target QR container exists
            if (!document.getElementById(cid)) {
                s++;
                return;
            }
            // Generate QR
            generatedQRCodePreview(item.qrcode, cid);
            // Set text fields safely
            $(`#gby_${s}`).text(variable1 || "");
            $(`#prdname_${s}`).text(variable2 || "");
            $(`#locname_${s}`).text(variable3 || "");
            $(`#aldate_${s}`).text(variable4 || "");
            s++;
        });

    }, 100);
}


function generatedQRCodePreview(code, elementId) {
    const container = document.getElementById(elementId);
    if (!container) return;

    // Clear old QR if already exists
    container.innerHTML = "";
    const parent = container.closest('.barcode-box');
    const size = 75; // Set size as per tray qr code
    new QRCode(container, {
        text: code,
        width: size,
        height: size,
        colorDark: "#000000",
        colorLight: "#ffffff",
        correctLevel: QRCode.CorrectLevel.H
    });
}



async function generateQRCodePDF({
    sourceDivId = "ViewBarcodeDiv",
    buttonId = null,
    fileName = "barcodes.pdf",
    orientation = "p",
    pageSize = "a4",
    scale = 2
} = {}) {
    try {
        const element = document.getElementById(sourceDivId);
        if (!element) throw new Error(`${sourceDivId} not found`);

        // Render HTML → Canvas
        const canvas = await html2canvas(element, {
            scale: scale,
            useCORS: true,
            logging: false
        });

        const { jsPDF } = window.jspdf;
        const pdf = new jsPDF(orientation, "pt", pageSize);

        const pageWidthPt = pdf.internal.pageSize.getWidth();
        const pageHeightPt = pdf.internal.pageSize.getHeight();

        const canvasWidthPx = canvas.width;
        const canvasHeightPx = canvas.height;

        const pxPerPt = canvasWidthPx / pageWidthPt;

        let pageSliceHeightPx = Math.floor(pageHeightPt * pxPerPt);
        if (pageSliceHeightPx <= 0) pageSliceHeightPx = canvasHeightPx;

        const pageCount = Math.ceil(canvasHeightPx / pageSliceHeightPx);

        for (let i = 0; i < pageCount; i++) {

            const sy = i * pageSliceHeightPx;
            const sh = Math.min(pageSliceHeightPx, canvasHeightPx - sy);
            if (sh <= 0) continue;

            const pageCanvas = document.createElement("canvas");
            pageCanvas.width = canvasWidthPx;
            pageCanvas.height = sh;

            const ctx = pageCanvas.getContext("2d");
            ctx.drawImage(
                canvas,
                0, sy, canvasWidthPx, sh,
                0, 0, canvasWidthPx, sh
            );

            const imgData = pageCanvas.toDataURL("image/jpeg", 0.95);

            if (i > 0) pdf.addPage();

            const imgHeightPt = sh / pxPerPt;
            pdf.addImage(imgData, "JPEG", 0, 0, pageWidthPt, imgHeightPt);
        }

        // Save PDF
        pdf.save(fileName);

        // Optional: open in new tab
        // const blob = pdf.output("blob");
        // const fileURL = URL.createObjectURL(blob);
        // window.open(fileURL);

    } catch (err) {
        console.error("PDF generation failed:", err);
        Swal?.fire({
            icon: "error",
            title: "PDF Generation Failed",
            text: "Unable to generate PDF. Please try again."
        }) || alert("Failed to generate PDF. See console for details.");
    }
}