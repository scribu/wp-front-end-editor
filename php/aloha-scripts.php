<?php

/**
 * This class handles all Aloha specific actions like configuration and script dependency management
 * It will provide Aloha Editor version 0.9.3
 */
abstract class FEE_AlohaEditor {
	const VERSION = '0.9.3';

	private static $aloha_src_path;
	private static $alohaSrcBaseUrl;

	public static function enqueue() {
		self::$aloha_src_path = dirname( FRONT_END_EDITOR_MAIN_FILE ) . '/lib/aloha-editor/';

		$enable_debugging = defined('SCRIPT_DEBUG') && is_dir( self::$aloha_src_path );

		self::$alohaSrcBaseUrl  = plugin_dir_url( FRONT_END_EDITOR_MAIN_FILE );
		self::$alohaSrcBaseUrl .= $enable_debugging ? 'lib/aloha-editor/WebContent/' : 'aloha-build/';

		// Aloha 0.9.3 isn't compatible with newer versions of jQuery :(
		wp_deregister_script('jquery');
		wp_register_script('jquery', self::$alohaSrcBaseUrl . 'deps/jquery-1.4.2.js', array(), '1.4.2', false);

		if ( $enable_debugging )
			self::enqueue_debug();
		else
			self::enqueue_compressed();

		self::enqueue_plugins();

		add_action( 'wp_head', array( __CLASS__, 'config' ) );
	}

	private static function enqueue_plugins() {
		// Core plugins
		$alohaPluginsBaseUrl = self::$alohaSrcBaseUrl . 'plugins/com.gentics.aloha.plugins.';

		wp_register_script('aloha-plugin-format', $alohaPluginsBaseUrl . 'Format/plugin.js', array(), self::VERSION);
		wp_register_script('aloha-plugin-table', $alohaPluginsBaseUrl . 'Table/plugin.js', array(), self::VERSION);
		wp_register_script('aloha-plugin-list',  $alohaPluginsBaseUrl . 'List/plugin.js', array(), self::VERSION);
		wp_register_script('aloha-plugin-link', $alohaPluginsBaseUrl . 'Link/plugin.js', array(), self::VERSION);
		wp_register_script('aloha-plugin-linklist', $alohaPluginsBaseUrl .'Link/LinkList.js', array(), self::VERSION);
		wp_register_script('aloha-plugin-highlighteditables', $alohaPluginsBaseUrl . 'HighlightEditables/plugin.js', array(), self::VERSION);
		wp_register_script('aloha-plugin-TOC', $alohaPluginsBaseUrl .'TOC/plugin.js' ,array(), self::VERSION);
		wp_register_script('aloha-plugin-delicious', $alohaPluginsBaseUrl .'Link/delicious.js', array(), self::VERSION);
		wp_register_script('aloha-plugin-paste', $alohaPluginsBaseUrl . 'Paste/plugin.js', array(), self::VERSION);
		wp_register_script('aloha-plugin-wordpastehandler', $alohaPluginsBaseUrl .'Paste/wordpastehandler.js', array(), self::VERSION);

		wp_enqueue_script('aloha-plugin-format');
		wp_enqueue_script('aloha-plugin-link');
		wp_enqueue_script('aloha-plugin-list');
		wp_enqueue_script('aloha-plugin-table');

		// Custom Plugins
		$alohaCustomPluginsBaseUrl = plugins_url('aloha-plugins/', FRONT_END_EDITOR_MAIN_FILE);

		wp_enqueue_script('aloha-plugin-wp-savecancel', $alohaCustomPluginsBaseUrl. 'wpSaveCancel/plugin.js', array(), self::VERSION);
		wp_enqueue_script('aloha-plugin-wp-image', $alohaCustomPluginsBaseUrl. 'wpImage/plugin.js', array(), self::VERSION);
	}

	private static function enqueue_debug() {
		// External Deps
		foreach ( self::get_deps_in_file( 'deps-includes.txt' ) as $handle => $path ) {
			self::queue_script( $handle, $path );
		}

		// Aloha Deps
		foreach ( self::get_deps_in_file( 'aloha-includes.txt' ) as $handle => $path ) {
			$handle = 'aloha-' . $handle;

			self::queue_script( $handle, $path );
		}

		// Styles
		foreach ( self::get_deps_in_file( 'css-includes.txt' ) as $handle => $path ) {
			$handle = strtolower( substr( $handle, 0, -1 ) );
			if ( false !== strpos( $path, 'plugins/' ) ) {
				$handle = 'aloha-' . $handle;
			}

			self::queue_style( $handle, $path );
		}
	}

	private static function enqueue_compressed() {
		wp_enqueue_script( 'aloha-editor', self::$alohaSrcBaseUrl . 'aloha.js', array('jquery'), self::VERSION );
	}

	static function config() {
		$i18n = reset( explode( '_', get_locale() ) );

?>
		<script type="text/javascript">
			GENTICS.Aloha.settings = {
				logLevels: {'error': true, 'warn': true, 'info': false, 'debug': false},
				errorhandling: false,
				ribbon: false,
				i18n: {current: '<?php echo $i18n; ?>'}
			};
		</script>
<?php
	}

	// Helper function for enqueing scripts
	private static function queue_script( $handle, $relative_src ) {
		wp_enqueue_script( $handle, self::$alohaSrcBaseUrl . $relative_src, array(), self::VERSION );
	}

	// Helper function for enqueing styles
	private static function queue_style( $handle, $relative_src ) {
		wp_enqueue_style( $handle, self::$alohaSrcBaseUrl . $relative_src, array(), self::VERSION );
	}

	// Returns an array containing the lines found in one of the *-includes.txt files
	private static function get_deps_in_file( $file_name ) {
		$deps = array();

		$file = fopen( self::$aloha_src_path . '/build/' . $file_name, 'r' );
		while ( ($path = fgets($file)) !== false ) {
			$handle = str_replace( '.', '-', strtolower( substr( basename( rtrim( $path ) ), 0, -3 ) ) );

			$deps[ $handle ] = $path;
		}
		fclose( $file );

		return $deps;
	}
}

