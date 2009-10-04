<?php

abstract class frontEditor
{
	static $options;

	private static $fields;
	private static $active_fields;
	private static $instances = array();

	private static $version;
	private static $nonce = 'front-editor';

	static function init($options, $version)
	{
		self::$options = $options;
		self::$version = $version;

		add_action('front_ed_fields', array(__CLASS__, 'make_instances'), 100);

		add_action('wp_ajax_front-editor', array(__CLASS__, 'ajax_response'));

		add_action('template_redirect', array(__CLASS__, '_init'));
	}

	static function _init()
	{
		if ( ! is_user_logged_in() )
			return;

		if ( apply_filters('front_ed_disable', false) )
			return;

		self::add_scripts();

		add_action('wp_head', array(__CLASS__, 'pass_to_js'));
		add_action('wp_head', array(__CLASS__, 'add_filters'), 100);
	}

	static function make_instances()
	{
		self::$active_fields = self::get_fields();
		foreach ( (array) self::$options->disabled as $name )
			unset(self::$active_fields[$name]);

		foreach ( self::$active_fields as $name => $args )
		{
			extract($args);
			self::$instances[$name] = new $class($name, $type);
		}
	}

	static function add_filters()
	{
		foreach ( self::$active_fields as $name => $args )
		{
			extract($args);

			$instance = self::$instances[$name];

			add_filter($name, array($instance, 'wrap'), $priority, $argc);
		}
	}

	private static function add_scripts()
	{
// DEBUG
// wp_enqueue_script('firebug-lite', 'http://getfirebug.com/releases/lite/1.2/firebug-lite-compressed.js');

		$url = plugin_dir_url(__FILE__) . 'inc/';

		if ( self::$options->rich )
		{
			wp_enqueue_style('jwysiwyg', $url . 'jwysiwyg/jquery.wysiwyg.css');
			wp_enqueue_script('jwysiwyg', $url . 'jwysiwyg/jquery.wysiwyg.js', array('jquery'), self::$version, true);
		}

		wp_enqueue_script('autogrow', $url . 'autogrow.js', array('jquery'), '1.2.5', true);

		// Core scripts
		wp_enqueue_style('front-editor', $url . 'editor/editor.css', self::$version);
		wp_enqueue_script('front-editor', $url . 'editor/editor.js', array('jquery'), self::$version, true);
	}

	static function pass_to_js()
	{
		// PHP < 5.2
		if ( ! function_exists('json_encode') )
			require_once dirname(__FILE__) . '/inc/json.php';

		foreach( self::$fields as $name => $args )
			$fields[] = array($name, $args['type']);

		$data = array(
			'save_text' => __('Save', 'front-end-editor'),
			'cancel_text' => __('Cancel', 'front-end-editor'),
			'fields' => $fields,
			'request' => admin_url('admin-ajax.php'),
			'spinner' => admin_url('images/loading.gif'),
			'nonce' => wp_create_nonce(self::$nonce),
		);

		$path = '/' . apply_filters('front_ed_wysiwyg_css', 'front-end-editor.css');

		if ( file_exists(TEMPLATEPATH . $path) )
			$data['css'] = get_template_directory_uri() . $path;
?>
<script type='text/javascript'>
frontEditorData = <?php echo json_encode($data) ?>;
</script>
<?php
		if ( self::$options->highlight )
		{
w?>
<style type='text/css'>.front-ed:hover, .front-ed:hover > * {background-color: #FFFFA5}</style>
<?php
		}
	}

	static function ajax_response()
	{
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
		if ( ! $instance->check($id) )
			die(-1);

		$args = self::$fields[$name];

		if ( $action == 'save' )
		{
			$content = stripslashes_deep($_POST['content']);
			$result = $instance->save($id, $content, $args);
			$result = @apply_filters($name, $result);
		}
		elseif ( $action == 'get' )
		{
			$result = $instance->get($id, $args);
			if ( $type == 'rich' )
				$result = wpautop($result);
		}

		die($result);
	}


	// Register a new editable field
	static function register()
	{
		list ( $filter, $args ) = func_get_arg(0);

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
	}

	static function get_fields()
	{
		return self::$fields;
	}

	static function get_args($filter)
	{
		return self::$fields[$filter];
	}
}

// All field classes should extend from this one or one of it's descendants
class frontEd_field
{
	protected $filter;
	protected $type;

	function __construct($filter, $type)
	{
		$this->filter = $filter;
		$this->type = $type;
		
		$this->setup();
	}

	// Optional actions to be done once per instance
	function setup(){}

	// Mark the field as editable
	function wrap($content, $id)
	{
		if ( is_feed() )
			return $content;

		$class = 'front-ed-' . $this->filter . ' front-ed';
		$id = 'fee_' . esc_attr($id);

		if ( $this->type == 'input' )
			return "<span id='{$id}' class='{$class}'>{$content}</span>";
		else
			return "<div id='{$id}' class='{$class}'>{$content}</div>";
	}

	// Retrieve the current data for the field
	function get($object_id, $filter, $args)
	{
		trigger_error("The get() method must be implemented in " . self::get_class(), E_USER_ERROR);
	}

	// Save the data retrieved from the field
	function save($object_id, $content, $filter, $args)
	{
		trigger_error("The save() method must be implemented in " . self::get_class(), E_USER_ERROR);
	}

	// Check user permissions
	function check($object_id)
	{
		trigger_error("The check() method must be implemented in " . self::get_class(), E_USER_ERROR);
	}

	function placeholder()
	{
		return '[' . __('empty', 'front-end-editor') . ']';
	}
	
	private function get_class()
	{
		if ( function_exists('get_called_class') )
			return "<strong>" . get_called_class() . "</strong>";
		else
			return 'the child class';
	}
}

/*
Registers a new editable field

@param string $filter
@param array $args(
	'class' => string (mandatory)
	'type' => string: 'input' | 'textarea' | 'rich' (default: input)
	'priority' => integer (default: 11)
	'argc' => integer (default: 1)
)
*/
function register_fronted_field()
{
	frontEditor::register(func_get_args());
}

