/**
 * Plugin that adds the Save and cancel buttons
 */
define([
	// js
	'aloha/jquery',
	'aloha/plugin',
	'aloha/floatingmenu',
	'i18n!aloha/nls/i18n',
	'i18n!wpSaveCancel/nls/i18n'
],
function WPImagePlugin ( aQuery, Plugin, FloatingMenu, i18nCore, i18n) {
	
	var jQuery = aQuery;
	var $ = aQuery;
	var GENTICS = window.GENTICS, Aloha = window.Aloha;

	return Plugin.create('org.fee.plugins.wpSaveCancel', {

		init: function () {
		// create the Save button
		this.saveButton = new Aloha.ui.Button({
			'iconClass' : 'SaveFEE',
			'size' : 'small',
			'onclick' : jQuery.proxy(this, 'save'),
			'tooltip' : FrontEndEditor.data.save_text
		});

		// create the Cancel button
		this.cancelButton = new Aloha.ui.Button({
			'iconClass' : 'CancelFEE',
			'size' : 'small',
			'onclick' : jQuery.proxy(this, 'cancel'),
			'tooltip' : FrontEndEditor.data.cancel_text
		});

		// add buttons to the floating menu
		FloatingMenu.addButton(
			'GENTICS.Aloha.continuoustext',
			this.saveButton,
			i18nCore.t('floatingmenu.tab.format'),
			4
		);

		FloatingMenu.addButton(
			'GENTICS.Aloha.continuoustext',
			this.cancelButton,
			i18nCore.t('floatingmenu.tab.format'),
			4
		);
	},

	save: function() {
		this.current_field.ajax_set();

		this.disableAloha();
	},

	cancel: function() {
		this.current_field.remove_form();

		this.disableAloha();
	},

	disableAloha: function() {
		Aloha.deactivateEditable()
	}
});
