/**
 * Plugin for integrating the WordPress media thickbox
 */
GENTICS.Aloha.ImageWP = jQuery.extend( new GENTICS.Aloha.Plugin('com.gentics.aloha.plugins.ImageWP'), {

	init: function () {
		// create a new button
		var button = new GENTICS.Aloha.ui.Button({
			'iconClass' : 'ImageWP',
			'size' : 'small',
			'onclick' : jQuery.proxy(this, 'insert'),
			'tooltip' : FrontEndEditor.data.image.insert
		});

		// add it to the floating menu
		GENTICS.Aloha.FloatingMenu.addButton(
			'GENTICS.Aloha.continuoustext',
			button,
			GENTICS.Aloha.i18n(GENTICS.Aloha, 'floatingmenu.tab.insert'),
			2
		);
	},

	insert: function() {
		FrontEndEditor.get_field_instance('image_rich').start_editing();
	}
});
