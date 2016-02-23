var com = {"qetrix": {"apps": {"common": {"converters": {}, "datastores": {}, "modules": {}}}, "libs": {"components": {}}}}; // Namespaces com.qetrix.*


/// for(;;);  - add to beginning of JSON response, prevents JSON hijacking
function xhr(j, a, n)
{
	var w = window, r = w.XMLHttpRequest ? new XMLHttpRequest() : (w.ActiveXObject ? new ActiveXObject("Microsoft.XMLHTTP") : 0);
	if (r) {
		r.onreadystatechange = function () {
			r.readyState == 4 ? (n || a)(r.responseText, r.responseXML) : 0
		};
		r.open(n ? "POST" : "GET", j + (j.indexOf("?") > 0 ? "&" : "?") + "xhr=" + new Date().getTime(), !0);
		if (n) {
			r.setRequestHeader("Content-type", "application/x-www-form-urlencoded; charset=utf-8");
			if (r.overrideMimeType)r.setRequestHeader("Connection", "close");
		}
		r.send(a)
	}
}


var modals = []; // Dialog boxes for Esc closing

function showModal(contents, width, height)
{
	console.log(this.app);
	var elm = document.getElementById("mwc");
	if (elm == null) {
		var mw = document.createElement("div");
		mw.id = "mw";
		var mwc = document.createElement("div");
		mwc.id = "mwc";
		mw.appendChild(mwc)
		document.body.appendChild(mw);
		elm = document.getElementById("mwc");
		initModWin();
	}

	elm.className = "";
	if (typeof contents == "object") {
		if (contents.href != null) {
			var label = contents.innerHTML.substr(contents.innerHTML.indexOf("<br>") + 4);
			elm.innerHTML = "<img src=\"" + contents.href + "\" alt=\"\"><div class=\"imglbl\">" + label + "</div>";
			elm.className = "img";
		}
	} else if (contents.indexOf("//") > -1 && contents.indexOf("\n") == -1) {
		this.app().envDS().getData(contents, function (text) {
			showModal(text, width, height);
		});
		return true;
	} else elm.innerHTML = contents;

	if (typeof width == "undefined") width = elm.offsetWidth;
	if (typeof height == "undefined") height = elm.offsetHeight;

	if (width > window.innerWidth - 10) width = window.innerWidth - 10;
	if (height > window.innerHeight - 10) height = window.innerHeight - 10;

	// TODO: Adjust larger pics
	elm.style.width = width + "px";
	elm.style.height = height + "px";

	elm.style.marginTop = ((window.innerHeight / 2) - (height / 2)) + "px";

	document.getElementById("mw").className = "vis";
	modals.push("mw");
	return true;
}

function hideModal()
{
	if (modals.length > 0) {
		var el = document.getElementById(modals.pop());
		console.log(modals);
		el.className = "";
	}
	return true;
}

function showAutoComplete(elm, evt, data, datakey)
{
	if (evt.keyCode == 9 || evt.keyCode == 27) return hideAutoComplete();
	elm.onblur = function () {
		setTimeout(function () {
			hideAutoComplete();
		}, 150);
	};

	var el = document.getElementById("ac");
	el.style.display = "block";
	var pos = Util.getElementPosition(elm);
	el.style.top = (pos.top + elm.offsetHeight) + "px";
	el.style.left = pos.left + "px";
	el.style.width = elm.offsetWidth + "px";
	el.innerHTML = "";

	if (data.substr(0, 1) == "[" || data.substr(0, 1) == "{") { // JSON
		var json = JSON.parse(data)[datakey];
		var ul = document.createElement("ul");
		ul.onclick = function (ee) {
			elm.value = ee.target.getAttribute("data-text");
			document.getElementById(elm.id.substr(0, elm.id.length - 5)).value = ee.target.getAttribute("data-value");
			return false;
		};
		for (var i in json) {
			var li = document.createElement("li");
			li.innerHTML = "<a href=\"#\" data-value=\"" + json[i].value + "\" data-text=\"" + json[i].text + "\">" + json[i].text + "</a>";
			ul.appendChild(li);
		}
		el.appendChild(ul);
	} else { // HTML

	}
	return true;
}

