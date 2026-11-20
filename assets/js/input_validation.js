
//alphanumeric_onlys
function alphanumeric_onlys(event)
 {
 var keycode;
 keycode=event.keyCode?event.keyCode:event.which;
 if ((keycode == 8) || (keycode >47 && keycode < 58) || (keycode == 13) || (keycode == 61) || (keycode >= 32 && keycode <= 36) || (keycode >= 39 && keycode <= 41) || (keycode >= 44 && keycode <= 47) || (keycode >= 65 && keycode <= 90) || (keycode >= 97 && keycode <= 122)) 
 	{
        return true; 
	}
 else 
 	{
	    return false;
	}

}

//only isFloatNumber
function isFloatNumber(event, val) {
	event = (event) ? event : window.event;
   	var charCode = (event.which) ? event.which : event.keyCode;
	if ((charCode >= 48 && charCode < 58) || (charCode == 46)) 
	{
  		return true;
	}
 	else
	{
   		return false;
	}
}

//only isNumber
function isNumber(event) {
	event = (event) ? event : window.event;
   	var charCode = (event.which) ? event.which : event.keyCode;
	if (charCode > 31 && (charCode < 48 || charCode > 57)) 
	{
  		return false;
	}
 	else
	{
   		return true;
	}
}

//Only one dot allowed and after dot allowed only 2 digits
function CheckFloatAmount(el, evt) {
    var charCode = (evt.which) ? evt.which : event.keyCode;
    var number = el.value.split('.');
    if (charCode != 46 && charCode > 31 && (charCode < 48 || charCode > 57)) {
        return false;
    }
    //just one dot
    if(number.length>1 && charCode == 46){
         return false;
    }
    //get the carat position
    var caratPos = getSelectionStart(el);
    var dotPos = el.value.indexOf(".");
    if( caratPos > dotPos && dotPos>-1 && (number[1].length > 1)){
        return false;
    }
    return true;
}

function getSelectionStart(o) {
	if (o.createTextRange) {
		var r = document.selection.createRange().duplicate()
		r.moveEnd('character', o.value.length)
		if (r.text == '') return o.value.length
		return o.value.lastIndexOf(r.text)
	} else return o.selectionStart
}


//Alphabets Only
function onlyAlphabets(e) {
try {
	 if (window.event) {
		var charCode = window.event.keyCode;
 	}
	 else if (e) {
		 var charCode = e.which;
	 }
	 else { return true; }
	if ((charCode == 32)||(charCode > 64 && charCode < 91) || (charCode > 96 && charCode < 123) )
	 	return true;
	else
		return false;
	}
	catch (err) {
 	alert(err.Description);
 	}
}


//Address only
function AddressOnlys(evt) {
	evt = (evt) ? evt : window.event;
   	var charCode = (evt.which) ? evt.which : evt.keyCode;

	if ((charCode == 8)  || (charCode == 110) || (charCode >=40 && charCode < 58)|| (charCode >= 65 && charCode <= 90) || (charCode == 13) || (charCode == 32) || (charCode >= 97 && charCode <= 125)) 
 	{
        return true; 
	}
 	else 
 	{
	    return false;
	}
}



/*
//Edit the message as your wish
var msg_box ="Right click disabled in this page"; function dis_rightclickIE()
{
	if (navigator.appName == 'Microsoft Internet Explorer' && (event.button == 2 || event.button == 3))
	alert(msg_box);
}
function dis_rightclickNS(e)
{
	if ((document.layers||document.getElementById&&!document.all) && (e.which==2||e.which==3))
	{
		alert(msg_box);
		return false;
	}
}
if (document.layers)
{
	document.captureEvents(Event.MOUSEDOWN);
	document.onmousedown=dis_rightclickNS;
}
else if (document.all&&!document.getElementById)
{
	document.onmousedown=dis_rightclickIE;
}
document.oncontextmenu=new Function("alert(msg_box);return false")
*/


//document.addEventListener('contextmenu', event=>event.preventDefault());