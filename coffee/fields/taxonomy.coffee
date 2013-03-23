{input} = require('./fields/basic.coffee')

class terminput extends input
	content_to_input: (content) ->
		super

		@input.suggest FrontEndEditor.data.ajax_url + '?action=ajax-tag-search&tax=' + @data.taxonomy, {
			multiple     : true
			resultsClass : 'fee-suggest-results'
			selectClass  : 'fee-suggest-over'
			matchClass   : 'fee-suggest-match'
		}

class termselect extends select
	content_to_input: (content) ->
		$dropdown = jQuery(content)

		@input.replaceWith($dropdown)
		@input = $dropdown

module.exports = {
	terminput
	termselect
}