function hideAutoComplete()
{
	var el = document.getElementById("ac");
	el.style.display = "none";
	return true;
}

function preventDefault(evt)
{
	evt = evt || window.event;
	if (evt.preventDefault) evt.preventDefault();
	evt.returnValue = false;
}

/** Disable scrolling
 * @return {boolean}
 */
function disableMouseWheelScroll(evt)
{
	preventDefault(evt);
	return false;
}

function initModWin()
{
	var modwin = document.getElementById("mw");
	if (modwin != null) {
		modwin.addEventListener("click", hideModal, false);
		if (modwin.addEventListener) {
			modwin.addEventListener("mousewheel", disableMouseWheelScroll, false); // IE9, Chrome, Safari, Opera
			modwin.addEventListener("DOMMouseScroll", disableMouseWheelScroll, false); // Firefox
		} else modwin.attachEvent("onmousewheel", disableMouseWheelScroll); // IE 6/7/8

		window.addEventListener("keydown", function (evt) {
			switch (evt.keyCode) {
				case 27: // Esc
					hideModal();
					break;
			}
		}, false);

		window.addEventListener("resize", function (evt) {
			if (modals.length > 0 && modals.indexOf("mw") > -1) {
				var el = document.getElementById("mwc");
				hideModal();
				showModal(el.innerHTML, el.offsetWidth, el.offsetHeight)
			}
		}, false);
	}
}

initModWin();

/*
 var menu = document.querySelector('.menu')
 var menuPosition = menu.getBoundingClientRect().top;
 window.addEventListener('scroll', function() {
 if (window.pageYOffset >= menuPosition) {
 menu.style.position = 'fixed';
 menu.style.top = '0px';
 } else {
 menu.style.position = 'static';
 menu.style.top = '';
 }
 });
 */

/*
 var menu = document.querySelector('.menu');
 var menuPosition = menu.getBoundingClientRect();
 var placeholder = document.createElement('div');
 placeholder.style.width = menuPosition.width + 'px';
 placeholder.style.height = menuPosition.height + 'px';
 var isAdded = false;

 window.addEventListener('scroll', function() {
 if (window.pageYOffset >= menuPosition.top && !isAdded) {
 menu.classList.add('sticky');
 menu.parentNode.insertBefore(placeholder, menu);
 isAdded = true;
 } else if (window.pageYOffset < menuPosition.top && isAdded) {
 menu.classList.remove('sticky');
 menu.parentNode.removeChild(placeholder);
 isAdded = false;
 }
 });



 .sticky {
 top: 0;
 position: fixed;
 }

 ////////// FADE:
 var s = document.getElementById('thing').style; s.opacity = 1; (function fade(){(s.opacity-=.1)<0?s.display="none":setTimeout(fade,40)})();

 ////////// XHR POST:
 var r = new XMLHttpRequest();r.open("POST","path/to/api",true);r.onreadystatechange=function(){if(r.readyState!=4||r.status!=200)return;alert("Success:"+r.responseText);};r.send("banana=yellow");


 */

