/*

ИНТЕГРАЦИЯ - ТИП ОБРАЩЕНИЯ - сайты cruche-academy.ru и skillbank.su

*/

jQuery("form[name=callMeGo]").each(function (index) {
	//console.log( index + ": " + jQuery( this ).attr('data-label') );
	inka = jQuery(this).attr('data-label');
	if (inka === '') inka = '[форма не опознается (data-label от callMeGo)]';
	jQuery(this).append('<input type="hidden" name="form_name_site" id="form_name_site" value="' + inka + '">');
});

/*

ИНТЕГРАЦИЯ ДЛЯ GET ПАРАМЕТРОВ

*/

function findGetParameter(parameterName) {
	var result = null,
		tmp = [];
	location.search
		.substr(1)
		.split("&")
		.forEach(function (item) {
			tmp = item.split("=");
			if (tmp[0] === parameterName) result = decodeURIComponent(tmp[1]);
		});
	return result;
}

function getCookie(name) {
	let matches = document.cookie.match(new RegExp(
		"(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"
	));
	return matches ? decodeURIComponent(matches[1]) : undefined;
}

function setCookie(name, value, options = {}) {
	options = {
		path: '/',
		// add other defaults here if necessary
	};
	if (options.expires instanceof Date) {
		options.expires = options.expires.toUTCString();
	}
	let updatedCookie = encodeURIComponent(name) + "=" + encodeURIComponent(value);
	for (let optionKey in options) {
		updatedCookie += "; " + optionKey;
		let optionValue = options[optionKey];
		if (optionValue !== true) {
			updatedCookie += "=" + optionValue;
		}
	}
	document.cookie = updatedCookie;
}

// Example of use:
//setCookie('user', 'John', {secure: true, 'max-age': 3600});

function deleteCookie(name) {
	setCookie(name, "", {
		'max-age': -1
	});
}

/* upgrade introvert_cookie */

introvert = getCookie("introvert_cookie");
console.log(introvert);
if (introvert) {
	try {
		introvert_decoded = decodeURI(introvert);
	} catch (E)
	{
		console.log(E)
		introvert_decoded = introvert
	}
	obj = JSON.parse(introvert_decoded);

	_ga = getCookie("_ga");
	if (_ga) {
		obj.clid = _ga;
	}

	utm_source = findGetParameter("utm_source");
	utm_medium = findGetParameter("utm_medium");
	utm_term = findGetParameter("utm_term");
	utm_campaign = findGetParameter("utm_campaign");
	utm_content = findGetParameter("utm_content");
	if (1 == 1) {
		obj.utm_source = utm_source;
		obj.utm_medium = utm_medium;
		obj.utm_term = utm_term;
		obj.utm_campaign = utm_campaign;
		obj.utm_content = utm_content;
	}

	myJSON = JSON.stringify(obj);

	name = "introvert_cookie";
	value = myJSON;
	//document.cookie = encodeURIComponent(name) + '=' + encodeURIComponent(value);
	setCookie(name, value);
}

/* у нас новый переход, есть своя roistat в гет */

introvert = getCookie("introvert_cookie");
roistat_get = findGetParameter("roistat");
if (roistat_get && introvert) {
	//console.log("GET roistat_get="+roistat_get);
	name = "roistat_marker_introvert";
	value = roistat_get;
	document.cookie = encodeURIComponent(name) + '=' + encodeURIComponent(value);

	try {
		introvert_decoded = decodeURI(introvert);
	} catch (E)
	{
		console.log(E)
		introvert_decoded = introvert
	}
	obj = JSON.parse(introvert_decoded);
	obj.roistat_marker = roistat_get;
	myJSON = JSON.stringify(obj);

	name = "introvert_cookie";
	value = myJSON;
	//document.cookie = encodeURIComponent(name) + '=' + encodeURIComponent(value);
	setCookie(name, value);
} else {
	/* у нас проход по сайту, нет своей roistat в гет, но возможно есть кука с маркером */

	introvert = getCookie("introvert_cookie");
	roistat_marker = getCookie("roistat_marker");
	if (roistat_marker && introvert) {
		//console.log("COOKIE roistat_marker="+roistat_marker);

		try {
			introvert_decoded = decodeURI(introvert);
		} catch (E)
		{
			console.log(E)
			introvert_decoded = introvert
		}
		obj = JSON.parse(introvert_decoded);
		obj.roistat_marker = roistat_marker;
		myJSON = JSON.stringify(obj);

		name = "introvert_cookie";
		value = myJSON;
		//document.cookie = encodeURIComponent(name) + '=' + encodeURIComponent(value);
		setCookie(name, value);
	}

}

/*

Интеграция передачи домена (no www) в куку интроверт + замена site

*/

