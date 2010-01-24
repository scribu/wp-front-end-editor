<?php

abstract class FEE_Core {
	static $options;

	private static $fields;
	private static $active_fields;
	private static $instances = array();

	private static $plugin_url;
	private static $version;
	private static $nonce = 'front-editor';

	static function init($file, $options, $version) {
		self::$options = $options;
		self::$version = $version;

		self::$plugin_url = plugin_dir_url($file) . 'inc/';

		add_action('front_ed_fields', array(__CLASS__, 'make_instances'), 100);

		add_action('wp_ajax_front-editor', array(__CLASS__, 'ajax_response'));

		add_action('template_redirect', array(__CLASS__, '_init'));
	}

	static function _init() {
		if ( ! is_user_logged_in() || apply_filters('front_ed_disable', false) )
			return;

		add_action('wp_head', array(__CLASS__, 'add_filters'), 100);

		add_action('wp_print_styles', array(__CLASS__, 'add_css'));
		add_action('wp_footer', array(__CLASS__, 'add_js'));
	}

	static function add_css() {
		$css_dev = defined('STYLE_DEBUG') && STYLE_DEBUG ? '.dev' : '';

		wp_enqueue_style('front-editor', self::$plugin_url . "editor/editor$css_dev.css", array(), self::$version);

		if ( ! self::$options->highlight )
			return;
?>
<style type='text/css'>.front-ed:hover, .front-ed:hover > * {background-color: #FFFFA5}</style>
<?php
	}

	static function add_js() {
		$js_dev = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '.dev' : '';

		// Prepare data
		$field_types = array();
		foreach ( self::$active_fields as $name => $args )
			$field_types[$name] = $args['type'];
		
		$data = array(
			'save_text' => __('Save', 'front-end-editor'),
			'cancel_text' => __('Cancel', 'front-end-editor'),
			'fields' => $field_types,
			'ajax_url' => admin_url('admin-ajax.php'),
			'spinner' => admin_url('images/loading.gif'),
			'nonce' => wp_create_nonce(self::$nonce),
		);

		$wrapped = FEE_Field_Base::get_wrapped();
		$dependencies = array('jquery');

		// Autosuggest
		if ( array_key_exists('terminput', $wrapped) ) {
			$dependencies[] = 'suggest';
		}

		// Rich Editor
		if ( array_key_exists('rich', $wrapped) ) {
			$data['nicedit_icons'] = self::$plugin_url . 'nicedit/nicEditorIcons.gif';

			wp_register_script('nicedit', self::$plugin_url . "nicedit/nicEdit$js_dev.js", array(), '0.9r23', true);
			$dependencies[] = 'nicedit';
		}

		// Thickbox
		if ( array_key_exists('image', $wrapped) ) {
			$data['caption'] = __('Change Image', 'front-end-editor');
			$data['img_revert'] = '(' . __('Use default', 'front-end-editor') . ')';
			$data['tb_close'] = get_bloginfo('wpurl') . '/wp-includes/js/thickbox/tb-close.png';
			$data['admin_url'] = admin_url();

			add_thickbox();

			wp_register_script('livequery', self::$plugin_url . 'livequery.js', array('jquery'), '1.1.0-pre', true);
			$dependencies[] = 'livequery';
		}

		// Core script
		wp_register_script('front-editor', self::$plugin_url . "editor/editor$js_dev.js", $dependencies, self::$version, true);

		scbUtil::do_scripts('front-editor');
?>
<script type='text/javascript'>frontEditorData = <?php echo json_encode($data) ?>;</script>
<?php
	}

	// Register a new editable field
	static function register() {
		list ( $filter, $args ) = func_get_arg(0);

		if ( ! is_subclass_of($args['class'], 'FEE_Field_Base') ) {
			trigger_error($args['class'] . " must be a subclass of " . 'FEE_Field_Base', E_USER_ERROR);
			return false;
		}

		if ( isset(self::$fields[$filter]) )
			$args = wp_parse_args($args, self::$fields[$filter]);
		else
			$args = wp_parse_args($args, array(
				'title' => ucfirst(str_replace('_', ' ', $filter)),
				'type' => 'input',
				'priority' => 11,
				'argc' => 1
			));

		self::$fields[$filter] = $args;

		return true;
	}

	static function make_instances() {
		self::$active_fields = self::get_fields();
		foreach ( (array) self::$options->disabled as $name )
			unset(self::$active_fields[$name]);

		foreach ( self::$active_fields as $name => $args ) {
			extract($args);

			self::$instances[$name] = new $class($name, $type);
		}
	}

	static function add_filters() {
		foreach ( self::$active_fields as $name => $args ) {
			extract($args);

			$instance = self::$instances[$name];

			add_filter($name, array($instance, 'wrap'), $priority, $argc);
		}
	}

	static function get_fields() {
		return self::$fields;
	}

	static function get_args($filter) {
		return self::$fields[$filter];
	}

	static function ajax_response() {
		// Is user trusted?
		check_ajax_referer(self::$nonce, 'nonce');

		$id = $_POST['item_id'];
		$name = $_POST['name'];
		$type = $_POST['type'];
		$action = $_POST['callback'];

		// Is the current field defined?
		if ( ! $instance = self::$instances[$name] )
			die(-1);

		// Does the user have the right to do this?
		if ( ! $instance->check($id) || ! $instance->allow($id) )
			die(-1);

		$args = self::get_args($name);

		if ( $action == 'save' ) {
			$content = stripslashes_deep($_POST['content']);
			$result = $instance->save($id, $content);
			$result = @apply_filters($name, $result);
		}
		elseif ( $action == 'get' ) {
			$result = $instance->get($id);

			if ( $type == 'rich' )
				$result = wpautop($result);
		}

		die($result);
	}
}

/*
Registers a new editable field

@param string $filter
@param array $args(
	'class' => string The name of the field handler class (mandatory)
	'title' => string The user-friendly title (optional)
	'type' => string: 'input' | 'textarea' | 'rich' | 'image' (default: input)
	'priority' => integer (default: 11)
	'argc' => integer (default: 1)
)
*/
function register_fronted_field() {
	return FEE_Core::register(func_get_args());
}