com.qetrix.libs.Util = new (function () {
	var self = {};

	self.log = function (v) {
		console.log(v);
	};

	self.urlEncode = function (str) {
		return encodeURIComponent(str);
	};

	self.urlDecode = function (str) {
		return decodeURIComponent(str);
	};

	self.e = function (id) {
		return document.getElementById(id);
	};

	self.content = function (id, data)
	{
		if (document.getElementById(id).outerHTML) {
			document.getElementById(id).outerHTML = data; // TODO: outerHTML not available in older IE
		} else {

		}
	};

	self.focus = function (obj, pos)
	{
		if (typeof obj === "string") obj = document.getElementById(obj);
		if (obj !== null) setTimeout(function () {
			obj.focus();
			if (typeof pos != "undefined") setCaretPos(obj, pos);
		}, 100);
	};

	self.crKey = function (str)
	{
		var r = str.toLowerCase();
		r = r.replace(new RegExp("\\s", 'g'), "-");
		r = r.replace(new RegExp("[àáâãäå]", 'g'), "a");
		r = r.replace(new RegExp("æ", 'g'), "ae");
		r = r.replace(new RegExp("[çč]", 'g'), "c");
		r = r.replace(new RegExp("[ď]", 'g'), "d");
		r = r.replace(new RegExp("[èéêëě]", 'g'), "e");
		r = r.replace(new RegExp("[ìíîï]", 'g'), "i");
		r = r.replace(new RegExp("ľ", 'g'), "l");
		r = r.replace(new RegExp("ñň", 'g'), "n");
		r = r.replace(new RegExp("[òóôõö]", 'g'), "o");
		r = r.replace(new RegExp("œ", 'g'), "oe");
		r = r.replace(new RegExp("[řŕ]", 'g'), "r");
		r = r.replace(new RegExp("[š]", 'g'), "s");
		r = r.replace(new RegExp("[ť]", 'g'), "t");
		r = r.replace(new RegExp("[ùúûüů]", 'g'), "u");
		r = r.replace(new RegExp("[ýÿ]", 'g'), "y");
		r = r.replace(new RegExp("[ž]", 'g'), "z");
		r = r.replace(new RegExp("\\W", 'g'), "-");
		r = r.replace("---", "-").replace("--", "-").replace("--", "-");
		if (r.substr(r.length - 1, 1) == "-") r = r.substr(0, r.length - 1);
		return r;
	};

	self.getElementPosition = function (elm)
	{
		var l = 0, t = 0;
		while (elm) {
			l += (elm.offsetLeft - elm.scrollLeft + elm.clientLeft);
			t += (elm.offsetTop - elm.scrollTop + elm.clientTop);
			elm = elm.offsetParent;
		}
		return { left: l, top: t };
	};

	return self;
})();

com.qetrix.apps.common.datastores.Http = new (function () {
	var self = {};

	self.getData = function (j, a)
	{
		var w = window, r = w.XMLHttpRequest ? new XMLHttpRequest() : (w.ActiveXObject ? new ActiveXObject("Microsoft.XMLHTTP") : 0);
		if (r) {
			r.onreadystatechange = function () {
				if (typeof a != "undefined") r.readyState == 4 ? (a)(r.responseText, r.responseXML) : 0
			};
			r.open("GET", j + (j.indexOf("?") > 0 ? "&" : "?") + "xhr=" + new Date().getTime(), !0);
			r.send(a);
		}
	};

	self.setData = function (j, a, n)
	{
		var w = window, r = w.XMLHttpRequest ? new XMLHttpRequest() : (w.ActiveXObject ? new ActiveXObject("Microsoft.XMLHTTP") : 0);
		if (r) {
			r.onreadystatechange = function () {
				r.readyState == 4 ? (n)(r.responseText, r.responseXML) : 0
			};
			r.open("POST", j + (j.indexOf("?") > 0 ? "&" : "?") + "xhr=" + new Date().getTime(), !0);
			r.setRequestHeader("Content-type", "application/x-www-form-urlencoded; charset=utf-8");
			if (r.overrideMimeType) r.setRequestHeader("Connection", "close");
			r.send(a);
		}
	};

	self.get = function (scope, v)
	{
		switch (scope) {
			case "get":
				v = v.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
				var regex = new RegExp("[\\?&]" + v + "=([^&#]*)"), results = regex.exec(location.search);
				return results === null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
			case "cookie":
				var nameEQ = v + "=";
				var ca = document.cookie.split(";");
				for (var i = 0; i < ca.length; i++) {
					var c = ca[i];
					while (c.charAt(0) == " ") c = c.substring(1, c.length);
					if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
				}
				return null;
			case "env":
				switch (v) {
					case "path":
						return path;
				}
		}
		return false;
	};

	self.set = function (scope, name, value, args)
	{
		args = args || {}; // If args is undefined (not passed, omitted)
		switch (scope) {
			case "cookie":
				var expires = "";
				if (typeof args.days != "undefined") {
					var date = new Date();
					date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
					expires = "; expires=" + date.toGMTString();
				}
				document.cookie = name + "=" + value + expires + "; path=" + self.path;
				return true;
		}
		return false;
	};

	return self;
})();

com.qetrix.apps.common.datastores.LocalStorage = new (function () {
	var self = {};
	return self;
})();

com.qetrix.libs.components.QList = new (function () {
	var self = {};

	self.add = function ()
	{

	};

	return self;
})();


