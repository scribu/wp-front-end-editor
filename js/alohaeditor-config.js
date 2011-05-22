GENTICS.Aloha.settings = {
	logLevels: {'error': true, 'warn': true, 'info': true, 'debug': false},
	errorhandling : false,
	ribbon: false,
	"i18n": {
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
