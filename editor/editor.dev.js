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


//_____Actual code starts here_____


	var FEE_Click = {
		_to_click: false,

		click: function(ev) {
//			if ( !FEE_Click._to_click )
//				return;

			var $el = $(ev.target).closest('a');

			if ( !$el.length || FEE_Click.is_overlay($el) )
				return;

			ev.stopImmediatePropagation();
			ev.preventDefault();

			FEE_Click._to_click = $el;

			setTimeout(FEE_Click.resume, 300);
		},

		resume: function() {
			var $link = FEE_Click._to_click;

			if ( !$link )
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

			FEE_Click._to_click = false;
		},

		dblclick: function(ev) {
			ev.stopPropagation();
			ev.preventDefault();

			// cancel delayed click
			FEE_Click._to_click = false;
		},

		is_overlay: function($el) {
			var attr = [$el.attr('id'), $el.attr("class"), $el.attr("rel")];

			var tokens = ['lightbox', 'thickbox', 'shutter', 'awppost_link'];

			for ( var i in tokens )
				for ( var j in attr )
					if ( attr[j].indexOf(tokens[i]) != -1 )
						return true;

			return false;
		}
	};


	var spinner = $('<img>').attr({
		'src'	: FrontEndEditor.data.spinner,
		'class'	: 'front-editor-spinner'
	});


	var fieldTypes = {};

	fieldTypes['base'] = Class.extend({
		
		init: function($el, type, name, id) {
			var self = this;

			self.set_el($el);
			self.type = type;
			self.name = name;
			self.id = id;

			self.el.bind({
				click	: FEE_Click.click,
				dblclick: FEE_Click.dblclick
			});

			self.el.dblclick($.proxy(self, 'dblclick'));
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

		get_content: null /* function() */,
		set_content: null /* function(content) */,

		ajax_get_handler: null /* function(content) */,
		ajax_set_handler: null /* function(content) */,

		ajax_args: function(args) {
			var self = this;

			return $.extend(args, {
				action	: 'front-end-editor',
				nonce	: FrontEndEditor.data.nonce,
				name	: self.name,
				type	: self.type,
				item_id	: self.id
			});
		},

		ajax_get: function() {
			var self = this;

			var data = self.ajax_args({
				callback: 'get', 
			});

			$.post(FrontEndEditor.data.ajax_url, data, $.proxy(self.ajax_get_handler, self));
		},

		ajax_set: function(content) {
			var self = this;

			var data = self.ajax_args({
				callback: 'save', 
				content: content || self.get_content()
			});

			$.post(FrontEndEditor.data.ajax_url, data, $.proxy(self.ajax_set_handler, self));
		}
	});


	fieldTypes['image'] = fieldTypes['base'].extend({
		
		dblclick: function(ev) {
			var self = this;

			tb_show(FrontEndEditor.data.image.change, FrontEndEditor.data.admin_url +
				'/media-upload.php?post_id=0&type=image&TB_iframe=true&width=640&editable_image=1');

			var $revert = $('<a id="fee-img-revert" href="#">').text(FrontEndEditor.data.image.revert);

			$revert.click(function(ev){
				self.ajax_set(-1);
			});

			$('#TB_ajaxWindowTitle').after($revert);
			$('#TB_closeWindowButton img').attr('src', FrontEndEditor.data.image.tb_close);

			$('#TB_iframeContent').bind('load', $.proxy(self, 'replace_button'));
		},

		replace_button: function(ev) {
			var self = this;

			var $frame = $(ev.target).contents();

			$('.media-item', $frame).livequery(function(){
				var $item = $(this);
				var $button = $('<a href="#" class="button">').text(FrontEndEditor.data.image.change);

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

	fieldTypes['thumbnail'] = fieldTypes['image'].extend({
		
		replace_button: function(ev) {
			var self = this;

			var $frame  = $(ev.target).contents();

			$frame.find('#tab-type_url').remove();

			self._super(ev);
		},

		get_content: function($item) {
			return $item.attr('id').replace('media-item-', '');
		}
	});


	fieldTypes['input'] = fieldTypes['base'].extend({

		init: function($el, type, name, id) {
			var self = this;

			self.spinner = spinner.clone();

			self._super($el, type, name, id);
		},

		input_tag: '<input type="text">',

		create_input: function() {
			var self = this;

			self.input = $(self.input_tag).attr({
				'id'	: 'fee-' + new Date().getTime(),
				'class'	: 'fee-form-content'
			});

			self.input.prependTo(self.form);
		},

		set_input: function(content) {
			var self = this;

			self.input.val(content);
		},

		get_content: function() {
			var self = this;

			return self.input.val();
		},

		set_content: function(content) {
			var self = this;
			
			if ( self.switched )
				self.el.find('a').html(content);
			else
				self.el.html(content);
		},

		ajax_get: function() {
			var self = this;

			self.el.hide().after(self.spinner.show());

			self.create_input();

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

			// Button markup
			self.save_button   = $('<button>').addClass('fee-form-save').text(FrontEndEditor.data.save_text).click($.proxy(self.form_submit, self));
			self.cancel_button = $('<button>').addClass('fee-form-cancel').text(FrontEndEditor.data.cancel_text).click($.proxy(self.form_remove, self));

			// Create form
			self.form = ( self.type == 'input' || self.type == 'terminput' ) ? $('<span>') : $('<div>');

			self.form
				.addClass('fee-form')
				.addClass('fee-type-' + self.type)
				.addClass('fee-filter-' + self.name)
				.append(self.save_button)
				.append(self.cancel_button);

			self.form.bind('keypress', $.proxy(self, 'keypress'));
			
			self.ajax_get();
		},

		form_remove: function(with_spinner) {
			var self = this;

			self.form.remove();

			if ( with_spinner === true )
				self.el.before(self.spinner.show());
			else
				self.el.show();

			self.el.trigger('fee_remove_form');
		},

		form_submit: function() {
			var self = this;
		
			self.ajax_set();
			self.form_remove(true);
		},

		keypress: function(ev) {
			var self = this;

			var keys = {ENTER: 13, ESCAPE: 27};
			var code = (ev.keyCode || ev.which || ev.charCode || 0);

			if ( code == keys.ENTER && self.type == 'input' )
				self.save_button.click();

			if ( code == keys.ESCAPE )
				self.cancel_button.click();
		}
	});

	fieldTypes['terminput'] = fieldTypes['input'].extend({
		
		set_input: function(content) {
			var self = this;

			self._super(content);

			self.input.suggest(FrontEndEditor.data.ajax_url + '?action=ajax-tag-search&tax=' + self.id.split('#')[1], {
				multiple		: true,
				resultsClass	: 'fee-suggest-results',
				selectClass		: 'fee-suggest-over',
				matchClass		: 'fee-suggest-match'
			});
		}
	});


	fieldTypes['textarea'] = fieldTypes['input'].extend({
		input_tag: '<textarea rows="10">'
	});

	fieldTypes['rich'] = fieldTypes['textarea'].extend({

		set_input: function(content) {
			var self = this;

			self._super(content);

			self.editor = new nicEditor(FrontEndEditor.data.nicedit).panelInstance(self.input.attr('id'));

			self.form.find('.nicEdit-main').focus();
		},

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

		ajax_set: function() {
			var self = this;

			self.editor.nicInstances[0].saveContent();

			self._super();
		}
	});


	fieldTypes['widget'] = fieldTypes['input'].extend({
		create_input: function() {

		},

		set_input: function(content) {
			var self = this;

			self.input = $(content);

			self.form.prepend(content);
		},

		get_content: function() {
			return '';
		},

		ajax_args: function(args) {
			var self = this;

			args = self._super(args);

			if ( 'get' == args.callback )
				return args;

			var data = {}, raw_data = self.form.find(':input').serializeArray();

			for ( var i in raw_data )
				data[raw_data[i].name] = raw_data[i].value;

			return $.extend(args, data);
		}
	});


	// export
	FrontEndEditor.fieldTypes = fieldTypes;

$(document).ready(function($) {
	
	// Create field instances
	$.each(FrontEndEditor.data.fields, function(name, type) {
		$('.fee-filter-' + name).each(function() {
			var $el = $(this);

			var id = $el.attr('data-fee');

			var parts = id.split('#');

			switch (name) {
				case 'post_meta': type = parts[2]; break;
				case 'editable_option': type = parts[1]; break;
			}

			new fieldTypes[type]($el, type, name, id);
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
