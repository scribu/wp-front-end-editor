group = require('./fields/group.coffee')

class FrontEndEditor.fieldTypes.createPost extends group
	ajax_set_args: ->
		args = super

		args.createPost = true

		args

	ajax_set_handler: (response) ->
		window.location = response.permalink
