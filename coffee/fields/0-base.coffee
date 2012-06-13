class FrontEndEditor.fieldTypes.base

	el: null,

	get_type: ->
		@constructor.name

	pre_edit_button: ->
		jQuery('<button>',
			'class': 'fee-hover-edit'
			'html': FrontEndEditor.data.edit_text
			'click': (ev) =>
				@last_mouse_pos = ev.pageY
				@start_editing()
		)

	start_editing: null

	init_hover: ($target) ->
		@hover = new FrontEndEditor.hover $target
		@hover.not_editing @pre_edit_button()

	ajax_get: ->
		@el.trigger 'edit_start'

		@_ajax_request {
			data: @ajax_get_args arguments...
			success: =>
				@ajax_get_handler arguments...
				@el.trigger 'edit_started'
		}

	ajax_set: ->
		@el.trigger 'edit_save'

		@_ajax_request {
			data: @ajax_set_args arguments...
			success: =>
				@ajax_set_handler arguments...
				@el.trigger 'edit_saved'
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
