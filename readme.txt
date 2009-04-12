=== Front-end Editor ===
Contributors: scribu
Donate link: http://scribu.net/wordpress
Tags: inline, editor, edit-in-place
Requires at least: 2.2
Tested up to: 2.8
Stable tag: trunk

Enable "edit in place" functionality on your site. Compatible with any theme.

== Description ==

Front-end Editor is a plugin that lets you make changes to your content *directly* from your site. No need to load the admin backend just to correct a typo.

To edit something, just double-click it.

The main goals are to be *fast* and to be *compatible with any theme*.

**Editable fields:**

* post/page title
* post/page content
* post/page custom fields
* post/page comment text
* post tags
* text widgets (title & content)

There is a settings page where you can disable editable fields that you don't want.

== Installation ==

You can either install it automatically from the WordPress admin, or do it manually:

1. Unzip "Front-end Editor" archive and put the folder into your plugins folder (/wp-content/plugins/).
1. Activate the plugin from the Plugins menu.

== Frequently Asked Questions ==

= If I use this plugin, won't everybody be able to edit my content? =

No. To edit a field, a user must be logged in and have the right permissions. For example, to edit the post content from the front-end, a user must be able to edit the post content from the regular back-end editor.

= How can I edit custom fields? =

Since custom fields can be used in so many ways, you have to edit your theme and replace code like this:

`echo get_post_meta($post->ID, 'my_key', true);`

with

`editable_post_meta($post->ID, 'my_key', 'textarea');`

The third parameter is optional and allows you to pick which type of field you want: *input*, *textarea* or *rich*.

= Can I make my own editable fields? =

Yes, but you have to know your way around WordPress' internals. Here is the [developer guide](http://scribu.net/wordpress/front-end-editor/developer-guide.html) to get you started.

= Title attributes =
In some themes, links get weird title atributes. A workaround is to disable "The title" editable field.

