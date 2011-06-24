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

	overlay: (function() {
		var $cover = jQuery('<div>', {'class': 'fee-loading'})
			.css('background-image', 'url(' + FrontEndEditor.data.spinner + ')')
			.hide()
			.prependTo(jQuery('body'));

		return {
			cover: function($el) {
				var $bgcolor;
				$el.parents().each(function() {
					$bgcolor = jQuery(this).css('background-color');
					if ( 'transparent' !== $bgcolor )
						return false;
				});

				$cover
					.css({
						'width': $el.width(),
						'height': $el.height(),
						'background-color': $bgcolor
					})
					.css($el.offset())
					.show();
			},

			hide: function() {
				$cover.hide();
			}
		};
	}()),

	edit_lock: function() {
		this._editing = true;
	},

	edit_unlock: function() {
		this._editing = false;
	},

	is_editing: function() {
		return this._editing;
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


jQuery(function() {

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

				if ( null === value ) {
					value = '';
				}

				data[ attr.name.substr(5) ] = value;
			}
		}

		return data;
	}

	function get_dims($el) {
		return {
			'width': $el.width(),
			'height': $el.height()
		};
	}

	// Init hover methods
	var hover_init;

	(function () {
		var
			HOVER_BORDER = 2,
			HOVER_PADDING = 2,
			hover_lock = false,
			hover_timeout,
			hover_border,
			hover_box,
			mouse_vert_pos;

		// Init hover border
		hover_border = jQuery('<div>')
			.addClass('fee-hover-border')
			.css('width', HOVER_BORDER)
			.hide().appendTo('body');

		// Init hover box
		hover_box = jQuery('<div>', {
			'class': 'fee-hover-edit',
			'html': FrontEndEditor.data.edit_text,
			'mouseover': function () { hover_lock = true; },
			'mouseout': function () { hover_lock = false; hover_hide(); }
		}).hide().appendTo('body');

		function box_position_vert() {
			var normal_height = mouse_vert_pos - hover_box.outerHeight()/2;
			hover_box.css('top', (normal_height - HOVER_BORDER) + 'px');
		}

		function hover_hide_immediately() {
			hover_box.hide();
			hover_border.hide();
		}

		function hover_hide() {
			hover_timeout = setTimeout(function () {
				if ( hover_lock ) {
					return;
				}

				hover_hide_immediately();
			}, 300);
		}

		function hover_show(callback) {
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

			// Position 'Edit' box
			hover_box.css('left', (offset.left - hover_box.outerWidth() - HOVER_PADDING) + 'px');
			box_position_vert();
			hover_box.show();

			// Position hover border
			hover_border.css({
				'left': (offset.left - HOVER_PADDING - HOVER_BORDER) + 'px',
				'top': (offset.top - HOVER_PADDING - HOVER_BORDER) + 'px',
				'height': (dims.height + HOVER_PADDING * 2) + 'px'
			}).show();
		}

		hover_init = function ($el, callback) {
			$el.bind({
				mouseover: function (ev) {
					if ( FrontEndEditor.is_editing() )
						return;

					mouse_vert_pos = ev.pageY;
					hover_show.call(this, callback);
				},

				mousemove: function (ev) {
					mouse_vert_pos = ev.pageY;
					box_position_vert();
				},

				mouseout: hover_hide
			});
		};
	}());

	// Text widget fields hack
	jQuery('[data-filter="widget_title"], [data-filter="widget_text"]').each(function() {
		var
			$el = jQuery(this),
			id = $el.closest('.widget_text').attr('id');

		if ( id )
			$el.attr('data-widget_id', id);
		else
			// undo wrap; can't find widget id
			$el.replaceWith($el.html());
	});

	// Create field instances
	jQuery('.fee-field').each(function () {
		var	$el = jQuery(this),
			data = extract_data_attr(this),
			editor;

		if ( !FrontEndEditor.is_field_defined(data.type) ) {
			if ( undefined !== console ) {
				console.warn('invalid field type', this);
			}
			return;
		}
	
		editor = FrontEndEditor.get_field_instance(data.type);

		editor = jQuery.extend(editor, {
			el: $el,
			data: data,
			filter: data.filter,
			type: data.type
		});
		editor.start();

		hover_init( $el, jQuery.proxy(editor, 'start_editing') );
	});
});