domain_active = 1;
domain_nowww = window.location.hostname;
domain_nowww = domain_nowww.replace("www.", "");
introvert = getCookie("introvert_cookie");
if (domain_nowww && introvert && domain_active == 1) {
	//console.log("DOMAIN domain_introvert="+domain_introvert);
	//name="domain_nowww";
	//value=domain_nowww;
	//document.cookie = encodeURIComponent(name) + '=' + encodeURIComponent(value);

	try {
		introvert_decoded = decodeURI(introvert);
	} catch (E)
	{
		console.log(E)
		introvert_decoded = introvert
	}
	obj = JSON.parse(introvert_decoded);
	//obj.domain_nowww=domain_nowww;
	obj.site = domain_nowww;
	obj.site_nowww = domain_nowww;
	//console.log("obj.site="+obj.site);
	myJSON = JSON.stringify(obj);

	name = "introvert_cookie";
	value = myJSON;
	//document.cookie = encodeURIComponent(name) + '=' + encodeURIComponent(value);
	setCookie(name, value);
} else {
	if (domain_active == 2) {
		try {
			introvert_decoded = decodeURI(introvert);
		} catch (E)
		{
			console.log(E)
			introvert_decoded = introvert
		}
		obj = JSON.parse(introvert_decoded);
		obj.domain_nowww = "";
		myJSON = JSON.stringify(obj);

		name = "introvert_cookie";
		value = myJSON;
		//document.cookie = encodeURIComponent(name) + '=' + encodeURIComponent(value);
		setCookie(name, value);
	}
}

/*

Интеграция добавления домена в поля form_name_site

*/

form_name_site_add = 1;
domain_site = window.location.hostname;
domain_site = domain_site.replace("www.", "");
if (form_name_site_add == 1) {
	//console.log("form_name_site domain add="+domain_site);
	if (document.getElementsByName("form_name_site")) {
		var x = document.getElementsByName("form_name_site");
		var i;
		for (i = 0; i < x.length; i++) {
			if (x[i].type == "hidden") {
				if (x[i].value !== '') {
					x_i_value = x[i].value;
					x_i_value = x_i_value.replace(domain_site, "");
					x[i].value = x_i_value;
					x[i].value = x[i].value + ' ' + domain_site;
				}
			}
		}
	}
}

/*

ОСТАЛАСЬ ТОЛЬКО ОДНА ИНТЕГРАЦИЯ ДЛЯ ФОРМ - для mirk.msk.ru - так как там нет PHP

*/

function introvert_settings(II) {

	II.setSettings({
		disableSubmit: true
	});

}

var post_js_active = 0;
var post_js = '';
var post_js_ip = '';
var post_js_city = '';
var post_js_country = '';

function func_ip_get() {
	// free 50000 over month for one api
	$.getJSON('https://api.ipgeolocation.io/ipgeo?apiKey=f82b2d2f2f6d4c4aacdcdf551e303774', function (data) {
		//console.log(JSON.stringify(data, null, 2));
		//console.log(data.ip);
		post_js_active = 1;
		post_js = data;
		post_js_ip = data.ip;
		post_js_city = data.city;
		post_js_country = data.country_name;
	});
}

if (window.location.hostname == 'mirk.msk.ru') {
	//func_ip_get();
}

function func_check_simple_captcha() {

	var cn = 0;
	var cn_ctrl = 0;
	if (document.getElementsByName("_cn")) {
		var x = document.getElementsByName("_cn");
		var i;
		for (i = 0; i < x.length; i++) {
			if (x[i].type == "text") {
				if (x[i].value !== '') cn++;
			}
		}
	} else {
		cn_ctrl = -1;
	}

	cn = 1; // allways = 1 - no simple captcha anymore ...

	if (cn > cn_ctrl) {
		return true;
	} else {
		return false;
	}

}

/* standart forms for anything */

