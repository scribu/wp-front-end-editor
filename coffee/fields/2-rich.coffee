class FrontEndEditor.fieldTypes.rich extends FrontEndEditor.fieldTypes.textarea

	content_from_input: ->
		Aloha.getEditableById(@form.attr('id')).getContents()

	create_input: jQuery.noop

	create_form: ->
		@form = Aloha.jQuery '<div class="fee-form fee-type-rich">'

	remove_form: ->
		@form.mahalo()

		super

	ajax_get_handler: (response) ->
		$el = @error_handler(response)

		if !$el
			return

		@create_form()

		@form.html(response.content)

		@el.hide()
		@form.insertAfter($el)

		@form.aloha()

		if not @part_of_group
			new FrontEndEditor.hover @form, @create_buttons()

			# hack to get the floating menu to show up immediately
			@form.focus()
			@form.dblclick()
