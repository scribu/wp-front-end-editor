/**
 * Plugin that adds the Save and cancel buttons
 */
GENTICS.Aloha.wpSaveCancel = jQuery.extend( new GENTICS.Aloha.Plugin('org.fee.plugins.wpSaveCancel'), {

	init: function () {
		// create the Save button
		this.saveButton = new GENTICS.Aloha.ui.Button({
			'iconClass' : 'SaveFEE',
			'size' : 'small',
			'onclick' : jQuery.proxy(this, 'save'),
			'tooltip' : FrontEndEditor.data.save_text
		});

		// create the Cancel button
		this.cancelButton = new GENTICS.Aloha.ui.Button({
			'iconClass' : 'CancelFEE',
			'size' : 'small',
			'onclick' : jQuery.proxy(this, 'cancel'),
			'tooltip' : FrontEndEditor.data.cancel_text
		});

		// add buttons to the floating menu
		GENTICS.Aloha.FloatingMenu.addButton(
			'GENTICS.Aloha.continuoustext',
			this.saveButton,
			GENTICS.Aloha.i18n(GENTICS.Aloha, 'floatingmenu.tab.format'),
			4
		);

		GENTICS.Aloha.FloatingMenu.addButton(
			'GENTICS.Aloha.continuoustext',
			this.cancelButton,
			GENTICS.Aloha.i18n(GENTICS.Aloha, 'floatingmenu.tab.format'),
			4
		);
	},

	save: function() {
		if (this.group != null) {
			this.group.ajax_set();
		} else {
			this.current_field.ajax_set();
		}

		this.disableAloha();
	},

	cancel: function() {
		if (this.group != null) {
			this.group.form_remove();
		} else {
			this.current_field.form_remove();
		}

		this.disableAloha();
	},

	disableAloha: function() {
		GENTICS.Aloha.deactivateEditable()
	}
});
