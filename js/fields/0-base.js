FrontEndEditor.define_field( 'base', false, {
	dependency: null, // script src

	start: jQuery.noop,

	ajax_get_handler: null,
	ajax_set_handler: null,

	ajax_get: function () {
		var data = this.ajax_args({
			callback: 'get'
		});

		FrontEndEditor.edit_lock();
		FrontEndEditor.sync_load(jQuery.proxy(this, 'ajax_get_handler'), data, this.dependency);
	},

	ajax_set: function (content) {
		var data = this.ajax_args({
			callback: 'save',
			content: content
		});

		FrontEndEditor.edit_unlock();
		jQuery.post(FrontEndEditor.data.ajax_url, data, jQuery.proxy(this, 'ajax_set_handler'), 'json');
	},

	ajax_args: function (args) {
		return jQuery.extend(args, {
			action	: 'front-end-editor',
			nonce	: FrontEndEditor.data.nonce,
			data	: this.data
		});
	}
});

