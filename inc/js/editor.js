jQuery(function($) {

$(document).ready(function() {
	var vars = window.frontEd_data;

	// AJAX handling
	get_data = function(el, container) {
		var get_data = {
			nonce: vars['nonce'],
			action: 'front-editor',
			callback: 'get',
			name: el.frontEdArgs[0],
			item_id: $(el).attr('rel')
		};

		jQuery.post(vars['request'], get_data, function(response) {
			container.val(response);

			var type = el.frontEdArgs[1];

			if (type == 'rich')
				container.wysiwyg({
					controls : {
						separator04         : { visible : true },
						insertOrderedList   : { visible : true },
						insertUnorderedList : { visible : true },
						html				: { visible : true }
					}
				});
			else if (type == 'textarea')
				container.autogrow({lineHeight: 16});
		});
	}

	send_data = function(el, container) {
		var post_data = {
			nonce: vars['nonce'],
			action: 'front-editor',
			callback: 'save',
			name: el.frontEdArgs[0],
			item_id: $(el).attr('rel'),
			content: container.val()
		};

		jQuery.post(vars['request'], post_data, function(response) {
			var speed = 'fast';

			if (el.frontEdArgs[1] != 'input') {
				$(el).css('display', 'block');
				speed = 'normal';
			}

			$(el).fadeOut(speed, function() {
				$(el).html(response);
			}).fadeIn(speed);
		});
	}


	// Form handling
	form_handler = function(el) {
		// Set up data container
		if (el.frontEdArgs[1] != 'input')
			container = $('<textarea>');
		else
			container = $('<input type="text">');

		container.attr('class', 'front-editor-content');

		// Set up form buttons
		var save_button = $('<input type="submit">').attr({'class': 'front-editor-save', 'value': vars.save_text});
		var cancel_button = $('<button>').attr({'class': 'front-editor-cancel', 'title': vars.cancel_text}).text('X');

		// Create form
		var form_id = 'front-editor-' + $(el).attr('rel') + '-' + el.frontEdArgs[0];
		var form = $('<form>').attr({'id': form_id, 'method': 'post', 'action': ''});
		form.append(container).append(save_button).append(cancel_button);

		// Add form
		var target = $(el).parents('a');
		if ( target.length == 0 )
			target = $(el);

		$(el).hide();
		target.after(form);

		get_data(el, container);

		remove_form = function() {
			$(el).show();
			form.remove();
			window.frontEd_trap = false;

			return false;
		}

		cancel_button.click(remove_form);

		form.submit(function() {
			send_data(el, container);
			remove_form();

			return false;
		});
	}


	// Click handling
	single_click = function(ev) {
		ev.stopPropagation();

		el = this;

		setTimeout(function() {
			if ( window.frontEd_trap )
				return;

			if ( typeof(window.frontEd_url) != 'undefined' )
				window.location = window.frontEd_url;
			else if ( $(el).parents('a').length > 0 )
				window.location = $(el).parents('a').attr('href');
		}, 350);

		return false;
	}

	double_click = function(ev) {
		ev.stopPropagation();

		window.frontEd_trap = true;

		form_handler(this);
		return false;
	}

	click_handler = function(el) {
		$(el).click(single_click);
		$(el).dblclick(double_click);

		$(el).find('a').click(function(ev) {
			window.frontEd_url = $(this).attr('href');
			ev.preventDefault();
			ev.stopPropagation();
			$(el).click();
		});

		$(el).find('a').dblclick(function(ev) {
			ev.stopPropagation();
			$(el).dblclick();

			return false;
		});
	}

	// Widget text fix: Add rel attr to each element
	$('span.front-ed-widget_text, span.front-ed-widget_title').each(function() {
		id = $(this).parents('.widget_text').attr('id');
		if (id)
			$(this).attr('rel', id);
		else
			$(this).attr('class', '');	// not a text widget
	});

	// Start click handling
	$.each(vars['fields'], function(i, args) {
		$('span.front-ed-' + args[0]).each(function() {
			this.frontEdArgs = args;
			click_handler(this);
		});
	});
});

});
