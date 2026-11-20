/*function PrintHtml(reportsdiv)
{
	var divContents = $('.'+reportsdiv +'').html();
	var a = window.open('', '', 'height=1000, width=700'); 
  	a.document.write('<html>'); 
   	a.document.write('<body>'); 
  	a.document.write(divContents); 
   	a.document.write('</body></html>'); 
   	a.document.close(); 
   	a.print();
}*/
function PrintHtml(reportsdiv) {
    var divContents = document.querySelector('.' + reportsdiv+ ' table').outerHTML;
    var iframe = document.createElement('iframe');
    iframe.style.display = 'none';
    document.body.appendChild(iframe);
    var doc = iframe.contentWindow.document;
    doc.open();
    doc.write('<html><head><title>Print</title>');
    doc.write('</head><body>');
    doc.write(divContents);
    doc.write('</body></html>');
    doc.close();
    
    setTimeout(function () {
        iframe.contentWindow.focus();
        iframe.contentWindow.print();
        setTimeout(function () {
            document.body.removeChild(iframe);
        }, 1000);
    }, 500); 
}

