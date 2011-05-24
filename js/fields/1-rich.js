//if ( FrontEndEditor.data.cleditor ) {

FrontEndEditor.define_field( 'rich', 'textarea', {
	
	/**
	 * Load the content for this rich element via ajax from the backend 
	 */
	ajax_get: function () {
		var that = this;

		that.overlay.show();
		
		var data = that.ajax_args({
			callback: 'get'
		});

		FrontEndEditor.sync_load(jQuery.proxy(that, 'ajax_get_handler'), data, that.dependency);

	},
	
	/**
	 * Handle the response for the content loading
	 */
	ajax_get_handler: function (response) {
		var	that = this;
		var $el = that.error_handler(response);

		if ( !$el ) {
			return;
		}

		//Replace the displayed content with the raw content from the database
		that.el.html(response.content);
		that.enable_editable();
		
	},
	
	content_to_front: function(content) {
		
	},
	
	/**
	 * Callback that gets called when the editable gets deactivated.
	 */
	save_editable: function (event, editableDiv) {
		 var that = this;
		 var postId = editableDiv.editable.obj.attr('data-post_id');
		 var content = editableDiv.editable.getContents();
		 if ( console != undefined ) {
			 console.log('Saving' + content + " for post with id "  + postId);
		 }
		 that.ajax_set(content);
	},

	/**
	 * Enable this element for aloha editing
	 */
	enable_editable: function () {
		var that = this;
		that.el.aloha();
		that.el.focus();
		GENTICS.Aloha.EventRegistry.subscribe(GENTICS.Aloha, "editableDeactivated", jQuery.proxy(that, 'save_editable') );
	},

//	// Copied from wp-admin/js/editor.dev.js
//	pre_wpautop: function (content) {
//		var blocklist1, blocklist2;
//
//		// Protect pre|script tags
//		content = content.replace(/<(pre|script)[^>]*>[\s\S]+?<\/\1>/g, function (a) {
//			a = a.replace(/<br ?\/?>[\r\n]*/g, '<wp_temp>');
//			return a.replace(/<\/?p( [^>]*)?>[\r\n]*/g, '<wp_temp>');
//		});
//
//		// Pretty it up for the source editor
//		blocklist1 = 'blockquote|ul|ol|li|table|thead|tbody|tfoot|tr|th|td|div|h[1-6]|p|fieldset';
//		content = content.replace(new RegExp('\\s*</('+blocklist1+')>\\s*', 'g'), '</$1>\n');
//		content = content.replace(new RegExp('\\s*<(('+blocklist1+')[^>]*)>', 'g'), '\n<$1>');
//
//		// Mark </p> if it has any attributes.
//		content = content.replace(/(<p [^>]+>.*?)<\/p>/g, '$1</p#>');
//
//		// Sepatate <div> containing <p>
//		content = content.replace(/<div([^>]*)>\s*<p>/gi, '<div$1>\n\n');
//
//		// Remove <p> and <br />
//		content = content.replace(/\s*<p>/gi, '');
//		content = content.replace(/\s*<\/p>\s*/gi, '\n\n');
//		content = content.replace(/\n[\s\u00a0]+\n/g, '\n\n');
//		content = content.replace(/\s*<br ?\/?>\s*/gi, '\n');
//
//		// Fix some block element newline issues
//		content = content.replace(/\s*<div/g, '\n<div');
//		content = content.replace(/<\/div>\s*/g, '</div>\n');
//		content = content.replace(/\s*\[caption([^\[]+)\[\/caption\]\s*/gi, '\n\n[caption$1[/caption]\n\n');
//		content = content.replace(/caption\]\n\n+\[caption/g, 'caption]\n\n[caption');
//
//		blocklist2 = 'blockquote|ul|ol|li|table|thead|tbody|tfoot|tr|th|td|h[1-6]|pre|fieldset';
//		content = content.replace(new RegExp('\\s*<(('+blocklist2+') ?[^>]*)\\s*>', 'g'), '\n<$1>');
//		content = content.replace(new RegExp('\\s*</('+blocklist2+')>\\s*', 'g'), '</$1>\n');
//		content = content.replace(/<li([^>]*)>/g, '\t<li$1>');
//
//		if ( content.indexOf('<object') != -1 ) {
//			content = content.replace(/<object[\s\S]+?<\/object>/g, function (a) {
//				return a.replace(/[\r\n]+/g, '');
//			});
//		}
//
//		// Unmark special paragraph closing tags
//		content = content.replace(/<\/p#>/g, '</p>\n');
//		content = content.replace(/\s*(<p [^>]+>[\s\S]*?<\/p>)/g, '\n$1');
//
//		// Trim whitespace
//		content = content.replace(/^\s+/, '');
//		content = content.replace(/[\s\u00a0]+$/, '');
//
//		// put back the line breaks in pre|script
//		content = content.replace(/<wp_temp>/g, '\n');
//
//		return content;
//	}
});

//}
