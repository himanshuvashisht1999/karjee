<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Test QR Print – TSC TTP-244 PRO</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        button { padding: 10px 24px; font-size: 16px; background: #1a7a4a; color: #fff; border: none; border-radius: 6px; cursor: pointer; }
        .qr-container { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 20px; }
        .qr-item { display: flex; align-items: center; gap: 8px; }
        /* Print stylesheet for TSC TTP‑244 PRO roll */
        @media print {
            @page { size: 77mm auto; margin: 0; }
            body { margin:0; padding:0; background:#fff; }
            button, h3 { display:none; }
        }
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
</head>
<body>
    <h3>Thermal Label Test – TSC TTP-244 PRO (77mm roll, 37×12mm, 2 cols)</h3>
    <button onclick="generateQRs()">🖨️ Generate QR Stickers</button>
    <button onclick="printStickers()">🖨️ Print Stickers (TSC)</button>
    <div id="qrContainer" class="qr-container"></div>

    <script>
        function generateQRs() {
            // Each entry now includes the QR code value and four additional parameters to display on the sticker
            const qrData = [
                { code: "10000660", p1: "KRF Farm", p2: "Batch: A", p3: "ROOT 128-Cell", p4: "LOT-001" },
                { code: "10000721", p1: "KRF Farm", p2: "Batch: B", p3: "ROOT 256-Cell", p4: "LOT-002" },
                { code: "10000892", p1: "KRF Farm", p2: "Batch: C", p3: "ROOT 512-Cell", p4: "LOT-003" },
                { code: "10001131", p1: "KRF Farm", p2: "Batch: D", p3: "ROOT 1024-Cell", p4: "LOT-004" },
                { code: "10001292", p1: "KRF Farm", p2: "Batch: E", p3: "ROOT 2048-Cell", p4: "LOT-005" },
                { code: "10001401", p1: "KRF Farm", p2: "Batch: F", p3: "ROOT 4096-Cell", p4: "LOT-006" }
            ];
            const container = document.getElementById('qrContainer');
            container.innerHTML = '';
            qrData.forEach((item, idx) => {
                const div = document.createElement('div');
                div.className = 'qr-item';
                const qrDiv = document.createElement('div');
                qrDiv.id = 'qr_' + idx;
                div.appendChild(qrDiv);
                // Create a small info block with the four parameters
                const info = document.createElement('div');
                info.style.fontSize = '10px';
                info.style.lineHeight = '1.2';
                info.innerHTML = `${item.p1}<br>${item.p2}<br>${item.p3}<br>${item.p4}`;
                div.appendChild(info);
                container.appendChild(div);
                new QRCode(qrDiv, { text: item.code, width: 100, height: 100, correctLevel: QRCode.CorrectLevel.M });
            });
        }
        // Open a print‑only window and trigger the browser's print dialog
        // If stickers are not generated yet, create them first
        function printStickers() {
            const container = document.getElementById('qrContainer');
            // Generate stickers if container is empty
            if (!container.innerHTML.trim()) {
                generateQRs();
            }
            const content = container.innerHTML;
            const win = window.open('', '_blank', 'width=800,height=600');
            win.document.write(`<!DOCTYPE html>
<html><head><title>Print Stickers</title>
<link rel="stylesheet" href="${location.href.split('#')[0]}">
<style>
    @page { size: 77mm auto; margin:0; }
    body { margin:0; padding:0; background:#fff; }
    .qr-item { display:flex; align-items:center; gap:8px; }
</style>
</head><body>
<div class="qr-container">${content}</div>
<script>
    setTimeout(()=>{ window.print(); }, 300);
<\/script>
</body></html>`);
            win.document.close();
        }
    </script>
</body>
</html>