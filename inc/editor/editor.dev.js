jQuery(document).ready(function($){
	if ( frontEditorData._loaded )
		return;
	frontEditorData._loaded = true;

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


	var spinner = $('<img>').attr({
		'src': frontEditorData.spinner,
		'class': 'front-editor-spinner'
	});

	var is_overlay = function($el) {
		var attr = $el.attr('id') + ' ' + $el.attr("class");
		attr = $.trim(attr).split(' ');

		var tokens = ['lightbox', 'shutter', 'thickbox', 'awppost_link'];

		for ( i in tokens )
			for ( j in attr )
				if ( attr[j].indexOf(tokens[i]) != -1 )
					return true;

		return false;
	};

	var resume = function() {
		if ( frontEditorData._trap )
			return;

		var $link = frontEditorData._to_click;

		if ( typeof $link == 'undefined' )
			return;

/*
		var ev_reference;
		var ev_capture = function(ev) {	ev_reference = ev; }

		var onClick = $link.attr('onclick');

		$link.bind('click', ev_capture);

		if ( typeof onClick == 'function' )
			$link.bind('click', onClick);

		$link.click();

		$link.unbind('click', ev_capture);

		if ( typeof onClick == 'function' )
			$link.unbind('click', onClick);

		if ( ev_reference.isDefaultPrevented() )
			return;
*/

		if ( typeof $link.attr('href') != 'undefined' && $link.attr('href') != '#' ) {
			if ( $link.attr('target') == '_blank' )
				window.open($link.attr('href'));
			else
				window.location.href = $link.attr('href');
		}

		delete frontEditorData._to_click;
	}

	var classes = [];

	classes['base'] = Class.extend({
		init: function($el, type, name, id) {
			var self = this;

			self.set_el($el);
			self.type = type;
			self.name = name;
			self.id = id;

			self.bind(self.el, 'click', self.click);
			self.bind(self.el, 'dblclick', self.dblclick);
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
		},

		click: function(ev) {
//			if ( typeof frontEditorData._to_click != 'undefined' )
//				return;

			var $el = $(ev.target).closest('a');

			if ( ! $el.length )
				return;

			if ( is_overlay($el) )
				return;

			ev.stopImmediatePropagation();
			ev.preventDefault();

			frontEditorData._to_click = $el;

			setTimeout(resume, 300);
		},

		dblclick: function(ev) {
			var self = this;

			ev.stopPropagation();
			ev.preventDefault();

			frontEditorData._trap = true;
		},

		get_content: null /* function() */,
		set_content: null /* function(content) */,

		ajax_get_handler: null /* function(content) */,
		ajax_set_handler: null /* function(content) */,

		ajax_get: function() {
			var self = this;

			var data = {
				'nonce': frontEditorData.nonce,
				'action': 'front-editor',
				'callback': 'get',
				'name': self.name,
				'type': self.type,
				'item_id': self.id
			};

			$.post(frontEditorData.request, data, function(response){
				self.ajax_get_handler(response);
			});
		},

		ajax_set: function(content) {
			var self = this;

			content = content || self.get_content();

			var data = {
				'nonce': frontEditorData.nonce,
				'action': 'front-editor',
				'callback': 'save',
				'name': self.name,
				'type': self.type,
				'item_id': self.id,
				'content': content
			};

			$.post(frontEditorData.request, data, function(response){
				self.ajax_set_handler(response);
			});
		},

		// Event utility: this = self
		bind: function(element, event, callback) {
			var self = this;

			element.bind(event, function(ev) {
				callback.call(self, ev);
			});
		}
	});

	classes['image'] = classes['base'].extend({
		dblclick: function(ev) {
			var self = this;

			self._super(ev);

			self.open_box();
		},

		open_box: function() {
			var self = this;

			tb_show(frontEditorData.caption, frontEditorData.admin_url +
				'/media-upload.php?type=image&TB_iframe=true&width=640&editable_image=1');

			var $revert = $('<a id="fee_img_revert" href="#">').text(frontEditorData.img_revert);
			
			$revert.click(function(ev){
				self.ajax_set(-1);
			});

			$('#TB_ajaxWindowTitle').after($revert);
			$('#TB_closeWindowButton img').attr('src', frontEditorData.tb_close);

			self.bind($('#TB_iframeContent'), 'load', self.replace_button);
		},

		replace_button: function(ev) {
			var self = this;

			var $frame  = $(ev.target).contents();

			$('.describe', $frame).livequery(function(){
				var $item = $(this);
				var $button = $('<a href="#" class="button">').text(frontEditorData.caption);

				$button.click(function(ev){
					self.ajax_set(self.get_content($item));
				});

				$(this).find(':submit, #go_button').replaceWith($button);
			});
		},

		get_content: function($item) {
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

	classes['input'] = classes['base'].extend({
		init: function($el, type, name, id) {
			var self = this;

			self.spinner = spinner.clone();

			self._super($el, type, name, id);
		},

		set_input: function(content) {
			var self = this;

			self.input = (self.type == 'input') ? $('<input type="text">') : $('<textarea>');

			self.input
				.attr('id', 'edit_' + self.el.attr('id'))
				.addClass('front-editor-content')
				.val(content);

			self.input.prependTo(self.form);
		},

		get_content: function() {
			var self = this;
			return self.input.val();
		},

		set_content: function(content) {
			var self = this;
			self.el.html(content);
		},

		ajax_get: function() {
			var self = this;

			self.el.hide().after(self.spinner.show());

			self._super();
		},

		ajax_set: function() {
			var self = this;

			self.el.before(self.spinner.show());

			self._super();
		},

		ajax_get_handler: function(content) {
			var self = this;

			self.spinner.hide().replaceWith(self.form);

			self.set_input(content);

			self.input.focus();
		},

		ajax_set_handler: function(content) {
			var self = this;

			self.set_content(content);

			self.spinner.hide();
			self.el.show();
		},

		dblclick: function(ev) {
			var self = this;

			self._super(ev);

			self.form_handler();
		},

		form_handler: function() {
			var self = this;

			// Button actions
			var form_remove = function(with_spinner) {
				frontEditorData._trap = false;

				self.form.remove();

				if ( with_spinner === true )
					self.el.before(self.spinner.show());
				else
					self.el.show();

				self.el.trigger('fee_remove_form');
			};

			var form_submit = function() {
				self.ajax_set();
				form_remove(true);
			};

			// Button markup
			self.save_button = $('<button>')
				.attr({'class': 'front-editor-save', 'title': frontEditorData.save_text})
				.text(frontEditorData.save_text)
				.click(form_submit);

			self.cancel_button = $('<button>')
				.attr({'class': 'front-editor-cancel', 'title': frontEditorData.cancel_text})
				.text('X')
				.click(form_remove);

			// Create form
			self.form = ( self.type == 'input' ) ? $('<span>') : $('<div>');

			self.form
				.addClass('front-editor-container')
				.append(self.save_button)
				.append(self.cancel_button);

			self.bind(self.form, 'keypress', self.keypress);
			
			self.ajax_get();
		},

		keypress: function(ev) {
			var self = this;

			var keys = {ENTER: 13, ESCAPE: 27};
			var code = (ev.keyCode || ev.which || ev.charCode || 0);

			if ( code == keys.ENTER && self.type == 'input' )
				self.save_button.click();

			if ( code == keys.ESCAPE )
				self.cancel_button.click();
		},
	});

	classes['textarea'] = classes['input'].extend({
		set_input: function(content) {
			var self = this;
			
			self._super(content);

			if ( self.type == 'textarea' )
				self.input.growfield();
		}
	});

	classes['rich'] = classes['textarea'].extend({
		get_content: function() {
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

		panel_options: {
			iconsPath: frontEditorData.nicedit_icons,
			buttonList: [
				'bold', 'italic', 'strikethrough',
				'left','center', 'right',
				'ul', 'ol',
				'fontFormat',
				'link', 'unlink', 'image',
				'xhtml'
			]
		},

		ajax_set: function() {
			var self = this;

			self.editor.nicInstances[0].saveContent();

			self._super();
		},

		set_input: function(content) {
			var self = this;

			self._super(content);

			self.editor = new nicEditor(self.panel_options).panelInstance(self.input.attr('id'));

			self.form.find('.nicEdit-main').focus();
		}
	});

	// Widget text hack: Add id attr to each element
	$('.front-ed-widget_text, .front-ed-widget_title').each(function() {
		var $el = $(this);
		var id = $el.parents('.widget_text').attr('id');

		if ( id )
			$el.attr('id', 'fee_' + id);
		else // undo wrap
			$el.replaceWith($el.html());
	});

	// Create field instances
	$.each(frontEditorData.fields, function(name, type) {
		$('.front-ed-' + name).each(function() {
			var $el = $(this);

			var id = $el.attr('id').substr(4);

			var parts = id.split('#');

			if ( parts.length == 4 )	// hack for frontEd_meta
				type = parts[2];

			new classes[type]($el, type, name, id);
		});
	});
});
