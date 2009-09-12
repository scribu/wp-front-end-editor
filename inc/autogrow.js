/*
 * Auto Expanding Text Area (1.2.5)
 * by Chrys Bader (www.chrysbader.com)
 * chrysb@gmail.com
 *
 * Special thanks to:
 * Jake Chapa - jake@hybridstudio.com
 * John Resig - jeresig@gmail.com
 *
 * Copyright (c) 2008 Chrys Bader (www.chrysbader.com)
 * Licensed under the GPL (GPL-LICENSE.txt) license.
 *
 *
 * NOTE: This script requires jQuery to work.  Download jQuery at www.jquery.com
 *
 */

(function(jQuery) {
	jQuery.fn.autogrow = function(o) {
		return this.each(function() {
			new jQuery.autogrow(this, o);
		});
	};


	/**
	 * The autogrow object.
	 *
	 * @constructor
	 * @name jQuery.autogrow
	 * @param Object e The textarea to create the autogrow for.
	 * @param Hash o A set of key/value pairs to set as configuration properties.
	 * @cat Plugins/autogrow
	 */

	jQuery.autogrow = function (e, o) {
		this.options		  	= o || {};
		this.dummy			  	= null;
		this.interval	 	  	= null;
		this.line_height	  	= this.options.lineHeight || parseInt(jQuery(e).css('line-height'));
		this.min_height		  	= this.options.minHeight || parseInt(jQuery(e).css('min-height'));
		this.max_height		  	= this.options.maxHeight || parseInt(jQuery(e).css('max-height'));
		this.textarea		  	= jQuery(e);

		if (isNaN(this.line_height) || this.line_height == 0)
			this.line_height = 14;	// IE thinks 'normal' is a helpful value

		if (isNaN(this.min_height) || this.min_height == 0)
			this.min_height = this.textarea.height();

		// Only one textarea activated at a time, the one being used
		this.init();
	}

	jQuery.autogrow.fn = jQuery.autogrow.prototype = {
		autogrow: '1.2.5'
	}

	jQuery.autogrow.fn.extend = jQuery.autogrow.extend = jQuery.extend;

	jQuery.autogrow.fn.extend({

		init: function() {
			var self = this;
			self.textarea
				.css({overflow: 'hidden', display: 'block'})
				.focus(function() { self.startExpand() });

			self.checkExpand(0, true);
		},

		startExpand: function() {
			var self = this;
			// while focused, leave an extra line.  The cursor can never actually reach
			// this extra line, but it makes the gesture for select-all much easier to perform
			// (or, _possible_ to perform, if the last line is overflow-x-hidden)
			self.checkExpand(self.line_height, true);
			self.interval = window.setInterval(function() {self.checkExpand(self.line_height, false)}, 400);
		},

		stopExpand: function() {
			clearInterval(this.interval);
			this.checkExpand(0, true);
		},

		checkExpand: function(extraspace, forcecheck) {
			if (this.dummy == null)
			{
				this.dummy = jQuery('<div></div>');
				this.dummy.css({
					'font-size'  : this.textarea.css('font-size'),
					'font-family': this.textarea.css('font-family'),
					'width'	     : this.textarea.css('width'),
					'padding'	 : this.textarea.css('padding'),
					'line-height': this.line_height + 'px',
					'min-height' : this.min_height + 'px',
					'max-height' : this.max_height + 'px',
					'overflow-x' : 'hidden',
					'position'   : 'absolute',
					'top'		 : 0,
					'left'		 : -9999
				}).appendTo('body');
			}

			// textarea -> div
			// textarea rules are unique. it's not the same as <pre> because it word-wraps,
			// but it does not condense spaces like html.
			// dot at the end is to make sure we leave room for the cursor, if the cursor is on a blank line
			var html = this.textarea.val().toString()
				.replace(/&/g, '&amp;')
				.replace(/</g, '&lt;')
				.replace(/>/g, '&gt;')
				.replace(/^ /, '&nbsp;')
				.replace(/  /g, ' &nbsp;') + ".";

			// IE is different, as per usual
//			if (jQuery.support.leadingWhitespace)
// compatibility with jQuery 1.2.x
			if (jQuery.browser.msie)
				html = html.replace(/\n/g, '<br>');
			else
				html = html.replace(/\n/g, '<BR>');

			if (forcecheck || this.dummy.html() != html)
			{
				this.dummy.html(html);

				var should_be_height = this.dummy.height() + extraspace + 1;
				if (this.max_height > 0 && (should_be_height > this.max_height))
				{
					this.textarea.height(this.max_height);
					this.textarea.css('overflow-y', 'auto');
				}
				else
				{
					this.textarea.css('overflow-y', 'hidden');
					if (this.textarea.height() != should_be_height)
						this.textarea.animate({height: should_be_height + 'px'}, 100);
				}
			}
		}	
	 });
})(jQuery);
