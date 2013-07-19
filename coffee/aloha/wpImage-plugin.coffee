# Plugin for integrating the WordPress media thickbox

define [ 'aloha/plugin', 'ui/ui', 'ui/button', 'i18n!aloha/nls/i18n' ], (Plugin, Ui, Button, i18nCore) ->
	Aloha = window.Aloha

	return Plugin.create('wpImage', {

		init: ->
			Ui.adopt('wpImage', Button, {
		        'name': 'wpImage',
		        'iconClass': 'ImageWP',
		        'onclick': this.insert,
		        'tooltip': i18nCore.t('floatingmenu.tab.insert'),
		        'scope': 'Aloha.continuoustext'
      		})

		insert: ->
			instance = new FrontEndEditor.fieldTypes.image_rich
			instance.start_editing()
	})
