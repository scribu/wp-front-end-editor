<?php

abstract class FEE_Core {
	const NONCE = 'front-end-editor';

	static $options;

	private static $fields;
	private static $instances = array();

	private static $plugin_url;

	private static $js_dependencies = array();

	static function init( $options ) {
		self::$options = $options;

		add_action( 'wp_ajax_front-end-editor', array( __CLASS__, 'ajax_response' ) );

		add_action( 'template_redirect', array( __CLASS__, '_init' ) );
		// TODO: Add equivalent hook for BuddyPress
	}

	static function _init() {
		if ( !is_user_logged_in() || apply_filters( 'front_end_editor_disable', false ) ) {
			return;
		}

		add_action( 'wp_head', array( __CLASS__, 'add_filters' ), 100 );
		add_action( 'wp_footer', array( __CLASS__, 'scripts' ) );
	}

	static function scripts() {
		$wrapped = array_keys( FEE_Field_Base::get_wrapped() );

		// No point loading all that JavaScript if there aren't any editable elements
		if ( empty( $wrapped ) ) {
			return;
		}

		// Prepare data
		$data = array(
			'edit_text' => __( 'Edit', 'front-end-editor' ),
			'save_text' => __( 'Save', 'front-end-editor' ),
			'cancel_text' => __( 'Cancel', 'front-end-editor' ),

			'spinner' => admin_url( 'images/loading.gif' ),
			'ajax_url' => self::get_ajax_url(),
			'nonce' => wp_create_nonce( self::NONCE ),
		);

		$css_dependencies = array();

		// Autosuggest
		if ( in_array( 'terminput', $wrapped ) ) {
			self::$js_dependencies[] = 'suggest';
		}

		if ( in_array( 'rich', $wrapped ) ) {
			wp_register_style( 'aloha-editor', plugins_url( 'lib/aloha-editor/css/aloha.css', FEE_MAIN_FILE ), array(), ALOHA_VERSION );
			$css_dependencies[] = 'aloha-editor';
		}

		// Thickbox
		if ( count( array_intersect( array( 'image', 'thumbnail', 'rich' ), $wrapped ) ) ) {
			$data['image'] = array(
				'url' => admin_url( 'media-upload.php?post_id=0&editable_image=1&TB_iframe=true&width=640' ),
				'change' => __( 'Change Image', 'front-end-editor' ),
				'insert' => __( 'Insert Image', 'front-end-editor' ),
				'revert' => '(' . __( 'Clear', 'front-end-editor' ) . ')',
				'tb_close' => get_bloginfo( 'wpurl' ) . '/wp-includes/js/thickbox/tb-close.png',
			);

			$css_dependencies[] = 'thickbox';
			self::$js_dependencies[] = 'thickbox';
		}

		// Core script
		$min = defined('SCRIPT_DEBUG') ? '' : '.min';
		self::register_script( 'fee-editor', "js/editor$min.js" );

		// Core style
		wp_register_style( 'fee-editor', plugins_url( 'css/core.css', FEE_MAIN_FILE ), $css_dependencies, FEE_VERSION );
		scbUtil::do_styles( 'fee-editor' );

?>
<script type='text/javascript'>
var FrontEndEditor = {};
FrontEndEditor.data = <?php echo json_encode( $data ) ?>;
</script>

<?php if ( in_array( 'rich', $wrapped ) ) { ?>
<script type='text/javascript'>
var Aloha = {};
Aloha.settings = {
	logLevels: { 'error': true, 'warn': true, 'info': true, 'debug': false, 'deprecated': true },
	bundles: {
		fee: '../../../aloha-plugins'
	},
	floatingmenu: {
		width: 410
	},
	sidebar: {
		disabled: true
	},
	plugins: {
		format: {
			config: ['b', 'i', 'del', 'sub', 'sup', 'p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'pre', 'removeFormat'],
		}
	}
};
</script>
<?php
	$plugins = array(
		'common/format',
		'common/align',
		'common/list',
		'common/link',
		'common/table',
		'common/undo',
		'common/paste',
		'common/block',
		'extra/cite',
		'fee/wpImage',
	);

	echo html( 'script', array(
		'src' => plugins_url( 'lib/aloha-editor/lib/aloha.js', FEE_MAIN_FILE ),
		'data-aloha-plugins' => implode( ',', $plugins )
	) ) . "\n";
}

		scbUtil::do_scripts( self::$js_dependencies );

		do_action( 'front_end_editor_loaded', $wrapped );
	}

	private static function get_ajax_url() {
		return admin_url( 'admin-ajax.php', 'relative' );
	}

	private static function register_script( $handle, $src, $dependencies = array() ) {
		$src = plugins_url( $src, FEE_MAIN_FILE );
		wp_register_script( $handle, $src, $dependencies, FEE_VERSION, true );
		self::$js_dependencies[] = $handle;
	}

	// Register a new editable field
	static function register() {
		list ( $filter, $args ) = func_get_arg( 0 );

		if ( !class_exists( $args['class'] ) ) {
			trigger_error( "Class '{$args['class']}' does not exist", E_USER_WARNING );
			return false;
		}

		if ( !is_subclass_of( $args['class'], 'FEE_Field_Base' ) ) {
			trigger_error( "{$args['class']} must be a subclass of 'FEE_Field_Base", E_USER_WARNING );
			return false;
		}

		if ( isset( self::$fields[$filter] ) )
			$args = wp_parse_args( $args, self::$fields[$filter] );
		else
			$args = wp_parse_args( $args, array(
				'title' => ucfirst( str_replace( '_', ' ', $filter ) ),
				'type' => 'input',
				'priority' => 11,
				'argc' => 1
			) );

		self::$fields[$filter] = $args;

		return true;
	}

	static function get_title( $filter ) {
		return self::$fields[$filter]['title'];
	}

	private static function make_instances() {
		$disabled = (array) self::$options->disabled;

		$active_fields = array();

		foreach ( self::get_fields() as $filter => $args ) {
			if ( in_array( $filter, $disabled ) )
				continue;

			$active_fields[ $filter ] = $args;

			extract( $args );

			self::$instances[ $filter ] = new $class( $filter, $type );
		}

		return $active_fields;
	}

	static function add_filters() {
		foreach ( self::make_instances() as $filter => $args ) {
			extract( $args );

			if ( empty( $title ) )
				continue;

			$instance = self::$instances[ $filter ];

			add_filter( $filter, array( $instance, 'wrap' ), $priority, $argc );
		}
	}

	static function get_fields() {
		// Safe hook for new editable fields to be registered
		if ( !did_action( 'front_end_editor_fields' ) )
			do_action( 'front_end_editor_fields' );

		return self::$fields;
	}

	static function get_args( $filter ) {
		return self::$fields[ $filter ];
	}

	static function ajax_response() {
		check_ajax_referer( self::NONCE, 'nonce' );

		self::make_instances();

		extract( wp_array_slice_assoc( $_POST, array( 'callback', 'data' ) ) );

		if ( 'save' == $callback )
			$content = stripslashes_deep( $_POST['content'] );
		else
			$content = null;

		if ( isset( $_POST['group'] ) ) {
			foreach ( $data as $i => $single_data ) {
				if ( isset( $_POST['commonData'] ) )
					$single_data = array_merge( $single_data, $_POST['commonData'] );
				$r[$i] = self::single_ajax_response( $callback, $single_data, @$content[$i] );
			}

			if ( isset( $_POST['createPost'] ) ) {
				$post_id = $_POST['commonData']['post_id'];
				wp_publish_post( $post_id );
				$r = array( 'permalink' => get_permalink( $post_id ) );
			}
		} else {
			$r = self::single_ajax_response( $callback, $data, $content );
		}

		die( json_encode( $r ) );
	}

	private static function single_ajax_response( $callback, $data, $content ) {
		$filter = $data['filter'];

		// Is the current field defined?
		if ( !$instance = self::$instances[ $filter ] )
			return array( 'error' => __( 'Undefined field', 'front-end-editor' ) );

		// Does the user have the right to do this?
		if ( !$instance->check( $data ) || !$instance->allow( $data ) )
			return array( 'error' => __( 'Insufficient permissions', 'front-end-editor' ) );

		$args = self::get_args( $filter );

		try {
			if ( 'save' == $callback ) {
				$instance->save( $data, $content );
				$result = $instance->get_filtered( $data );
			}
			elseif ( 'get' == $callback ) {
				$result = (string) $instance->get( $data );

				if ( 'rich' == $data['type'] )
					$result = wpautop( $result );
			}
			$result = array( 'content' => $result );
		} catch ( Exception $e ) {
			$result = array( 'error' => $e->getMessage() );
		}

		return $result;
	}
}

/**
 * Registers a new editable field
 *
 * @param string $filter
 * @param array $args(
 * 	'class' => string The name of the field handler class ( mandatory )
 * 	'title' => string The user-friendly title ( optional )
 * 	'type' => string: 'input' | 'textarea' | 'rich' | 'image' ( default: input )
 * 	'priority' => integer ( default: 11 )
 * 	'argc' => integer ( default: 1 )
 * )
 */
function fee_register_field() {
	$args = func_get_args();

	return FEE_Core::register( $args );
}