/** App */
com.qetrix.libs.QApp = new (function () {
	var self = {};

	self.name = null;
	self.envDS = function (scope, name) {
		if (typeof scope == "undefined") return com.qetrix.apps.common.datastores.Http;
		if (typeof name == "undefined") return com.qetrix.apps.common.datastores.Http.get(scope);
		return com.qetrix.apps.common.datastores.Http.get(scope, name);
	};

	return self;
})();

this.app = function () {
	return com.qetrix.libs.QApp;
}; // Shorthand for App; usage: this.app()
var Util = com.qetrix.libs.Util; // Shorthand for Util


// TODO - incorporate into envDS
function goTo(path)
{
	location.href = path;
	return false;
}


/*var xhrpostbut = null;
 function xhrPost(form)
 {
 var xhrfce = arguments[1];
 var elem = form.elements;
 var str = "";
 var upd = "";
 var send = true;
 var disabled = [];
 for (var i=0; i<elem.length; i++) if (typeof elem[i].value != "undefined" && elem[i].name != "") {
 if (elem[i].type != "button" && elem[i].disabled == "") { /// We don"t process buttons
 if (elem[i].name == "#UPD") upd = e(elem[i].value);
 if (elem[i].type == "checkbox") {
 /// Checkboxes
 str += "&" + elem[i].name + "=" + (elem[i].checked ? elem[i].value > 0 ? elem[i].value : 1 : 0);
 } else if (elem[i].type == "radio") {
 /// Radios
 if (elem[i].checked) str += "&" + elem[i].name + "=" + elem[i].value;
 } else if (elem[i].type != "submit") {
 /// Most of elements
 str += "&" + elem[i].name + "=" + elem[i].value.replace(/\+/g, "%2B").replace(/&/, "%26");
 } else {
 if (elem[i].name == "butb") {
 send = false;
 } else if (elem[i].value != "" && getElementStyle(elem[i], "display") != "none") {
 /// submit, but only visible
 if (xhrpostbut == null || elem[i].name == xhrpostbut.name) str += "&" + elem[i].name + "=" + elem[i].value.replace(/\+/g, "%2B").replace(/&/g, "%26");
 } else if (xhrpostbut != null && elem[i].name == xhrpostbut.name) xhrpostbut = null; /// If selected submit is not visible, unset xhrpostbut, submit will be the first one visible
 }
 if (elem[i].type != "hidden") {
 elem[i].disabled = "disabled";
 disabled.push(elem[i]);
 } else if (e(elem[i].id+"-inp") != null) {
 e(elem[i].id+"-inp").disabled = "disabled";
 disabled.push(e(elem[i].id+"-inp"));
 }
 }
 }

 xhrpostbut = null;
 if (upd == "" || upd == null) upd = form;
 var backup = upd.innerHTML;
 var args = arguments;

 if (send) {
 try{xhr(form.action, str + "&ajax=true&app="+APP, function(text) {
 for (var elemx in disabled) disabled[elemx].disabled = "";
 if (e("postmsg") != null) {
 var cnt = e("postmsg").innerHTML.split("<div").length; /// count how many divs are there
 if (cnt < 1) cnt = 1;
 }
 if (typeof xhrfce == "function") {xhrfce(text, args);return true;} else if (e(xhrfce) != null) {setInnerHTML(xhrfce, text);return true;}
 if (upd == "" || text == "") {location.reload(true);return;} else setInnerHTML(upd, text);
 });}catch(err){
 for (var elemx in disabled) disabled[elemx].disabled = "";
 setInnerHTML(upd, "<div class="errMsg">Chyba! " + err + "</div>" + backup);
 }
 }
 return true;
 }*/


