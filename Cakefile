fs = require('fs')
path = require('path')
mkdirp = require('mkdirp')

io = (callback, inputPath, outputPath) ->
	mkdirp.sync path.dirname(outputPath)

	input = fs.readFileSync inputPath, 'utf8'
	callback input, (output) ->
		fs.writeFileSync outputPath, output, 'utf8'

compress_js = (input, cb) ->
	jsp = require('uglify-js').parser
	pro = require('uglify-js').uglify

	ast = jsp.parse(input)
	ast = pro.ast_squeeze(ast)

	cb pro.gen_code(ast)

compile_less = (input, cb, compress = true) ->
	less = require('less')

	parser = new less.Parser

	parser.parse input, (err, tree) ->
		if err
			console.error(err)
			process.exit(1)

		cb tree.toCSS({compress})

compile_less_dev = (input, cb) ->
	compile_less input, cb, false

task 'watch:less', 'Watch the .less file for changes', (options) ->
	less = require('less')

	console.log 'Watching less/core.less...'

	fs.watch 'less/core.less', (event, fname) ->
		if 'change' != event
			return

		console.log 'File changed. Recompiling...'
		io compile_less_dev, 'less/core.less', 'css/core.css'

task 'dev:css', 'Generate uncompressed CSS', (options) ->
	io compile_less_dev, 'less/core.less', 'css/core.css'

task 'build:css', 'Generate compressed CSS', (options) ->
	io compile_less, 'less/core.less', 'build/editor.css'

task 'build:js', 'Generate compressed JS', (options) ->
	{exec} = require('child_process')

	exec 'cd coffee; cat core.coffee hover.coffee init.coffee fields/*.coffee | coffee -cs > ../build/editor.js', (err, stdout, stderr) ->
		throw err if err

	io compress_js, 'build/editor.js', 'build/editor.min.js'

task 'build:aloha', 'Generate Aloha plugin(s)', (options) ->
	{exec} = require('child_process')

	plugin = 'wpImage'

	dir = "aloha-plugins/#{plugin}/lib/"
	mkdirp.sync dir

	exec "coffee -b $2 -o #{dir}/ coffee/aloha/#{plugin}-plugin", (err, stdout, stderr) ->
		throw err if err

task 'build', 'Generate a build for wp.org', (options) ->
	invoke 'build:css'
	invoke 'build:js'
	invoke 'build:aloha'
