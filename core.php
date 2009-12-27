<?php

abstract class frontEditor {
	static $options;

	const baseclass = 'frontEd_field';

	private static $fields;
	private static $active_fields;
	private static $field_types;
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
		if ( ! is_user_logged_in()
		  or ! self::can_use_editor()
		  or apply_filters('front_ed_disable', false) )
			return;

		add_action('wp_head', array(__CLASS__, 'add_filters'), 100);

		self::add_scripts();

		add_action('wp_head', array(__CLASS__, 'add_css'));
		add_action('wp_footer', array(__CLASS__, 'add_js'));
	}

	private static function can_use_editor() {
		foreach ( self::$instances as $instance )
			if ( $instance->check() )
				return true;

		return false;
	}

	private static function add_scripts() {
		$css_dev = defined('STYLE_DEBUG') && STYLE_DEBUG ? '.dev' : '';
		$js_dev = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '.dev' : '';

		// Autogrow
		if ( in_array('textarea', self::$field_types) ) {
			wp_enqueue_script('growfield', self::$plugin_url . 'growfield.js', array('jquery'), '2', true);
		}

		// Autosuggest
		if ( in_array('terminput', self::$field_types) ) {
			wp_enqueue_script('suggest');
		}

		// Rich Editor
		if ( in_array('rich', self::$field_types) ) {
			wp_enqueue_script('nicedit', self::$plugin_url . "nicedit/nicEdit$js_dev.js", array(), '0.9r23',true);
		}

		// Thickbox
		if ( in_array('image', self::$field_types) ) {
			add_thickbox();

			wp_enqueue_script('livequery', self::$plugin_url . 'livequery.js', array('jquery'), '1.1.0-pre', true);
		}

		// Core scripts
		wp_enqueue_style('front-editor', self::$plugin_url . "editor/editor$css_dev.css", array(), self::$version);
		wp_enqueue_script('front-editor', self::$plugin_url . "editor/editor$js_dev.js", array('jquery'), self::$version, true);
	}

	static function add_css() {
		if ( ! self::$options->highlight ) 
			return;
?>
<style type='text/css'>.front-ed:hover, .front-ed:hover > * {background-color: #FFFFA5}</style>
<?php
	}

	static function add_js() {
		// Prepare data
		$data = array(
			'save_text' => __('Save', 'front-end-editor'),
			'cancel_text' => __('Cancel', 'front-end-editor'),
			'fields' => self::$field_types,
			'ajax_url' => admin_url('admin-ajax.php'),
			'spinner' => admin_url('images/loading.gif'),
			'nonce' => wp_create_nonce(self::$nonce),
		);

		if ( in_array('rich', self::$field_types) ) {
			$data['nicedit_icons'] = self::$plugin_url . 'nicedit/nicEditorIcons.gif';
		}

		if ( in_array('image', self::$field_types) ) {
			$data['caption'] = __('Change Image', 'front-end-editor');
			$data['img_revert'] = '(' . __('Use default', 'front-end-editor') . ')';
			$data['tb_close'] = get_bloginfo('wpurl') . '/wp-includes/js/thickbox/tb-close.png';
			$data['admin_url'] = admin_url();
		}

?>
<script type='text/javascript'>frontEditorData = <?php echo json_encode($data) ?>;</script>
<?php
	}

	// Register a new editable field
	static function register() {
		list ( $filter, $args ) = func_get_arg(0);

		if ( ! is_subclass_of($args['class'], self::baseclass) ) {
			trigger_error($args['class'] . " must be a subclass of " . self::baseclass, E_USER_ERROR);
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

		foreach ( self::$active_fields as $name => $args )
			self::$field_types[$name] = $args['type'];

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
			$result = $instance->save($id, $_POST['content']);
			$result = @apply_filters($name, stripslashes_deep($result));
		}
		elseif ( $action == 'get' ) {
			$result = $instance->get($id);

			if ( $type == 'rich' )
				$result = wpautop($result);
		}

		die($result);
	}
}

// All field classes should extend from this one or one of it's descendants
abstract class frontEd_field {
	private $filter;
	private $input_type;

	final public function __construct($filter, $type) {
		$this->filter = $filter;
		$this->input_type = $type;

		$this->setup();
	}

	/**
	 * Optional actions to be done once per instance
	 */
	protected function setup() {}

	/**
	 * Mark the field as editable
	 * @return string Wrapped content
	 */
	public function wrap($content, $id, $inline = false) {
		if ( ! $this->allow($id) )
			return $content;

		if ( ! is_scalar($content) )
			trigger_error("scalar expected. " . gettype($content) . " given", E_USER_WARNING);

		$class = 'front-ed-' . $this->filter . ' front-ed';
		$id = 'fee_' . esc_attr($id);

		$wrap_in = ( $inline || in_array($this->input_type, array('input', 'terminput', 'image')) ) ? 'span' : 'div';

		return html("$wrap_in id='{$id}' class='{$class}'", $content);
	}

	/**
	 * Retrieve the current data for the field
	 * @return string Unfiltered content
	 */
	abstract public function get($object_id);

	/**
	 * Save the data retrieved from the field
	 * @return string Saved content
	 */
	abstract public function save($object_id, $content);

	/**
	 * Check user permissions
	 * @return bool
	 */
	abstract public function check($object_id = 0);

	/**
	 * The type of object this field operates with
	 * @return string
	 */
	abstract static function get_object_type();

	/**
	 * Generate a standard placeholder
	 * @return string
	 */ 
	protected function placeholder() {
		return '[' . __('empty', 'front-end-editor') . ']';
	}

	/**
	 * Allow external code to block editing for certain objects
	 * @return bool
	 */
	final public function allow($object_id) {
		return apply_filters('front_ed_allow_' . $this->get_object_type(), true, $object_id, $this->filter, $this->input_type);
	}

	/**
	 * Get the filter of the current instance
	 * @return string
	 */
	final protected function get_filter() {
		return $this->filter;
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
	return frontEditor::register(func_get_args());
}

