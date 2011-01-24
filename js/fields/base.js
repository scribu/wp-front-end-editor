FrontEndEditor.fieldTypes['base'] = Class.extend({
	dependency: null, // script src

	init: function ($el, data, filter) {
		var self = this;

		self = jQuery.extend(self, {
			el: $el,
			data: data,
			filter: filter,
			type: data.type
		});

		FrontEndEditor.delayed_double_click(self.el, jQuery.proxy(self, 'dblclick'));
	},

	create_input: null,

	content_to_input: null,
	content_from_input: null,

	content_to_front: null,

	ajax_get_handler: null,
	ajax_set_handler: null,

	ajax_args: function (args) {
		var self = this;

		return jQuery.extend(args, {
			action	: 'front-end-editor',
			nonce	: FrontEndEditor.data.nonce,
			filter	: self.filter,
			data	: self.data
		});
	},

	ajax_get: function () {
		var self = this;

		var data = self.ajax_args({
			callback: 'get'
		});

		FrontEndEditor.sync_load(jQuery.proxy(self, 'ajax_get_handler'), data, self.dependency);
	},

	ajax_set: function (content) {
		var self = this;

		var data = self.ajax_args({
			callback: 'save',
			content: content || self.content_from_input()
		});

		jQuery.post(FrontEndEditor.data.ajax_url, data, jQuery.proxy(self, 'ajax_set_handler'), 'json');
	},

	init_cleditor: function($el) {
		var	self = this,
			$iframe,
			$iframe_body;

		$el.cleditor({
			controls: FrontEndEditor.data.cleditor.controls,
			width: self.form.width()
		});

		$iframe = self.form.find('.cleditorMain iframe').contents();

		$iframe.find('head')
			.append('<link rel="stylesheet" href="' + jQuery('#jquery-qtip-css').attr('href') + '">')
			.append('<link rel="stylesheet" href="' + jQuery('#fee-editor-css').attr('href') + '">');

		$iframe_body = $iframe.find('body');

		$iframe_body.delegate('a', 'click', function(ev) {
			var	$el = jQuery(ev.target);

			if ( $el.data('qtip') ) {
				$el.data('qtip').show();
				return false;
			}

			var $content = jQuery('<span>');

			$content.append( jQuery('<a>', {
				'href'	: $el.attr('href'),
				'target': '_blank',
				'class'	: 'fee-visit-link',
				'html'	: FrontEndEditor.data.cleditor.link.visit
			}));

			$content.append( jQuery('<a>', {
				'href'	: '#',
				'class'	: 'fee-change-link',
				'html'	: FrontEndEditor.data.cleditor.link.change,
//				'click'	: function(ev) {
//					$el.parents('.nicEdit-main').focus();
//					button.mouseClick();
//					$el.data('qtip').hide();
//					return false;
//				}
			}));

			$content.append( jQuery('<a>', {
				'href'	: '#',
				'class'	: 'fee-remove-link',
				'html'	: FrontEndEditor.data.cleditor.link.remove,
//				'click' : function(ev) {
//					$el.parents('.nicEdit-main').focus();
//					$el.replaceWith($el.html());
//					return false;
//				}
			}) );

			$content.find('.fee-visit-link, .fee-change-link').after(' - ');

			$el.qtip({
				show	: {
					event: ev.type, 
					ready: true,
					effect: false,
					delay: 0
				},
				hide	: {
					event: 'click',
//					target: $el.parents('.nicEdit-main'),
					effect: false,
					delay: 0
				},
				content : $content,
				position: {
					at: 'bottom left',
					my: 'top left',
					container: $iframe_body
				},
				style: {
					tip: false,
					classes: 'fee-click'
				}
			});
		});
	}
});

