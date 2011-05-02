// http://ejohn.org/blog/simple-javascript-inheritance/
// Inspired by base2 and Prototype
(function(){
  var initializing = false, fnTest = /xyz/.test(function(){xyz;}) ? /\b_super\b/ : /.*/;

  // The base Class implementation (does nothing)
  this.Class = function(){};

  // Create a new Class that inherits from this class
  Class.extend = function(prop) {
	var _super = this.prototype;

	// Instantiate a base class (but only create the instance,
	// don't run the init constructor)
	initializing = true;
	var prototype = new this();
	initializing = false;

	// Copy the properties over onto the new prototype
	for (var name in prop) {
	  // Check if we're overwriting an existing function
	  prototype[name] = ( typeof prop[name] == "function" &&
	    typeof _super[name] == "function" && fnTest.test(prop[name]) ) ?
	    (function(name, fn){
	      return function() {
	        var tmp = this._super;

	        // Add a new ._super() method that is the same method
	        // but on the super-class
	        this._super = _super[name];

	        // The method only need to be bound temporarily, so we
	        // remove it when we're done executing
	        var ret = fn.apply(this, arguments);
	        this._super = tmp;

	        return ret;
	      };
	    })(name, prop[name]) :
	    prop[name];
	}

	// The dummy class constructor
	function Class() {
	  // All construction is actually done in the init method
	  if ( !initializing && this.init )
	    this.init.apply(this, arguments);
	}

	// Populate our constructed prototype object
	Class.prototype = prototype;

	// Enforce the constructor to be what we expect
	Class.constructor = Class;

	// And make this class extendable
	Class.extend = arguments.callee;

	return Class;
  };
})();


//_____Custom code starts here_____

jQuery.extend( FrontEndEditor, {
	fieldTypes: {},

	define_field: function(field_name, field_ancestor, methods) {
		var ancestor = field_ancestor ? FrontEndEditor.fieldTypes[field_ancestor] : Class;

		FrontEndEditor.fieldTypes[field_name] = ancestor.extend(methods);
	},

	overlay: function($el) {
		var $cover = jQuery('<div>', {'class': 'fee-loading'})
			.css('background-image', 'url(' + FrontEndEditor.data.spinner + ')')
			.hide()
			.prependTo(jQuery('body'));

		return {
			show: function() {
				$cover
					.css({
						width: $el.width(),
						height: $el.height()
					})
					.css($el.offset())
					.show();
			},

			hide: function() {
				$cover.hide();
			}
		};
	},

	// Do an ajax request, while loading a required script
	sync_load: (function(){
		var cache = [];

		return function(callback, data, src) {
			var count = 0, content;

			function proceed() {
				count++;
				if ( 2 === count )
					callback(content);
			}

			if ( !src || cache[src] ) {
				proceed();
			} else {
				cache[src] = jQuery('<script>').attr({
					type: 'text/javascript',
					src: src,
					load: proceed
				}).prependTo('head');
			}

			jQuery.post(FrontEndEditor.data.ajax_url, data, function(data) {
				content = data;
				proceed();
			}, 'json');
		};
	}())
});

jQuery(document).ready(function($) {

	// fetch all 'data-' attributes from a DOM node
	function extract_data_attr(el) {
		var i, data = {};

		for (i = 0; i < el.attributes.length; i++) {
			var attr = el.attributes.item(i);

			if ( attr.specified && 0 === attr.name.indexOf('data-') ) {
				var value = attr.value;

				try {
					value = jQuery.parseJSON(value);
				} catch(e) {}

				if ( null === value )
					value = '';

				data[ attr.name.substr(5) ] = value;
			}
		}

		return data;
	}

	// Init hover methods
	var hover_hide, hover_show;

	(function () {
		var HOVER_BORDER = 2,
			HOVER_PADDING = 2,
			hover_lock = false,
			hover_timeout,
			hover_borders = {},
			hover_box = jQuery('<div>', {
				'class': 'fee-hover-edit',
				'html': FrontEndEditor.data.edit_text,
				'mouseover': function () { hover_lock = true; },
				'mouseout': function () { hover_lock = false; hover_hide(); }
			}).hide().appendTo('body');

		jQuery.each(['top', 'left'], function(i, key) {
			hover_borders[key] = jQuery('<div>').addClass('fee-hover-' + key).hide().appendTo('body');
		});

		function hover_hide_immediately() {
			hover_box.hide();

			hover_borders.top.hide();
			hover_borders.left.hide();
		}

		hover_hide = function () {
			hover_timeout = setTimeout(function () {
				if ( hover_lock )
					return;

				hover_hide_immediately();
			}, 300);
		};

		hover_show = function (callback) {
			var $self = jQuery(this),
				offset = $self.offset(),
				dims = {
					width: $self.width(),
					height: $self.height()
				};

			clearTimeout(hover_timeout);

			hover_box.unbind('click');

			hover_box.bind('click', hover_hide_immediately);
			hover_box.bind('click', callback);

			// Add 'Edit' box
			hover_box.css({
				'top': (offset.top - HOVER_PADDING - HOVER_BORDER) + 'px',
				'left': (offset.left - hover_box.outerWidth() - HOVER_PADDING) + 'px'
			}).show();

			// Add hover as individual divs
			hover_borders.top
				.css({
					'width': (dims.width + HOVER_PADDING * 2 + HOVER_BORDER * 2) + 'px',
					'left': (offset.left - HOVER_PADDING - HOVER_BORDER) + 'px',
					'top': (offset.top - HOVER_PADDING - HOVER_BORDER) + 'px'
				})
				.show();

			hover_borders.left
				.css({
					'height': (dims.height + HOVER_PADDING * 2) + 'px',
					'top': (offset.top - HOVER_PADDING) + 'px',
					'left': (offset.left - HOVER_PADDING - HOVER_BORDER) + 'px'
				})
				.show();
		};
	}());

	// Create field instances
	jQuery.each(FrontEndEditor.data.fields, function (i, filter) {
		jQuery('.fee-filter-' + filter)
			.mouseout(hover_hide)
			.each(function () {
				var $el = jQuery(this),
					data = extract_data_attr(this),
					editor;

				if ( undefined === FrontEndEditor.fieldTypes[data.type] ) {
					if ( undefined !== console )
						console.warn('invalid field type', this);
					return;
				}

				editor = new FrontEndEditor.fieldTypes[data.type]();

				editor = jQuery.extend(editor, {
					el: $el,
					data: data,
					filter: filter,
					type: data.type
				});
				editor.start();

				$el.mouseover(function () {
					hover_show.call( this, jQuery.proxy(editor, 'start_editing') );
				});
			});
	});
});
