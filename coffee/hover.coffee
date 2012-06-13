HOVER_BORDER = 2
HORIZONTAL_PADDING = 4
VERTICAL_PADDING = 2


# Static controls handler
class FrontEndEditor.controls

	constructor: (@container) ->
		false

	not_editing: ($content) ->
		@container.html $content

	editing: ($content, vert_pos) ->
		@container.html $content


# Hovering controls handler
class FrontEndEditor.hover

	lock: false,
	timeout: null,

	constructor: (@target) ->
		# Webkit really doesn't like block elements inside inline elements
		if @target.width() > @target.parent().width()
			@target.css('display', 'block')

		@border = jQuery('<div>',
			'class': 'fee-hover-border',
			'css': { width: HOVER_BORDER }
		).hide().appendTo('body')

		@container = jQuery('<div>',
			'class': 'fee-hover-container'
		).hide().appendTo('body')

		@container.click (ev) =>
			ev.preventDefault()
			@hide_immediately()

		@target.mousemove (ev) =>
			@position_vert(ev.pageY)

		@target.mouseover (ev) =>
			@show(ev.pageY)

	not_editing: ($content) ->
		@container.html($content)

		@container.bind 'mouseover.autohide', =>
			@lock = true

		@container.bind 'mouseout.autohide', =>
			@lock = false
			@hide()

		@target.bind 'mouseout.autohide', (ev) =>
			@hide()

	editing: ($content, vert_pos) ->
		@container.html $content

		@target.unbind '.autohide'
		@container.unbind '.autohide'

		@show vert_pos

	hide_immediately: ->
		@container.hide()
		@border.hide()

	hide: ->
		@timeout = setTimeout =>
			if @lock
				return

			@hide_immediately()
		, 300

	position_vert: (vert_pos) ->
		normal_height = vert_pos - @container.outerHeight()/2

		@container.css('top', (normal_height - HOVER_BORDER) + 'px')

	show: (vert_pos) ->
		@position_vert(vert_pos)

		offset = @target.offset()

		clearTimeout @timeout

		# Position container
		@container.css 'left', (offset.left - @container.outerWidth() - HORIZONTAL_PADDING - HOVER_BORDER) + 'px'
		@container.show()

		# Position border
		@border.css(
			'left'  : (offset.left - HORIZONTAL_PADDING - HOVER_BORDER) + 'px'
			'top'   : (offset.top  - VERTICAL_PADDING - HOVER_BORDER) + 'px'
			'height': (@target.outerHeight() + VERTICAL_PADDING * 2) + 'px'
		).show()
