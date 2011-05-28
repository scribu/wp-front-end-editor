/**
 * Plugin that adds the Save and cancel buttons
 */
GENTICS.Aloha.FEE = jQuery.extend( new GENTICS.Aloha.Plugin('com.gentics.aloha.plugins.FEE'), {

	init: function () {
		// create the Save button
		var save_button = new GENTICS.Aloha.ui.Button({
			'onclick' : jQuery.proxy(this, 'save'),
			'label' : FrontEndEditor.data.save_text
		});

		// add it to the floating menu
		GENTICS.Aloha.FloatingMenu.addButton(
			'GENTICS.Aloha.continuoustext',
			save_button,
			GENTICS.Aloha.i18n(GENTICS.Aloha, 'floatingmenu.tab.format'),
			4
		);

		// create the Cancel button
		var cancel_button = new GENTICS.Aloha.ui.Button({
			'onclick' : jQuery.proxy(this, 'cancel'),
			'label' : FrontEndEditor.data.cancel_text
		});

		// add it to the floating menu
		GENTICS.Aloha.FloatingMenu.addButton(
			'GENTICS.Aloha.continuoustext',
			cancel_button,
			GENTICS.Aloha.i18n(GENTICS.Aloha, 'floatingmenu.tab.format'),
			4
		);
	},

	save: function() {
		alert('save');
	},

	cancel: function() {
		alert('cancel');
	}
});
