FrontEndEditor.define_field( 'input', 'base', {

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
		var	self = this,
			data = self.ajax_args({
				callback: 'save',
				content: self.content_from_input()
			});

		self.overlay.show();

		jQuery.post(FrontEndEditor.data.ajax_url, data, jQuery.proxy(self, 'ajax_set_handler'), 'json');
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
		self.form = (jQuery.inArray(self.type, ['input', 'terminput', 'termselect']) > -1) ? jQuery('<span>') : jQuery('<div>');

		self.form
			.addClass('fee-form')
			.addClass('fee-type-' + self.type)
			.addClass('fee-filter-' + self.filter)
			.append(self.save_button)
			.append(self.cancel_button);

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


FrontEndEditor.define_field( 'checkbox', 'input', {
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


FrontEndEditor.define_field( 'select', 'input', {
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


FrontEndEditor.define_field( 'textarea', 'input', {
	input_tag: '<textarea rows="10">'
});

