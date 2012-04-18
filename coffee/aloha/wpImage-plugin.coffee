# Plugin for integrating the WordPress media thickbox

define [ 'aloha/plugin', 'aloha/floatingmenu', 'i18n!aloha/nls/i18n' ], (Plugin, FloatingMenu, i18nCore) ->
	Aloha = window.Aloha

	return Plugin.create('wpImage', {

		init: ->
			button = new Aloha.ui.Button({
				'name' : 'wpImage',
				'iconClass' : 'ImageWP',
				'size' : 'small',
				'onclick' : @insert,
				'tooltip' : FrontEndEditor.data.image.insert
			})

			FloatingMenu.addButton(
				'Aloha.continuoustext',
				button,
				i18nCore.t('floatingmenu.tab.insert'),
				2
			)

		insert: ->
			instance = new FrontEndEditor.fieldTypes.image_rich
			instance.start_editing()
	})
