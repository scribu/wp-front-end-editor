FrontEndEditor.define_field( 'image_base', 'base', {
	button_text: FrontEndEditor.data.image ? FrontEndEditor.data.image.change : null,

	start_editing: function () {
		tb_show(FrontEndEditor.data.image.change, FrontEndEditor.data.image.url);

		jQuery('#TB_closeWindowButton img').attr('src', FrontEndEditor.data.image.tb_close);

		jQuery('#TB_iframeContent').load(jQuery.proxy(this, 'replace_button'));
	},

	replace_button: function (ev) {
		var self = this;

		jQuery(ev.target).contents().delegate('.media-item', 'mouseenter', function () {
			var $item = jQuery(this), $button;

			if ( !$item.find('.pinkynail').length ) {
				return;	// not done uploading yet
			}

			if ( $item.data('fee_altered') ) {
				return;	// already modified
			}				

			$button = jQuery('<a href="#" class="button">')
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

FrontEndEditor.define_field( 'image_rich', 'image_base', {
	button_text: FrontEndEditor.data.image ? FrontEndEditor.data.image.insert : null,

	ajax_set: function (url) {
		var editor = this.data.editor;

		editor.execCommand(this.data.command, url, null, this.data.button);

		tb_remove();
		editor.focus();
	}
});

(function () {
	var buttons = jQuery.cleditor.buttons;

	buttons.wpimage = {
		'command': buttons.image.command,
		'stripIndex': buttons.image.stripIndex,
		'title': buttons.image.title,
		'name': 'wpimage',
		'popupName': undefined,
		'buttonClick': function(event, data) {
			var editor = FrontEndEditor.get_field_instance('image_rich');

			editor.data = data;
			editor.start_editing();

			return false;
		}
	};

	// Add the button to the default controls
	jQuery.cleditor.defaultOptions.controls = jQuery.cleditor.defaultOptions.controls
		.replace("image ", "wpimage ");
}());

}


FrontEndEditor.define_field( 'image', 'image_base', {

	start_editing: function (ev) {
		var self = this;

		self._super(ev);

		jQuery('<a id="fee-img-revert" href="#">')
			.text(FrontEndEditor.data.image.revert)
			.click(function (ev) {
				self.ajax_set(-1);
			})
			.insertAfter('#TB_ajaxWindowTitle');
	},

	ajax_set_handler: function (response) {
		var
			self = this,
			url = response.content;

		if ( '-1' === url ) {
			location.reload(true);
		} else {
			self.el.find('img').attr('src', url);
			tb_remove();
		}
	}
});


FrontEndEditor.define_field( 'thumbnail', 'image', {
	replace_button: function (ev) {
		var self = this;

		jQuery(ev.target).contents().find('#tab-type_url').remove();

		self._super(ev);
	},

	content_from_input: function ($item) {
		return $item.attr('id').replace('media-item-', '');
	}
});

