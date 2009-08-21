=== Front-end Editor ===
Contributors: scribu
Donate link: http://scribu.net/wordpress
Tags: inline, editor, edit-in-place, wysiwyg
Requires at least: 2.5
Tested up to: 2.8
Stable tag: 1.1.4

Enable "edit in place" functionality on your site. Compatible with any theme.

== Description ==

Front-end Editor is a plugin that lets you make changes to your content *directly* from your site. No need to load the admin backend just to correct a typo.

To edit something, just double-click it.

The main goals are to be *fast* and to be *compatible with any theme*.

**Editable fields:**

<ul>
	<li><strong>posts & pages</strong>
	<ul>
		<li>title</li>
		<li>content</li>
		<li>excerpt</li>
		<li>tags</li>
		<li>custom taxonomies</li>
		<li>custom fields</li>
	</ul></li>
	<li><strong>comments</strong>
	<ul>
		<li>content</li>
	</ul></li>
	<li><strong>text widgets</strong>
	<ul>
		<li>title</li>
		<li>content</li>
	</ul></li>
</ul>

There is a settings page where you can disable editable fields that you don't want.

*PHP5 required!*

== Installation ==

You can either install it automatically from the WordPress admin, or do it manually:

1. Unzip "Front-end Editor" archive and put the folder into your plugins folder (/wp-content/plugins/).
1. Activate the plugin from the Plugins menu.

== Frequently Asked Questions ==

= If I use this plugin, won't everybody be able to edit my content? =

No. To edit a field, a user must be logged in and have the right permissions. For example, to edit the post content from the front-end, a user must be able to edit the post content from the regular back-end editor.

= How can I change the hover color? =

You can add this line to *style.css* in your theme directory:

`.front-ed:hover, .front-ed:hover * {background-color: color !important}`

where *color* is one of these values: [CSS colors](http://www.w3schools.com/CSS/css_colors.asp).

= How can I edit custom fields? =

Since custom fields can be used in so many ways, you have to edit your theme and replace code like this:

`echo get_post_meta($post->ID, 'my_key', true);`

with

`editable_post_meta(get_the_ID(), 'my_key', 'textarea');`

The third parameter is optional and allows you to pick which type of field you want: *input*, *textarea* or *rich*.

= Can I make my own editable fields? =

Yes, but you have to know your way around WordPress' internals. Here is the [developer guide](http://scribu.net/wordpress/front-end-editor/developer-guide.html) to get you started.

= How can I mark the fields as editable? =

The easiest way is with CSS. You can use the *.front-ed* selector to style all editable fields on a page.

= Title attributes =
In some themes, links get weird title atributes. If this messes up your theme, just disable "The title" field.

== Screenshots ==

1. The inline WYSIWYG editor
2. The settings page

== Changelog ==

= 1.1.3 =
* css bugfix
* added belarusian translation
* updated italian translation

= 1.1 =
* custom taxonomies editing
* usability improvements
* added turkish translation
* [more info](http://scribu.net/wordpress/front-end-editor/fee-1-1.html)

= 1.0.6 =
* fixed links with target="_blank"
* inputs and textareas are focused after double-clicking
* added Russian translation

= 1.0.5 =
* added align buttons, fixed autogrow issue

= 1.0.4 =
* the_title and the_tags improvements

= 1.0.3 =
* bugfix

= 1.0.2 = 
* swedish translation

= 1.0 =
* single paragraph editing
* [more info](http://scribu.net/wordpress/front-end-editor/fee-1-0.html)

= 0.9 =
* editable custom fields
* [more info](http://scribu.net/wordpress/front-end-editor/fee-0-9.html)

= 0.8 =
* rich text editor (jWYSIWYG)
* l10n
* [more info](http://scribu.net/wordpress/front-end-editor/fee-0-8.html)

= 0.7 =
* settings page
* [more info](http://scribu.net/wordpress/front-end-editor/fee-0-7.html)

= 0.6 =
* editable post tags
* [more info](http://scribu.net/wordpress/front-end-editor/fee-0-6.html)

= 0.5 =
* initial release
* [more info](http://scribu.net/wordpress/front-end-editor/fee-0-5.html)

