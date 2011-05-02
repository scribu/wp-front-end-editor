if ( FrontEndEditor.data.suggest ) {

	FrontEndEditor.define_field( 'terminput', 'input', {
		dependency: FrontEndEditor.data.suggest.src,

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
}

FrontEndEditor.define_field( 'termselect', 'select', {
	content_to_input: function (content) {
		var self = this,
			$dropdown = jQuery(content);

		self.input.replaceWith( $dropdown );
		self.input = $dropdown;
	}
});
