# Plugin that adds the Save and cancel buttons

define [ 'aloha/plugin', 'aloha/floatingmenu', 'i18n!aloha/nls/i18n' ], (Plugin, FloatingMenu, i18nCore) ->
	Aloha = window.Aloha

	return Plugin.create('wpSaveCancel', {
		init: ->
			# create the Save button
			this.saveButton = new Aloha.ui.Button(
				'name' : 'wpSave',
				'iconClass' : 'SaveFEE',
				'size' : 'small',
				'onclick' : jQuery(this, 'save'),
				'label' : FrontEndEditor.data.save_text
			)

			# create the Cancel button
			this.cancelButton = new Aloha.ui.Button(
				'name' : 'wpCancel',
				'iconClass' : 'CancelFEE',
				'size' : 'small',
				'onclick' : jQuery(this, 'cancel'),
				'label' : FrontEndEditor.data.cancel_text
			)

			# add buttons to the floating menu
			FloatingMenu.addButton(
				'Aloha.continuoustext',
				this.saveButton,
				i18nCore.t('floatingmenu.tab.format'),
				4
			)

			FloatingMenu.addButton(
				'Aloha.continuoustext',
				this.cancelButton,
				i18nCore.t('floatingmenu.tab.format'),
				4
			)

		save: ->
			FrontEndEditor.current_field.ajax_set()

			@cancel()

		cancel: ->
			FrontEndEditor.current_field.remove_form()
	})
