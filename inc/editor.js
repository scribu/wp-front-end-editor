function front_ed_init(vars)
{
	// constructor
	function editableField(el, args)
	{
		this.el = jQuery(el);
		this.name = args[0];

		// Set type, based on rel attribute
		rel = this.el.attr('rel').split('#');

		if (rel.length == 3)
			this.type = rel[2];
		else
			this.type = args[1];

		// From a span content
		// To span a content
		$parent = this.el.parents('a');
		if ( $parent.length )
		{
			$link = $parent.clone(true)
				.html(this.el.html());

			$wrap = this.el.clone(true)
				.html($link);

			$parent.replaceWith($wrap);

			this.el = $wrap;
		}
	}


	// AJAX handling
	get_data = function(field)
	{
		var get_data = {
			nonce: vars['nonce'],
			action: 'front-editor',
			callback: 'get',
			name: field.name,
			type: field.type,
			item_id: field.el.attr('rel')
		};

		jQuery.post(vars['request'], get_data, function(response)
		{
			field.container.val(response);

			if (field.type == 'rich')
				field.container.wysiwyg({
					controls : {
						separator04         : { visible : true },
						insertOrderedList   : { visible : true },
						insertUnorderedList : { visible : true },
						html				: { visible : true }
					}
				});
			else if (field.type == 'textarea')
				field.container.autogrow({lineHeight: 16});
		});
	}

	send_data = function(field)
	{
		var post_data = {
			nonce: vars['nonce'],
			action: 'front-editor',
			callback: 'save',
			name: field.name,
			type: field.type,
			item_id: field.el.attr('rel'),
			content: field.container.val()
		};

		jQuery.post(vars['request'], post_data, function(response)
		{
			var speed = 'fast';

			if (field.type != 'input')
			{
				field.el.css('display', 'block');
				speed = 'normal';
			}

			field.el.fadeOut(speed, function()
			{
				field.el.html(response);
			}).fadeIn(speed);
		});
	}


	// Form handling
	form_handler = function(field)
	{
		// Set up data container
		if (field.type != 'input')
			field.container = jQuery('<textarea>');
		else
			field.container = jQuery('<input type="text">');

		field.container.attr('class', 'front-editor-content');

		// Set up form buttons
		var save_button = jQuery('<button>')
			.attr({'class': 'front-editor-save', 'title': vars.save_text})
			.text(vars.save_text);

		var cancel_button = jQuery('<button>')
			.attr({'class': 'front-editor-cancel', 'title': vars.cancel_text})
			.text('X');

		// Create form
		var form = jQuery('<div>')
			.attr('class', 'front-editor-container')
			.append(field.container)
			.append(save_button)
			.append(cancel_button);

		// Add form	
		field.el.hide();
		field.el.after(form);

		get_data(field);

		remove_form = function()
		{
			window.frontEd_trap = false;

			field.el.show();
			form.remove();
		}

		cancel_button.click(remove_form);

		save_button.click(function(ev)
		{
			send_data(field);
			remove_form();
		});
	}

	// Click handling
	function click_handler(field)
	{
		single_click = function(ev)
		{
			$el = jQuery(ev.target);

			// Child single click
			if ( $el.is('a') && !is_overlay($el) )
			{
				ev.stopPropagation();
				ev.preventDefault();

				window.frontEd_url = $el.attr('href');
			}

			setTimeout(function()
			{
				if ( window.frontEd_trap )
					return;

				if ( typeof(window.frontEd_url) != 'undefined' )
					window.location = window.frontEd_url;
			}, 300);
		}

		double_click = function(ev)
		{
			ev.stopPropagation();
			ev.preventDefault();

			window.frontEd_trap = true;

			form_handler(field);
		}

		is_overlay = function($el)
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

		field.el
			.click(single_click)
			.dblclick(double_click);
	}

	// Widget text hack: Add rel attr to each element
	jQuery('span.front-ed-widget_text, span.front-ed-widget_title').each(function()
	{
		id = jQuery(this).parents('.widget_text').attr('id');
		if (id)
			jQuery(this).attr('rel', id);
		else
			jQuery(this).attr('class', '');	// not a text widget
	});

	// Start click handling
	for ( i in vars['fields'] )
	{
		args = vars['fields'][i];
		jQuery('span.front-ed-' + args[0]).each(function()
		{
			click_handler(new editableField(this, args));
		});
	}
}

