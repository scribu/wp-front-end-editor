jQuery(document).ready(function($){
	if ( frontEditorData._loaded )
		return;
	frontEditorData._loaded = true;

	var spinner = $('<img>').attr({
		'src': frontEditorData.spinner,
		'class': 'front-editor-spinner'
	});

	var editableField = function(el, args)
	{
		var field = this;

		field.set_el(el);
		field.name = args[0];

		// Set id, type attribute
		field.id = field.el.attr('id').substr(4);
		var parts = field.id.split('#');

		if (parts.length == 3)
			field.type = parts[2];
		else
			field.type = args[1];

		field.spinner = spinner.clone();

		field.el.click(function(ev) { field.click(ev) });
		field.el.dblclick(function(ev) { field.dblclick(ev) });
	}

	editableField.prototype = 
	{
		set_el : function(el)
		{
			this.el = $(el);

			// From a > .front-ed > content
			// To .front-ed > a > content
			var $parent = this.el.parents('a');

			if ( !$parent.length )
				return;

			var $link = $parent.clone(true)
				.html(this.el.html());

			var $wrap = this.el.clone(true)
				.html($link);

			$parent.replaceWith($wrap);

			this.el = $wrap;
		},

		click : function(ev)
		{
			var $el = $(ev.target);

			var is_overlay = function()
			{
				var attr = $el.attr('id') + ' ' + $el.attr("class");
				attr = $.trim(attr).split(' ');

				var tokens = ['lightbox', 'shutter', 'thickbox'];

				for ( i in tokens )
					for ( j in attr )
						if ( attr[j].indexOf(tokens[i]) != -1 )
							return true;

				return false;
			}

			// Child single click
			if ( $el.is('a') && !is_overlay() )
			{
				ev.stopPropagation();
				ev.preventDefault();

				frontEditorData.to_click = $el;
			}

			setTimeout(function()
			{
				if ( frontEditorData.trap )
					return;

				var $el = frontEditorData.to_click;

				if ( typeof $el == 'undefined' || typeof $el.attr('href') == 'undefined' )
					return;

				if ( $el.attr('target') == '_blank' )
					window.open($el.attr('href'));
				else
					window.location.href = $el.attr('href');
			}, 300);
		},

		dblclick : function(ev)
		{
			var field = this;

			ev.stopPropagation();
			ev.preventDefault();

			frontEditorData.trap = true;

			field.form_handler();
		},

		form_handler : function()
		{
			var field = this;

			field.get_data();

			var submit_form = function()
			{
				field.send_data();
				remove_form(true);
			};

			var remove_form = function(with_spinner)
			{
				frontEditorData.trap = false;

				field.form.remove();

				if (with_spinner === true)
					field.el.before(field.spinner.show());
				else
					field.el.show();
					
				field.el.trigger('fee_remove_form');
			};

			// Setup form buttons
			field.save_button = $('<button>')
				.attr({'class': 'front-editor-save', 'title': frontEditorData.save_text})
				.text(frontEditorData.save_text)
				.click(submit_form);

			field.cancel_button = $('<button>')
				.attr({'class': 'front-editor-cancel', 'title': frontEditorData.cancel_text})
				.text('X')
				.click(remove_form);

			// Create form
			if ( field.type == 'input' )
				field.form = $('<span>');
			else
				field.form = $('<div>');

			field.form
				.addClass('front-editor-container')
				.append(field.save_button)
				.append(field.cancel_button);

			field.el.hide().after(field.spinner.show());

			field.form.keypress(function(ev) { field.keypress(ev); });
		},

		keypress : function(ev)
		{
			var field = this;

			var keys = {ENTER: 13, ESCAPE: 27};

			var code = (ev.keyCode || ev.which || ev.charCode || 0);

			if (code == keys.ENTER && field.type == 'input')
				field.save_button.click();
			else if (code == keys.ESCAPE)
				field.cancel_button.click();
		},

		setup_input : function(content)
		{
			var field = this;

			var jwysiwyg_args = {
				controls : {
					justifyLeft			: { visible : true },
					justifyCenter		: { visible : true },
					justifyRight		: { visible : true },
					separator04			: { visible : true },
					insertOrderedList	: { visible : true },
					insertUnorderedList	: { visible : true },
					html				: { visible : true }
				}
			}

			field.input = (field.type == 'input') ? $('<input type="text">') : $('<textarea>');

			field.input
				.addClass('front-editor-content')
				.val(content)
				.prependTo(field.form);

			field.spinner.hide().replaceWith(field.form);

			if (field.type == 'rich')
			{
				field.input.trigger('pre_wysiwyg_init');

				field.input.wysiwyg(jwysiwyg_args);

				field.wysiwyg_enhancements();
			}
			else if (field.type == 'textarea')
				field.input.autogrow({lineHeight: 16});

			field.input.focus();
		},
		
		wysiwyg_enhancements : function()
		{
			var field = this;
			var $iframe = field.form.find('#IFrame');
			var $frame = $iframe.contents();

			$iframe.trigger('wysiwyg_init');

			// Hotkeys
			$frame.keypress(function(e) { field.keypress(e); });

			// Extra CSS
			if ( typeof frontEditorData.css != 'undefined' )
				var css = "@import url('" + frontEditorData.css + "');\n";
			else
				var css = '';

			css += 'img.alignleft {float:left; margin: 0 1em .5em 0} img.alignright {float:right; margin: 0 0 .5em 1em} img.aligncenter {display:block; margin:0 auto .5em auto}';

			$('<style type="text/css">' + css + '</style>')
				.appendTo($frame.find('head'));

			// Autogrow
			if ( $.browser.msie )
				return $iframe.css('height', '200px');

			var $body = $frame.find('body')
				.css('overflow', 'hidden');

			var intid = setInterval(function() {
				var should_be_height = $body.height() + 32 + 20;	// height + margin + space

				if (should_be_height != $iframe.height())
					$iframe.height(should_be_height);
			}, 400);

			field.el.bind('fee_remove_form', function() {
				clearInterval(intid);
			});
			
			$iframe.trigger('wysiwyg_init');
		},

		get_data : function()
		{
			var field = this;

			var data = {
				nonce: frontEditorData.nonce,
				action: 'front-editor',
				callback: 'get',
				name: field.name,
				type: field.type,
				item_id: field.id
			};

			$.post(frontEditorData.request, data, function(response){
				field.setup_input(response);
			});
		},

		send_data : function()
		{
			var field = this;

			field.el.before(field.spinner.show());

			if (field.type == 'rich')
				field.input.trigger('wysiwyg_save');

			var data = {
				nonce: frontEditorData.nonce,
				action: 'front-editor',
				callback: 'save',
				name: field.name,
				type: field.type,
				item_id: field.id,
				content: field.pre_wpautop(field.input.val())
			};

			$.post(frontEditorData.request, data, function(response){
				field.el.html(response);
				field.spinner.hide();
				field.el.show();
			});
		},

		// Copied from wp-admin/js/editor.dev.js		
		pre_wpautop : function(content) {
			var blocklist1, blocklist2;

			// Protect pre|script tags
			content = content.replace(/<(pre|script)[^>]*>[\s\S]+?<\/\1>/g, function(a) {
				a = a.replace(/<br ?\/?>[\r\n]*/g, '<wp_temp>');
				return a.replace(/<\/?p( [^>]*)?>[\r\n]*/g, '<wp_temp>');
			});

			// Pretty it up for the source editor
			blocklist1 = 'blockquote|ul|ol|li|table|thead|tbody|tr|th|td|div|h[1-6]|p';
			content = content.replace(new RegExp('\\s*</('+blocklist1+')>\\s*', 'mg'), '</$1>\n');
			content = content.replace(new RegExp('\\s*<(('+blocklist1+')[^>]*)>', 'mg'), '\n<$1>');

			// Mark </p> if it has any attributes.
			content = content.replace(new RegExp('(<p [^>]+>.*?)</p>', 'mg'), '$1</p#>');

			// Sepatate <div> containing <p>
			content = content.replace(new RegExp('<div([^>]*)>\\s*<p>', 'mgi'), '<div$1>\n\n');

			// Remove <p> and <br />
			content = content.replace(new RegExp('\\s*<p>', 'mgi'), '');
			content = content.replace(new RegExp('\\s*</p>\\s*', 'mgi'), '\n\n');
			content = content.replace(new RegExp('\\n\\s*\\n', 'mgi'), '\n\n');
			content = content.replace(new RegExp('\\s*<br ?/?>\\s*', 'gi'), '\n');

			// Fix some block element newline issues
			content = content.replace(new RegExp('\\s*<div', 'mg'), '\n<div');
			content = content.replace(new RegExp('</div>\\s*', 'mg'), '</div>\n');
			content = content.replace(new RegExp('\\s*\\[caption([^\\[]+)\\[/caption\\]\\s*', 'gi'), '\n\n[caption$1[/caption]\n\n');
			content = content.replace(new RegExp('caption\\]\\n\\n+\\[caption', 'g'), 'caption]\n\n[caption');

			blocklist2 = 'blockquote|ul|ol|li|table|thead|tr|th|td|h[1-6]|pre';
			content = content.replace(new RegExp('\\s*<(('+blocklist2+') ?[^>]*)\\s*>', 'mg'), '\n<$1>');
			content = content.replace(new RegExp('\\s*</('+blocklist2+')>\\s*', 'mg'), '</$1>\n');
			content = content.replace(new RegExp('<li([^>]*)>', 'g'), '\t<li$1>');

			if ( content.indexOf('<object') != -1 ) {
				content = content.replace(/<object[\s\S]+?<\/object>/g, function(a){
					return a.replace(/[\r\n]+/g, '');
				});
			}

			// Unmark special paragraph closing tags
			content = content.replace(new RegExp('</p#>', 'g'), '</p>\n');
			content = content.replace(new RegExp('\\s*(<p [^>]+>.*</p>)', 'mg'), '\n$1');

			// Trim whitespace
			content = content.replace(new RegExp('^\\s*', ''), '');
			content = content.replace(new RegExp('[\\s\\u00a0]*$', ''), '');

			// put back the line breaks in pre|script
			content = content.replace(/<wp_temp>/g, '\n');

			// Hope.
			return content;
		}
	};

	// Widget text hack: Add id attr to each element
	$('.front-ed-widget_text, .front-ed-widget_title').each(function(){
		var $el = $(this);
		var id = $el.parents('.widget_text').attr('id');
		if (id)
			$el.attr('id', 'fee_' + id);
		else
			$el.attr('class', '');	// not a text widget
	});

	// Start click handling
	for ( var i in frontEditorData.fields )
	{
		var args = frontEditorData.fields[i];

		$('.front-ed-' + args[0]).each(function(){
			new editableField(this, args);
		});
	}
});
