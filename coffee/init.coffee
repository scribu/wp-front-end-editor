require('./hover.coffee')
require('./core.coffee')

jQuery(document).ready ->

	# Text widget fields hack
	for el in jQuery('[data-filter="widget_title"], [data-filter="widget_text"]')
		$el = jQuery(el)
		$widget = $el.closest('.widget_text')

		if $widget.length
			$el.attr('data-widget_id', $widget.attr('id'))
			$widget.addClass('fee-group')
		else
			$el.unwrap()

	FrontEndEditor.init_fields()

jQuery(window).load ->
	# Automatically start editing the first auto-draft
	jQuery('.fee-group.status-auto-draft').data('fee-editor')?.start_editing()
