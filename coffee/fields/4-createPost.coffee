class FrontEndEditor.fieldTypes.createPost extends FrontEndEditor.fieldTypes.group
	ajax_set_args: ->
		args = super

		args.createPost = true

		args

	ajax_set_handler: (response) ->
		window.location = response.permalink
