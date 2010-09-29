=== Front-end Editor ===
Contributors: scribu
Donate link: http://scribu.net/paypal
Tags: inline, editor, edit-in-place, visual, wysiwyg
Requires at least: 3.0
Tested up to: 3.0
Stable tag: 1.9.1

Want to edit something? Just double-click it!

== Description ==

Front-end Editor is a plugin that lets you make changes to your content *directly* from your site. No need to load the admin backend just to correct a typo.

To edit something, just double-click it!

**Goals:**

* save as many trips to the backend as possible
* compatible with any theme, out of the box
* light and fast

**Editable fields:**

<ul>
	<li><strong>posts & pages</strong>
	<ul>
		<li>title - `the_title()`</li>
		<li>content - `the_content()`</li>
		<li>excerpt - `the_excerpt()`</li>
		<li>categories - `the_category()`</li>
		<li>tags - `the_tags()`</li>
		<li>custom taxonomies - `the_terms()`</li>
		<li>custom fields - `editable_post_meta()`*</li>
		<li>thumbnail - `the_post_thumbnail()`</li>
	</ul></li>
	<li><strong>comments</strong></li>
	<li><strong>authors</strong>
	<ul>
		<li>description - `the_author_meta()`</li>
	</ul></li>
	<li><strong>terms</strong>
	<ul>
		<li>name - `single_tag_title()`, `single_cat_title()`</li>
		<li>description - `term_description()`</li>
	</ul></li>
	<li><strong>widgets</strong></li>
	<li><strong>theme images - `editable_image()`*</strong>
	<li><strong>options</strong>
	<ul>
		<li>title - `bloginfo('name')`</li>
		<li>description - bloginfo('description')</li>
		<li>other - `editable_option()`*</li>
	</ul></li>
</ul>

Template tags marked with * are defined by the plugin.

There is a settings page where you can disable editable fields that you don't want, as well as other options.

