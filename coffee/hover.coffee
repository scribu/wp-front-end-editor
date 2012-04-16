class FrontEndEditor.hover

	HOVER_BORDER: 2
	HOVER_PADDING: 2

	lock: false,
	timeout: null,

	constructor: ($el, $content) ->
		@border = jQuery('<div>',
			'class': 'fee-hover-border',
			'css': { width: @HOVER_BORDER }
		).hide().appendTo('body')

		@box = jQuery('<div>',
			'class': 'fee-hover-container'
			'html': $content
			'click': jQuery.proxy(this, 'hide_immediately')
			'mouseover': =>
				@lock = true
			'mouseout': =>
				@lock = false
				@hide()
		).hide().appendTo('body')

		$el.bind {
			'mouseover': (ev) =>
				# TODO: no longer works
				# if FrontEndEditor.is_editing()
				# 	return

				@position_vert(ev.pageY)
				@show($el)

			'mousemove': (ev) =>
				@position_vert(ev.pageY)

			'mouseout': jQuery.proxy(this, 'hide')
		}

	get_dims: ($el) -> {
		'width': $el.width()
		'height': $el.height()
	}

	position_vert: (mouse_vert_pos) ->
		normal_height = mouse_vert_pos - @box.outerHeight()/2

		@box.css('top', (normal_height - @HOVER_BORDER) + 'px')

	hide_immediately: ->
		@box.hide()
		@border.hide()

	hide: ->
		@timeout = setTimeout =>
			if @lock
				return

			@hide_immediately()
		, 300

	show: (el) ->
		$self = jQuery(el)
		offset = $self.offset()
		dims = @get_dims $self

		# Webkit really doesn't like block elements inside inline elements
		# TODO: only do once
		if dims.width > $self.parent().width()
			$self.css('display', 'block')
			dims = @get_dims($self)

		clearTimeout @timeout

		# Position 'Edit' box
		@box.css 'left', (offset.left - @box.outerWidth() - @HOVER_PADDING - 2) + 'px'
		@box.show()

		# Position hover border
		@border.css(
			'left'  : (offset.left - @HOVER_PADDING - 2) + 'px'
			'top'   : (offset.top  - @HOVER_PADDING - @HOVER_BORDER) + 'px'
			'height': (dims.height + @HOVER_PADDING * 2) + 'px'
		).show()
