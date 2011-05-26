FrontEndEditor.define_field( 'image_base', 'base', {
	button_text: FrontEndEditor.data.image ? FrontEndEditor.data.image.change : null,

	start_editing: function () {
		tb_show(this.button_text, FrontEndEditor.data.image.url);

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


if ( GENTICS ) {

FrontEndEditor.define_field( 'image_rich', 'image_base', {
	button_text: FrontEndEditor.data.image ? FrontEndEditor.data.image.insert : null,

	start_editing: function() {
		jQuery('#GENTICS_floatingmenu_shadow, .GENTICS_floatingmenu').hide();
		this._super();
	},

	replace_button: function (ev) {
		var self = this;

		jQuery(ev.target).contents().delegate('.media-item :submit', 'click', function () {
			var
				$button = jQuery(this),
				data = $button.closest('form').serializeArray();

			data.push({name: $button.attr('name'), value: $button.attr('name')});
			data.push({name: 'action', value: 'fee_image_insert'});

			jQuery.post(
				FrontEndEditor.data.ajax_url,
				data,
				jQuery.proxy(self, 'ajax_set_handler')
			);

			return false;
		});
	},

	ajax_set_handler: function (html) {
		GENTICS.Utils.Dom.insertIntoDOM(
			jQuery(html),
			GENTICS.Aloha.Selection.getRangeObject(),
			jQuery(GENTICS.Aloha.activeEditable.obj)
		);

		tb_remove();
		jQuery('#GENTICS_floatingmenu_shadow, .GENTICS_floatingmenu').show();
	}
});

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