function wikiTextKeyDown(sender, evt)
{
	switch (evt.keyCode) {
		case 9:
			if (evt.ctrlKey) return true;
			var pos = getCaretPos(sender);
			sender.value = sender.value.substr(0, pos) + "	" + sender.value.substr(pos);
			setCaretPos(sender, pos + 1);
			return false;
		case 13:
			if (evt.ctrlKey) {
				wiki(sender, "</>", "");
				return false;
			}
			var pos = getCaretPos(sender);
			if (pos == 0 || sender.value.substr(pos - 1, 1) == "\n") return true;
			var clpos = currentLine(sender, pos);
			if (clpos.substr(0, 2) == "* ") {
				if (clpos == "* ") {
					sender.value = sender.value.substr(0, pos - 3) + "\n" + sender.value.substr(pos);
					setCaretPos(sender, pos + 2);
				} else {
					sender.value = sender.value.substr(0, pos) + "\n* " + sender.value.substr(pos);
					setCaretPos(sender, pos + 3);
					return false;
				}
			}
			break;
		case 27:
			location.href = location.href.substr(0, location.href.length - 5);
			return false;
		case 162:
			if (evt.ctrlKey && sender.selectionStart < sender.selectionEnd) {
				wiki(sender, "„", "“");
				return false;
			}
			break;
		case 66:
			if (evt.ctrlKey && sender.selectionStart < sender.selectionEnd) {
				wiki(sender, "'''", "'''");
				return false;
			}
			break;
		case 72:
			if (evt.ctrlKey && sender.selectionStart < sender.selectionEnd) {
				wiki(sender, "== ", " ==");
				return false;
			}
			break;
		case 73:
			if (evt.ctrlKey && sender.selectionStart < sender.selectionEnd) {
				wiki(sender, "''", "''");
				return false;
			}
			break;
		case 76:
			if (evt.ctrlKey && sender.selectionStart < sender.selectionEnd) {
				wiki(sender, "[[", "]]");
				return false;
			}
			break;
		case 80:
			if (evt.ctrlKey) {
				if (sender.selectionStart == sender.selectionEnd) wiki(sender.id, "[[Image:image.jpg|Popisek|thumb|right]]", "");
				return false;
			}
			break;
		case 83:
			if (evt.ctrlKey) {
				e(sender.id + "_savebut").click();
				return false;
			}
			break;
	}
	return true;
}

function getCaretPos(nBox)
{
	var cursorPos = 0;
	if (document.selection) {
		nBox.focus();
		var tmpRange = document.selection.createRange();
		tmpRange.moveStart("character", -nBox.value.length);
		cursorPos = tmpRange.text.length;
	} else if (nBox.selectionStart || nBox.selectionStart == "0") cursorPos = nBox.selectionStart;
	return cursorPos;
}

function setCaretPos(field, start, end)
{
	if (isString(field)) field = e(field);
	if (typeof end == "undefined" || isNaN(end) || end < 0) end = start;
	if (field.createTextRange) {
		var selRange = field.createTextRange();
		selRange.collapse(true);
		selRange.moveStart("character", start);
		selRange.moveEnd("character", end - start);
		selRange.select();
	} else {
		if (field.selectionStart) {
			field.selectionStart = start;
			field.selectionEnd = end;
		} else if (field.setSelectionRange) field.setSelectionRange(start, end);
	}
	field.focus();
}

function isString(o)
{
	return "string" == typeof o;
}

function currentLine(ta, pos)
{
	var taval = ta.value, start = taval.lastIndexOf("\n", pos - 1) + 1, end = taval.indexOf("\n", pos);
	if (end == -1) end = taval.length;
	return taval.substr(start, end - start);
}

function createLine(id, x1, y1, x2, y2, clr)
{
	/// Example: createLine("line1x1", 100, 100, 200, 300, "#f00");

	var a = x1 - x2, b = y1 - y2, len = Math.sqrt(a * a + b * b);
	var angle = Math.PI - Math.atan2(-b, a);

	var line;
	if (Util.e(id) == null) {
		line = document.createElement("div");
		line.id = id;
		document.body.appendChild(line);
	} else line = Util.e(id);
	line.setAttribute("style", "border:1px solid " + clr + ";width:" + len + "px;height:0;transform:rotate(" + angle + "rad);position:absolute;top: " + ((y1 + y2) / 2) + "px;left: " + (((x1 + x2) / 2) - len / 2) + "px;");
	return line;
}

document.body.appendChild(createLine(100, 100, 200, 200));

function trl_ac(sender, evt, id)
{
	setTimeout(function () {
		this.app().envDS().setData(path + "trl_ac/" + id + "?_f=json", "value=" + sender.value, function (json) {
			showAutoComplete(sender, evt, json, "trl_ac");
		});
	}, 10);
}
