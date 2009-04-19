<?php

class frontEditor {
	public $fields;
	private $version;
	private $nonce_action = 'front-editor';
	private $options;

	function __construct($options, $version) {
		$this->options = $options;

		$this->version = $version;

		// Set core hooks
		add_action('template_redirect', array($this, 'add_scripts'));
		add_action('wp_ajax_front-editor', array($this, 'ajax_response'));
	}

	// Register a new editable field
	function register($filter, $class, $args = '') {
		$args = wp_parse_args($args, array(
			'title' => ucfirst(str_replace('_', ' ', $filter)),
			'type' => 'input',
			'priority' => 99,
			'argc' => 1
		));

		$args['class'] = $class;

		$this->fields[$filter] = $args;
	}

	function add_scripts() {
		if ( !is_user_logged_in() )
			return;

// DEBUG
// wp_enqueue_script('firebug-lite', 'http://getfirebug.com/releases/lite/1.2/firebug-lite-compressed.js');

		$url = $this->_get_plugin_url() . '/inc';

		if ( $this->options->rich ) {
			wp_enqueue_style('jwysiwyg', $url . '/js/jwysiwyg/jquery.wysiwyg.css');
			wp_enqueue_script('jwysiwyg', $url . '/js/jwysiwyg/jquery.wysiwyg.js', array('jquery'));
		}

		wp_enqueue_script('autogrow', $url . '/js/autogrow.js', array('jquery'));

		// Core scripts
		wp_enqueue_style('front-editor', $url . '/editor.css', $this->version);
		wp_enqueue_script('front-editor', $url . '/editor.js', array('jquery'), $this->version);

		add_action('wp_head', array($this, 'add_filters'));
	}

	function add_filters() {
		foreach ( $this->fields as $name => $args ) {
			if ( @in_array($name, $this->options->disabled) )
				continue;

			extract($args);
			if ( call_user_func(array($class, 'check')) )
				add_filter($name, array($class, 'wrap'), $priority, $argc);
		}

		$this->pass_to_js();
	}

	// Send necesarry info to JS land
	function pass_to_js() {
		// PHP < 5.2
		if ( !function_exists('json_encode') )
			require_once(dirname(__FILE__) . '/inc/json.php');

		foreach( $this->fields as $name => $args ) {
			$type = $args['type'];

			if ( $type == 'rich' && ! $this->options->rich )
				$type = 'textarea';

			$fields[] = array($name, $type);
		}

		$data = array(
			'save_text' => __('Save', 'front-end-editor'),
			'cancel_text' => __('Cancel', 'front-end-editor'),
			'fields' => $fields,
			'request' => get_bloginfo('wpurl') . '/wp-admin/admin-ajax.php',
			'nonce' => wp_create_nonce($this->nonce_action)
		);
?>
<script type='text/javascript'>
jQuery(document).ready(function() {
	front_ed_init(<?php echo json_encode($data) ?>);
});
</script>
<?php
	}

	// Common response procedures
	function ajax_response() {
		// Is user trusted?
		check_ajax_referer($this->nonce_action, 'nonce');

		$id = $_POST['item_id'];
		$name = $_POST['name'];
		$type = $_POST['type'];
		$action = $_POST['callback'];

		// Is the current field defined?
		if ( ! $args = $this->fields[$name] )
			die(-1);

		// Does the user have the right to do this?
		if ( ! call_user_func(array($args['class'], 'check'), $id) )
			die(-1);

		// Make sure the charset is set correctly
		header('Content-Type: text/html; charset=' . get_option('blog_charset'));

		$callback = array($args['class'], $action);

		if ( $action == 'save' ) {
			$content = stripslashes_deep($_POST['content']);
			$result = call_user_func($callback, $id, $content, $name, $args);
			$result = apply_filters($name, $result);
		} elseif ( $action == 'get' ) {
			$result = call_user_func($callback, $id, $name, $args);
			if ( $type == 'rich' )
				$result = wpautop($result);
		}

		die($result);
	}

	function get_args($filter) {
		return $this->fields[$filter];
	}

	function _get_plugin_url() {
		// WP < 2.6
		if ( !function_exists('plugins_url') )
			return get_option('siteurl') . '/wp-content/plugins/' . plugin_basename(dirname(__FILE__));

		return plugins_url(plugin_basename(dirname(__FILE__)));
	}
}

function register_fronted_field($filter, $class, $args = '') {
	global $frontEditor;

	$frontEditor->register($filter, $class, $args);
}

