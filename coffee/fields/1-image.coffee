class FrontEndEditor.fieldTypes.image_base extends FrontEndEditor.fieldTypes.base

	button_text: FrontEndEditor.data.image?.change

	start_editing: ->
		tb_show(@button_text, FrontEndEditor.data.image.url)

		jQuery('#TB_closeWindowButton img').attr('src', FrontEndEditor.data.image.tb_close)

		jQuery('#TB_iframeContent').load (ev) =>
			iframe = ev.currentTarget.contentWindow
			$thickbox = iframe.jQuery(iframe.document)

			@thickbox_load($thickbox)

			if jQuery.noop isnt @media_item_manipulation
				# existing items
				$thickbox.find('.media-item').each (i, el) =>
					@media_item_manipulation iframe.jQuery(el)

				# newly uploaded items
				$thickbox.ajaxComplete (event, request) =>
					item_id = jQuery(request.responseText).find('.media-item-info').attr('id')
					@media_item_manipulation $thickbox.find('#' + item_id).closest('.media-item')

	thickbox_load: ($thickbox) ->
		# Submit the form to our own ajax handler, to get back the HTML
		$thickbox.delegate('.media-item :submit', 'click', (ev) =>
			$button = jQuery(ev.currentTarget)
			data = $button.closest('form').serializeArray()

			data.push({name: $button.attr('name'), value: $button.attr('name')})
			data.push({name: 'action', value: 'fee_image_insert'})

			jQuery.post(FrontEndEditor.data.ajax_url, data, => @image_html_handler())

			return false
		)

	media_item_manipulation: ($item) ->
		$item.find('#go_button').remove()
		$item.find(':submit').val(@button_text)


class FrontEndEditor.fieldTypes.image extends FrontEndEditor.fieldTypes.image_base

	start_editing: ->
		super

		# Add a Revert button to the thickbox chrome
		jQuery('<a id="fee-img-revert" href="#">')
			.text(FrontEndEditor.data.image.revert)
			.click( (ev) =>
				@ajax_set(-1)
				return false
			)
			.insertAfter('#TB_ajaxWindowTitle')

	media_item_manipulation: ($item) ->
		$item.find('tbody tr').not('.image-size, .submit').hide()
		super

	image_html_handler: (html) ->
		$html = jQuery(html)
		if $html.is('a')
			$html = $html.find('img')

		@ajax_set $html.attr('src')

	ajax_set_handler: (response) ->
		url = response.content

		if '-1' is url
			location.reload(true)
		else
			@el.find('img').attr('src', url)
			tb_remove()


class FrontEndEditor.fieldTypes.thumbnail extends FrontEndEditor.fieldTypes.image

	thickbox_load: ($thickbox) ->

		$thickbox.find('#tab-type_url').remove()

		$thickbox.delegate('.media-item :submit', 'click', (ev) =>
			$item = jQuery(ev.currentTarget).closest('.media-item')
			attachment_id = $item.attr('id').replace('media-item-', '')

			@ajax_set(attachment_id)

			return false
		)

	media_item_manipulation: ($item) ->
		$item.find('tbody tr').not('.submit').remove()
		super


Aloha?.require( ['aloha/selection'], (Selection) ->
	class FrontEndEditor.fieldTypes.image_rich extends FrontEndEditor.fieldTypes.image_base

		button_text: FrontEndEditor.data.image?.insert

		start_editing: ->
			jQuery('.aloha-floatingmenu, #aloha-floatingmenu-shadow').hide()
			super

		media_item_manipulation: jQuery.noop

		image_html_handler: (html) ->
			GENTICS.Utils.Dom.insertIntoDOM(
				jQuery(html)
				Selection.getRangeObject()
				Aloha.activeEditable.obj
			)

			tb_remove()
			jQuery('.aloha-floatingmenu, #aloha-floatingmenu-shadow').show()
)
