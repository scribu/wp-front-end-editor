MINIFIER = java -jar ~/bin/yuicompressor-2.4.2/build/yuicompressor-2.4.2.jar

FORCE : editor.css editor.js

editor.js : core.dev.js fields.dev.js
	${MINIFIER} 'core.dev.js' > 'editor.js'
	${MINIFIER} 'fields.dev.js' >> 'editor.js'

editor.css : editor.dev.css
	${MINIFIER} 'editor.dev.css' > 'editor.css'