var myhanda = function () {

	var form_heading = II.$(this).closest('.gr-form').find('.gr-head').text();

	if (form_heading === '') {
		form_heading = II.$(this).closest('.localconsult__inner').find('.localconsult__inner-title').text();
	}
	if (form_heading === '') {
		form_heading = document.title;
	}

	status_send = '';
	if (form_heading.indexOf('рассылк') !== -1) {
		status_send = '31653277';
	}
	if (form_heading.indexOf('модель') !== -1) {
		status_send = '31326787';
	}

	domain_site = window.location.hostname;
	domain_site = domain_site.replace("www.", "");
	form_heading = 'Заявка с формы ' + form_heading.toLowerCase() + ' с сайта ' + domain_site;

	var handler = II.getHandler({
		form: II.$(this).closest('.gr-form'), // можно передать обработчику jQuery объект текущей формы
		params: {
			//post_js_ip: post_js_ip,
			//post_js_city: post_js_city,
			//post_js_country: post_js_country,
			status: status_send,
			form_name: form_heading,
			btn_name: II.$(this).text()
		}
	});

	captcha_chk = func_check_simple_captcha();

	// отправляем данные
	//if (captcha_chk) handler();
	if (captcha_chk) {

		forma = II.$(this).closest('.gr-form');

		post_telp = (forma.find('input[name="d[0]"]').length > 0 ? forma.find('input[name="d[0]"]').get(0).value : '');
		post_name = (forma.find('input[name="d[1]"]').length > 0 ? forma.find('input[name="d[1]"]').get(0).value : '');
		post_mail = (forma.find('input[name="d[2]"]').length > 0 ? forma.find('input[name="d[2]"]').get(0).value : '');
		post_body = (forma.find('input[name="d[3]"]').length > 0 ? forma.find('input[name="d[3]"]').get(0).value : forma.find('textarea[name="d[3]"]').get(0).value);

		var d = [
			post_telp,
			post_name,
			post_mail,
			post_body
		];

		introvert = getCookie("introvert_cookie");
		try {
			introvert_decoded = decodeURI(introvert);
		} catch (E)
		{
			console.log(E)
			introvert_decoded = introvert
		}
		introvert_cookie = JSON.parse(introvert_decoded);

		params = {
			//post_js_ip: post_js_ip,
			//post_js_city: post_js_city,
			//post_js_country: post_js_country,
			form: II.$(this).closest('.gr-form').length,
			d: d,
			post_telp: post_telp,
			post_name: post_name,
			post_mail: post_mail,
			post_body: post_body,
			status: status_send,
			form_name: form_heading,
			btn_name: II.$(this).text()
		}

		Object.keys(introvert_cookie).forEach(key => {
			params[key] = introvert_cookie[key];
		});

		II.send(
			'https://api.yadrocrm.ru/integration/site?key=2c40715e',
			params,
			function (data) {
				// do something
				//console.log('II.send done.')
			}
		);
	}
};

function funca() {
	II.$('.gr-button').unbind('click', myhanda);
	II.$('.gr-button').bind('click', myhanda);

	if (document.getElementsByName("_cn")) {
		var x = document.getElementsByName("_cn");
		var i;
		for (i = 0; i < x.length; i++) {
			if (x[i].type == "text") {
				x[i].setAttribute("style", "width: 80px !important; height: 50px !important; margin-top: -15px !important");
			}
		}
	}

	if (document.getElementsByClassName("mgCaptcha-block")) {
		var xc = document.getElementsByClassName("mgCaptcha-block");
		var ic;
		for (ic = 0; ic < xc.length; ic++) {
			if (xc[ic].type != "text") {
				xc[ic].setAttribute("style", "float: left !important");
			}
		}
	}

}

/* start by host */
if (1 == 2)
	if (window.location.hostname == 'mirk.msk.ru') window.addEventListener('DOMSubtreeModified', funca);

/* new forms for webinars */

var myhanda_2 = function () {

	var form_heading = II.$(this).closest('.g-anketa-wrapper').find('.title').text();

	if (form_heading === '') {
		form_heading = document.title;
	}

	domain_site = window.location.hostname;
	domain_site = domain_site.replace("www.", "");
	form_heading = 'Заявка с формы ' + form_heading.toLowerCase() + ' с сайта ' + domain_site;

	var post_name = (document.getElementById("post_name") ? document.getElementById("post_name").value : '');
	var post_mail = (document.getElementById("post_mail") ? document.getElementById("post_mail").value : '');
	var post_body = (document.getElementById("post_body") ? document.getElementById("post_body").value : '');

	var handler = II.getHandler({
		form: II.$(this).closest('.g-anketa-wrapper').find('form'), // можно передать обработчику jQuery объект текущей формы
		params: {
			//post_js_ip: post_js_ip,
			//post_js_city: post_js_city,
			//post_js_country: post_js_country,
			post_name: post_name,
			post_mail: post_mail,
			post_body: post_body,
			form_name: form_heading,
			btn_name: II.$(this).text()
		}
	});

	captcha_chk = func_check_simple_captcha();

	introvert = getCookie("introvert_cookie");
	try {
		introvert_decoded = decodeURI(introvert);
	} catch (E)
	{
		console.log(E)
		introvert_decoded = introvert
	}
	introvert_cookie = JSON.parse(introvert_decoded);

	params = {
		//post_js_ip: post_js_ip,
		//post_js_city: post_js_city,
		//post_js_country: post_js_country,
		post_name: post_name,
		post_mail: post_mail,
		post_body: post_body,
		form_name: form_heading,
		btn_name: II.$(this).text()
	}

	Object.keys(introvert_cookie).forEach(key => {
		params[key] = introvert_cookie[key];
	});

	// отправляем данные
	//if (captcha_chk) handler();
	if (captcha_chk) {
		II.send(
			'https://api.yadrocrm.ru/integration/site?key=2c40715e',
			params,
			function (data) {
				// do something
				//console.log('II.send done.')
			}
		);
	}
};

function funca_2() {
	II.$('.g-button').unbind('click', myhanda_2);
	II.$('.g-button').bind('click', myhanda_2);
}

/* start by host */
if (1 == 2)
	if (window.location.hostname == 'mirk.msk.ru') window.addEventListener('DOMSubtreeModified', funca_2);

