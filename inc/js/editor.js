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
			
			if (el.frontEdArgs[1] == 'textarea') {
//				container.wysiwyg();
				container.autogrow({lineHeight: 16});
			}
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

			if (el.frontEdArgs[1] == 'textarea') {
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
		// Set up form html
		var form_id = 'front-editor-' + $(el).attr('rel') + '-' + el.frontEdArgs[0];

		var form_html = '<form id="' + form_id + '" method="post" action="">';
	
		if (el.frontEdArgs[1] == 'textarea')
			form_html += '<textarea class="front-editor-content"></textarea>';
		else
			form_html += '<input type="text" class="front-editor-content" value="" />';

		form_html +=
			'<input class="front-editor-save" type="submit" value="Save" />' +
			'<button class="front-editor-cancel" title="Cancel">X</button>' +
			'</form>';

		// Add form
		var target = $(el).parents('a');
		if ( target.length == 0 )
			target = $(el);

		$(el).hide();
		target.after(form_html);

		var form = $('#' + form_id);
		var container = form.find('.front-editor-content');

		get_data(el, container);

		remove_form = function() {
			$(el).show();
			form.remove();
			window.frontEd_trap = false;

			return false;
		}

		form.find('.front-editor-cancel').click(remove_form);

		form.submit(function(ev) {
			ev.preventDefault();
			send_data(el, container);
			remove_form();
		});
	}


	// Click handling
	single_click = function(ev) {
		ev.preventDefault();
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
	}

	double_click = function(ev) {
		ev.preventDefault();
		ev.stopPropagation();

		window.frontEd_trap = true;

		form_handler(this);
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
			ev.preventDefault();
			ev.stopPropagation();
			$(el).dblclick();
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
