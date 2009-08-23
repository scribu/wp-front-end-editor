<?php

abstract class frontEditor
{
	static $options;

	private static $fields;
	private static $instances = array();

	private static $version;
	private static $nonce = 'front-editor';

	static function init($options, $version)
	{
		self::$options = $options;
		self::$version = $version;

		// Set core hooks
		add_action('template_redirect', array(__CLASS__, 'add_scripts'));
		add_action('wp_head', array(__CLASS__, 'pass_to_js'));
		add_action('wp_ajax_front-editor', array(__CLASS__, 'ajax_response'));
		
		add_action('front_ed_fields', array(__CLASS__, 'add_filters'), 100);
	}

	// Register a new editable field
	static function register()
	{
		$fargs = func_get_arg(0);
		
		$filter = $fargs[0];

		if ( is_array($fargs[1]) )
			$args = $fargs[1];
		else
		{
			$args['class'] = $fargs[1];

			if ( isset($fargs[2]) )
				$args = $args + $fargs[2];
		}

		$args = wp_parse_args($args, array(
			'title' => ucfirst(str_replace('_', ' ', $filter)),
			'type' => 'input',
			'priority' => 10,
			'argc' => 1
		));

		self::$fields[$filter] = $args;
	}

	static function add_scripts()
	{
		if ( !is_user_logged_in() )
			return;

// DEBUG
# wp_enqueue_script('firebug-lite', 'http://getfirebug.com/releases/lite/1.2/firebug-lite-compressed.js');

		$url = plugin_dir_url(__FILE__) . 'inc/';

		if ( self::$options->rich )
		{
			wp_enqueue_style('jwysiwyg', $url . 'js/jwysiwyg/jquery.wysiwyg.css');
			wp_enqueue_script('jwysiwyg', $url . 'js/jwysiwyg/jquery.wysiwyg.js', array('jquery'), self::$version, true);
		}

		wp_enqueue_script('autogrow', $url . 'js/autogrow.js', array('jquery'), '1.2.5', true);

		// Core scripts
		wp_enqueue_style('front-editor', $url . 'editor.css', self::$version);
		wp_enqueue_script('front-editor', $url . 'editor.js', array('jquery'), self::$version, true);
	}

	static function add_filters()
	{
		foreach ( self::$fields as $name => $args )
		{
			if ( in_array($name, self::$options->disabled) )
				continue;

			extract($args);

			$instance = new $class($name, $type);
			self::$instances[$class] = $instance;

			add_filter($name, array($instance, 'wrap'), $priority, $argc);
		}
	}

	// Send necesarry info to JS land
	static function pass_to_js()
	{
		// PHP < 5.2
		if ( !function_exists('json_encode') )
			require_once(dirname(__FILE__) . '/inc/json.php');

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
?>
<script type='text/javascript'>
frontEditorData = <?php echo json_encode($data) ?>;
</script>
<?php
	}

	// Common response procedures
	static function ajax_response()
	{
		// Is user trusted?
		check_ajax_referer(self::$nonce, 'nonce');

		$id = $_POST['item_id'];
		$name = $_POST['name'];
		$type = $_POST['type'];
		$action = $_POST['callback'];

		// Is the current field defined?
		if ( ! $args = self::$fields[$name] )
			die(-1);

		// Does the user have the right to do this?
		$instance = self::$instances[$args['class']];

		if ( ! $instance->check($id) )
			die(-1);

		// WP < 2.8
		header('Content-Type: text/html; charset=' . get_option('blog_charset'));

		if ( $action == 'save' )
		{
			$content = stripslashes_deep($_POST['content']);
			$result = $instance->save($id, $content, $args);
			$result = apply_filters($name, $result);
		}
		elseif ( $action == 'get' )
		{
			$result = $instance->get($id, $args);
			if ( $type == 'rich' )
				$result = wpautop($result);
		}

		die($result);
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

		if ( $this->type == 'input' )
			return "<span rel='{$id}' class='{$class}'>{$content}</span>";
		else
			return "<div rel='{$id}' class='{$class}'>{$content}</div>";
	}

	// Retrieve the current data for the field
	function get($object_id, $filter, $args)
	{
		if ( function_exists('get_called_class') )
			$class = "<strong>" . get_called_class() . "</strong>";
		else
			$class = 'the child class';

		trigger_error("The get() must be implemented in a subclass", E_USER_ERROR);
	}

	// Save the data retrieved from the field
	function save($object_id, $content, $filter, $args)
	{
		if ( function_exists('get_called_class') )
			$class = "<strong>" . get_called_class() . "</strong>";
		else
			$class = 'the child class';

		trigger_error("The save() must be implemented in a subclass", E_USER_ERROR);
	}

	// Check user permissions
	function check($object_id)
	{
		if ( function_exists('get_called_class') )
			$class = "<strong>" . get_called_class() . "</strong>";
		else
			$class = 'the child class';

		trigger_error("The check() method must be implemented in $class", E_USER_ERROR);
	}

	function placeholder()
	{
		return '[' . __('empty', 'front-end-editor') . ']';
	}
}

/*
Registers a new editable field

@param string $filter
@param array $args(
	'class' => string (mandatory)
	'type' => string: 'input' | 'textarea' | 'rich' (default: input)
	'priority' => integer (default: 10)
	'argc' => integer (default: 1)
)
*/
function register_fronted_field()
{
	frontEditor::register(func_get_args());
}


// WP < 2.8
if ( !function_exists('plugin_dir_url') ) :
function plugin_dir_url($file) 
{
	// WP < 2.6
	if ( !function_exists('plugins_url') )
		return trailingslashit(get_option('siteurl') . '/wp-content/plugins/' . plugin_basename($file));

	return trailingslashit(plugins_url(plugin_basename(dirname($file))));
}
endif;

