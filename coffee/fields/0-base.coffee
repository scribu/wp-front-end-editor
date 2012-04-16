class FrontEndEditor.fieldTypes.base

	get_type: ->
		@constructor.name

	start_editing: null

	init_hover: ($container) ->
		@hover = new FrontEndEditor.hover $container, jQuery.proxy(this, 'start_editing')

	ajax_get: ->
		FrontEndEditor.edit_lock @el

		@_ajax_request {
			data: @ajax_get_args arguments...
			success: jQuery.proxy(this, 'ajax_get_handler'),
		}

	ajax_set: ->
		@_ajax_request {
			data: @ajax_set_args arguments...
			success: jQuery.proxy(this, 'ajax_set_handler'),
		}

	_ajax_request: (args) ->
		args.url = FrontEndEditor.data.ajax_url
		args.type = 'POST'
		args.dataType = 'json'

		jQuery.ajax args

	ajax_get_handler: null

	ajax_set_handler: null

	ajax_get_args: ->
		args = @ajax_args()

		args.callback = 'get'

		args

	ajax_set_args: (content) ->
		args = @ajax_args()

		args.callback = 'save'
		args.content = content

		args

	ajax_args: ->
		action : 'front-end-editor'
		nonce  : FrontEndEditor.data.nonce
		data   : @data
