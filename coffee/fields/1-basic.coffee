class FrontEndEditor.fieldTypes.input extends FrontEndEditor.fieldTypes.base

	input_tag: '<input type="text">'

	start_editing: ->
		@create_form()
		@create_buttons()
		@create_input()

		@ajax_get()

		false

	create_buttons: ->
		@save_button = jQuery('<button>',
			'class': 'fee-form-save'
			'text' : FrontEndEditor.data.save_text
			'click': jQuery.proxy(this, 'submit_form')
		)

		@cancel_button = jQuery('<button>',
			'class': 'fee-form-cancel'
			'text' : FrontEndEditor.data.cancel_text
			'click': jQuery.proxy(this, 'remove_form')
		)

		@form
			.append(@save_button)
			.append(@cancel_button)

	create_form: ->
		@form = if @el.is('span') then jQuery('<span>') else jQuery('<div>')

		@form
			.addClass('fee-form')
			.addClass('fee-type-' + @get_type())

		@form.keypress(jQuery.proxy(this, 'keypress'))

	remove_form: ->
		@form.remove()
		@el.show()

		FrontEndEditor.edit_unlock(@el)

		return false

	submit_form: (ev) ->
		@ajax_set()

		return false

	keypress: (ev) ->
		keys = {ENTER: 13, ESCAPE: 27}
		code = ev.keyCode or ev.which or ev.charCode or 0

		if code is keys.ENTER and 'input' is @get_type()
			@save_button.click()

		if code is keys.ESCAPE
			@cancel_button.click()

	create_input: ->
		@input = jQuery(@input_tag).attr(
			'id'    : 'fee-' + new Date().getTime()
			'class' : 'fee-form-content'
		)

		@input.prependTo(@form)

	content_to_input: (content) ->
		@input.val(content)

	content_from_input: ->
		return @input.val()

	content_to_front: (content) ->
		@el.html(content)

	ajax_get: ->
		FrontEndEditor.overlay.cover(@el)
		super

	ajax_set_args: (contentData) ->
		FrontEndEditor.overlay.cover(@form)

		if 0 is arguments.length
			contentData = @content_from_input()

		super contentData

	ajax_get_handler: (response) ->
		$el = @error_handler(response)

		if !$el
			return

		@el.hide()

		$el.after(@form)

		@content_to_input(response.content)

		@input.focus()

	ajax_set_handler: (response) ->
		$el = @error_handler(response)

		if !$el
			return

		@content_to_front(response.content)

		@remove_form()

	# Returns the element after which the form should be inserted
	error_handler: (response) ->
		$parent = @el.closest('a')
		$el = if $parent.length then $parent else @el

		FrontEndEditor.overlay.hide()

		if response.error
			jQuery('<div class="fee-error">')
				.append(jQuery('<span class="fee-message">').html(response.error))
				.append(jQuery('<span class="fee-dismiss">x</span>').click(->
					$error_box.remove()
				))
				.insertBefore($el)
			return false

		return $el


class FrontEndEditor.fieldTypes.select extends FrontEndEditor.fieldTypes.input
	input_tag: '<select>'

	content_to_input: (content) ->
		for value, title in @data.values
			@input.append(jQuery('<option>',
				value: value
				html: title
				selected: content is value
			))

	content_from_input: ->
		return @input.find(':selected').val()


class FrontEndEditor.fieldTypes.textarea extends FrontEndEditor.fieldTypes.input
	input_tag: '<textarea rows="10">'
