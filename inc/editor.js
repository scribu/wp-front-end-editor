jQuery(document).ready(function($)
{
	var spinner = $(new Image()).attr('src', frontEditorData.spinner);

	var editableField = function(el, args)
	{
		var field = this;

		field.set_el(el);
		field.name = args[0];

		// Set type, based on rel attribute
		var rel = field.el.attr('rel').split('#');

		if (rel.length == 3)
			field.type = rel[2];
		else
			field.type = args[1];

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
				attr = $.trim(attr).split(' ');

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

				frontEditorData.to_click = $el;
			}

			setTimeout(function()
			{
				if ( frontEditorData.trap )
					return;

				var $el = frontEditorData.to_click;

				if ( typeof($el) != 'undefined' )
					if ( $el.attr('target') == '_blank' )
						window.open($el.attr('href'));
					else
						window.location.href = $el.attr('href');
			}, 300);
		},

		form_handler : function()
		{
			var field = this;

			var submit_form = function()
			{
				field.send_data();
				remove_form(true);
			};

			var remove_form = function(with_spinner)
			{
				frontEditorData.trap = false;

				form.remove();

				if (with_spinner === true)
					field.el.before(spinner.show());
				else
					field.el.show();
			};

			if (field.type != 'input')
				field.container = $('<textarea>');
			else
			{
				field.container = $('<input type="text">');
				field.container.keypress(function(ev){
					var code = (ev.keyCode ? ev.keyCode : ev.which);
					if (code == 13)
						submit_form();
				});
			}

			field.container.addClass('front-editor-content');

			// Set up form buttons
			var save_button = $('<button>')
				.attr({'class': 'front-editor-save', 'title': frontEditorData.save_text})
				.text(frontEditorData.save_text)
				.click(submit_form);

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

			field.el.hide().after(spinner.show());

			field.get_data(form);
		},

		get_data : function(form)
		{
			var field = this;

			var data = {
				nonce: frontEditorData.nonce,
				action: 'front-editor',
				callback: 'get',
				name: field.name,
				type: field.type,
				item_id: field.el.attr('rel')
			};

			$.post(frontEditorData.request, data, function(response)
			{
				var jwysiwyg_args = {
					controls : {
						justifyLeft         : { visible : true },
						justifyCenter       : { visible : true },
						justifyRight        : { visible : true },
						separator04         : { visible : true },
						insertOrderedList   : { visible : true },
						insertUnorderedList : { visible : true },
						html				: { visible : true },
					}
				};

				field.container.val(response);

				spinner.hide().replaceWith(form);

				if (field.type == 'rich')
					field.container.wysiwyg(jwysiwyg_args);
				else if (field.type == 'textarea')
					field.container.autogrow({lineHeight: 16});

				field.container.focus();
			});
		},

		send_data : function()
		{
			var field = this;

			field.el.before(spinner.show());

			var data = {
				nonce: frontEditorData.nonce,
				action: 'front-editor',
				callback: 'save',
				name: field.name,
				type: field.type,
				item_id: field.el.attr('rel'),
				content: field.container.val()
			};

			$.post(frontEditorData.request, data, function(response)
			{
				spinner.hide();
				field.el.show();

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
	for ( var i in frontEditorData.fields )
	{
		var args = frontEditorData.fields[i];
		$('.front-ed-' + args[0]).each(function()
		{
			new editableField(this, args);
		});
	}
});

