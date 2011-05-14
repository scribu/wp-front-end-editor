jQuery.extend( FrontEndEditor, {
	fieldTypes: {},

	define_field: function(field_name, field_ancestor, methods) {
		var ancestor = field_ancestor ? this.fieldTypes[field_ancestor] : Class;

		this.fieldTypes[field_name] = ancestor.extend(methods);
	},

	is_field_defined: function(field_name) {
		return Boolean(this.fieldTypes[field_name]);
	},

	get_field_instance: function(field_name) {
		return new this.fieldTypes[field_name]();
	},

	overlay: function($el) {
		var
			$cover = jQuery('<div>', {'class': 'fee-loading'})
				.css('background-image', 'url(' + this.data.spinner + ')')
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

			jQuery.post(this.data.ajax_url, data, function(data) {
				content = data;
				proceed();
			}, 'json');
		};
	}())
});


jQuery(function($) {

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
		var
			HOVER_BORDER = 2,
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

		function get_dims($el) {
			return {
				'width': $el.width(),
				'height': $el.height()
			};
		}

		hover_hide = function () {
			hover_timeout = setTimeout(function () {
				if ( hover_lock )
					return;

				hover_hide_immediately();
			}, 300);
		};

		hover_show = function (callback) {
			var
				$self = jQuery(this),
				offset = $self.offset(),
				dims = get_dims($self);

			// Webkit really doesn't like block elements inside inline elements
			if ( dims.width > $self.parent().width() ) {
				$self.css('display', 'block');
				dims = get_dims($self);
			}

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
			var position = {
				'left': (offset.left - HOVER_PADDING - HOVER_BORDER) + 'px',
				'top': (offset.top - HOVER_PADDING - HOVER_BORDER) + 'px'
			};

			hover_borders.top
				.css(position)
				.css({
					'width': (dims.width + HOVER_PADDING * 2) + 'px',
					'height': HOVER_BORDER
				})
				.show();

			hover_borders.left
				.css(position)
				.css({
					'height': (dims.height + HOVER_PADDING * 2) + 'px',
					'width': HOVER_BORDER
				})
				.show();
		};
	}());

	// Create field instances
	jQuery.each(FrontEndEditor.data.fields, function (i, filter) {
		jQuery('.fee-filter-' + filter).each(function () {
			var
				$el = jQuery(this),
				data = extract_data_attr(this),
				editor;

			if ( !FrontEndEditor.is_field_defined(data.type) ) {
				if ( undefined !== console )
					console.warn('invalid field type', this);
				return;
			}

			editor = FrontEndEditor.get_field_instance(data.type);

			editor = jQuery.extend(editor, {
				el: $el,
				data: data,
				filter: filter,
				type: data.type
			});
			editor.start();

			// Bind hover to element
			$el.mouseout(hover_hide)
			   .mouseover(function () {
				hover_show.call( this, jQuery.proxy(editor, 'start_editing') );
			});
		});
	});
});
