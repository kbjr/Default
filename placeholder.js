/**
 * HTML5 Placeholder Patch
 *
 * Adds support for the HTML5 placeholder attribute to
 * browsers which do not natively support it.
 *
 * @author     James Brumond
 * @version    0.1.1-a
 * @copyright  Copyright 2010 James Brumond
 * @license    Dual licensed under MIT and GPL
 */

(function(window, undefined) {

if ('getElementsByTagName' in document) {
	
	// Test for native placeholder support
	var supportsPlaceholder = (function() {
		var input = document.createElement('input');
		input.type = 'text';
		var r = ('placeholder' in input);
		input = null;
		return r;
	}());
	
	// If there is no native support, build it
	if (! supportsPlaceholder) {

		// Every <input/> in the document
		var inputs = document.getElementsByTagName('input'),
		
		// The color to make the placeholder text
		placeholderColor = '#aaa',
		
		// Builds composite styles
		getComposite = function(elem, prop) {
			switch (prop) {
				case 'margin': return [
					getStyle(elem, 'marginTop', true),
					getStyle(elem, 'marginRight', true),
					getStyle(elem, 'marginBottom', true),
					getStyle(elem, 'marginLeft', true)
				].join(' '); break;
				case 'padding': return [
					getStyle(elem, 'paddingTop', true),
					getStyle(elem, 'paddingRight', true),
					getStyle(elem, 'paddingBottom', true),
					getStyle(elem, 'paddingLeft', true)
				].join(' '); break;
				default: return false; break;
			}
		},
		
		// Gets an element's style property
		getStyle = function(elem, prop) {
			var style = (arguments[2]) ? false : getComposite(elem, prop);
			if (! style) {
				if (elem.currentStyle)
					style = elem.currentStyle[prop];
				else if (window.getComputedStyle)
					style = document.defaultView.getComputedStyle(elem, null)[prop];
				else if (elem.style && prop in elem.style)
					style = elem.style[prop];
			}
			return style;
		},
		
		// Gets the offset position of an element
		getOffset = function(input) {
			var top = input.offsetTop + parseFloat(getStyle(input, 'paddingTop')),
			left = input.offsetLeft + parseFloat(getStyle(input, 'paddingLeft'));
			return { top: top, left: left };
		},
		
		// Generates an element from some markup
		html = function(markup) {
			if (typeof markup === 'string') {
				if (markup.indexOf('<') !== -1) {
					var elem, wrapper = document.createElement('div');
					wrapper.innerHTML = markup;
					elem = wrapper.removeChild(wrapper.firstChild);
					wrapper = null;
					return elem;
				} else {
					var elem = document.createElement(markup);
				}
			}
		},
		
		// Sets placeholders
		setPlaceholder = function(input, text) {
			if (text === undefined) {
				var text, placeholder = input.placeholder || input.getAttribute('placeholder');
				if (typeof placeholder === 'string' && placeholder.length) {
					text = placeholder;
				} else {
					text = false;
				}
			}
			
			// Build the span element
			var offset = getOffset(input),
			span = html('<span style="position: absolute; display: inline-block; color: ' + placeholderColor +
				'; margin: 0; padding: 0; top: ' + offset.top + 'px; left: ' + offset.left + 'px; cursor: text;">' +
				text + '</span>');
				
			// Link the placeholder and input elements
			span.relatedInput = input;
			input.relatedSpan = span;
			input.placeholderVisible = true;
			
			// Insert the placeholder in the DOM
			input.parentNode.appendChild(span);
			
			// Event functions
			var
			onresize = function() {
				repositionPlaceholder(span);
			},
			onfocus = function() {
				hidePlaceholder(input);
				input.focus();
			},
			onblur = function() {
				if (! input.value.length) {
					showPlaceholder(input);
				}
			};
			
			// Set event handlers
			addEventSimple(window, 'resize', onresize);
			addEventSimple(span, 'click', onfocus);
			addEventSimple(input, 'focus', onfocus);
			addEventSimple(input, 'blur', onblur);
		},
		
		// Hides an input's placeholder
		hidePlaceholder = function(input) {
			if (input.placeholderVisible) {
				input.parentNode.removeChild(input.relatedSpan);
				input.placeholderVisible = false;
			}
		},
		
		// Shows an input's placeholder
		showPlaceholder = function(input) {
			if (! input.placeholderVisible) {
				input.parentNode.appendChild(input.relatedSpan);
				input.placeholderVisible = true;
			}
		},
		
		// Attaches an event handler
		addEventSimple = function(obj, evt, fn) {
			if (obj.addEventListener)
				obj.addEventListener(evt, fn, false);
			else if (obj.attachEvent)
				obj.attachEvent('on' + evt, fn);
		},
		
		// Makes sure that the placeholder is in the right place
		repositionPlaceholder = function(placeholder) {
			var input = placeholder.relatedInput;
		};
		
		// Run through each of the elements
		window.fixPlaceholder = function(inputs) {
			for (var i = 0; i < inputs.length; i++) {
				var input = inputs[i], placeholder;
				if (input.type && (input.type === 'text' || input.type === 'password')) {
					placeholder = input.placeholder || input.getAttribute('placeholder');
					if (typeof placeholder === 'string' && placeholder.length) {
						setPlaceholder(input);
					}
				}
			}
		};
		
		addEventSimple(window, 'load', function() {
			window.fixPlaceholder(inputs);
		});
	
	} else {
	
		window.fixPlaceholder = function() { };
	
	}

}

}(window));

/* End of file placeholder.js */
