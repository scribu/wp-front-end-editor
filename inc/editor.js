jQuery(document).ready(function($)
{
	var editableField = function(el, args)
	{
		this.set_el(el);
		this.name = args[0];

		// Set type, based on rel attribute
		var rel = this.el.attr('rel').split('#');

		if (rel.length == 3)
			this.type = rel[2];
		else
			this.type = args[1];

		var field = this;

		field.el.click(field.click);

		field.el.dblclick(function(ev)
		{
			ev.stopPropagation();
			ev.preventDefault();

			frontEditorData.trap = true;

			field.form_handler();
		});
	};

	editableField.prototype = 
	{
		set_el : function(el)
		{
			this.el = $(el);

			// From a > span > content
			// To span > a > content
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
			$el = $(ev.target);
			var is_overlay = function($el)
			{
				var attr = $el.attr("rel") + ' ' + $el.attr("class");
				attr = jQuery.trim(attr).split(' ');

				var tokens = ['lightbox', 'shutter', 'thickbox'];

				for ( i in tokens )
					for ( j in attr )
						if ( attr[j].indexOf(tokens[i]) != -1 )
							return true;

				return false;
			}

			// Child single click
			if ( $el.is('a') && !is_overlay($el) )
			{
				ev.stopPropagation();
				ev.preventDefault();

				frontEditorData.clicked = $el.attr('href');
			}

			setTimeout(function()
			{
				if ( frontEditorData.trap )
					return;

				if ( typeof(frontEditorData.clicked) != 'undefined' )
					window.location = frontEditorData.clicked;
			}, 300);
		},

		form_handler : function()
		{
			var field = this;

			if (field.type != 'input')
				field.container = $('<textarea>');
			else
				field.container = $('<input type="text">');

			field.container.addClass('front-editor-content');

			var remove_form = function()
			{
				frontEditorData.trap = false;

				field.el.show();
				form.remove();
			};

			// Set up form buttons
			var save_button = $('<button>')
				.attr({'class': 'front-editor-save', 'title': frontEditorData.save_text})
				.text(frontEditorData.save_text)
				.click(function(ev)
				{
					field.send_data();
					remove_form();
				});

			var cancel_button = $('<button>')
				.attr({'class': 'front-editor-cancel', 'title': frontEditorData.cancel_text})
				.text('X')
				.click(remove_form);

			// Create form
			var form = $('<div>')
				.addClass('front-editor-container')
				.append(field.container)
				.append(save_button)
				.append(cancel_button);

			field.el.hide().after(form);

			field.get_data();
		},

		get_data : function()
		{
			var data = {
				nonce: frontEditorData['nonce'],
				action: 'front-editor',
				callback: 'get',
				name: this.name,
				type: this.type,
				item_id: this.el.attr('rel')
			};

			var field = this;
			jQuery.post(frontEditorData['request'], data, function(response)
			{
				field.container.val(response);

				if (field.type == 'rich')
					field.container.wysiwyg({
						controls : {
							justifyLeft         : { visible : true },
							justifyCenter       : { visible : true },
							justifyRight        : { visible : true },
							separator04         : { visible : true },
							insertOrderedList   : { visible : true },
							insertUnorderedList : { visible : true },
							html				: { visible : true },
						}
					});
				else if (field.type == 'textarea')
					field.container.autogrow({lineHeight: 16});
			});
		},

		send_data : function()
		{
			var data = {
				nonce: frontEditorData['nonce'],
				action: 'front-editor',
				callback: 'save',
				name: this.name,
				type: this.type,
				item_id: this.el.attr('rel'),
				content: this.container.val()
			};

			var field = this;
			jQuery.post(frontEditorData['request'], data, function(response)
			{
				var speed = 'fast';

				if (field.type != 'input')
				{
					field.el.css('display', 'block');
					speed = 'normal';
				}

				field.el.fadeOut(speed, function()
				{
					field.el.html(response).fadeIn(speed);
				});
			});
		}
	};

	// Widget text hack: Add rel attr to each element
	$('.front-ed-widget_text, .front-ed-widget_title').each(function()
	{
		var $el = $(this);
		var id = $el.parents('.widget_text').attr('id');
		if (id)
			$el.attr('rel', id);
		else
			$el.attr('class', '');	// not a text widget
	});

	// Start click handling
	for ( var i in frontEditorData['fields'] )
	{
		var args = frontEditorData['fields'][i];
		$('.front-ed-' + args[0]).each(function()
		{
			new editableField(this, args);
		});
	}
});

