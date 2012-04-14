class FrontEndEditor.fieldTypes.widget extends FrontEndEditor.fieldTypes.textarea

	create_input: jQuery.noop

	content_to_input: (content) ->
		@input = jQuery(content)

		@form.prepend(content)

	ajax_set_args: ->
		args = super

		for {name, value} in @form.find(':input').serializeArray()
			args[name] =
				if args[name] is undefined
					value
				else if jQuery.isArray( args[name] )
						args[name].concat(value)
				else
					[args[name], value]

		return args
