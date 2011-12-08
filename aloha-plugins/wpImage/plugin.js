/**
 * Plugin for integrating the WordPress media thickbox
 */
define([
	// js
	'aloha/jquery',
	'aloha/plugin',
	'aloha/floatingmenu',
	'i18n!aloha/nls/i18n'
],
function WPImagePlugin ( aQuery, Plugin, FloatingMenu, i18nCore) {
	
	var jQuery = aQuery;
	var $ = aQuery;
	var GENTICS = window.GENTICS, Aloha = window.Aloha;

	return Plugin.create('org.fee.plugins.wpImage', {

		init: function () {
			
			// create a new button
			var button = new Aloha.ui.Button({
				'iconClass' : 'ImageWP',
				'size' : 'small',
				'onclick' : jQuery.proxy(this, 'insert'),
				'tooltip' : FrontEndEditor.data.image.insert
			});

			// add it to the floating menu
			FloatingMenu.addButton(
				'GENTICS.Aloha.continuoustext',
				button,
				i18nCore.t('floatingmenu.tab.insert'),
				2
			);
		},

		insert: function() {
			FrontEndEditor.get_field_instance('image_rich').start_editing();
		}
		
	});	
});