/* dumb forms for pages */

var myhanda_3 = function () {

	var form_heading = II.$(this).closest('.cont-text-right').find('.form-head').text();

	if (form_heading === '') {
		form_heading = document.title;
	}

	domain_site = window.location.hostname;
	domain_site = domain_site.replace("www.", "");
	form_heading = 'Заявка с формы ' + form_heading.toLowerCase() + ' с сайта ' + domain_site;

	var post_telp = (document.getElementById("d[0]") ? document.getElementById("d[0]").value : '');
	var post_name = (document.getElementById("d[1]") ? document.getElementById("d[1]").value : '');
	var post_mail = (document.getElementById("d[2]") ? document.getElementById("d[2]").value : '');
	var post_body = (document.getElementById("d[3]") ? document.getElementById("d[3]").value : '');

	var handler = II.getHandler({
		form: II.$(this).closest('.cont-text-right').find('form'), // можно передать обработчику jQuery объект текущей формы
		params: {
			//post_js_ip: post_js_ip,
			//post_js_city: post_js_city,
			//post_js_country: post_js_country,
			post_telp: post_telp,
			post_name: post_name,
			post_mail: post_mail,
			post_body: post_body,
			form_name: form_heading,
			btn_name: II.$(this).text()
		}
	});

	captcha_chk = func_check_simple_captcha();

	introvert = getCookie("introvert_cookie");
	try {
		introvert_decoded = decodeURI(introvert);
	} catch (E)
	{
		console.log(E)
		introvert_decoded = introvert
	}
	introvert_cookie = JSON.parse(introvert_decoded);

	params = {
		//post_js_ip: post_js_ip,
		//post_js_city: post_js_city,
		//post_js_country: post_js_country,
		post_telp: post_telp,
		post_name: post_name,
		post_mail: post_mail,
		post_body: post_body,
		form_name: form_heading,
		btn_name: II.$(this).text()
	}

	Object.keys(introvert_cookie).forEach(key => {
		params[key] = introvert_cookie[key];
	});

	// отправляем данные
	//if (captcha_chk) handler();
	if (captcha_chk) {
		II.send(
			'https://api.yadrocrm.ru/integration/site?key=2c40715e',
			params,
			function (data) {
				// do something
				//console.log('II.send done.')
			}
		);
	}
};

function funca_3() {
	II.$('input[type=submit]').unbind('click', myhanda_3);
	II.$('input[type=submit]').bind('click', myhanda_3);
}

/* start by host and url */
if (1 == 2)
	if (window.location.hostname == 'mirk.msk.ru')
		if (document.location.pathname == '/nash-adres') window.addEventListener('DOMSubtreeModified', funca_3);

/* start by host and url */
if (1 == 2)
	if (window.location.hostname == 'mirk.msk.ru')
		if (document.location.pathname == '/napishite-nam') window.addEventListener('DOMSubtreeModified', funca_3);

/* start by host and url */
if (1 == 2)
	if (window.location.hostname == 'mirk.msk.ru')
		if (document.location.pathname == '/hochu-prezentaciyu-s-raz-yasneniem-prikaza-327n') window.addEventListener('DOMSubtreeModified', funca_3);


/* dumb forms for vrach-kosmetolog */

