/*
 * jquery.defaultText.js
 * 
 * Author: James Brumond
 * Version: 0.3.1
 * Date Created: 2 June 2010
 * Date Last Mod: 12 July 2010
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

$.fn.defaultText = function(options) {
	var options = options || { };
	if (typeof options != 'string') {
		options.defText = options.defText || '';
		options.textStyle = options.textStyle || { };
		options.inputStyle = options.inputStyle || { };
		options.onfocus = options.onfocus || function() { };
		options.onblur = options.onblur || function() { };
	}
	return this.filter('input[type=text], input[type=password], textarea').each(function() {
		var input = this;
		if (typeof input._defaultText == 'undefined') {
			if (typeof options == 'string') return true;
			input._defaultText = {
				options: options,
				reset: function() {
					setStyle(input._defaultText.span, {
						position: 'absolute',
						padding: '0px',
						margin: '0px',
						fontFamily: getStyle(input, 'fontFamily'),
						fontSize: getStyle(input, 'fontSize'),
						fontWeight: getStyle(input, 'fontWeight'),
						color: getStyle(input, 'color'),
						width: getStyle(input, 'width'),
						cursor: 'text',
						backgroundColor: getStyle(input, 'backgroundColor'),
						// contribution by whatcould <http://github.com/whatcould>
						zIndex: (parseInt(getStyle(input, 'zIndex')) || 0) + 1
					});
					setStyle(span, input._defaultText.options.textStyle);
					setStyle(input, input._defaultText.options.inputStyle);
					input._defaultText.position();
				},
				span: null,
				click: null,
				position: null
			}
		}
		if (typeof options == 'string') {
			switch (options) {
				case 'reset':
					if (typeof input._defaultText.reset != 'function') return true;
					input._defaultText.reset();
					break;
				case 'destroy':
					if (typeof input._defaultText.reset != 'function') return true;
					input._defaultText.span.parentNode.removeChild(input._defaultText.span);
					input._defaultText = false;
					break;
				default: break;
			}
		} else {
			// build the text element and put it in the document
			var text = (options.defText == '') ? input.title : options.defText,
			span = input.parentNode.appendChild(_elem('span', {
				style: {
					position: 'absolute',
					padding: '0px',
					margin: '0px',
					fontFamily: getStyle(input, 'fontFamily'),
					fontSize: getStyle(input, 'fontSize'),
					fontWeight: getStyle(input, 'fontWeight'),
					color: getStyle(input, 'color'),
					width: getStyle(input, 'width'),
					cursor: 'text',
					backgroundColor: getStyle(input, 'backgroundColor'),
					// contribution by whatcould <http://github.com/whatcould>
					zIndex: (parseInt(getStyle(input, 'zIndex')) || 0) + 1
				},
				innerHTML: text,
				className: 'default'
			}));
			// define the click/focus event function
			input._defaultText.click = function() {
				$(span).hide();
				input.focus();
				options.onfocus.call(input);
			};
			input._defaultText.position = function() {
				span.style.top = parseInt(getStyle(input, 'paddingTop')) +
					parseInt(getStyle(input, 'borderTopWidth')) + input.offsetTop + 'px';
				span.style.left = parseInt(getStyle(input, 'paddingLeft')) +
					parseInt(getStyle(input, 'borderLeftWidth')) + input.offsetLeft + 'px';
			};
			if (String(input.id).length > 0) {
				span.id = 'default-' + input.id;
			}
			setStyle(span, options.textStyle);
			setStyle(input, options.inputStyle);
			// change/add input properties
			if (input.defValue) input.parentNode.removeChild(input.defValue);
			input.defValue = span;
			// add the click/focus event
			$(input).add(span).focus(function() {
				input._defaultText.click(); }).click(function() {
				input._defaultText.click();
			});
			$(input).blur(function() {
				if (input.value == '') $(span).show();
				options.onblur.call(input);
			});
			// add a window.resize event to make sure the positioning doesn't change
			$(window).resize(input._defaultText.position);
			$(window).focus(input._defaultText.position);
			// FIXME sometimes it doesn't quite take, so call
			// it a couple times
			window.setTimeout(input._defaultText.position, 50);
			window.setTimeout(input._defaultText.position, 500);
			window.setTimeout(input._defaultText.position, 3000);
			// add the span to the element's _defaultText property
			input._defaultText.span = span;
		}
	});
};

}(window, jQuery));
