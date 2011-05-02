(function() {

var ancestor = FrontEndEditor.fieldTypes['rich'] ? 'rich' : 'textarea';

FrontEndEditor.define_field( 'widget', ancestor, {

	create_input: jQuery.noop,

	content_to_input: function (content) {
		var self = this;

		self.input = jQuery(content);

		self.form.prepend(content);

		if ( 0 == self.data.widget_id.indexOf('text-') && 'rich' == ancestor ) {
			self.init_cleditor( self.form.find('textarea') );
		}
	},

	content_from_input: function () {
		var self = this;

		return self.form.find('textarea').val();
	},

	ajax_args: function (args) {
		var self = this;

		args = self._super(args);

		if ( 'get' == args.callback )
			return args;

		var raw_data = self.form.find(':input').serializeArray();

		jQuery.each(args, function (name, value) {
			raw_data.push({'name': name, 'value': value});
		});

		jQuery.each(args.data, function (name, value) {
			raw_data.push({'name': 'data[' + name + ']', 'value': value});
		});

		return raw_data;
	}
});

}());