Links: [Plugin News](http://scribu.net/wordpress/front-end-editor) | [Author's Site](http://scribu.net)

== Installation ==

You can either install it automatically from the WordPress admin, or do it manually:

1. Unzip "Front-end Editor" archive and put the folder into your plugins folder (/wp-content/plugins/).
1. Activate the plugin from the Plugins menu.

== Frequently Asked Questions ==

= Error on activation: "Parse error: syntax error, unexpected..." =

Make sure your host is running PHP 5. The only foolproof way to do this is to add this line to wp-config.php (after the opening `<?php` tag):

`var_dump(PHP_VERSION);`
<br>

= I double-click and nothing happens =

First see [Common Mistakes in Themes](http://scribu.net/wordpress/front-end-editor/common-mistakes-in-themes.html).

Next, check for JavaScript errors (In Firefox, press Ctrl + Shift + J and reload the page).

= Does it work with WP Super Cache? =

To avoid problems with WP Super Cache or W3 Total Cache, you have to disable caching for logged-in users.

= How can I change the hover color? =

You can add this line to *style.css* in your theme directory:

`.fee-field:hover, .fee-field:hover * {background-color: mycolor !important}`

where *mycolor* is one of these values: [CSS colors](http://www.w3schools.com/CSS/css_colors.asp).

= How can I edit custom fields? =

Since custom fields can be used in so many ways, you have to make some code replacements in your theme:

Replace something like this:

`<?php echo get_post_meta($post->ID, 'my_key', true); ?>`

with this:

`<?php editable_post_meta(get_the_ID(), 'my_key', 'textarea'); ?>`

The third parameter is optional and allows you to pick which type of field you want: *input*, *textarea* or *rich*.

If you have a custom field with multiple values, you can use `get_editable_post_meta()`. For example:

`
<ul>
<?php
$values = get_editable_post_meta(get_the_ID(), 'my_key');
foreach ( $values as $value )
	echo '<li>' . $value . '</li>';
?>
</ul>
`

= How can I make theme images editable? =

Again, you have to modify your theme's code. Replace something like this:

`<img src="<?php bloginfo('template_url'); ?>/images/header_1.jpg" width="970" height="140" alt="<?php bloginfo('name'); ?> header image 1" title="<?php bloginfo('name'); ?> header image 1" />`

with this:

`<?php editable_image('header-1', 
	get_bloginfo('template_url') . '/images/header_1.jpg', 
	array('width' => 970, 'height' => 140, 'alt' => get_bloginfo('name'))); 
?>`

The editable_image() template tag is located in fields/other.php.

= Can I make my own editable fields? =

Yes, but you have to know programming. Just hack away at one of the existing fields, found in `front-end-editor/fields`.

== Screenshots ==

1. The tooltip
2. Editing the post content
3. Editing the post title
4. Changing a theme image
5. The settings page

== Translations ==

* Danish - [Georg](http://wordpress.blogos.dk/)
* Dutch - [Ron Hartman](http://www.fr-fanatic.com/)
* French - [Li-An](http://www.li-an.fr)
* Italian - [Gianni Diurno](http://gidibao.net)
* Georgian - Levani Melikishvili
* German - Gottfried, Dominik Heyberg
* Japaneze - kzh
* Norwegian - John Myrstad
* Polish - [Expromo](http://expromo.pl)
* Portuguese - [Fernanda Foertter](http://www.hpcprogrammer.com)
* Belarusian - [M. Comfi](http://www.comfi.com)
* Russian - BoreS
* Spanish - [Esteban](http://netmdp.com/)
* Swedish - [Müfit Kiper](http://kitkonsult.se/)
* Turkish - [Burak Gulbahce](http://www.saylangoz.com/wordpress/)

If you want to translate this plugin, please read [this](http://scribu.net/wordpress/translating-plugins.html).

== Changelog ==

= 1.9.2 =
* post locking
* make the_tags() work no matter what args are used
* apply esc_attr() to data attributes
* expose 'unlink' and 'bgcolor' buttons

= 1.9.1 =
* re-added nicEdit to text widgets
* nicEdit: img button opens WordPress media thickbox
* nicEdit: translatable buttons
* nicEdit: extra buttons available in the settings page
* load nicEdit or suggest.js in parallel with first edit
* display spinner on top of editable area
* various bugfixes

= 1.9 =
* full widget editing
* custom post type support
* new editable field: term description
* more robust paragraph editing
* more robust double-click handling
* nicEdit: button management from the admin
* removed "Reset the post date on each edit" option
* [more info](http://scribu.net/wordpress/front-end-editor/fee-1-9.html)

= 1.8 =
* added tooltip
* restyled save/cancel buttons
* fixed widget editing
* exposed JavaScript field types
* [more info](http://scribu.net/wordpress/front-end-editor/fee-1-8.html)

= 1.7.2 =
* fixed narrow textarea problem
* fixed IE8 error
* nicEdit: included upload plugin

= 1.7.1 =
* made date reset optional
* better lightbox detection

= 1.7 =
* new editable fields: post thumbnails & arbitrary options
* nicEdit: added font-family and font-color buttons
* nicEdit: made configuration filterable
* dropped Growfield from textareas
* load CSS only when needed
* standardized CSS ids and classes
* renamed hooks from 'front_ed_*' to 'front_end_editor_*'
* fixed: when editing the post content, the post date isn't updated
* fixed: when editing tags, the input bounces to a new line
* fixed: after editing linked post title, the title is not linked anymore
* fixed: editable_post_meta() doesn't work outside The Loop
* fixed: warning when a NULL is passed to FEE_Field_Base::wrap()
* [more info](http://scribu.net/wordpress/front-end-editor/fee-1-7.html)

= 1.6.1 =
* fixed escaping issues

= 1.6 =
* new editable field: post categories
* added editing of custom fields with multiple values
* added editing of any widget title
* improved script loading
* added placeholder to editable_post_meta
* fixed issue with comment paragraphs
* fixed issues with the $post global
* [more info](http://scribu.net/wordpress/front-end-editor/fee-1-6.html)

= 1.5.1 =
* fixed auto-upgrade error
* added German translation

= 1.5 =
* new editable field: theme images
* switched to NicEdit
* don't remove blockquotes when editing a single paragraph
* better handling of text widgets
* compress JS & CSS
* compatibility with Ajaxed WordPress plugin
* added ES translation
* [more info](http://scribu.net/wordpress/front-end-editor/fee-1-5.html)

= 1.4 =
* new editable fields: category title and tag title
* added $echo parameter to editable_post_meta()
* easier way to restrict editable content
* don't load CSS & JS if the current user can't edit any of the fields
* switched from Autogrow to Growfield (fixes IE compatibility)
* added Georgian translation
* [more info](http://scribu.net/wordpress/front-end-editor/fee-1-4.html)

= 1.3.3 =
* fixed duplicate header error

= 1.3.2 =
* site title bugfix

= 1.3.1 =
* settings page bugfix
* updated translations

= 1.3 =
* new editable fields: site title & site description
* the rich editor respects .alignleft etc.
* ability to add extra css to the rich editor via front-end-editor.css
* added Polish translation
* use id="" instead of rel=""
* postThumbs compatibility
* [more info](http://scribu.net/wordpress/front-end-editor/fee-1-3.html)

= 1.2.1 =
* widget bugfix

= 1.2 =
* made author description editable
* yellow background while hovering over editable field
* experimental wysiwyg autogrow
* hopefully valid xHTML
* HTML code is cleaned up before saving
* added Portuguese translation
* [more info](http://scribu.net/wordpress/front-end-editor/fee-1-2.html)

= 1.1.4 =
* fix white screen error for non-admins

= 1.1.3 =
* css bugfix
* added Belarusian translation
* updated italian translation

= 1.1 =
* new editable field: post terms
* usability improvements
* added Turkish translation
* [more info](http://scribu.net/wordpress/front-end-editor/fee-1-1.html)

= 1.0.6 =
* fixed links with target="_blank"
* inputs and textareas are focused after double-clicking
* added Russian translation

= 1.0.5 =
* added align buttons, fixed autogrow issue
* the_title and the_tags improvements
* added Swedish translation

= 1.0 =
* single paragraph editing
* [more info](http://scribu.net/wordpress/front-end-editor/fee-1-0.html)

= 0.9 =
* new editable field: post custom fields
* [more info](http://scribu.net/wordpress/front-end-editor/fee-0-9.html)

= 0.8 =
* rich text editor (jWYSIWYG)
* l10n
* [more info](http://scribu.net/wordpress/front-end-editor/fee-0-8.html)

= 0.7 =
* settings page
* [more info](http://scribu.net/wordpress/front-end-editor/fee-0-7.html)

= 0.6 =
* new editable field: post tags
* [more info](http://scribu.net/wordpress/front-end-editor/fee-0-6.html)

= 0.5 =
* initial release
* [more info](http://scribu.net/wordpress/front-end-editor/fee-0-5.html)