var myhanda_4 = function () {

	var form_heading = II.$(this).closest('.lp-form-10-inner').find('.lp-form-10__title').eq(1).text();

	if (form_heading === '') {
		form_heading = document.title;
	}

	//form_heading='mirk.vrach.kosmetolog';

	console.log(form_heading);

	domain_site = window.location.hostname;
	domain_site = domain_site.replace("www.", "");
	//form_heading='Заявка с формы '+form_heading.toLowerCase()+' с сайта '+domain_site;
	form_heading = 'Заявка с формы ' + form_heading.toLowerCase() + ' с сайта mirk.vrach.kosmetolog';

	var post_telp = II.$(this).closest('form').find('input[name="d[1]"]').get(0).value;
	var post_name = II.$(this).closest('form').find('input[name="d[0]"]').get(0).value;
	var post_mail = II.$(this).closest('form').find('input[name="d[2]"]').get(0).value;
	var post_city = II.$(this).closest('form').find('input[name="d[2]"]').get(0).value;
	var post_body = II.$(this).closest('form').find('textarea[name="d[3]"]').get(0).value;
	//post_body=post_body+" Форма обучения: "+II.$(this).closest('form').find('.lp-form-tpl__field-select__input').text();
	var post_fedu = II.$(this).closest('form').find('.lp-form-tpl__field-select__input').text();

	post_chk = false;
	if (II.$(this).closest('form').find('input[name="d[5][]"]').get(0)) post_chk = II.$(this).closest('form').find('input[name="d[5][]"]').get(0).checked;
	if (post_telp) console.log('post_telp=ok'); else console.log('post_telp=none');
	if (post_chk) console.log('post_chk=true'); else console.log('post_chk=false');

	//console.log(post_telp+post_name+post_city+post_body);

	var handler1 = II.getHandler({
		form: II.$(this).closest('form'), // можно передать обработчику jQuery объект текущей формы
		params: {
			//post_js_ip: post_js_ip,
			//post_js_city: post_js_city,
			//post_js_country: post_js_country,
			post_telp: post_telp,
			post_name: post_name,
			post_mail: post_mail,
			post_body: post_body,
			post_fedu: post_fedu,
			post_city: post_city,
			form_name: form_heading,
			btn_name: II.$(this).text()
		}
	});

	captcha_chk = func_check_simple_captcha();

	btn_name_send = II.$(this).text();

	introvert = getCookie("introvert_cookie");
	try {
		introvert_decoded = decodeURI(introvert);
	} catch (E)
	{
		console.log(E)
		introvert_decoded = introvert
	}
	introvert_cookie = JSON.parse(introvert_decoded);

	params = {
		//introvert_cookie: introvert_cookie,
		//post_js_ip: post_js_ip,
		//post_js_city: post_js_city,
		//post_js_country: post_js_country,
		post_telp: post_telp,
		post_name: post_name,
		post_mail: post_mail,
		post_body: post_body,
		post_fedu: post_fedu,
		post_city: post_city,
		form_name: form_heading,
		//btn_name: II.$(this).text()
		btn_name: btn_name_send
	}

	Object.keys(introvert_cookie).forEach(key => {
		params[key] = introvert_cookie[key];
	});

	// отправляем данные
	//if (captcha_chk) handler1();
	if (captcha_chk && post_chk && post_telp != '') {
		II.send(
			'https://api.yadrocrm.ru/integration/site?key=2c40715e',
			params,
			function (data) {
				// do something
				//console.log('II.send done.')
			}
		);
	}
	//console.log('iv sent - this message is after call of hander1();');
};

function funca_4() {
	II.$('.lp-form-tpl__button').unbind('click', myhanda_4);
	II.$('.lp-form-tpl__button').bind('click', myhanda_4);
}

/* start by host and url */
if (1 == 2)
	if (window.location.hostname == 'mirk.msk.ru')
		if (document.location.pathname == '/vrach-kosmetolog') window.addEventListener('DOMSubtreeModified', funca_4);

//if (window.location.hostname=='mirk.msk.ru' || window.location.hostname=='new.mirk.msk.ru')
if (1 == 2)
	if (window.location.hostname == 'new.mirk.msk.ru') {
		function parseQuery(queryString) {
			var query = {};
			var pairs = (queryString[0] === '?' ? queryString.substr(1) : queryString).split('&');
			for (var i = 0; i < pairs.length; i++) {
				var pair = pairs[i].split('=');
				query[decodeURIComponent(pair[0])] = decodeURIComponent(pair[1] || '');
			}
			return query;
		}

		var myhanda_5 = function () {
			introvert = getCookie("introvert_cookie");
			try {
				introvert_decoded = decodeURI(introvert);
			} catch (E)
			{
				console.log(E)
				introvert_decoded = introvert
			}
			introvert_cookie = JSON.parse(introvert_decoded);

			//str=$(this).serialize();

			//post_qstr=''; //str;
			post_telp = '';
			post_name = '';
			post_body = '';
			form_heading = '';

			params = {
				//post_qstr: post_qstr,
				post_telp: post_telp,
				post_name: post_name,
				post_body: post_body,
				form_name: form_heading
			}

			Object.keys(introvert_cookie).forEach(key => {
				params[key] = introvert_cookie[key];
			});

			str = $(this).serialize();

			qstr = parseQuery(str);
			Object.keys(qstr).forEach(key => {
				params[key] = qstr[key];
			});

			inputs = this.getElementsByTagName('input');
			for (index = 0; index < inputs.length; ++index) {
				input = inputs[index];
				if (input.name.indexOf('d[0]') == 0) {
					params['post_name'] = input.value;
				}
				if (
					(input.name.indexOf('d[1]') == 0)
					|| (input.name.indexOf('user_phone') == 0)
				) {
					params['post_telp'] = input.value;
				}
				if (input.name.indexOf('d[2]') == 0) {
					params['post_body'] = input.value;
				}
				if (input.name.indexOf('product_name') > 0) {
					params['form_name'] = input.value;
				}
			}
			inputs = this.getElementsByTagName('textarea');
			for (index = 0; index < inputs.length; ++index) {
				input = inputs[index];
				if (input.name.indexOf('d[0]') == 0) {
					params['post_name'] = input.value;
				}
				if (input.name.indexOf('d[1]') == 0) {
					params['post_telp'] = input.value;
				}
				if (input.name.indexOf('d[2]') == 0) {
					params['post_body'] = input.value;
				}
				if (input.name.indexOf('product_name') > 0) {
					params['form_name'] = input.value;
				}
			}

			params['form_name'] = 'Заявка с формы ' + params['form_name'] + ' с сайта ' + window.location.hostname;

			//console.log(params);

			captcha_chk = true;
			post_chk = true;
			post_telp = false;
			if (params['post_telp'] != '') post_telp = true;

			// отправляем данные
			if (captcha_chk && post_chk && post_telp) {
				II.send(
					'https://api.yadrocrm.ru/integration/site?key=2c40715e',
					params,
					function (data) {
						// do something
					}
				);
			}
		}

		function funca_5() {
			$("form").each(function () {
				if (
					(this.getAttribute("data-s3-anketa-id") != '')
					// || (this.classList.contains("shop2-order-options"))
					|| (this.classList.contains("shop2-order-form"))
				) {
					//console.log(this.length);
					inputs = this.getElementsByTagName('input');
					is_d_form = 0;
					for (index = 0; index < inputs.length; ++index) {
						input = inputs[index];
						if (input.name.indexOf('d[') == 0) {
							is_d_form = 1;
						}
						if (input.name.indexOf('[') > 0) {
							is_d_form = 1;
						}
						if (input.name.indexOf('user_phone') >= 0) {
							is_d_form = 1;
						}
					}
					if (is_d_form == 1) {
						this.id = (this.getAttribute("data-s3-anketa-id") || 'shop2-order-options');
						//str=$("#"+this.id).serialize();
						//console.log(str);

						/*
                        buttons = this.getElementsByTagName('button');
                        for (index = 0; index < buttons.length; ++index)
                                {
                                        button=buttons[index];
                                        button.id=this.getAttribute("data-s3-anketa-id");
                                        II.$("#"+button.id).unbind('click', myhanda_5);
                                        II.$("#"+button.id).bind('click', myhanda_5);
                                }
                        */

						II.$("#" + this.id).unbind('submit', myhanda_5);
						II.$("#" + this.id).bind('submit', myhanda_5);
					}
				}
			});
		}

		window.addEventListener('DOMSubtreeModified', funca_5);
	}

