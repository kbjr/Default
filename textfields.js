/*
 * textfields.js
 * Author: James Brumond
 * Date Created: 2 June 2010
 * Date Last Mod: 3 June 2010
 *
 * Copyright 2010 James Brumond
 * Dual licensed under MIT and GPL
 */

(function(window, $) {

var

_hasOwn = function(o, i) {
	if (typeof jsk != 'undefined')
		return jsk.helpers.hasOwnProperty(o, i);
	if (o.hasOwnProperty)
		return o.hasOwnProperty(i);
	return null;
},

_forOwn = function(obj, func) {
	for (var i in obj) {
		if (_hasOwn(obj, i)) {
			func(i, obj[i]);
		}
	}
},

_elem = function(tag, options) {
	var elem = document.createElement(tag),
	options = options || { };
	_forOwn(options, function(i) {
		switch (i) {
			case "style":
				setStyle(elem, options[i]);
				break;
			default: elem[i] = options[i]; break;
		}
	});
	return elem;
},

setStyle = setStyle || (function(elem, props) {
	_forOwn(props, function(i) {
		elem.style[i] = props[i];
	});
}),

getStyle = function(elem, styleProp) {
	var style = null;
	if (elem.currentStyle)
		style = elem.currentStyle[styleProp];
	else if (window.getComputedStyle)
		style = document.defaultView.getComputedStyle(elem, null)[styleProp];
	return style;
};

$.fn.defaultText = function(defText, styleOverride) {
	var styleOveride = styleOverride || { };
	return this.filter('input[type=text], input[type=password]').each(function() {
		// build the text element and put it in the document
		var input = this,
		text = (defText == '!') ? input.title : defText,
		span = input.parentNode.appendChild(_elem('span', {
			style: {
				position: 'absolute',
				padding: '0px',
				margin: '0px',
				fontFamily: getStyle(input, 'fontFamily'),
				fontSize: getStyle(input, 'fontSize'),
				fontWeight: getStyle(input, 'fontWeight'),
				color: getStyle(input, 'color'),
				width: input.clientWidth + 'px',
				cursor: 'text',
				backgroundColor: getStyle(input, 'backgroundColor')
			},
			innerHTML: text
		})),
		// define the click/focus event function
		click = function() {
			$(span).hide();
			input.focus();
		},
		position = function() {
			span.style.top = parseInt(getStyle(input, 'paddingTop')) +
				parseInt(getStyle(input, 'borderTopWidth')) + input.offsetTop + 'px';
			span.style.left = parseInt(getStyle(input, 'paddingLeft')) +
				parseInt(getStyle(input, 'borderLeftWidth')) + input.offsetLeft + 'px';
		};
		setStyle(span, styleOverride);
		// change/add input properties
		if (input.defValue) input.parentNode.removeChild(input.defValue);
		input.defValue = span;
		// add the click/focus event
		$(input).add(span).focus(function() { click(); }).click(function() { click(); });
		$(input).blur(function() {
			if (input.value == '') {
				$(span).show();
			}
		});
		// add a window.resize event to make sure the positioning doesn't change
		$(window).resize(position);
		window.setTimeout(position, 20);
	});
};

}(window, jQuery));
