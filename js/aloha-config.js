GENTICS.Aloha.settings = {
	logLevels: {'error': true, 'warn': true, 'info': true, 'debug': false},
	errorhandling : false,
	ribbon: false,
	"i18n": {
		"current": "en"
	},
	"plugins": {
		"com.gentics.aloha.plugins.DragAndDropFiles": {
			config : {
				drop : {
					max_file_size : '200000',
					upload : {
						//'uploader_class':GENTICS.Aloha.Uploader, //this is the default
						config : {
							'url': '/content/',
							'extra_headers':{'Accept':'application/json'},
							'additional_params': {"location":""},
							'www_encoded': false
						}
					}
				}
			}
		},
		"com.gentics.aloha.plugins.Table": {
			config : [],
			editables : {
				'.fee-field'	: [ 'table' ]
			}
		}
	}
};
