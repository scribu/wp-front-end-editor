<?php

/**
 * This class handles all aloha specific actions like configuration and script dependency management
 * It will provide aloha editor 0.10
 */
abstract class FEE_AlohaEditor {

	/**
	 * Enqueues the aloha editor dependencies depending on user status. 
	 * Enqueuing will only performed if the user is loggedin and outsite 
	 * of the admin area (dashboard).
	 */
	static function enqueueAloha() {

		//wp_register_script('aewip',  'js/aewip.js', array (), '0.10', false);
		wp_register_script('aloha.config', plugins_url('js/alohaeditor-config.js.php', FRONT_END_EDITOR_MAIN_FILE), array (), '0.10', false);
		wp_register_script('aloha.init', plugins_url('js/alohaeditor-init.js.php', FRONT_END_EDITOR_MAIN_FILE), array (), '0.10', false);

		// Deregister jquery and register aloha jquery version
		wp_deregister_script('jquery');
		wp_register_script('jquery', FEE_AlohaEditor::_getAlohaSrcBaseUrl() . 'dep/jquery-1.5.1.js', array (), '0.10', false);


		if (is_user_logged_in() && !is_admin()) {
			
			// Load the aloha editor configuration
			wp_enqueue_script('aloha.config');
			
			//load jquery
			wp_enqueue_script('jquery');
			

			// Initalize aloha
			wp_enqueue_script('aloha.init');
			
			//wp_enqueue_script('aewip');
			// Load format plugin and all aloha dependencies
			wp_enqueue_script('aloha.format');
			
			
			//wp_enqueue_script('aloha.ribbon');
			//wp_enqueue_script('aloha.table');
			//wp_enqueue_script('aloha.list');
			//wp_enqueue_script('aloha.highlighteditables');
			//wp_enqueue_script('aloha.markdown');
			//wp_enqueue_script('aloha.aewip');


		}
	}

	/**
	 * Prints the script that enables editables
	 */
	static function printAlohaEditableConfiguration() {
	?>		
	<script type="text/javascript" defer> 
		$(document).ready(function() {
			$('#title').aloha();
			$('#teaser').aloha();
			$('#content').aloha();	
		});
	</script> 
	<?
	}

	/**
	 * Returns the absolute baseurl to the aloha editor plugin directory
	 */
	static function _getAlohaPluginsBaseUrl() {
		return FEE_AlohaEditor :: _getAlohaSrcBaseUrl() . 'plugin/';
	}

	/**
	 * Returns the absolute baseurl to the aloha editor src directory
	 */
	static function _getAlohaSrcBaseUrl() {

		$baseUrl = plugins_url('/', 'wp-front-end-editor');
		$baseUrl = $baseUrl . 'wp-front-end-editor/';
		return $baseUrl . 'alohaeditor/src/';
	}

