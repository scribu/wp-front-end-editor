FrontEndEditor.fieldTypes['terminput'] = FrontEndEditor.fieldTypes['input'].extend({
	dependency: FrontEndEditor.data.suggest ? FrontEndEditor.data.suggest.src : null,

	content_to_input: function (content) {
		var self = this;

		self._super(content);

		self.input.suggest(FrontEndEditor.data.ajax_url + '?action=ajax-tag-search&tax=' + self.data.taxonomy, {
			multiple		: true,
			resultsClass	: 'fee-suggest-results',
			selectClass		: 'fee-suggest-over',
			matchClass		: 'fee-suggest-match'
		});
	}
});
