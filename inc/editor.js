jQuery(function($) {

// constructor
function editableField(el, args) {
	this.el = $(el);
	this.name = args[0];

	// Set type, based on rel attribute
	rel = this.el.attr('rel').split('#', 3);

	if (rel.length == 3)
		this.type = rel[2];
	else
		this.type = args[1];

	this.has_parent = this.el.parents('a').length > 0;
}

$(document).ready(function() {
	var vars = window.frontEd_data;

	// AJAX handling
	get_data = function(field) {
		var get_data = {
			nonce: vars['nonce'],
			action: 'front-editor',
			callback: 'get',
			name: field.name,
			item_id: field.el.attr('rel')
		};

		jQuery.post(vars['request'], get_data, function(response) {
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

	send_data = function(field) {
		var post_data = {
			nonce: vars['nonce'],
			action: 'front-editor',
			callback: 'save',
			name: field.name,
			item_id: field.el.attr('rel'),
			content: field.container.val()
		};

		jQuery.post(vars['request'], post_data, function(response) {
			var speed = 'fast';

			if (field.type != 'input') {
				field.el.css('display', 'block');
				speed = 'normal';
			}

			field.el.fadeOut(speed, function() {
				field.el.html(response);
			}).fadeIn(speed);
		});
	}


	// Form handling
	form_handler = function(field) {
		// Set up data container
		if (field.type != 'input')
			field.container = $('<textarea>');
		else
			field.container = $('<input type="text">');

		field.container.attr('class', 'front-editor-content');

		// Set up form buttons
		var save_button = $('<button>').attr({'class': 'front-editor-save', 'title': vars.save_text}).text(vars.save_text);
		var cancel_button = $('<button>').attr({'class': 'front-editor-cancel', 'title': vars.cancel_text}).text('X');

		// Create form
		var form = $('<div>').attr('class', 'front-editor-container')
			.append(field.container)
			.append(save_button)
			.append(cancel_button);

		// Add form
		if ( field.has_parent )
			target = field.el.parents('a');
		else
			target = field.el;

		field.el.hide();
		target.after(form);

		get_data(field);

		remove_form = function() {
			window.frontEd_trap = false;

			field.el.show();
			form.remove();
		}

		cancel_button.click(remove_form);

		save_button.click(function(ev) {
			send_data(field);
			remove_form();
		});
	}

	// Click handling
	click_handler = function(field) {
		single_click = function(ev) {
			if ( field.has_parent ) {
				ev.stopPropagation();
				ev.preventDefault();
			}	

			setTimeout(function() {
				if ( window.frontEd_trap )
					return;

				if ( typeof(window.frontEd_url) != 'undefined' )
					window.location = window.frontEd_url;
				else if ( field.has_parent )
					window.location = field.el.parents('a').attr('href');
			}, 300);
		}

		double_click = function(ev) {
			ev.stopPropagation();
			ev.preventDefault();

			window.frontEd_trap = true;

			form_handler(field);
		}

		field.el
			.click(single_click)
			.dblclick(double_click);

		child_single_click = function(ev) {
			ev.stopPropagation();
			ev.preventDefault();

			window.frontEd_url = $(this).attr('href');

			field.el.click();
		}

		child_double_click = function(ev) {
			ev.stopPropagation();
			ev.preventDefault();

			field.el.dblclick();
		}

		popup_check = function() {
			// Lightbox
			if ( $(this).attr("rel").indexOf('lightbox') != -1 )
				return false;

			// Shutter
			if ( $(this).attr("rel").indexOf('shutter') != -1 )
				return false;

			return true;
		}

		field.el.find('a')
			.filter(popup_check)
			.click(child_single_click)
			.dblclick(child_double_click);
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
			click_handler(new editableField(this, args));
		});
	});
});

});
