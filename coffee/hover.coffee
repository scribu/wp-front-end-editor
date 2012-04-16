class FrontEndEditor.hover

	HOVER_BORDER: 2
	HOVER_PADDING: 2

	lock: false,
	timeout: null,

	constructor: ($el, callback) ->
		@border = jQuery('<div>')
			.addClass('fee-hover-border')
			.css('width', @HOVER_BORDER)
			.hide().appendTo('body')

		@box = jQuery('<div>',
			'class': 'fee-hover-edit'
			'html': FrontEndEditor.data.edit_text
			'mouseover': =>
				@lock = true
			'mouseout': =>
				@lock = false
				@hide()
		).hide().appendTo('body')

		$el.bind {
			'mouseover': (ev) =>
				if FrontEndEditor.is_editing()
					return

				@position_vert(ev.pageY)
				@show($el, callback)

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

	show: (el, callback) ->
		$self = jQuery(el)
		offset = $self.offset()
		dims = @get_dims $self

		# Webkit really doesn't like block elements inside inline elements
		if dims.width > $self.parent().width()
			$self.css('display', 'block')
			dims = @get_dims($self)

		clearTimeout @timeout

		@box.unbind 'click'

		@box.click =>
			@hide_immediately()
			callback()

		# Position 'Edit' box
		@box.css 'left', (offset.left - @box.outerWidth() - @HOVER_PADDING) + 'px'
		@box.show()

		# Position hover border
		@border.css(
			'left'  : (offset.left - @HOVER_PADDING - @HOVER_BORDER) + 'px'
			'top'   : (offset.top  - @HOVER_PADDING - @HOVER_BORDER) + 'px'
			'height': (dims.height + @HOVER_PADDING * 2) + 'px'
		).show()
