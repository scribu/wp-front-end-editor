=== Front-end Editor ===
Contributors: scribu, Jotschi
Donate link: http://scribu.net/wordpress/front-end-editor
Tags: inline, editor, edit-in-place, visual, wysiwyg
Requires at least: 3.0
Tested up to: 3.1
Stable tag: trunk

Edit content inline, without going to the admin area.

== Description ==

Front-end Editor is a plugin that lets you make changes to your content *directly* from your site. No need to load the admin backend just to correct a typo.

**Goals:**

* save as many trips to the backend as possible
* compatible with any theme, out of the box
* light and fast

You can edit posts, pages, custom post types, comments, widgets and many [more elements](http://github.com/scribu/wp-front-end-editor/wiki/List-of-editable-elements).

Links: [**Wiki**](http://github.com/scribu/wp-front-end-editor/wiki) | [Plugin News](http://scribu.net/wordpress/front-end-editor) | [Author's Site](http://scribu.net)

Additional icons by [Yusuke Kamiyamane](http://p.yusukekamiyamane.com/).

== Frequently Asked Questions ==

= Error on activation: "Parse error: syntax error, unexpected..." =

Make sure your host is running PHP 5. The only foolproof way to do this is to add this line to wp-config.php (after the opening `<?php` tag):

`var_dump(PHP_VERSION);`
<br>

= Nothing happens after activating it =

1. Make sure you're logged in and have the appropriate capabilities.
2. See [Common Mistakes in Themes](http://scribu.net/wordpress/front-end-editor/common-mistakes-in-themes.html).
3. Check for JavaScript errors. [Firebug](http://getfirebug.com/) is a great tool for this.

= Can you add the ability to create posts, instead of just editing? =

No, because there already are [several plugins](http://wordpress.org/support/topic/front-end-custom-form-to-post?replies=5#post-1584286) for that already.

= Can you change the wysiwyg editor to TinyMCE? =

No, because TinyMCE is anything but tiny and would take a long time to load.

Also because I couldn't get it to work.

= Does it work with WP Super Cache? =

To avoid problems with WP Super Cache or W3 Total Cache, I recommend disabling caching for logged-in users.

== Screenshots ==

1. The tooltip
2. Editing the post content
3. Editing the post title
4. Changing a theme image
5. The settings page

== Changelog ==

= 2.2 =
* added single_term_title field
* set field name as title attribute on placeholders

= 2.1 =
* switched to Aloha Editor
* made Edit button follow mouse vertically and removed top border
* better image handling
* other bugfixes
* [more info](http://scribu.net/wordpress/front-end-editor/fee-2-1.html)

= 2.0.1 =
* removed right and bottom borders when hovering
* fixed incorrect dimensions in Webkit browsers
* fixed invalid placeholding when content is '0'

= 2.0 =
* replaced double-click action with an 'Edit' overlay
* added dropdown for editing terms in hierarchical taxonomies
* introduced front_end_editor_wrap filter
* introduced fee_cleditor_css filter
* introduced fee_cleditor_height filter
* [more info](http://scribu.net/wordpress/front-end-editor/fee-2-0.html)

= 1.9.3 =
* switched to CLEditor
* fixed encoding issues with paragraph editing
* fixed image fields handling
* fixed typo which made spinner not show
* check 'edit_theme_options' capability instead of 'edit_themes'
* [more info](http://scribu.net/wordpress/front-end-editor/fee-1-9-3.html)

= 1.9.2.1 =
* disable post locking

= 1.9.2 =
* nicEdit: limit height to window height
* nicEdit: Google Docs like link tooltip
* nicEdit: expose 'bgcolor' button
* iPhone style tooltip
* make the_tags() work no matter what args are used
* apply esc_attr() to data attributes

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

