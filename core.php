<?php

abstract class FEE_Core {
	static $options;

	private static $fields;
	private static $active_fields;
	private static $instances = array();

	private static $plugin_url;
	private static $version;
	private static $nonce = 'front-editor';

	static function init( $options, $version ) {
		self::$options = $options;
		self::$version = $version;

		add_action( 'front_end_editor_fields', array( __CLASS__, 'make_instances' ), 100 );

		add_action( 'wp_ajax_front-end-editor', array( __CLASS__, 'ajax_response' ) );

		add_action( 'template_redirect', array( __CLASS__, '_init' ) );
	}

	static function _init() {
		if ( !is_user_logged_in() || apply_filters( 'front_end_editor_disable', false ) )
			return;

		add_action( 'wp_head', array( __CLASS__, 'add_filters' ), 100 );
		add_action( 'wp_footer', array( __CLASS__, 'scripts' ) );

		if ( self::$options->highlight )
			add_action( 'wp_head', array( __CLASS__, 'highlight' ) );
	}

	static function highlight() {
?>
<style type='text/css'>.fee-field:hover, .fee-field:hover > * {background-color: #ffffa5}</style>
<?php
	}

	static function scripts() {
		$wrapped = array_keys( FEE_Field_Base::get_wrapped() );

		if ( empty( $wrapped ) )
			return;

		// Prepare data
		$data = array(
			'save_text' => __( 'Save', 'front-end-editor' ),
			'cancel_text' => __( 'Cancel', 'front-end-editor' ),
			'fields' => array_keys( self::$active_fields ),
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'spinner' => admin_url( 'images/loading.gif' ),
			'nonce' => wp_create_nonce( self::$nonce ),
		);

		$url = plugins_url( 'js/', __FILE__ );

		$dev = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '.dev' : '';

		$css_dependencies = array();
		$js_dependencies = array( 'jquery' );

		// qTip
		if ( self::$options->tooltip ) {
			$data['controls'] = array(
				'edit' => __('Double-click to edit', 'front-end-editor'),
//				'delete' => __('Delete', 'front-end-editor')
			);

			wp_register_style( 'jquery-qtip', $url . 'qtip/jquery.qtip.css', array(), '2.0.0pre' );
			wp_register_script( 'jquery-qtip', $url . 'qtip/jquery.qtip.js', array(), '2.0.0pre', true );
			$css_dependencies[] = 'jquery-qtip';
			$js_dependencies[] = 'jquery-qtip';
		}

		// Autosuggest
		if ( in_array( 'terminput', $wrapped ) ) {
			$data['suggest'] = array(
				'src' => self::get_src('suggest')
			);
		}

		// Rich Editor
		if ( in_array( 'rich', $wrapped ) ) {
			wp_register_style( 'cleditor', $url . "cleditor/cleditor.css", array(), '1.3.0' );
			$css_dependencies[] = 'cleditor';

			wp_register_script( 'cleditor', $url . "cleditor/cleditor$dev.js", array('jquery'), '1.3.0', true );
			$js_dependencies[] = 'cleditor';

			$data['cleditor'] = array(
				'controls' => self::$options->cleditor_controls,
			);

			load_plugin_textdomain( 'cleditor', '', FRONT_END_EDITOR_PLUGIN_BASENAME . '/lang/cleditor' );

			$cledit_i18n = array();
			foreach ( get_translations_for_domain('cleditor')->entries as $entry ) {
				$cledit_i18n[ $entry->singular ] = $entry->translations[0];
			}
		}

		if ( !empty( $cledit_i18n ) )
			$cledit_i18n = 'CLEDITOR_I18N = ' . json_encode( $cledit_i18n );
		else
			$cledit_i18n = '';

		// Thickbox
		if ( count( array_intersect( array( 'image', 'thumbnail', 'rich' ), $wrapped ) ) ) {
			$data['image'] = array(
				'url' => admin_url( 'media-upload.php?post_id=0&type=image&editable_image=1&TB_iframe=true&width=640' ),
				'change' => __( 'Change Image', 'front-end-editor' ),
				'insert' => __( 'Insert Image', 'front-end-editor' ),
				'revert' => '(' . __( 'Clear', 'front-end-editor' ) . ')',
				'tb_close' => get_bloginfo( 'wpurl' ) . '/wp-includes/js/thickbox/tb-close.png',
			);

			$css_dependencies[] = 'thickbox';
			$js_dependencies[] = 'thickbox';
		}

		// Core script
		if ( defined('SCRIPT_DEBUG') ) {
			wp_register_script( 'jquery-delayed-double-click', $url . 'jquery.delayed-double-click.js', array('jquery'), self::$version, true );
			$js_dependencies[] = 'jquery-delayed-double-click';

			wp_register_script( 'fee-core', $url . "core.dev.js", $js_dependencies, self::$version, true );
			$js_dependencies[] = 'fee-core';

			foreach ( array_slice( scandir( dirname(__FILE__) . '/js/fields' ), 2 ) as $file ) {
				wp_register_script( "fee-fields-$file", $url . "fields/$file", array( 'fee-core' ), self::$version, true );
				$js_dependencies[] = "fee-fields-$file";
			}
		} else {
			wp_register_script( 'fee-editor', $url . "editor.js", $js_dependencies, self::$version, true );
			$js_dependencies[] = 'fee-editor';		
		}

		wp_register_style( 'fee-editor', plugins_url( "css/editor$dev.css", __FILE__ ), $css_dependencies, self::$version );
?>
<script type='text/javascript'>
var FrontEndEditor = {};
FrontEndEditor.data = <?php echo json_encode( $data ) ?>;
<?php echo $cledit_i18n; ?>
</script>
<?php
		scbUtil::do_scripts( $js_dependencies );
		scbUtil::do_styles( 'fee-editor' );
 
		do_action( 'front_end_editor_loaded', $wrapped );
	}

	private static function get_src( $handle ) {
		global $wp_scripts;

		if ( !is_object( $wp_scripts ) )
			$wp_scripts = new WP_Scripts;

		return get_bloginfo('wpurl') . $wp_scripts->registered[$handle]->src;
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

	static function make_instances() {
		$disabled = (array) self::$options->disabled;

		self::$active_fields = array();
		foreach ( self::get_fields() as $key => $data )
			if ( !in_array( $key, $disabled ) )
				self::$active_fields[ $key ] = $data;

		foreach ( self::$active_fields as $filter => $args ) {
			extract( $args );

			self::$instances[ $filter ] = new $class( $filter, $type );
		}
	}

	static function add_filters() {
		foreach ( self::$active_fields as $filter => $args ) {
			extract( $args );

			if ( empty( $title ) )
				continue;

			$instance = self::$instances[ $filter ];

			add_filter( $filter, array( $instance, 'wrap' ), $priority, $argc );
		}
	}

	static function get_fields() {
		return self::$fields;
	}

	static function get_args( $filter ) {
		return self::$fields[ $filter ];
	}

	static function ajax_response() {
		// Is user trusted?
		check_ajax_referer( self::$nonce, 'nonce' );

		extract( scbUtil::array_extract( $_POST, array( 'filter', 'callback', 'data' ) ) );

		// Is the current field defined?
		if ( !$instance = self::$instances[ $filter ] )
			die( -1 );

		// Does the user have the right to do this?
		if ( !$instance->check( $data ) || !$instance->allow( $data ) )
			die( -1 );

		$args = self::get_args( $filter );

		try {
			if ( 'save' == $callback ) {
				$content = stripslashes_deep( $_POST['content'] );
				$result = $instance->save( $data, $content );
				$result = @apply_filters( $filter, $result );
			}
			elseif ( 'get' == $callback ) {
				$result = $instance->get( $data );

				if ( 'rich' == $data['type'] )
					$result = wpautop( $result );
			}
			$result = array( 'content' => $result );
		} catch ( Exception $e ) {
			$result = array( 'error' => $e->getMessage() );
		}

		die( json_encode( $result ) );
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

