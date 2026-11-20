function loadCssFile(url) {
	const link = document.createElement('link');
	link.rel = 'stylesheet';
	link.href = url;
	document.head.appendChild(link);
}

loadCssFile('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css');
document.addEventListener("DOMContentLoaded", function () {
	createvoicerecordercontrol();
});

function createvoicerecordercontrol() {
	var vrd = document.getElementsByClassName("voicerecorder");

	for (var i = 0; i < vrd.length; i++) {
		var pid = vrd[i].id;
		if (!vrd[i].classList.contains("p-2")) {
			vrd[i].classList.add("p-2");
		}

		var stb = document.createElement("button");
		stb.id = pid + "_" + "startRecord";
		stb.classList.add("start");
		stb.classList.add("recording_btn");
		stb.style.border = "0";
		stb.style.backgroundColor = "transparent";//"#28a745"
		stb.style.margin = ".5rem !important";
		var ilist = document.createElement("i");
		ilist.classList.add("fas");
		ilist.classList.add("fa-microphone");
		ilist.style.fontSize = "36px";
		ilist.style.color = "green";
		stb.appendChild(ilist);
		vrd[i].appendChild(stb);
		var spb = document.createElement("button");
		spb.id = pid + "_" + "stopRecord";
		spb.classList.add("stop");
		spb.classList.add("recording_btn");
		spb.style.border = "0";
		spb.style.backgroundColor = "transparent"; //"#dc3545"
		spb.style.margin = ".5rem !important";
		spb.style.display = "none";
		var ilists = document.createElement("i");
		ilists.classList.add("fa");
		ilists.classList.add("fa-microphone-slash");
		ilists.style.fontSize = "36px";
		ilists.style.color = "red";
		spb.appendChild(ilists);
		vrd[i].appendChild(spb);
		var tmd = document.createElement("div");
		tmd.id = pid + "_" + "recordingTime";
		tmd.style.display = "inline-block";
		tmd.style.margin = "0";
		vrd[i].appendChild(tmd);
		var tms = document.createElement("span");
		tms.id = pid + "_" + "timeElapsed";
		tms.innerHTML = "0";
		tms.style.display = "none";
		tmd.appendChild(tms);
		var actrl = document.createElement("audio");
		actrl.id = pid + "_" + "audioPlayback";
		actrl.classList.add("form-control");
		actrl.classList.add("audioPlayback");
		actrl.setAttribute("controlsList", "nodownload");
		actrl.setAttribute("controls", true);
		vrd[i].appendChild(actrl);

		var rbt = document.getElementsByClassName("recording_btn");
		for (var j = 0; j < rbt.length; j++) {
			rbt[j].addEventListener("click", function (event) {
				startstopRec(event.target);
			});
		}
	}
}

var _mediaRecorder;
var _audioChunks = [];
var _recordingInterval;
var _startTime;
var seconds = 0;
async function startstopRec(elem) {
	var pid = elem.closest("div").id;
	var btnid = elem.closest("button").id;
	if (btnid == pid + "_" + "startRecord") {
		seconds = 0;
		$('audio').each(function () {
			this.pause();
			this.currentTime = 0;
		});
		$("#" + pid + "_" + "timeElapsed").show();
		$("#" + pid + "_" + "audioPlayback").removeAttr('src');

		if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
			try {
				const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
				_mediaRecorder = new MediaRecorder(stream);

				_mediaRecorder.ondataavailable = function (event) {
					if (event.data.size > 0) {
						_audioChunks.push(event.data);
					}
				};

				_mediaRecorder.onstart = function () {
					$("#" + pid).addClass("activerecorder");
					$('.voicerecorder:not(.activerecorder)').find('*').prop("disabled", true);
					$('.voicerecorder:not(.activerecorder)').find('audio').removeAttr('controls');
					$("#" + pid + "_" + "timeElapsed").show();
					_startTime = Date.now();
					$("#" + pid + "_" + "timeElapsed").text('0');
					if (!_recordingInterval) {
						_recordingInterval = setInterval(function () {
							updateRecordingTime(pid);
						}, 1000);
					}
				};
				_mediaRecorder.onstop = async function () {
					clearInterval(_recordingInterval);
					_recordingInterval = null;
					$("#" + pid + "_" + "timeElapsed").hide();
					const audioBlob = new Blob(_audioChunks, { type: 'audio/wav' });
					const timestamp = new Date().toISOString().replace(/[-:.]/g, '');
					const filename = `recording_${timestamp}.wav`;
					const audioFile = new File([audioBlob], filename, { type: 'audio/wav' });
					const audioURL = URL.createObjectURL(audioBlob);
					$("#" + pid + "_" + "audioPlayback").attr('src', "");
					$("#" + pid + "_" + "audioPlayback").attr('src', audioURL);
					_audioChunks = [];
					$("#" + pid + "_" + "audioPlayback").data("audioFile", audioFile);
					$("#" + pid).addClass("recording");
					$('.voicerecorder').find('*').prop("disabled", false);

					$('.voicerecorder.activerecorder').find('audio').prop('controls', true);
					$('.voicerecorder.recording').find('audio').prop('controls', true);
					$("#" + pid).removeClass("activerecorder");
				};

				_mediaRecorder.start();
				$("#" + pid + "_" + "startRecord").hide();
				$("#" + pid + "_" + "stopRecord").show();
				/*$("#"+pid+"_"+"startRecord").removeClass("btn-success");
				$("#"+pid+"_"+"startRecord").prop('disabled', true);
				$("#"+pid+"_"+"stopRecord").prop('disabled', false);
				$("#"+pid+"_"+"stopRecord").addClass("btn-danger");*/
			} catch (error) {
				console.error('Error accessing microphone: ', error);
			}
		} else {
			console.error('getUserMedia not supported on your browser!');
		}
	} else if (btnid == pid + "_" + "stopRecord") {
		//$(".savevoicebtn").prop("disabled", false);
		if (_mediaRecorder && _mediaRecorder.state !== 'inactive') {
			_mediaRecorder.stop();
			$("#" + pid + "_" + "startRecord").show();
			$("#" + pid + "_" + "stopRecord").hide();
		}
	}
}
function updateRecordingTime(pid) {
	$("#" + pid + "_" + "timeElapsed").text(seconds + " s");
	seconds++;
}