	/**
	 * Registers the aloha editor depdencies and plugins
	 */
	static function registerAloha() {

		$alohaSrcBaseUrl = FEE_AlohaEditor :: _getAlohaSrcBaseUrl();
		$alohaPluginsBaseUrl = FEE_AlohaEditor :: _getAlohaPluginsBaseUrl();

		//Include no deps version for development
		if (defined('SCRIPT_DEBUG')) {

			// Aloha Deps
			wp_register_script('ext-jquery-adapter', $alohaSrcBaseUrl . 'dep/ext-3.2.1/adapter/jquery/ext-jquery-adapter.js', array (), '0.10', false);
			wp_register_script('ext-all', $alohaSrcBaseUrl . 'dep/ext-3.2.1/ext-all.js', array (), '0.10', false);

			// Aloha JQuery Deps
			wp_register_script('jquery.aloha', $alohaSrcBaseUrl . 'core/jquery.aloha.js', array (
				'jquery'
			), '0.10', false);
			wp_register_script('jquery.json', $alohaSrcBaseUrl . 'dep/jquery.json-2.2.min.js', array (
				'jquery'
			), '0.10', false);
			wp_register_script('jquery.getUrlParam', $alohaSrcBaseUrl . 'dep/jquery.getUrlParam.js', array (
				'jquery'
			), '0.10', false);
			wp_register_script('jquery.store', $alohaSrcBaseUrl . 'dep/jquery.store.js', array (
				'jquery'
			), '0.10', false);

			// Other deps
			wp_register_script('lang', $alohaSrcBaseUrl . 'util/lang.js', array (), '0.10', false);
			wp_register_script('range', $alohaSrcBaseUrl . 'util/range.js', array (), '0.10', false);
			wp_register_script('position', $alohaSrcBaseUrl . 'util/position.js', array (), '0.10', false);
			wp_register_script('dom', $alohaSrcBaseUrl . 'util/dom.js', array (), '0.10', false);

			// Aloha core
			wp_register_script('base', $alohaSrcBaseUrl . 'util/base.js', array (), '0.10', false);
			wp_register_script('ext-alohaproxy', $alohaSrcBaseUrl . 'core/ext-alohaproxy.js', array (
				'ext-all',
				'ext-jquery-adapter'
			), '0.10', false);
			wp_register_script('ext-alohareader', $alohaSrcBaseUrl . 'core/ext-alohareader.js', array (
				'ext-all',
				'ext-jquery-adapter'
			), '0.10', false);
			wp_register_script('ext-alohatreeloader', $alohaSrcBaseUrl . 'core/ext-alohatreeloader.js', array (
				'ext-all',
				'ext-jquery-adapter'
			), '0.10', false);

			// register core dependencies
			wp_register_script('core', $alohaSrcBaseUrl . 'core/core.js', array (
				'base',
				'ext-jquery-adapter',
				'ext-all',
				'jquery.aloha',
				'jquery.json',
				'jquery.getUrlParam',
				'jquery.store',
				'lang',
				'range',
				'position',
				'dom',
				'ext-alohaproxy',
				'ext-alohareader',
				'ext-alohatreeloader'
			), '0.10', false);

			// register ui scripts
			wp_register_script('ui', $alohaSrcBaseUrl . 'core/ui.js', array (), '0.10', false);
			wp_register_script('ui-attributefield', $alohaSrcBaseUrl . 'core/ui-attributefield.js', array (), '0.10', false);
			wp_register_script('ui-browser', $alohaSrcBaseUrl . 'core/ui-browser.js', array (), '0.10', false);
			wp_register_script('editable', $alohaSrcBaseUrl . 'core/editable.js', array (), '0.10', false);
			wp_register_script('floatingmenu', $alohaSrcBaseUrl . 'core/floatingmenu.js', array (), '0.10', false);
			wp_register_script('ierange-m2', $alohaSrcBaseUrl . 'core/ierange-m2.js', array (), '0.10', false);
			wp_register_script('log', $alohaSrcBaseUrl . 'core/log.js', array (), '0.10', false);
			wp_register_script('markup', $alohaSrcBaseUrl . 'core/markup.js', array (), '0.10', false);
			wp_register_script('message', $alohaSrcBaseUrl . 'core/message.js', array (), '0.10', false);
			wp_register_script('plugin', $alohaSrcBaseUrl . 'core/plugin.js', array (), '0.10', false);
			wp_register_script('selection', $alohaSrcBaseUrl . 'core/selection.js', array (), '0.10', false);
			wp_register_script('sidebar', $alohaSrcBaseUrl . 'core/sidebar.js', array (), '0.10', false);
			wp_register_script('repositorymanager', $alohaSrcBaseUrl . 'core/repositorymanager.js', array (), '0.10', false);
			wp_register_script('repository', $alohaSrcBaseUrl . 'core/repository.js', array (), '0.10', false);
			wp_register_script('repositoryobjects', $alohaSrcBaseUrl . 'core/repositoryobjects.js', array (), '0.10', false);

			$plugindeps = array (
				'core',
				'ui',
				'ui-attributefield',
				'ui-browser',
				'editable',
				'floatingmenu',
				'ierange-m2',
				'log',
				'markup',
				'message',
				'plugin',
				'selection',
				'sidebar',
				'repositorymanager',
				'repository',
				'repositoryobjects'
			);

			// Aloha Plugins
			wp_register_script('aloha.format', $alohaPluginsBaseUrl . 'format/src/format.js', $plugindeps, '0.10', false);
			/*
			wp_register_script('aloha_table', $alohaPluginsBaseUrl . '/table/src/table.js', array("aloha"), '1.2', false);
			wp_register_script('aloha_list', $alohaPluginsBaseUrl . '/list/src/list.js', array("aloha"), '1.2', false);
			wp_register_script('aloha_link', $alohaPluginsBaseUrl . '/link/src/link.js', array("aloha"), '1.2', false);
			wp_register_script('aloha_ribbon', $alohaPluginsBaseUrl .  '/ribbon/src/ribbon.js', array("aloha"), '1.2', false);
			wp_register_script('aloha_highlighteditables', $alohaPluginsBaseUrl . '/highlighteditables/src/highlighteditables.js', array("aloha"), '1.2', false);
			*/

			// FEE Aloha plugins
			//wp_register_script('aloha_aewip', $url . 'aloha-plugins/aewip/src/aewip.js',array("aloha"),'1.2', false);
			//wp_register_script('aloha_markdown', $alohaPluginsBaseUrl . 'alohaeditor/plugins/com.gentics.aloha.plugins.Markdown/plugin.js', array("aloha"), '1.2', false);
		} else {
			//TODO decide whether we should use the build version
		}
	}

	
	/**
	 * This function will print the aloha editor initialiation javascript
	 */
	static function printAlohaEditorInit() {
	?>
	
window.alohaQuery = window.jQuery.sub();
// Ensure Namespace
window.GENTICS = window.GENTICS || {};
window.GENTICS.Utils = window.GENTICS.Utils || {};
window.Aloha = window.Aloha || {};
window.Aloha.settings = window.Aloha.settings || {};
window.Aloha.ui = window.Aloha.ui || {};
window.Aloha_loaded_plugins = window.Aloha_loaded_plugins || [];
window.Aloha_pluginDir = window.Aloha_pluginDir || false;
window.Aloha_base = '/wp-content/plugins/wp-front-end-editor/alohaeditor/src';
(function(window,document) {

	var $ = window.alohaQuery;
	
	// Wait for jQuery and DOM
	$(function(){
		
			var $body = $('body');
			alohaInit = true;
			$body.createPromiseEvent('aloha');
			window.Aloha.init();

			// Prepare Plugin Loading
			window.Aloha_loaded_plugins = window.Aloha_loaded_plugins||[];
			
	}); // DOM event
})(window, document);


		<?

	}


