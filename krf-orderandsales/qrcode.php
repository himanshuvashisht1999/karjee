<button onclick="printLabels()">Print Labels</button>

<script>
    function printLabels() {

        // ?? Your label data (dynamic)
        let labels = [
            {
                qr: "KRF-BOX-001",
                title: "KRF-BOX-20",
                grade: "Grafted Brinjal",
                pkd: "12/2026",
                op: "01/2028"
            },
            {
                qr: "KRF-BOX-002",
                title: "KRF-BOX-20",
                grade: "Grafted Brinjal",
                pkd: "12/2026",
                op: "01/2028"
            }
        ];

        fetch("qrcode_print_label.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({ labels: labels })
        })
            .then(res => res.json())
            .then(data => {
                alert(data.message);
            })
            .catch(err => {
                console.error(err);
            });
    }
</script>