FrontEndEditor.define_field( 'image_base', 'base', {
	button_text: FrontEndEditor.data.image ? FrontEndEditor.data.image.change : null,

	start_editing: function () {
		var self = this;

		tb_show(self.button_text, FrontEndEditor.data.image.url);

		jQuery('#TB_closeWindowButton img').attr('src', FrontEndEditor.data.image.tb_close);

		jQuery('#TB_iframeContent').load( function (ev) {
			var $thickbox = jQuery(ev.target).contents();

			self.thickbox_load($thickbox);

			if ( jQuery.noop !== self.media_item_manipulation )
				self.media_item_handler($thickbox);
		});
	},

	thickbox_load: function ($thickbox) {
		var self = this;

		$thickbox.delegate('.media-item :submit', 'click', function () {
			var
				$button = jQuery(this),
				data = $button.closest('form').serializeArray();

			data.push({name: $button.attr('name'), value: $button.attr('name')});
			data.push({name: 'action', value: 'fee_image_insert'});

			jQuery.post(
				FrontEndEditor.data.ajax_url,
				data,
				jQuery.proxy(self, 'image_html_handler')
			);

			return false;
		});
	},

	media_item_handler: function ($thickbox) {
		var self = this;

		$thickbox.delegate('.media-item', 'mouseenter', function () {
			var $item = jQuery(this);

			if ( !$item.find('.pinkynail').length ) {
				return;	// not done uploading yet
			}

			if ( $item.data('fee_altered') ) {
				return;	// already modified
			}

			self.media_item_manipulation($item);

			$item.data('fee_altered', true);
		});
	},

	media_item_manipulation: function ($item) {
		$item.find('#go_button').remove();
		$item.find(':submit').val(this.button_text);
	}
});


if ( GENTICS ) {

FrontEndEditor.define_field( 'image_rich', 'image_base', {
	button_text: FrontEndEditor.data.image ? FrontEndEditor.data.image.insert : null,

	start_editing: function() {
		jQuery('#GENTICS_floatingmenu_shadow, .GENTICS_floatingmenu').hide();
		this._super();
	},

	media_item_manipulation: jQuery.noop,

	image_html_handler: function (html) {
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

	start_editing: function () {
		var self = this;

		self._super();

		jQuery('<a id="fee-img-revert" href="#">')
			.text(FrontEndEditor.data.image.revert)
			.click(function (ev) {
				self.ajax_set(-1);
				return false;
			})
			.insertAfter('#TB_ajaxWindowTitle');
	},

	media_item_manipulation: function($item) {
		$item.find('tbody tr').not('.image-size, .submit').remove();
		this._super($item);
	},

	image_html_handler: function (html) {
		var $html = jQuery(html);
		if ( $html.is('a') )
			$html = $html.find('img');

		this.ajax_set( $html.attr('src') );
	},

	ajax_set_handler: function (response) {
		var url = response.content;

		if ( '-1' === url ) {
			location.reload(true);
		} else {
			this.el.find('img').attr('src', url);
			tb_remove();
		}
	}
});


FrontEndEditor.define_field( 'thumbnail', 'image', {

	thickbox_load: function ($thickbox) {
		var self = this;

		$thickbox.find('#tab-type_url').remove();

		$thickbox.delegate('.media-item :submit', 'click', function () {
			var
				$item = jQuery(this).closest('.media-item'),
				attr_id = $item.attr('id').replace('media-item-', '');

			self.ajax_set(attr_id);

			return false;
		});
	},

	media_item_manipulation: function($item) {
		$item.find('tbody tr').not('.submit').remove();
		this._super($item);
	}
});

