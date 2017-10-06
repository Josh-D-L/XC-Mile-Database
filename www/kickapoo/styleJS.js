function myOnload() {
	var home = Math.round(document.getElementById('home').height);
	document.getElementById('bigContainer').style.top = home + 4;
	if (window.matchMedia("screen and (min-width: 1367px)").matches) {
		document.getElementById('leftAd').style.top = home + 4;
		document.getElementById('rightAd').style.top = home + 4;
	}
	if (window.matchMedia("screen and (min-width: 641px) and (max-width: 1366px)").matches) {
		document.getElementById('topBar').style.width = window.innerWidth - 320 + "px";
	}
	if (window.matchMedia("screen and (min-width: 641px)").matches) {
		document.getElementById('bigContainer').style.width = window.innerWidth - 320 + "px";
	}
	if (window.matchMedia("screen and (max-width: 640px)").matches) {
		document.getElementById('bigContainer').style.height = window.innerHeight - home - 2;
	}
}
window.onload = myOnload();