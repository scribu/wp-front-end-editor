(function($){

	if ( FrontEndEditor._loaded )
		return;
	FrontEndEditor._loaded = true;

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


	var DoubleClick = {

		_event: false,
		_delayed: false,

		register: function($el, callback) {
			$el.bind({
				click	: DoubleClick.click,
				dblclick: DoubleClick.dblclick
			});
			
			$el.dblclick(callback);
		},

		click: function(ev) {
			// TODO: instead of 'click', capture the 'submit' event
			if ( $(ev.target).is('select, option, input, button') )
				return;

			if ( DoubleClick._delayed )
				return;

			ev.stopImmediatePropagation();
			ev.preventDefault();

			if ( DoubleClick._event )
				return;

			DoubleClick._event = ev;

			setTimeout(DoubleClick.resume, 300);
		},

		resume: function() {
			if ( !DoubleClick._event )
				return;

			var $target = $(DoubleClick._event.target);

			var new_event = $.Event('click');

			DoubleClick._delayed = true;

			$target.trigger(new_event);

			DoubleClick._delayed = false;

			if ( new_event.isDefaultPrevented() )
				return;

			var $link = $target.closest('a');

			if ( !$link.length )
				return;

			if ( typeof $link.attr('href') != 'undefined' && $link.attr('href') != '#' ) {
				if ( $link.attr('target') == '_blank' )
					window.open($link.attr('href'));
				else
					window.location.href = $link.attr('href');
			}

			DoubleClick._event = false;
		},

		dblclick: function(ev) {
			ev.stopPropagation();
			ev.preventDefault();

			// cancel delayed click
			DoubleClick._event = false;
		}
	};


	function Overlay($el) {

		var $cover = $('<div class="fee-loading>')
			.css('background-image', 'url(' + FrontEndEditor.data.spinner + ')')
			.hide()
			.prependTo($('body'));

		this.show = function() {
			$cover
				.css({
					width: $el.width(),
					height: $el.height()
				})
				.css($el.offset())
				.show();
		};

		this.hide = function() {
			$cover.hide();
		};
	};

	// Do an ajax request, while loading a required script
	function sync_load(callback, data, src) {
		var count = 0, content;

		function proceed() {
			count++;
			if ( 2 == count )
				callback(content);
		}

		if ( !src || sync_load.cache[src] ) {
			proceed();
		} else {
			sync_load.cache[src] = $('<script>').attr({
				type: 'text/javascript', 
				src: src,
				load: proceed
			}).prependTo('head');
		}

		$.post(FrontEndEditor.data.ajax_url, data, function(data) {
			content = data;
			proceed();
		});
	}
	sync_load.cache = [];

	// Create a new nicEditor instance and return it
	function init_nicEdit(id) {
		tmp = new nicEditor(FrontEndEditor.data.nicedit).panelInstance(id);
		return tmp.nicInstances[0];
	}

	var fieldTypes = {};

	fieldTypes['base'] = Class.extend({
		dependency: null, // script src

		init: function($el, type, filter, data) {
			var self = this;

			self.set_el($el);
			self.type = type;
			self.filter = filter;
			self.data = data;

			DoubleClick.register(self.el, $.proxy(self, 'dblclick'));
		},

		set_el: function($el) {
			var self = this;

			self.el = $el;

			// From a > .front-ed > content
			// To .front-ed > a > content
			var $parent = self.el.parents('a');

			if ( ! $parent.length )
				return;

			var $link = $parent.clone(true)
				.html(self.el.html());

			var $wrap = self.el.clone(true)
				.html($link);

			$parent.replaceWith($wrap);

			self.el = $wrap;
			self.switched = true;
		},

		create_input: null,

		content_to_input: null,
		content_from_input: null,

		content_to_front: null,

		ajax_get_handler: null,
		ajax_set_handler: null,

		ajax_args: function(args) {
			var self = this;

			return $.extend(args, {
				action	: 'front-end-editor',
				nonce	: FrontEndEditor.data.nonce,
				filter	: self.filter,
				data	: self.data
			});
		},

		ajax_get: function() {
			var self = this;

			var data = self.ajax_args({
				callback: 'get'
			});

			sync_load($.proxy(self, 'ajax_get_handler'), data, self.dependency);
		},

		ajax_set: function(content) {
			var self = this;

			var data = self.ajax_args({
				callback: 'save', 
				content: content || self.content_from_input()
			});

			$.post(FrontEndEditor.data.ajax_url, data, $.proxy(self, 'ajax_set_handler'));
		}
	});


	fieldTypes['image'] = fieldTypes['base'].extend({
		
		dblclick: function(ev) {
			var self = this;

			tb_show(FrontEndEditor.data.image.change, FrontEndEditor.data.admin_url +
				'/media-upload.php?post_id=0&type=image&TB_iframe=true&width=640&editable_image=1');

			$('<a id="fee-img-revert" href="#">')
				.text(FrontEndEditor.data.image.revert)
				.click(function(ev) {
					self.ajax_set(-1);
				})
				.insertAfter('#TB_ajaxWindowTitle');

			$('#TB_closeWindowButton img').attr('src', FrontEndEditor.data.image.tb_close);

			$('#TB_iframeContent').load($.proxy(self, 'replace_button'));
		},

		replace_button: function(ev) {
			var self = this;

			var $frame = $(ev.target).contents();

			$('.media-item', $frame).livequery(function(){
				var $item = $(this);
				var $button = $('<a href="#" class="button">')
					.text(FrontEndEditor.data.image.change)
					.click(function(ev){
						self.ajax_set(self.content_from_input($item));
					});

				$item.find(':submit, #go_button').replaceWith($button);
			});
		},

		content_from_input: function($item) {
			var $field;

			// Media library
			$field = $item.find('.urlfile');
			if ( $field.length )
				return $field.attr('title');

			// From URL (embed)
			$field = $item.find('#embed-src');
			if ( $field.length )
				return $field.val();

			// From URL
			$field = $item.find('#src');
			if ( $field.length )
				return $field.val();

			return false;
		},

		ajax_set_handler: function(url) {
			var self = this;

			if ( url == -1 ) {
				window.location.reload(true);
			} else {
				self.el.find('img').attr('src', url);
				tb_remove();
			}
		}
	});

	fieldTypes['thumbnail'] = fieldTypes['image'].extend({
		
		replace_button: function(ev) {
			var self = this;

			var $frame = $(ev.target).contents();

			$frame.find('#tab-type_url').remove();

			self._super(ev);
		},

		content_from_input: function($item) {
			return $item.attr('id').replace('media-item-', '');
		}
	});

	fieldTypes['input'] = fieldTypes['base'].extend({

		input_tag: '<input type="text">',

		init: function($el, type, name, id) {
			var self = this;

			self._super($el, type, name, id);

			self.overlay = new Overlay(self.el);
		},

		create_input: function() {
			var self = this;

			self.input = $(self.input_tag).attr({
				'id'    : 'fee-' + new Date().getTime(),
				'class' : 'fee-form-content'
			});

			self.input.prependTo(self.form);
		},

		content_to_input: function(content) {
			var self = this;

			self.input.val(content);
		},

		content_from_input: function() {
			var self = this;

			return self.input.val();
		},

		content_to_front: function(content) {
			var self = this,
				$el = self.switched ? self.el.find('a') : self.el;

			$el.html(content);
		},

		ajax_get: function() {
			var self = this;

			self.overlay.show();

			self.create_input();

			self._super();
		},

		ajax_set: function() {
			var self = this;

			self.overlay.show();

			self._super();
		},

		ajax_get_handler: function(content) {
			var self = this;

			self.overlay.hide();
			self.el.hide().after(self.form);

			self.content_to_input(content);

			self.input.focus();
		},

		ajax_set_handler: function(content) {
			var self = this;

			self.content_to_front(content);

			self.overlay.hide();
			self.el.show();
		},

		dblclick: function(ev) {
			var self = this;

			// Buttons
			self.save_button = $('<button>')
				.addClass('fee-form-save')
				.text(FrontEndEditor.data.save_text)
				.click($.proxy(self, 'form_submit'));

			self.cancel_button = $('<button>')
				.addClass('fee-form-cancel')
				.text(FrontEndEditor.data.cancel_text)
				.click($.proxy(self, 'form_remove'));

			// Form
			self.form = ( self.type.indexOf('input') >= 0 ) ? $('<span>') : $('<div>');

			self.form
				.addClass('fee-form')
				.addClass('fee-type-' + self.type)
				.addClass('fee-filter-' + self.filter)
				.append(self.save_button)
				.append(self.cancel_button);

			self.form.bind('keypress', $.proxy(self, 'keypress'));

			self.ajax_get();
		},

		form_remove: function(ev) {
			var self = this;

			self.remove_form(false);

			ev.stopPropagation();
			ev.preventDefault();
		},

		form_submit: function(ev) {
			var self = this;
		
			self.ajax_set();
			self.remove_form(true);
			
			ev.stopPropagation();
			ev.preventDefault();
		},

		remove_form: function(with_spinner) {
			var self = this;

			self.form.remove();

			self.el.show();

			if ( true === with_spinner )
				self.overlay.show();
		},

		keypress: function(ev) {
			var self = this;

			var keys = {ENTER: 13, ESCAPE: 27};
			var code = (ev.keyCode || ev.which || ev.charCode || 0);

			if ( code == keys.ENTER && 'input' == self.type )
				self.save_button.click();

			if ( code == keys.ESCAPE )
				self.cancel_button.click();
		}
	});

	fieldTypes['terminput'] = fieldTypes['input'].extend({
		dependency: FrontEndEditor.data.suggest.src,

		content_to_input: function(content) {
			var self = this;

			self._super(content);

			self.input.suggest(FrontEndEditor.data.ajax_url + '?action=ajax-tag-search&tax=' + self.data.taxonomy, {
				multiple		: true,
				resultsClass	: 'fee-suggest-results',
				selectClass		: 'fee-suggest-over',
				matchClass		: 'fee-suggest-match'
			});
		}
	});

	fieldTypes['checkbox'] = fieldTypes['input'].extend({
		input_tag: '<input type="checkbox">',

		content_to_input: function(content) {
			var self = this;

			content = content ? 'checked' : '';

			self.input.attr('checked', content);
		},

		content_from_input: function() {
			var self = this;

			return 0 + self.input.is(':checked');
		},

		content_to_front: function() {
			var self = this,
				$el = self.switched ? self.el.find('a') : self.el,
				content = self.data.values[ self.content_from_input() ];

			$el.html(content);
		}
	});

	fieldTypes['select'] = fieldTypes['input'].extend({
		input_tag: '<select>',

		content_to_input: function(content) {
			var self = this;

			$.each(self.data.values, function(value, title) {
				var $option = $('<option>')
					.attr({
						html: value,
						value: value,
						selected: (content == value) ? 'selected': ''
					})
					.html(title);

				self.input.append($option);
			});
		},

		content_from_input: function() {
			var self = this;

			return self.input.find(':selected').val();
		}
	});

	fieldTypes['textarea'] = fieldTypes['input'].extend({
		input_tag: '<textarea rows="10">'
	});

	fieldTypes['rich'] = fieldTypes['textarea'].extend({
		dependency: FrontEndEditor.data.nicedit.src,

		content_to_input: function(content) {
			var self = this;

			self._super(content);

			self.editor = init_nicEdit(self.input.attr('id'));

			self.form.find('.nicEdit-main').focus();
		},

		content_from_input: function() {
			var self = this;

			return self.pre_wpautop(self.input.val());
		},

		// Copied from wp-admin/js/editor.dev.js
		pre_wpautop: function(content) {
			var blocklist1, blocklist2;

			// Protect pre|script tags
			content = content.replace(/<(pre|script)[^>]*>[\s\S]+?<\/\1>/g, function(a) {
				a = a.replace(/<br ?\/?>[\r\n]*/g, '<wp_temp>');
				return a.replace(/<\/?p( [^>]*)?>[\r\n]*/g, '<wp_temp>');
			});

			// Pretty it up for the source editor
			blocklist1 = 'blockquote|ul|ol|li|table|thead|tbody|tfoot|tr|th|td|div|h[1-6]|p|fieldset';
			content = content.replace(new RegExp('\\s*</('+blocklist1+')>\\s*', 'g'), '</$1>\n');
			content = content.replace(new RegExp('\\s*<(('+blocklist1+')[^>]*)>', 'g'), '\n<$1>');

			// Mark </p> if it has any attributes.
			content = content.replace(/(<p [^>]+>.*?)<\/p>/g, '$1</p#>');

			// Sepatate <div> containing <p>
			content = content.replace(/<div([^>]*)>\s*<p>/gi, '<div$1>\n\n');

			// Remove <p> and <br />
			content = content.replace(/\s*<p>/gi, '');
			content = content.replace(/\s*<\/p>\s*/gi, '\n\n');
			content = content.replace(/\n[\s\u00a0]+\n/g, '\n\n');
			content = content.replace(/\s*<br ?\/?>\s*/gi, '\n');

			// Fix some block element newline issues
			content = content.replace(/\s*<div/g, '\n<div');
			content = content.replace(/<\/div>\s*/g, '</div>\n');
			content = content.replace(/\s*\[caption([^\[]+)\[\/caption\]\s*/gi, '\n\n[caption$1[/caption]\n\n');
			content = content.replace(/caption\]\n\n+\[caption/g, 'caption]\n\n[caption');

			blocklist2 = 'blockquote|ul|ol|li|table|thead|tbody|tfoot|tr|th|td|h[1-6]|pre|fieldset';
			content = content.replace(new RegExp('\\s*<(('+blocklist2+') ?[^>]*)\\s*>', 'g'), '\n<$1>');
			content = content.replace(new RegExp('\\s*</('+blocklist2+')>\\s*', 'g'), '</$1>\n');
			content = content.replace(/<li([^>]*)>/g, '\t<li$1>');

			if ( content.indexOf('<object') != -1 ) {
				content = content.replace(/<object[\s\S]+?<\/object>/g, function(a){
					return a.replace(/[\r\n]+/g, '');
				});
			}

			// Unmark special paragraph closing tags
			content = content.replace(/<\/p#>/g, '</p>\n');
			content = content.replace(/\s*(<p [^>]+>[\s\S]*?<\/p>)/g, '\n$1');

			// Trim whitespace
			content = content.replace(/^\s+/, '');
			content = content.replace(/[\s\u00a0]+$/, '');

			// put back the line breaks in pre|script
			content = content.replace(/<wp_temp>/g, '\n');

			return content;
		},

		ajax_set: function() {
			var self = this;

			self.editor.saveContent();

			self._super();
		}
	});


	fieldTypes['widget'] = fieldTypes['textarea'].extend({

		create_input: function() {},

		ajax_get: function() {
			var self = this;

			self.is_text_widget = ( 0 == self.data.widget_id.indexOf('text-') );

			if ( self.is_text_widget )
				self.dependency = FrontEndEditor.data.nicedit.src;

			self._super();
		},

		content_to_input: function(content) {
			var self = this;

			self.input = $(content);

			self.form.prepend(content);

			if ( self.is_text_widget ) {
				self.editor = init_nicEdit(self.form.find('textarea').attr('id'));

				self.form.find('.nicEdit-main').focus();
			}
		},

		content_from_input: function() {
			return '';
		},

		ajax_args: function(args) {
			var self = this;

			args = self._super(args);

			if ( 'get' == args.callback )
				return args;

			if ( self.is_text_widget )
				self.editor.saveContent();

			var raw_data = self.form.find(':input').serializeArray(),
				data = {};

			$.each(raw_data, function() {
				data[this.name] = this.value;
			});

			return $.extend(args, data);
		}
	});


	// export
	FrontEndEditor.fieldTypes = fieldTypes;

$(document).ready(function($) {

	// fetch all 'data-' attributes from a DOM node
	function extract_data_attr(el) {
		var data = {};

		for (var i=0; i < el.attributes.length; i++) {
			var attr = el.attributes.item(i);

			if ( attr.specified && 0 == attr.name.indexOf('data-') ) {
				var value = attr.value;

				try {
					value = $.parseJSON(value);
				} catch(e) {}

				data[attr.name.substr(5)] = value;
			}
		}

		return data;
	}

	// Create field instances
	$.each(FrontEndEditor.data.fields, function(i, filter) {
		$('.fee-filter-' + filter).each(function() {
			var $el = $(this),
				data = extract_data_attr(this),
				type = data.type;

			new fieldTypes[type]($el, type, filter, data);
		});
	});

	// Tooltip init
	if ( FrontEndEditor.data.tooltip ) {
		$('.fee-field').qtip({
			content	: FrontEndEditor.data.tooltip.text,
			position: { corner: { target: 'topMiddle' }, adjust: { x: 0, y: -40 } },
			show	: { effect: 'fade' },
			style	: {
				height: 10,
				paddingTop: '4px',
				paddingRight: '5px',
				paddingBottom: '6px',
				paddingLeft: '25px',
				background: '#bbbebf url(' + FrontEndEditor.data.tooltip.icon + ') top left no-repeat',
				color: '#ffffff',
				textAlign: 'left',
				lineHeight: '100%',
				fontFamily: 'sans-serif',
				fontSize: '14px',
				opacity: '0.75',
				border: {
					width: 0,
					radius: 5,
					color: '#bbbebf'
				},
				tip: 'bottomLeft',
				name: 'dark'
			}
		});
	}
});
})(jQuery);
