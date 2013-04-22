base = require('./fields/base.coffee')

class input extends base

	input_tag: '<input type="text">'

	start_editing: ->
		@create_form()
		@create_input()

		@ajax_get()

	create_buttons: ->
		@save_button = jQuery('<button>',
			'class': 'fee-form-save'
			'text' : FrontEndEditor.data.save_text
			'click': => @ajax_set()
		)

		@cancel_button = jQuery('<button>',
			'class': 'fee-form-cancel'
			'text' : FrontEndEditor.data.cancel_text
			'click': => @remove_form()
		)

		return @save_button.add @cancel_button

	create_form: ->
		@form = jQuery(if @el.is('span') then '<span>' else '<div>')
			.addClass('fee-form')
			.addClass('fee-type-' + @get_type())

		@form.keypress (ev) => @keypress(ev.keyCode or ev.which or ev.charCode or 0)

	remove_form: ->
		@form.remove()
		@el.show()

	keypress: (code) ->
		keys = {ENTER: 13, ESCAPE: 27}

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
		@form.insertAfter($el)

		@content_to_input(response.content)

		@input.focus()

		if not @part_of_group
			@show_control_buttons()

	show_control_buttons: ->
		hover = new FrontEndEditor.hover @form
		hover.editing @create_buttons(), @last_mouse_pos

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


class select extends input
	input_tag: '<select>'

	content_to_input: (content) ->
		for own value, title of @data.values
			@input.append(jQuery('<option>',
				value: value
				html: title
				selected: content is value
			))
		false

	content_from_input: ->
		return @input.find(':selected').val()


class textarea extends input
	input_tag: '<textarea rows="10">'


module.exports = {
	input
	select
	textarea
}
