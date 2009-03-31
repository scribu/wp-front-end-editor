jQuery(function($) {

$(document).ready(function() {
	var vars = window.frontEd_data;

	// Get element content through AJAX
	var get_data = function(el, container, args) {
		var get_data = {
			action: 'front-editor',
			method: 'get',
			name: args[0],
			nonce: vars['nonce'],
			post_id: el.attr('rel')
		};

		jQuery.post(vars['request'], get_data, function(response) {
			container.val(response);
		});
	}

	// Update element content through AJAX
	var send_data = function(el, content, args) {
		var post_data = {
			action: 'front-editor',
			method: 'save',
			name: args[0],
			nonce: vars['nonce'],
			post_id: el.attr('rel'),
			content: content
		};

		jQuery.post(vars['request'], post_data, function(response) {
			el.fadeOut('fast', function() {
				if (args[1] == 'textarea') {
					el.html(response);
					el.css('display', 'block');
				} else
					el.text(response);
			}).fadeIn('fast');
		});
	}

	var disable_click = function() {
		return false;
	}

	$.each(vars['fields'], function(i, args) {
		$('span.front-ed-' + args[0]).click(function(ev) {
			var el = $(this);

			if (ev.detail == 1) {
				if ( el.parents('a').length == 0 )
					return;

				// Handle linked elements
				el.parents('a').click(disable_click);
				setTimeout(function() {
					if ( window.frontEd_trap )
						return;

					window.location = el.parents('a').attr('href');
				}, 500);

				return false;
			}

			window.frontEd_trap = true;

			// Set up form
			var form_id = 'front-editor-' + el.attr('rel') + '-' + args[0];

			var form_html = '<form id="' + form_id + '" method="post" action="">';

			if (args[1] == 'textarea')
				form_html += '<textarea class="front-editor"></textarea>';
			else
				form_html += '<input type="text" class="front-editor" value="" />';

			form_html +=
				'<input class="front-editor-save" type="submit" value="Save" />' +
				'<button class="front-editor-cancel" title="Cancel">X</button>' +
				'</form>';

			// Add form
			if ( el.parents('a').length == 0 )
				target = el;
			else
				target = el.parents('a');

			el.hide();
			target.after(form_html);

			var form = $('#' + form_id);
			get_data(el, form.find('.front-editor'), args);

			// Set form behaviour
			var remove_form = function() {
				el.show();
				form.remove();
				window.frontEd_trap = false;

				return false;
			}

			form.find('.front-editor-cancel').click(remove_form);

			form.submit(function() {
				var content = form.find('.front-editor').val();

				send_data(el, content, args);
				remove_form();

				return false;
			});

			return false;
		});
	});
});

});
