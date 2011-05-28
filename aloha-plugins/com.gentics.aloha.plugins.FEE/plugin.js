/**
 * Plugin that adds the Save and cancel buttons
 */
GENTICS.Aloha.FEE = jQuery.extend( new GENTICS.Aloha.Plugin('com.gentics.aloha.plugins.FEE'), {

	init: function () {
		// create the Save button
		var save_button = new GENTICS.Aloha.ui.Button({
			'iconClass' : 'GENTICS_button Example_strong',
			'size' : 'small',
			'onclick' : jQuery.proxy(this, 'save'),
			'tooltip' : FrontEndEditor.data.save_text
		});

		// create the Cancel button
		var cancel_button = new GENTICS.Aloha.ui.Button({
			'iconClass' : 'GENTICS_button Example_strong',
			'size' : 'small',
			'onclick' : jQuery.proxy(this, 'cancel'),
			'tooltip' : FrontEndEditor.data.cancel_text
		});

		// add buttons to the floating menu
		GENTICS.Aloha.FloatingMenu.addButton(
			'GENTICS.Aloha.continuoustext',
			save_button,
			GENTICS.Aloha.i18n(GENTICS.Aloha, 'floatingmenu.tab.format'),
			4
		);

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
