define(['aloha/plugin', 'aloha/floatingmenu', 'i18n!aloha/nls/i18n'], function(Plugin, FloatingMenu, i18nCore){
  var Aloha;
  Aloha = window.Aloha;
  return Plugin.create('org.fee.plugins.wpSaveCancel', {
    init: function(){
      this.saveButton = new Aloha.ui.Button({
        'iconClass': 'SaveFEE',
        'size': 'small',
        'onclick': jQuery.proxy(this, 'save'),
        'tooltip': FrontEndEditor.data.save_text
      });
      this.cancelButton = new Aloha.ui.Button({
        'iconClass': 'CancelFEE',
        'size': 'small',
        'onclick': jQuery.proxy(this, 'cancel'),
        'tooltip': FrontEndEditor.data.cancel_text
      });
      FloatingMenu.addButton('GENTICS.Aloha.continuoustext', this.saveButton, i18nCore.t('floatingmenu.tab.format'), 4);
      return FloatingMenu.addButton('GENTICS.Aloha.continuoustext', this.cancelButton, i18nCore.t('floatingmenu.tab.format'), 4);
    },
    save: function(){
      this.current_field.ajax_set();
      return this.disableAloha();
    },
    cancel: function(){
      this.current_field.remove_form();
      return this.disableAloha();
    },
    disableAloha: function(){
      return Aloha.deactivateEditable();
    }
  });
});