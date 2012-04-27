HOVER_BORDER = 2
HORIZONTAL_PADDING = 4
VERTICAL_PADDING = 2

class FrontEndEditor.hover

	lock: false,
	timeout: null,

	constructor: ($el, $content, autohide = true) ->
		# Webkit really doesn't like block elements inside inline elements
		if $el.width() > $el.parent().width()
			$el.css('display', 'block')

		@border = jQuery('<div>',
			'class': 'fee-hover-border',
			'css': { width: HOVER_BORDER }
		).hide().appendTo('body')

		@container = jQuery('<div>',
			'class': 'fee-hover-container'
			'html': $content

			'click': (ev) =>
				ev.preventDefault()
				@hide_immediately()
		).hide().appendTo('body')

		$el.mousemove (ev) =>
			@position_vert(ev.pageY)

		$el.mouseover (ev) =>
			@position_vert(ev.pageY)
			@show($el)

		if autohide
			$el.mouseout (ev) =>
				@hide()

			@container.mouseover =>
				@lock = true

			@container.mouseout =>
				@lock = false
				@hide()

	position_vert: (mouse_vert_pos) ->
		normal_height = mouse_vert_pos - @container.outerHeight()/2

		@container.css('top', (normal_height - HOVER_BORDER) + 'px')

	hide_immediately: ->
		@container.hide()
		@border.hide()

	hide: ->
		@timeout = setTimeout =>
			if @lock
				return

			@hide_immediately()
		, 300

	show: ($el) ->
		offset = $el.offset()

		clearTimeout @timeout

		# Position container
		@container.css 'left', (offset.left - @container.outerWidth() - HORIZONTAL_PADDING) + 'px'
		@container.show()

		# Position border
		@border.css(
			'left'  : (offset.left - HORIZONTAL_PADDING) + 'px'
			'top'   : (offset.top  - VERTICAL_PADDING - HOVER_BORDER) + 'px'
			'height': ($el.height() + VERTICAL_PADDING * 2) + 'px'
		).show()
