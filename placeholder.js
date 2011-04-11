/**
 * HTML5 Placeholder Patch
 *
 * Adds support for the HTML5 placeholder attribute to
 * browsers which do not natively support it.
 *
 * @author     James Brumond
 * @version    0.1.2-a
 * @copyright  Copyright 2010 James Brumond
 * @license    Dual licensed under MIT and GPL
 */

(function(window, undefined) {

if (document.getElementsByTagName) {
	
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
		
		// The color to make the placeholder text
		var placeholderColor = '#aaa',
		
		/**
		 * Read a style property from an element
		 *
		 * @access  public
		 * @param   node      the element
		 * @param   string    the property to read
		 * @return  string
		 */
		getStyle = (function() {
			// Get a composite style property
			var getComposite = (function() {
				// Composite properties
				var composites = {
					margin: [ 'Top', 'Right', 'Bottom', 'Left' ],
					padding: [ 'Top', 'Right', 'Bottom', 'Left' ],
					borderTop: [ 'Width', 'Color', 'Style' ],
					borderRight: [ 'Width', 'Color', 'Style' ],
					borderBottom: [ 'Width', 'Color', 'Style' ],
					borderLeft: [ 'Width', 'Color', 'Style' ],
					background: [ 'Color', 'Image', 'Repeat', 'Attachment', 'Position' ],
					font: [ 'Style', 'Variant', 'Weight', 'Size', 'Family' ],
					listStyle: [ 'Type', 'Position', 'Image' ],
					outline: [ 'Color', 'Style', 'Width' ]
				}
				// The actual getComposite function
				return function(elem, prop) {
					if (prop in composites) {
						var segs = composites[prop], result = [ ];
						for (var i = 0; i < segs.length; i++) {
							result[i] = getStyle(elem, prop + segs[i], true);
						}
						return result.join(' ');
					}
					return null;
				};
			}()),
			// Converts "some-property" to "someProperty"
			toCamelCase = function(prop) {
				prop = prop.split('-');
				for (var i = 1; i < prop.length; i++) {
					var ch = prop[i][0];
					prop[i] = ch.toUpperCase() + prop[i].substring(1);
				}
				return prop.join('');
			},
			// Do the actual getting
			getStyleValue = function(e, p) {
				var style = null;
				if (e.currentStyle) {
					style = e.currentStyle[p];
				} else if (window.getComputedStyle) {
					style = document.defaultView.getComputedStyle(e, null)[p];
				} else if (p in e.style) {
					style = e.style[p];
				}
				return style;
			};
			// The actual getStyle function
			return function(elem, prop, comp) {
				var prop = toCamelCase(prop || ''),
				style = (comp) ? null : getComposite(elem, prop);
				if (style == null) {
					if (elem.parentNode == null) {
						var body = document.getElementsByTagName('body')[0];
						elem = body.appendChild(elem);
						style = getStyleValue(elem, prop);
						elem = body.removeChild(elem);
					} else {
						style = getStyleValue(elem, prop);
					}			
				}
				return style;
			};
		}()),

		// Sets some style properties
		setStyle = function(elem, styles) {
			for (var i in styles) {
				if (styles.hasOwnProperty(i)) {
					elem.style[i] = styles[i];
				}
			}
		}
		
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
			var offset = getOffset(input), span,
			zIndex = getStyle(input, 'zIndex') || 99999;
			span = html('<span>' + text + '</span>');
			setStyle(span, {
				position: 'absolute',
				display: 'inline-block',
				color: placeholderColor,
				margin: 0,
				padding: 0,
				top: offset.top + 'px',
				left: offset.left + 'px',
				cursor: text,
				zIndex: zIndex,
				font: getStyle(input, 'font'),
				background: '#fff'
			});

			// Fix an IE positioning bug with offsetParent.style.position == static (#1)
			if (input.offsetParent && getStyle(input.offsetParent, 'position') === 'static') {
				input.offsetParent.style.position = 'relative';
			}
				
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
			if (obj.addEventListener) {
				obj.addEventListener(evt, fn, false);
			} else if (obj.attachEvent) {
				obj.attachEvent('on' + evt, fn);
			}
		},
		
		// Makes sure that the placeholder is in the right place
		repositionPlaceholder = function(placeholder) {
			var input = placeholder.relatedInput;
		};
		
		// Run through each of the elements
		window.fixPlaceholder = function(inputs) {
			// Every <input/> in the document
			var inputs = inputs || document.getElementsByTagName('input');
			if (typeof inputs.nodeType === 'number') inputs = [ inputs ];
			// Loop though, adding placeholders
			for (var i = 0; i < inputs.length; i++) {
				var input = inputs[i], placeholder;
				if (input.type && (input.type === 'text' || input.type === 'password')) {
					placeholder = input.placeholder || input.getAttribute('placeholder');
					if (typeof placeholder === 'string' && placeholder.length) {
						setPlaceholder(input, placeholder);
					}
				}
			}
		};
		
		addEventSimple(window, 'load', function() {
			window.fixPlaceholder();
		});
	
	} else {
	
		window.fixPlaceholder = function() { };
	
	}

}

}(window));

/* End of file placeholder.js */
