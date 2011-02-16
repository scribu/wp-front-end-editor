(function($){

	var event = false,
		delayed = false;

	function is_regular_link($target) {
		if ( $target.is('select, option, input, button') ) // TODO: instead of 'click', capture the 'submit' event
			return false;

		if ( $target.attr('onclick') )
			return false;

		var $link = $target.closest('a');

		if ( !$link.length )
			return false;

		if ( $link.attr('onclick') || !$link.attr('href') || '#' === $link.attr('href') )
			return false;

		return true;
	}

	function resume() {
		if ( !event )
			return;

		var $target = jQuery(event.target);

		var new_event = jQuery.Event('click');

		delayed = true;

		$target.trigger(new_event);

		delayed = false;

		if ( new_event.isDefaultPrevented() )
			return;

		var $link = $target.closest('a');

		if ( '_blank' === $link.attr('target') )
			open($link.attr('href'));
		else
			location.href = $link.attr('href');

		event = false;
	}

	function click(ev) {
		if ( delayed )
			return;

		if ( !is_regular_link( jQuery(ev.target) ) )
			return;

		ev.stopImmediatePropagation();
		ev.preventDefault();

		if ( event )
			return;

		event = ev;

		setTimeout(resume, 300);
	}

	function dblclick(ev) {
		ev.stopPropagation();
		ev.preventDefault();

		// cancel delayed click
		event = false;
	}

	$.fn.delayedDblClick = function(callback) {
		this.bind({
			click	: click,
			dblclick: dblclick
		});

		return this.dblclick(callback);
	};
})(jQuery);
