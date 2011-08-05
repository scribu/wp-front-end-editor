FrontEndEditor.define_field( 'input', 'base', {

	input_tag: '<input type="text">',

	create_input: function () {
		this.input = jQuery(this.input_tag).attr({
			'id'    : 'fee-' + new Date().getTime(),
			'class' : 'fee-form-content'
		});

		this.input.prependTo(this.form);
	},

	content_to_input: function (content) {
		this.input.val(content);
		this.form.trigger('ready.fee', [this.data]);
	},

	content_from_input: function () {
		return this.input.val();
	},

	content_to_front: function (content) {
		this.el.html(content);
		this.form.trigger('saved.fee', [this.data]);
	},

	ajax_get: function () {
		FrontEndEditor.overlay.cover(this.el);
		this.create_input();
		this._super();
	},

	ajax_set: function (contentData) {
		if ( 0 == arguments.length )
			contentData = this.content_from_input();

		var data = this.ajax_args({
			callback: 'save',
			content: contentData
		});

		FrontEndEditor.overlay.cover(this.form);

		FrontEndEditor.edit_unlock();
		jQuery.post(FrontEndEditor.data.ajax_url, data, jQuery.proxy(this, 'ajax_set_handler'), 'json');
	},

	ajax_get_handler: function (response) {
		var $el = this.error_handler(response);

		if ( !$el ) {
			return;
		}

		this.el.hide();

		$el.after(this.form);

		this.content_to_input(response.content);

		this.input.focus();
	},

	ajax_set_handler: function (response) {
		var $el = this.error_handler(response);

		if ( !$el ) {
			return;
		}

		this.content_to_front(response.content);

		this.form.remove();
		this.el.show();
	},

	// Returns the element after which the form should be inserted
	error_handler: function (response) {
		var
			$parent = this.el.closest('a'),
			$el = $parent.length ? $parent : this.el;

		FrontEndEditor.overlay.hide();

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

	start_editing: function (ev) {

		this.save_button = jQuery('<button>', {
			'class': 'fee-form-save',
			'text' : FrontEndEditor.data.save_text,
			'click': jQuery.proxy(this, 'form_submit')
		});

		this.cancel_button = jQuery('<button>', {
			'class': 'fee-form-cancel',
			'text' : FrontEndEditor.data.cancel_text,
			'click': jQuery.proxy(this, 'form_remove')
		});

		this.form_create();

		this.form
			.append(this.save_button)
			.append(this.cancel_button);

		this.ajax_get();
	},

	form_create: function() {
		this.form = this.el.is('span') ? jQuery('<span>') : jQuery('<div>');

		this.form
			.addClass('fee-form')
			.addClass('fee-type-' + this.type)
			.addClass('fee-filter-' + this.filter);

		this.form.keypress(jQuery.proxy(this, 'keypress'));
	},

	form_remove: function (ev) {
		this.form.remove();
		this.el.show();

		FrontEndEditor.edit_unlock();

		return false;
	},

	form_submit: function (ev) {
		this.ajax_set();

		return false;
	},

	keypress: function (ev) {
		var
			keys = {ENTER: 13, ESCAPE: 27},
			code = (ev.keyCode || ev.which || ev.charCode || 0);

		if ( code === keys.ENTER && 'input' === this.type ) {
			this.save_button.click();
		}

		if ( code === keys.ESCAPE ) {
			this.cancel_button.click();
		}
	}
});


FrontEndEditor.define_field( 'checkbox', 'input', {
	input_tag: '<input type="checkbox">',

	content_to_input: function (content) {
		content = content ? 'checked' : '';

		this.input.attr('checked', content);
	},

	content_from_input: function () {
		return Number(this.input.is(':checked'));
	},

	content_to_front: function () {
		this.el.html(this.data.values[this.content_from_input()]);
	}
});


FrontEndEditor.define_field( 'select', 'input', {
	input_tag: '<select>',

	content_to_input: function (content) {
		var self = this;

		jQuery.each(self.data.values, function (value, title) {
			self.input.append(jQuery('<option>', {
				value: value,
				html: title,
				selected: (content === value) ? 'selected': ''
			}));
		});
	},

	content_from_input: function () {
		return this.input.find(':selected').val();
	}
});


FrontEndEditor.define_field( 'textarea', 'input', {
	input_tag: '<textarea rows="10">'
});
