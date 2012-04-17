fs = require('fs')

io = (callback, inputPath, outputPath) ->
	fs.readFile inputPath, 'utf8', (err, data) ->
		if err
			console.error("Could not read from file: %s", err)
			process.exit(1)

		callback data, (output) ->
			fs.writeFile outputPath, output, (err) ->
				if err
					console.error("Could not write to file: %s", err)
					process.exit(1)

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

task 'dev:css', 'Watch the .less file for changes', (options) ->
	less = require('less')

	console.log 'Watching less/core.less...'

	fs.watch 'less/core.less', (event, fname) ->
		if 'change' != event
			return

		console.log 'File changed. Recompiling...'
		io compile_less_dev, 'less/core.less', 'css/core.css'

task 'build:css', 'Generate compressed CSS', (options) ->
	io compile_less, 'less/core.less', 'build/editor.css'

task 'build:js', 'Generate compressed JS', (options) ->
	{exec} = require('child_process')

	exec 'cd coffee; cat core.coffee hover.coffee init.coffee fields/*.coffee | coffee -cs > ../build/editor.js', (err, stdout, stderr) ->
		throw err if err

	io compress_js, 'build/editor.js', 'build/editor.min.js'

task 'build:aloha', 'Generate Aloha plugin(s)', (options) ->
	# TODO

task 'build', 'Generate a build for wp.org', (options) ->
	invoke 'build:css'
	invoke 'build:js'
	invoke 'build:aloha'
