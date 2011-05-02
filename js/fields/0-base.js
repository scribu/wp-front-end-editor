FrontEndEditor.define_field( 'base', false, {
	dependency: null, // script src

	start: jQuery.noop,

	ajax_get_handler: null,
	ajax_set_handler: null,

	ajax_get: function () {
		var self = this,
			data = self.ajax_args({
				callback: 'get'
			});

		FrontEndEditor.sync_load(jQuery.proxy(self, 'ajax_get_handler'), data, self.dependency);
	},

	ajax_set: function (content) {
		var	self = this,
			data = self.ajax_args({
				callback: 'save',
				content: content
			});

		jQuery.post(FrontEndEditor.data.ajax_url, data, jQuery.proxy(self, 'ajax_set_handler'), 'json');
	},

	ajax_args: function (args) {
		var self = this;

		return jQuery.extend(args, {
			action	: 'front-end-editor',
			nonce	: FrontEndEditor.data.nonce,
			filter	: self.filter,
			data	: self.data
		});
	}
});

