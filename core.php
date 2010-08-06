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
		$wrapped = FEE_Field_Base::get_wrapped();

		if ( empty( $wrapped ) )
			return;

		$url = plugins_url( 'editor/', __FILE__ );

		$css_dev = defined( 'STYLE_DEBUG' ) && STYLE_DEBUG ? '.dev' : '';
		$js_dev = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '.dev' : '';

		// Prepare data
		$data = array(
			'save_text' => __( 'Save', 'front-end-editor' ),
			'cancel_text' => __( 'Cancel', 'front-end-editor' ),
			'fields' => array_keys( self::$active_fields ),
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'spinner' => admin_url( 'images/loading.gif' ),
			'nonce' => wp_create_nonce( self::$nonce ),
		);

		$css_dependencies = array();
		$js_dependencies = array( 'jquery' );

		// qTip
		if ( self::$options->tooltip ) {
			$data['tooltip'] = array(
				'icon' => $url . 'editor.png',
				'text' => __( 'Double-click to edit', 'front-end-editor' )
			);

			wp_register_script( 'jquery-qtip', $url . "jquery.qtip$js_dev.js", array(), '1.0-rc3', true );
			$js_dependencies[] = 'jquery-qtip';
		}

		// Autosuggest
		if ( array_key_exists( 'terminput', $wrapped ) ) {
			$data['suggest'] = array(
				'src' => self::get_src('suggest')
			);
		}

		// Rich Editor
		$nicEditL10n = '';
		if ( array_key_exists( 'rich', $wrapped ) ) {
			$data['nicedit'] = apply_filters( 'front_end_editor_nicedit', array(
				'src' => $url . "nicedit/nicEdit$js_dev.js?ver=0.9r23",
				'iconsPath' => $url . 'nicedit/nicEditorIcons.gif',
				'buttonList' => self::$options->ne_buttons
			) );

			$nicEditL10n = array(
				'Click to Bold' => __( 'Click to Bold', 'front-end-editor' ),
				'Click to Italic' => __( 'Click to Italic', 'front-end-editor' ),
				'Click to Underline' => __( 'Click to Underline', 'front-end-editor' ),
				'Left Align' => __( 'Left Align', 'front-end-editor' ),
				'Center Align' => __( 'Center Align', 'front-end-editor' ),
				'Right Align' => __( 'Right Align', 'front-end-editor' ),
				'Justify Align' => __( 'Justify Align', 'front-end-editor' ),
				'Insert Ordered List' => __( 'Insert Ordered List', 'front-end-editor' ),
				'Insert Unordered List' => __( 'Insert Unordered List', 'front-end-editor' ),
				'Click to Subscript' => __( 'Click to Subscript', 'front-end-editor' ),
				'Click to Superscript' => __( 'Click to Superscript', 'front-end-editor' ),
				'Click to Strike Through' => __( 'Click to Strike Through', 'front-end-editor' ),
				'Remove Formatting' => __( 'Remove Formatting', 'front-end-editor' ),
				'Indent Text' => __( 'Indent Text', 'front-end-editor' ),
				'Remove Indent' => __( 'Remove Indent', 'front-end-editor' ),
				'Horizontal Rule' => __( 'Horizontal Rule', 'front-end-editor' ),
				'Select Font Size' => __( 'Select Font Size', 'front-end-editor' ),
				'Select Font Family' => __( 'Select Font Family', 'front-end-editor' ),
				'Select Font Format' => __( 'Select Font Format', 'front-end-editor' ),
				'Add Link' => __( 'Add Link', 'front-end-editor' ),
				'Remove Link' => __( 'Remove Link', 'front-end-editor' ),
				'Change Text Color' => __( 'Change Text Color', 'front-end-editor' ),
				'Change Background Color' => __( 'Change Background Color', 'front-end-editor' ),
				'Add Image' => __( 'Add Image', 'front-end-editor' ),
				'Upload Image' => __( 'Upload Image', 'front-end-editor' ),
				'Edit HTML' => __( 'Edit HTML', 'front-end-editor' ),
			);
			$nicEditL10n = 'var nicEditL10n = ' . json_encode( $nicEditL10n );
		}

		// Thickbox
		if ( array_key_exists( 'image', $wrapped ) || array_key_exists( 'thumbnail', $wrapped ) ) {
			$data['admin_url'] = admin_url();

			$data['image'] = array(
				'change' => __( 'Change Image', 'front-end-editor' ),
				'revert' => '(' . __( 'Clear', 'front-end-editor' ) . ')',
				'tb_close' => get_bloginfo( 'wpurl' ) . '/wp-includes/js/thickbox/tb-close.png',
			);

			$css_dependencies[] = 'thickbox';
			$js_dependencies[] = 'thickbox';

			wp_register_script( 'livequery', $url . 'livequery.js', array( 'jquery' ), '1.1.0-pre', true );
			$js_dependencies[] = 'livequery';
		}

		// Core script
		wp_register_style( 'front-end-editor', $url . "editor$css_dev.css", $css_dependencies, self::$version );
		wp_register_script( 'front-end-editor', $url . "editor$js_dev.js", $js_dependencies, self::$version, true );
?>
<script type='text/javascript'>
var FrontEndEditor = {};
FrontEndEditor.data = <?php echo json_encode( $data ) ?>;
<?php echo $nicEditL10n; ?>
</script>
<?php
		scbUtil::do_scripts( 'front-end-editor' );
		scbUtil::do_styles( 'front-end-editor' );
	}

	private static function get_src( $handle ) {
		global $wp_scripts;

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

		die( $result );
	}
}

/*
Registers a new editable field

@param string $filter
@param array $args(
	'class' => string The name of the field handler class ( mandatory )
	'title' => string The user-friendly title ( optional )
	'type' => string: 'input' | 'textarea' | 'rich' | 'image' ( default: input )
	'priority' => integer ( default: 11 )
	'argc' => integer ( default: 1 )
)
*/
function register_fronted_field() {
	return FEE_Core::register( func_get_args() );
}

