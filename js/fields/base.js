FrontEndEditor.fieldTypes['base'] = Class.extend({
	dependency: null, // script src

	start: jQuery.noop,
	create_input: null,

	content_to_input: null,
	content_from_input: null,

	content_to_front: null,

	ajax_get_handler: null,
	ajax_set_handler: null,

	ajax_args: function (args) {
		var self = this;

		return jQuery.extend(args, {
			action	: 'front-end-editor',
			nonce	: FrontEndEditor.data.nonce,
			filter	: self.filter,
			data	: self.data
		});
	},

	ajax_get: function () {
		var self = this;

		var data = self.ajax_args({
			callback: 'get'
		});

		FrontEndEditor.sync_load(jQuery.proxy(self, 'ajax_get_handler'), data, self.dependency);
	},

	ajax_set: function (content) {
		var self = this,
			data = self.ajax_args({
			callback: 'save',
			content: content || self.content_from_input()
		});

		jQuery.post(FrontEndEditor.data.ajax_url, data, jQuery.proxy(self, 'ajax_set_handler'), 'json');
	},

	init_cleditor: function($el) {
		var self = this;

		$el.cleditor({
			controls: FrontEndEditor.data.cleditor.controls,
			width: self.form.width(),
			height: FrontEndEditor.data.cleditor.height,
			bodyStyle: 'cursor:text; margin:4px; ' + FrontEndEditor.data.cleditor.css.replace(/"/g, "'")
		});
	}
});

