// http://ejohn.org/blog/simple-javascript-inheritance/
// Inspired by base2 and Prototype
(function(){
  var initializing = false, fnTest = /xyz/.test(function(){xyz;}) ? /\b_super\b/ : /.*/;

  // The base Class implementation (does nothing)
  this.Class = function(){};

  // Create a new Class that inherits from this class
  Class.extend = function(prop) {
	var _super = this.prototype;

	// Instantiate a base class (but only create the instance,
	// don't run the init constructor)
	initializing = true;
	var prototype = new this();
	initializing = false;

	// Copy the properties over onto the new prototype
	for (var name in prop) {
	  // Check if we're overwriting an existing function
	  prototype[name] = ( typeof prop[name] == "function" &&
	    typeof _super[name] == "function" && fnTest.test(prop[name]) ) ?
	    (function(name, fn){
	      return function() {
	        var tmp = this._super;

	        // Add a new ._super() method that is the same method
	        // but on the super-class
	        this._super = _super[name];

	        // The method only need to be bound temporarily, so we
	        // remove it when we're done executing
	        var ret = fn.apply(this, arguments);
	        this._super = tmp;

	        return ret;
	      };
	    })(name, prop[name]) :
	    prop[name];
	}

	// The dummy class constructor
	function Class() {
	  // All construction is actually done in the init method
	  if ( !initializing && this.init )
	    this.init.apply(this, arguments);
	}

	// Populate our constructed prototype object
	Class.prototype = prototype;

	// Enforce the constructor to be what we expect
	Class.constructor = Class;

	// And make this class extendable
	Class.extend = arguments.callee;

	return Class;
  };
})();


//_____Custom code starts here_____

FrontEndEditor.fieldTypes = {};

FrontEndEditor.delayed_double_click = (function(){

	var	event = false,
		delayed = false;

	function is_regular_link($target) {
		if ( $target.is('select, option, input, button') ) // TODO: instead of 'click', capture the 'submit' event
			return false;

		if ( $target.attr('onclick') )
			return false;

		var $link = $target.closest('a');

		if ( !$link.length )
			return false;

		if ( $link.attr('onclick') || !$link.attr('href') || $link.attr('href') == '#' )
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

		if ( $link.attr('target') == '_blank' )
			window.open($link.attr('href'));
		else
			window.location.href = $link.attr('href');

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

	return function($el, callback) {
		$el.bind({
			click	: click,
			dblclick: dblclick
		});

		$el.dblclick(callback);
	};
}());


FrontEndEditor.overlay = function($el) {

	var $cover = jQuery('<div class="fee-loading>')
		.css('background-image', 'url(' + FrontEndEditor.data.spinner + ')')
		.hide()
		.prependTo(jQuery('body'));

	return {
		show: function() {
			$cover
				.css({
					width: $el.width(),
					height: $el.height()
				})
				.css($el.offset())
				.show();
		},

		hide: function() {
			$cover.hide();
		}
	};
}

// Do an ajax request, while loading a required script
FrontEndEditor.sync_load = (function(){
	var cache = [];

	return function(callback, data, src) {
		var count = 0, content;

		function proceed() {
			count++;
			if ( 2 == count )
				callback(content);
		}

		if ( !src || cache[src] ) {
			proceed();
		} else {
			cache[src] = jQuery('<script>').attr({
				type: 'text/javascript',
				src: src,
				load: proceed
			}).prependTo('head');
		}

		jQuery.post(FrontEndEditor.data.ajax_url, data, function(data) {
			content = data;
			proceed();
		}, 'json');
	};
}());

// Create a new nicEditor instance and return it
FrontEndEditor.init_nicEdit = function($el, self) {
	var nicArgs = FrontEndEditor.data.nicedit, tmp;

	nicArgs.maxHeight = jQuery(window).height() - 50;

	tmp = new nicEditor(nicArgs).panelInstance( $el.attr('id') );

	self.form.find('.nicEdit-main').focus();

	return tmp.nicInstances[0];
}

jQuery(document).ready(function($) {

	// fetch all 'data-' attributes from a DOM node
	function extract_data_attr(el) {
		var data = {};

		for (var i=0; i < el.attributes.length; i++) {
			var attr = el.attributes.item(i);

			if ( attr.specified && 0 == attr.name.indexOf('data-') ) {
				var value = attr.value;

				try {
					value = jQuery.parseJSON(value);
				} catch(e) {}

				if ( null === value )
					value = '';

				data[ attr.name.substr(5) ] = value;
			}
		}

		return data;
	}

	// Create field instances
	jQuery.each(FrontEndEditor.data.fields, function(i, filter) {
		jQuery('.fee-filter-' + filter).each(function() {
			var $el = jQuery(this),
				data = extract_data_attr(this),
				type = data.type;

			new FrontEndEditor.fieldTypes[type]($el, type, filter, data);
		});
	});

	// Tooltip init
	if ( FrontEndEditor.data.controls ) {
		var controls = [];
		jQuery.each(FrontEndEditor.data.controls, function(key, value) {
			controls.push('<span class="fee-control">' + value + '</span>');
		});

		jQuery('.fee-field').qtip({
			content: controls.join('<span class="fee-separator"> | </span>'),
			show: { effect: 'fade' },
//			hide: {	fixed: true },
//			hide: 'click',
			position: {
				at: 'top center',
				my: 'bottom center'
			},
			style: {
				tip: {
					corner: 'bottom center',
					width: 16,
					height: 10
				},
				classes: 'ui-tooltip-fee ui-tooltip-rounded'
			}
		});
	}
});
