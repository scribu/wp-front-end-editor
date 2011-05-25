/**
 *
 */
GENTICS.Aloha.Fee = new GENTICS.Aloha.Plugin('com.gentics.aloha.plugins.Fee');


//GENTICS.Aloha.Aewip.languages = ['en', 'de'];


GENTICS.Aloha.Fee.scopes = { "aloha":0, "markdown":1 };

/**
* Default scope is aloha
*/
GENTICS.Aloha.Fee.scope = GENTICS.Aloha.Fee.scopes['aloha'];

GENTICS.Aloha.Fee.init = function () {
	var that = this;

	//GENTICS.Aloha.Ribbon.hide();

	//GENTICS.Aloha.Ribbon.show();

	// create a new button
	  var that = this;

	  var button = new GENTICS.Aloha.ui.Button({
	    'iconClass' : 'GENTICS_button Example_strong',
	    'size' : 'small',
	    'onclick' : function () {
	                  that.save();
	            },
	    'tooltip' : that.i18n('button.save.tooltip')
	  });

	  // add it to the floating menu
	  GENTICS.Aloha.FloatingMenu.addButton(
	    'GENTICS.Aloha.continuoustext',
	    button,
	    GENTICS.Aloha.i18n(GENTICS.Aloha, 'floatingmenu.tab.format'),
	    4
	  );
	
}

GENTICS.Aloha.Fee.save = function() {
	alert('save');
}




