class FrontEndEditor.fieldTypes.group extends FrontEndEditor.fieldTypes.input

	constructor: (@el, @editors) ->
		super

	create_input: jQuery.noop

	create_form: ->
		for editor in @editors
			editor.create_form()
			editor.create_input()

		@form = @el

	remove_form: ->
		for editor in @editors
			editor.remove_form()

		@hover.container.html @pre_edit_button()
		@hover.bind_autohide()

	content_from_input: ->
		(editor.content_from_input() for editor in @editors)

	keypress: jQuery.noop

	ajax_set: ->
		super

		FrontEndEditor.overlay.cover @el

	ajax_args: ->
		args = super

		args.group = true

		dataArr = (editor.data for editor in @editors)

		if dataArr.length is 1
			args.data = dataArr
		else
			# copy all properties from first field
			commonData = jQuery.extend {}, dataArr[0]

			# keep only common props
			for i in [1...dataArr.length]
				for own key, value of commonData
					if value isnt dataArr[i][key]
						delete commonData[key]

			# construct new dataArr with only the distinct props
			args.data =
				for data in dataArr
					item = {}
					for own key of data
						if key not in commonData
							item[key] = data[key]
					item

			args.commonData = commonData

		args

	ajax_get_handler: (response) ->
		for editor, i in @editors
			editor.ajax_get_handler response[i]

		@editors[0].input?.focus()

		@hover.container.html @create_buttons()
		@hover.unbind_autohide()

	ajax_set_handler: (response) ->
		for editor, i in @editors
			editor.ajax_set_handler response[i]

		@remove_form()
