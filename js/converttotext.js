function gettext(file,outputelem){
	if(isFileObject(file)){
		const {name} = file;
		var ext = name.toLowerCase().substring(name.lastIndexOf('.') + 1);
	}
	else{
		var ext = file.toLowerCase().substring(filename.lastIndexOf('.') + 1);
	}
	if(ext=="doc"||ext=="docx"||ext=="xls"||ext=="xlsx"||ext=="pdf"){
		gettextdump(file,ext,outputelem);
	}
	else if (ext=="png"||ext=="jpg"||ext=="jpeg"){
		getOCRdump(filename,outputelem);
	}
	else if (ext=="odt"){
		getODTdump(filename,outputelem);
	}	
}
function gettextdump(file,ext,outputelem){
	const docToText = new DocToText();
	docToText.extractToText(file, ext)
	.then(function (text) {
		console.log(text);
	 document.getElementById(outputelem).innerHTML = convertNewlineToBr(text);
	})
}
function isFileObject(input) {
    return input instanceof File || input instanceof Blob;
}
function convertNewlineToBr(text) {
    return text.replace(/\n/g, '<br>');
}

function getOCRdump(file,outputelem){
	var exttext = "";
	Tesseract.recognize(
	file,
	'eng' 
).then(({ data: { text } }) => {
  document.getElementById(outputelem).innerHTML = text;	
})
}
async function getODTdump(url,outputelem){
	const fileRes = await fetch(url);
	const blob = await fileRes.blob();
	let fileName = url.split("/").pop();
	const file = new File([blob], fileName, {
    type: blob.type,
	});
	const reader = new FileReader();

	reader.onload = function (event) {
		extractTextFromODT(event.target.result).then((text) => {
			document.getElementById(outputelem).innerHTML = text;
		});
	};

	reader.readAsArrayBuffer(file);
}

function extractTextFromODT(arrayBuffer) {
	return new Promise((resolve, reject) => {
		const zip = new JSZip();
		const textPromise = zip.loadAsync(arrayBuffer)
			.then((zip) => {
				const contentXml = zip.file("content.xml");
				if (!contentXml) {
					reject("content.xml not found in the ODT file.");
					return;
				}

				return contentXml.async("text");
			});
			textPromise.then((text) => {
			const parser = new DOMParser();
			const xmlDoc = parser.parseFromString(text, "text/xml");
			const paragraphs = xmlDoc.getElementsByTagName("text:p");
			let extractedText = "";

			for (let i = 0; i < paragraphs.length; i++) {
				extractedText += paragraphs[i].textContent + "\n";
			}

			resolve(extractedText);
		}).catch((error) => {
			reject(error);
		});
	});
}