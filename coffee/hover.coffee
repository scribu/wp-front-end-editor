FrontEndEditor.hover_init = do ->
	get_dims = ($el) -> {
		'width': $el.width()
		'height': $el.height()
	}

	HOVER_BORDER = 2
	HOVER_PADDING = 2
	hover = { lock: false, timeout: null }

	# Init hover border
	hover_border = jQuery('<div>')
		.addClass('fee-hover-border')
		.css('width', HOVER_BORDER)
		.hide().appendTo('body')

	# Init hover box
	hover_box = jQuery('<div>',
		'class': 'fee-hover-edit'
		'html': FrontEndEditor.data.edit_text
		'mouseover': ->
			hover.lock = true
		'mouseout': ->
			hover.lock = false
			hover_hide()
	).hide().appendTo('body')

	box_position_vert = (mouse_vert_pos) ->
		normal_height = mouse_vert_pos - hover_box.outerHeight()/2
		hover_box.css('top', (normal_height - HOVER_BORDER) + 'px')

	hover_hide_immediately = ->
		hover_box.hide()
		hover_border.hide()

	hover_hide = ->
		hover.timeout = setTimeout ->
			if hover.lock
				return

			hover_hide_immediately()
		, 300

	hover_show = (callback) ->
		$self = jQuery(this)
		offset = $self.offset()
		dims = get_dims($self)

		# Webkit really doesn't like block elements inside inline elements
		if dims.width > $self.parent().width()
			$self.css('display', 'block')
			dims = get_dims($self)

		clearTimeout(hover.timeout)

		hover_box.unbind('click')

		hover_box.bind('click', hover_hide_immediately)
		hover_box.bind('click', callback)

		# Position 'Edit' box
		hover_box.css('left', (offset.left - hover_box.outerWidth() - HOVER_PADDING) + 'px')
		hover_box.show()

		# Position hover border
		hover_border.css(
			'left': (offset.left - HOVER_PADDING - HOVER_BORDER) + 'px'
			'top': (offset.top - HOVER_PADDING - HOVER_BORDER) + 'px'
			'height': (dims.height + HOVER_PADDING * 2) + 'px'
		).show()

	return ($el, callback) ->
		$el.bind {
			mouseover: (ev) ->
				if FrontEndEditor.is_editing()
					return

				box_position_vert(ev.pageY)
				hover_show.call(this, callback)

			mousemove: (ev) ->
				box_position_vert(ev.pageY)

			mouseout: hover_hide
		}
