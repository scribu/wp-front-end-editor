FrontEndEditor.fieldTypes['input'] = FrontEndEditor.fieldTypes['base'].extend({

	input_tag: '<input type="text">',

	start: function () {
		var self = this;

		self.overlay = FrontEndEditor.overlay(self.el);
	},

	create_input: function () {
		var self = this;

		self.input = jQuery(self.input_tag).attr({
			'id'    : 'fee-' + new Date().getTime(),
			'class' : 'fee-form-content'
		});

		self.input.prependTo(self.form);
	},

	content_to_input: function (content) {
		var self = this;

		self.input.val(content);
		self.form.trigger('ready.fee', [self.data]);
	},

	content_from_input: function () {
		var self = this;

		return self.input.val();
	},

	content_to_front: function (content) {
		var self = this;

		self.el.html(content);
		self.form.trigger('saved.fee', [self.data]);
	},

	ajax_get: function () {
		var self = this;

		self.overlay.show();

		self.create_input();

		self._super();
	},

	ajax_set: function () {
		var self = this;

		self.overlay.show();

		self._super();
	},

	ajax_get_handler: function (response) {
		var self = this,
			$el = self.error_handler(response);

		if ( !$el )
			return;

		self.el.hide();

		$el.after(self.form);

		self.content_to_input(response.content);

		self.input.focus();
	},

	ajax_set_handler: function (response) {
		var self = this,
			$el = self.error_handler(response);

		if ( !$el )
			return;

		self.content_to_front(response.content);

		self.el.show();
	},

	error_handler: function (response) {
		var self = this,
			$parent = self.el.parents('a'),
			$el = $parent.length ? $parent : self.el;

		self.overlay.hide();

		if ( response.error ) {
			var $error_box = jQuery('<div class="fee-error">');

			$error_box
				.append(
					jQuery('<span class="fee-message">').html(response.error)
				)
				.append(
					jQuery('<span class="fee-dismiss">x</span>').click(function () {
						$error_box.remove();
					})
				);

			$el.before($error_box);
			return false;
		}

		return $el;
	},

	dblclick: function (ev) {
		var self = this;

		// Buttons
		self.save_button = jQuery('<button>')
			.addClass('fee-form-save')
			.text(FrontEndEditor.data.save_text)
			.click(jQuery.proxy(self, 'form_submit'));

		self.cancel_button = jQuery('<button>')
			.addClass('fee-form-cancel')
			.text(FrontEndEditor.data.cancel_text)
			.click(jQuery.proxy(self, 'form_remove'));

		// Form
		self.form = jQuery('<div>');

		self.form
			.addClass('fee-form')
			.addClass('fee-type-' + self.type)
			.addClass('fee-filter-' + self.filter)
			.append(self.save_button)
			.append(self.cancel_button);

		if ( self.el.hasClass('fee-inline') )
			self.form.addClass('fee-inline');

		self.form.bind('keypress', jQuery.proxy(self, 'keypress'));

		self.ajax_get();
	},

	form_remove: function (ev) {
		var self = this;

		self.remove_form(false);

		ev.stopPropagation();
		ev.preventDefault();
	},

	form_submit: function (ev) {
		var self = this;

		self.ajax_set();
		self.remove_form(true);

		ev.stopPropagation();
		ev.preventDefault();
	},

	remove_form: function (with_spinner) {
		var self = this;

		self.form.remove();

		self.el.show();

		if ( true === with_spinner )
			self.overlay.show();
	},

	keypress: function (ev) {
		var self = this,
			keys = {ENTER: 13, ESCAPE: 27},
			code = (ev.keyCode || ev.which || ev.charCode || 0);

		if ( code === keys.ENTER && 'input' === self.type )
			self.save_button.click();

		if ( code === keys.ESCAPE )
			self.cancel_button.click();
	}
});

FrontEndEditor.fieldTypes['checkbox'] = FrontEndEditor.fieldTypes['input'].extend({
	input_tag: '<input type="checkbox">',

	content_to_input: function (content) {
		var self = this;

		content = content ? 'checked' : '';

		self.input.attr('checked', content);
	},

	content_from_input: function () {
		var self = this;

		return Number(self.input.is(':checked'));
	},

	content_to_front: function () {
		var self = this,
			content = self.data.values[ self.content_from_input() ];

		self.el.html(content);
	}
});

FrontEndEditor.fieldTypes['select'] = FrontEndEditor.fieldTypes['input'].extend({
	input_tag: '<select>',

	content_to_input: function (content) {
		var self = this;

		jQuery.each(self.data.values, function (value, title) {
			var $option = jQuery('<option>')
				.attr({
					html: value,
					value: value,
					selected: (content === value) ? 'selected': ''
				})
				.html(title);

			self.input.append($option);
		});
	},

	content_from_input: function () {
		var self = this;

		return self.input.find(':selected').val();
	}
});

FrontEndEditor.fieldTypes['textarea'] = FrontEndEditor.fieldTypes['input'].extend({
	input_tag: '<textarea rows="10">'
});

FrontEndEditor.fieldTypes['rich'] = FrontEndEditor.fieldTypes['textarea'].extend({
	dependency: FrontEndEditor.data.nicedit ? FrontEndEditor.data.nicedit.src : null,

	content_to_input: function (content) {
		var self = this;

		self._super(content);

		self.init_cleditor( self.input );
	},

	content_from_input: function () {
		var self = this;

		return self.pre_wpautop(self.input.val());
	},

	// Copied from wp-admin/js/editor.dev.js
	pre_wpautop: function (content) {
		var blocklist1, blocklist2;

		// Protect pre|script tags
		content = content.replace(/<(pre|script)[^>]*>[\s\S]+?<\/\1>/g, function (a) {
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
			content = content.replace(/<object[\s\S]+?<\/object>/g, function (a) {
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
	}
});

