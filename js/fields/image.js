FrontEndEditor.fieldTypes['image_base'] = FrontEndEditor.fieldTypes['base'].extend({
	button_text: FrontEndEditor.data.image ? FrontEndEditor.data.image.change : null,

	dblclick: function () {
		var self = this;

		tb_show(FrontEndEditor.data.image.change, FrontEndEditor.data.image.url);

		jQuery('#TB_closeWindowButton img').attr('src', FrontEndEditor.data.image.tb_close);

		jQuery('#TB_iframeContent').load(jQuery.proxy(self, 'replace_button'));
	},

	replace_button: function (ev) {
		var self = this,
			$frame = jQuery(ev.target).contents();

		$frame.delegate('.media-item', 'hover', function () {
			var $item = jQuery(this);

			if ( $item.data('fee_altered') )
				return;

			var $button = jQuery('<a href="#" class="button">')
				.text(self.button_text)
				.click(function (ev) {
					self.ajax_set(self.content_from_input($item));
				});

			$item.find(':submit, #go_button').remove();
			$item.find('.del-link').before($button);

			$item.data('fee_altered', true);
		});
	},

	content_from_input: function ($item) {
		var $field;

		// Media library
		$field = $item.find('.urlfile');
		if ( $field.length )
			return $field.attr('title');

		// From URL (embed)
		$field = $item.find('#embed-src');
		if ( $field.length )
			return $field.val();

		// From URL
		$field = $item.find('#src');
		if ( $field.length )
			return $field.val();

		return false;
	}
});

// Add wpimage button
if ( jQuery.cleditor ) {

FrontEndEditor.fieldTypes['image_rich'] = FrontEndEditor.fieldTypes['image_base'].extend({
	button_text: FrontEndEditor.data.image ? FrontEndEditor.data.image.insert : null,

	init: function (data) {
		this.data = data;
		this.dblclick();
	},

	ajax_set: function (url) {
		var	data = this.data,
			editor = data.editor;

		editor.execCommand(data.command, url, null, data.button);

		tb_remove();
		editor.focus();
	}
});

jQuery.cleditor.buttons.wpimage = jQuery.extend(jQuery.cleditor.buttons.image, {
	name: "wpimage",
	popupName: "undefined",
	buttonClick: function(event, data) {
		new FrontEndEditor.fieldTypes['image_rich'](data);
	}
});

// Add the button to the default controls
jQuery.cleditor.defaultOptions.controls = jQuery.cleditor.defaultOptions.controls
	.replace("image ", "wpimage ");

}

FrontEndEditor.fieldTypes['image'] = FrontEndEditor.fieldTypes['image_base'].extend({

	dblclick: function (ev) {
		var self = this;

		self._super(ev);

		jQuery('<a id="fee-img-revert" href="#">')
			.text(FrontEndEditor.data.image.revert)
			.click(function (ev) {
				self.ajax_set(-1);
			})
			.insertAfter('#TB_ajaxWindowTitle');
	},

	ajax_set_handler: function (url) {
		var self = this;

		if ( url == -1 ) {
			window.location.reload(true);
		} else {
			self.el.find('img').attr('src', url);
			tb_remove();
		}
	}
});

FrontEndEditor.fieldTypes['thumbnail'] = FrontEndEditor.fieldTypes['image'].extend({

	replace_button: function (ev) {
		var self = this;

		var $frame = jQuery(ev.target).contents();

		$frame.find('#tab-type_url').remove();

		self._super(ev);
	},

	content_from_input: function ($item) {
		return $item.attr('id').replace('media-item-', '');
	}
});

