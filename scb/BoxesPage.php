<?php

/*
Creates an admin page with widgets, similar to the dashboard

For example, if you defined the boxes like this:

$this->boxes = array(
	array('settings', 'Settings box', 'normal')
	...
);

You must also define two methods in your class for each box:

function settings_box() - this is where the box content is echoed
function settings_handler() - this is where the box settings are saved
...
*/
abstract class scbBoxesPage extends scbAdminPage {
	/*
		A box definition looks like this:
		array($slug, $title, $column);

		Available columns: normal, side, column3, column4
	*/
	protected $boxes;

	function page_init() {
		if ( !isset($this->args['columns']) )
			$this->args['columns'] = 2;

		parent::page_init();

		add_action('load-' . $this->pagehook, array($this, 'boxes_init'));
		add_filter('screen_layout_columns', array($this, 'columns'));

		register_uninstall_hook($this->file, array($this, 'uninstall'));
	}

	function default_css() {
?>
<style type="text/css">
.meta-box-sortables {margin: 0 5px !important}
.inside {clear:both; overflow:hidden; padding: 10px 10px 0 10px !important}
.inside table {margin: 0 !important; padding: 0 !important}
.inside table td {vertical-align: middle !important}
.inside table .regular-text {width: 100% !important}
.inside .form-table th {width: 30%; max-width: 200px; padding: 10px 0 !important}
.inside .widefat .check-column {padding-bottom: 7px !important}
.inside p, .inside table {margin: 0 0 10px 0 !important}
.inside p.submit {float:left !important; padding: 0 !important}
</style>
<?php
	}

	function page_content() {
		$this->default_css();

		global $screen_layout_columns;

		if ( isset($screen_layout_columns) ) {
			$hide2 = $hide3 = $hide4 = '';
			switch ( $screen_layout_columns ) {
				case 4:
					$width = 'width:24.5%;';
					break;
				case 3:
					$width = 'width:32.67%;';
					$hide4 = 'display:none;';
					break;
				case 2:
					$width = 'width:49%;';
					$hide3 = $hide4 = 'display:none;';
					break;
				default:
					$width = 'width:98%;';
					$hide2 = $hide3 = $hide4 = 'display:none;';
			}
		}
?>
<div id='<?php echo $this->pagehook ?>-widgets' class='metabox-holder'>
<?php
	echo "\t<div class='postbox-container' style='$width'>\n";
	do_meta_boxes( $this->pagehook, 'normal', '' );

	echo "\t</div><div class='postbox-container' style='{$hide2}$width'>\n";
	do_meta_boxes( $this->pagehook, 'side', '' );

	echo "\t</div><div class='postbox-container' style='{$hide3}$width'>\n";
	do_meta_boxes( $this->pagehook, 'column3', '' );

	echo "\t</div><div class='postbox-container' style='{$hide4}$width'>\n";
	do_meta_boxes( $this->pagehook, 'column4', '' );
?>
</div></div>
<?php
	}

	function page_footer() {
		$this->_boxes_js_init();
		parent::page_footer();
	}

	function form_handler() {
		if ( empty($_POST) )
			return;

		check_admin_referer($this->nonce);

		// Box handler
		foreach ( $this->boxes as $box )
			call_user_func(array($this, $box[0] . '_handler'));

		if ( $this->options )
			$this->formdata = $this->options->get();
	}

	function columns($columns) {
		$columns[$this->pagehook] = $this->args['columns'];

		return $columns;
	}

	function uninstall() {
		global $wpdb;

		$hook = str_replace('-', '', $this->pagehook);

		foreach ( array('metaboxhidden', 'closedpostboxes', 'wp_metaboxorder', 'screen_layout') as $option )
			$keys[] = "'{$option}_{$hook}'";

		$keys = '(' . implode(', ', $keys) . ')';

		$wpdb->query("
			DELETE FROM {$wpdb->usermeta}
			WHERE meta_key IN {$keys}
		");
	}

	function boxes_init() {
		wp_enqueue_script('common');
		wp_enqueue_script('wp-lists');
		wp_enqueue_script('postbox');

		$this->_add_boxes();
	}

	function _add_boxes() {
		foreach($this->boxes as $i)
			add_meta_box($i[0], $i[1], array($this, "{$i[0]}_box"), $this->pagehook, $i[2]);
	}

	// Adds necesary code for JS to work
	function _boxes_js_init() {
		echo $this->js_wrap(
<<<EOT
//<![CDATA[
jQuery(document).ready( function($){
	// close postboxes that should be closed
	$('.if-js-closed').removeClass('if-js-closed').addClass('closed');
	// postboxes setup
	postboxes.add_postbox_toggles('$this->pagehook');
});
//]]>
EOT
);
?>

<form style='display: none' method='get' action=''>
	<p>
<?php
	wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
	wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );
?>
	</p>
</form>
<?php
	}
}