/* test of our script for amo */

if (window.location.hostname == 'mirk.msk.ru' || window.location.hostname == 'new.mirk.msk.ru')
//if (window.location.hostname=='mirk.msk.ru')
{
	function parseQuery1(queryString) {
		var query = {};
		var pairs = (queryString[0] === '?' ? queryString.substr(1) : queryString).split('&');
		for (var i = 0; i < pairs.length; i++) {
			var pair = pairs[i].split('=');
			query[decodeURIComponent(pair[0])] = decodeURIComponent(pair[1] || '');
		}
		return query;
	}

	serialize = function (obj) {
		var str = [];
		for (var p in obj)
			if (obj.hasOwnProperty(p)) {
				str.push(encodeURIComponent(p) + "=" + encodeURIComponent(obj[p]));
			}
		return str.join("&");
	}
	var myhanda_6 = function () {
		init_params = 'ok';
		params = {
			init_params: init_params
		}

		introvert = getCookie("introvert_cookie");
		try {
			introvert_decoded = decodeURI(introvert);
		} catch (E)
		{
			console.log(E)
			introvert_decoded = introvert
		}
		introvert_cookie = JSON.parse(introvert_decoded);

		Object.keys(introvert_cookie).forEach(key => {
			params[key] = introvert_cookie[key];
		});

		roistat_visit = getCookie("roistat_visit");
		roistat_visit_decoded = decodeURI(roistat_visit);
		//roistat_visit_cookie = JSON.parse(roistat_visit_decoded);

		key_rv = 'roistat_visit';
		if (params[key_rv]) {
		} else {
			if (roistat_visit_decoded != '') {
				params[key_rv] = roistat_visit_decoded;
			}
		}

		btn_form_id = this.getAttribute("data-form-id");
		elm_form = document.getElementById(btn_form_id);

		str = $("#" + btn_form_id).serialize();
		qstr = parseQuery1(str);
		Object.keys(qstr).forEach(key => {
			key_p = key;
			key_p = key_p.replace(/\[/gi, '-');
			key_p = key_p.replace(/\]/gi, '');
			params[key_p] = qstr[key];
		});

		indb = 0;
		buttons = elm_form.getElementsByTagName('button');
		for (index = 0; index < buttons.length; ++index) {
			indb++;
			button = buttons[index];
			params['button_' + indb] = button.textContent || button.innerText;
		}
		buttons = $('#' + btn_form_id).find('input[type="submit"]');
		for (index = 0; index < buttons.length; ++index) {
			indb++;
			button = buttons[index];
			params['button_' + indb] = button.textContent || button.innerText;
		}

		params['url_path'] = document.location.pathname + window.location.search;
		//region Опознавание форм


		/**
		 * @param {string} form_name
		 * @param {string[]} ids
		 */
		function Form(form_name, ids)
		{
			this.form_name = form_name
			this.ids = ids
			return this
		}


		/**
		 * Массив всех форм на сайте (название для form_name_site и список возможных btn_form_id
		 * @type {Form[]}
		 */
		let forms = [
			new Form(
				'Обратный звонок',
				['13107901_form_[0-9]+', '26046107_form_[0-9]+', '25477507_form_[0-9]+', '514615_form_[0-9]+']),
			new Form(
				'Получите бесплатную консультацию по поводу обучения',
				['14126701_form_[0-9]+', '25478507_form_[0-9]+', '14126501_form_[0-9]+']
			),
			new Form(
				'Напишите нам',
				['25490707_form_[0-9]+', '25490707_form_[0-9]+']
			),
			new Form(
				'Заказать звонок',
				['11517702_form_[0-9]+', '58386505_form_[0-9]+']
			),
			new Form(
				'Оставить отзыв',
				['shop2-order-options_form_1']
			),
			new Form(
				'Быстрая запись на обучение',
				['13107701_form_[0-9]+', '25477307_form_[0-9]+']
			),
			new Form(
				'Заказать консультацию специалиста',
				['16647101_form_[0-9]+', '25478907_form_[0-9]+', '25478307_form_[0-9]+']
			),
			new Form(
				'Записаться на курс и мастер-класс по массажу',
				['11518502_form_[0-9]+']
			),
			new Form(
				'Обратная связь / нужна консультация',
				['16705301_form_[0-9]+']
			),
			new Form(
				'Приведи друга',
				['11518102_form_[0-9]+']
			),
			new Form(
				'Вопросы про дистанционное обучение',
				['685301_form_[0-9]+']
			),
			new Form(
				'Запись на обучение',
				['24955501_form_5', '25479307_form_[0-9]+']
			),
			new Form(
				'Отклик на вакансию',
				['30324505_form_[0-9]+']
			),
			new Form(
				'Напишите нам',
				['58386305_form_[0-9]+']
			),

			new Form(
				'Заявка из корзины'
					['shop2-order-options_btn_1'],
			),
			new Form(
				'записаться на мероприятие _X_',
				['25473707_form_[0-9]+', '25473707_btn_[0-9]+']
			),
			new Form(
				'Записаться моделью',
				['8128707_form_[0-9]+', '25474907_form_[0-9]+']
			),

		]



		form_name = '[form_name]'; // по умолчанию
		for(i = 0; i < forms.length; i++)
		{
			// Проверяем все формы из массива, пока не найдем нужную. Поиск идет по регулярному выражению в массиве формы
			form = forms[i]
			var matches = form.ids.filter(function(pattern) {
				return new RegExp(pattern).test(btn_form_id);
			})
			if(matches.length > 0)
			{
				form_name = form.form_name
				break
			}

		}
		console.log(form_name)
		//endregion

		//region Старый код для опознавания форм
		// if (btn_form_id === '13107901_form_3' || btn_form_id === '26046107_form_6' || btn_form_id === '25477507_form_3' || btn_form_id === '514615_form_9' || btn_form_id === '13107901_form_4' || btn_form_id === '25477507_form_5' || btn_form_id === '25477507_form_4') {
		// 	form_name = 'Обратный звонок';
		// } else if (btn_form_id === '14126701_form_1' || btn_form_id === '25478507_form_1') {
		// 	form_name = 'Получите бесплатную консультацию по поводу обучения';
		// } else if (btn_form_id === '25490707_form_7' || btn_form_id === '25490707_form_8') {
		// 	form_name = 'Напишите нам';
		// } else if (btn_form_id === '11517702_form_1' || btn_form_id === '58386505_form_5') {
		// 	form_name = 'Заказать звонок';
		// } else if (btn_form_id === 'shop2-order-options_form_1') {
		// 	form_name = 'Оставить отзыв';
		// } else if (btn_form_id === '13107701_form_4' || btn_form_id === '25477307_form_5') {
		// 	form_name = 'Быстрая запись на обучение';
		// } else if (btn_form_id === '16647101_form_1' || btn_form_id === '16647101_form_2' || btn_form_id === '25478907_form_2' || btn_form_id === '25478307_form_5' || btn_form_id === '25478307_form_6' || btn_form_id === '25478907_form_2') {
		// 	form_name = 'Заказать консультацию специалиста';
		// } else if (btn_form_id === '11518502_form_3') {
		// 	form_name = 'Записаться на курс и мастер-класс по массажу';
		// } else if (btn_form_id === '16705301_form_3' || btn_form_id === '16705301_form_4') {
		// 	form_name = 'Обратная связь / нужна консультация';
		// } else if (btn_form_id === '11518102_form_3') {
		// 	form_name = 'Приведи друга';
		// } else if (btn_form_id === '685301_form_1') {
		// 	form_name = 'Вопросы про дистанционное обучение';
		// } else if (btn_form_id === '13107901_form_5') {
		// 	form_name = 'Обратный звонок';
		// } else if (btn_form_id === '24955501_form_5' || btn_form_id === '24955501_form_6' || btn_form_id === '25479307_form_6') {
		// 	form_name = 'Запись на обучение';
		// } else if (btn_form_id === '30324505_form_1') {
		// 	form_name = 'Отклик на вакансию';
		// } else if (btn_form_id === '58386305_form_5') {
		// 	form_name = 'Напишите нам';
		// } else if (btn_form_id === '8128707_form_4' || btn_form_id === '25474907_form_6') {
		// 	form_name = 'Записаться моделью';
		// } else if (btn_form_id === 'shop2-order-options_btn_1') {
		// 	form_name = 'Заявка из корзины';
		// } else if (btn_form_id === '14126501_form_5') {
		// 	form_name = 'Получить консультацию';
		// } else if (btn_form_id === '25473707_form_1' || btn_form_id === '25473707_btn_1') {
		// 	form_name = 'записаться на мероприятие _X_';
		// } else {
		// 	form_name = '[form_name]';
		// }
		//endregion


		finds = elm_form.getElementsByClassName('gr-head');
		for (index = 0; index < finds.length; ++index) {
			find = finds[index];
			if (find.innerText != '') {
				form_name = find.textContent || find.innerText;
				console.log(form_name);
			}
		}
		finds = elm_form.getElementsByClassName('form-head');
		for (index = 0; index < finds.length; ++index) {
			find = finds[index];
			if (find.innerText != '') {
				form_name = find.textContent || find.innerText;
				console.log(form_name);
			}
		}

		params['form_name'] = 'Заявка с формы ' + form_name + ' с сайта ' + window.location.hostname;

		status_send = '';
		if (form_name.indexOf('рассылк') !== -1) {
			status_send = '31653277';
		}
		if (form_name.indexOf('модель') !== -1) {
			status_send = '31326787';
		}
		params['status_send'] = status_send;

		if (1 == 1) {

			//console.log(params);

			params_str = serialize(params);
			// foo=hi%20there&bar=100%25

			//console.log(params_str);

			$.ajax({
				type: "POST",
				url: "https://www.cruche-academy.ru/mirk/2c40715e.php",
				data: "mirk=a9u2=z2j?DVW" + "&" + params_str,
				success: function (html) {
					//
					console.log('send - OK');
					//
					if (html == '1') {
						console.log('get - OK');
					}
					//
				}
			});
		}
	}

	function funca_6() {
		inda = 0;
		indb = 0;
		$("form").each(function () {
			if (
				(this.getAttribute("action") != '/search')
				&& (this.getAttribute("action") != '/o-kompanii/search')
				&& (this.getAttribute("action") != '/o-kompanii?mode=cart&action=up')
				&& (this.getAttribute("action") != '/o-kompanii?mode=cart&action=add')
			)
				if (
					(this.getAttribute("data-s3-anketa-id") != '')
					// || (this.classList.contains("shop2-order-options"))
					|| (this.classList.contains("shop2-order-form")) /* now: no class on page */
					//|| (1==1) /* any form */
				) {
					inda++;
					is_d_form = 1;
					if (is_d_form == 1) {
						form_id = (this.getAttribute("data-s3-anketa-id") || 'shop2-order-options') + "_form_" + inda;

						if (this.getAttribute("id") == 'shop2-cart') {
							form_id = this.getAttribute("id");
						}
						this.id = form_id;

						buttons = this.getElementsByTagName('button');
						for (index = 0; index < buttons.length; ++index) {
							indb++;
							button = buttons[index];
							button.id = (this.getAttribute("data-s3-anketa-id") || 'shop2-order-options') + "_btn_" + indb;
							II.$("#" + button.id).unbind('click', myhanda_6);
							II.$("#" + button.id).bind('click', myhanda_6);

							II.$("#" + button.id).unbind('mousedown', myhanda_6);
							II.$("#" + button.id).bind('mousedown', myhanda_6);

							II.$("#" + button.id).attr("data-form-id", form_id);

							//II.$("#"+button.id).attr("type","button");
							//II.$("#"+button.id).attr("type","");

							//II.$("#"+button.id).attr("name","btn_name");
						}


						buttons = $('#' + form_id).find('input[type="submit"]');
						for (index = 0; index < buttons.length; ++index) {
							indb++;
							button = buttons[index];
							button.id = (this.getAttribute("data-s3-anketa-id") || 'shop2-order-options') + "_btn_" + indb;
							//II.$("#"+button.id).unbind('click', myhanda_6);
							//II.$("#"+button.id).bind('click', myhanda_6);

							II.$("#" + button.id).unbind('mousedown', myhanda_6);
							II.$("#" + button.id).bind('mousedown', myhanda_6);

							II.$("#" + button.id).attr("data-form-id", form_id);

							//II.$("#"+button.id).attr("type","button");
							//II.$("#"+button.id).attr("type","");

							//II.$("#"+button.id).attr("name","btn_name");
						}

					}
				}
		});
	}

	window.addEventListener('DOMSubtreeModified', funca_6);
}