	/**
	 * Prints the aloha editor, plugins configuration
	 */
	static function printAlohaEditorConfiguration() {
?>
		
GENTICS.Aloha.settings = {
	logLevels: {'error': true, 'warn': true, 'info': true, 'debug': false},
	errorhandling : false,
	ribbon: false,	
	"i18n": {
		// you can either let the system detect the users language (set acceptLanguage on server)
		// In PHP this would would be '<?=$_SERVER['HTTP_ACCEPT_LANGUAGE']?>' resulting in 
		// "acceptLanguage": 'de-de,de;q=0.8,it;q=0.6,en-us;q=0.7,en;q=0.2'
		// or set current on server side to be in sync with your backend system 
		"current": "en" 
	},
	"repositories": {
	 	"com.gentics.aloha.repositories.LinkList": {
	 		data: [
 		        { name: 'Aloha Developers Wiki', url:'http://www.aloha-editor.com/wiki', type:'website', weight: 0.50 },
 		        { name: 'Aloha Editor - The HTML5 Editor', url:'http://aloha-editor.com', type:'website', weight: 0.90  },
 		        { name: 'Aloha Demo', url:'http://www.aloha-editor.com/demos.html', type:'website', weight: 0.75  },
 		        { name: 'Aloha Wordpress Demo', url:'http://www.aloha-editor.com/demos/wordpress-demo/index.html', type:'website', weight: 0.75  },
 		        { name: 'Aloha Logo', url:'http://www.aloha-editor.com/images/aloha-editor-logo.png', type:'image', weight: 0.10  }
	 		]
		}
	},
	"plugins": {
	 	"com.gentics.aloha.plugins.Format": {
		 	// all elements with no specific configuration get this configuration
			config : [ 'b', 'i','sub','sup'],
		  	editables : {
				// no formatting allowed for title
				'#title'	: [ ], 
				// formatting for all editable DIVs
				'div'		: [ 'b', 'i', 'del', 'sub', 'sup'  ], 
				// content is a DIV and has class .article so it gets both buttons
				'.article'	: [ 'b', 'i', 'p', 'title', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'pre', 'removeFormat']
		  	}
		},
	 	"com.gentics.aloha.plugins.List": { 
		 	// all elements with no specific configuration get an UL, just for fun :)
			config : [ 'ul' ],
		  	editables : {
				// Even if this is configured it is not set because OL and UL are not allowed in H1.
				'#title'	: [ 'ol' ], 
				// all divs get OL
				'div'		: [ 'ol' ], 
				// content is a DIV. It would get only OL but with class .article it also gets UL.
				'.article'	: [ 'ul' ]
		  	}
		},
	 	"com.gentics.aloha.plugins.Link": {
		 	// all elements with no specific configuration may insert links
			config : [ 'a' ],
		  	editables : {
				// No links in the title.
				'#title'	: [  ]
		  	},
		  	// all links that match the targetregex will get set the target
 			// e.g. ^(?!.*aloha-editor.com).* matches all href except aloha-editor.com
		  	targetregex : '^(?!.*aloha-editor.com).*',
		  	// this target is set when either targetregex matches or not set
		    // e.g. _blank opens all links in new window
		  	target : '_blank',
		  	// the same for css class as for target
		  	cssclassregex : '^(?!.*aloha-editor.com).*',
		  	cssclass : 'aloha',
		  	// use all resources of type website for autosuggest
		  	objectTypeFilter: ['website'],
		  	// handle change of href
		  	onHrefChange: function( obj, href, item ) {
			  	if ( item ) {
					jQuery(obj).attr('data-name', item.name);
			  	} else {
					jQuery(obj).removeAttr('data-name');
			  	}
		  	}
		},
	 	"com.gentics.aloha.plugins.Table": { 
		 	// all elements with no specific configuration are not allowed to insert tables
			config : [ ],
		  	editables : {
				// Allow insert tables only into .article
				'.article'	: [ 'table' ] 
		  	}
		}
  	}
};

		
		<?


	}
}