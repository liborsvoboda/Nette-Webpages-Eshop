/** Copy to Clipboard */
// our hidden textarea, where we pass the text which we want to copy, to
var copyarea = document.createElement("textarea");
copyarea.style.cssText = 'position:absolute; left: -1000px; top: -1000px;';

function copyAffiliateLink() {
	var affiliate_link_data = document.getElementById("aff-link");
	document.body.appendChild(copyarea);
	copyarea.value = affiliate_link_data.value;
	copyarea.select();
	document.execCommand('copy');
	document.body.removeChild(copyarea);
};

$("#finishOrderBtn").click(function () {
	//.on("click", function () {
	$("#loading-overlay").removeClass("d-none");
	//$('<div class="loading-overlay align-items-center" id="loading-overlay"><div class= "spinner-border align-middle loading-overlay-spinner" role = "status" > <span class="sr-only">...</span></div></div>').append("body");
	
});
