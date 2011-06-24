FrontEndEditor.define_field( 'widget', 'textarea', {

	create_input: jQuery.noop,

	content_to_input: function (content) {
		this.input = jQuery(content);

		this.form.prepend(content);
	},

	ajax_args: function (args) {
		var raw_data;

		args = this._super(args);

		if ( 'get' === args.callback )
			return args;

		raw_data = this.form.find(':input').serializeArray();

		jQuery.each(args, function (name, value) {
			raw_data.push({'name': name, 'value': value});
		});

		jQuery.each(args.data, function (name, value) {
			raw_data.push({'name': 'data[' + name + ']', 'value': value});
		});

		return raw_data;
	}
});
