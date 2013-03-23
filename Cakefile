fs = require('fs')
path = require('path')
mkdirp = require('mkdirp').sync
{spawn, exec} = require('child_process')
UglifyJS = require('uglify-js')

browserify = (source, dest, callback) ->
	opts = ["-t", "coffeeify", source, "-o", dest].join ' '

	exec "./node_modules/browserify/bin/cmd.js " + opts, (err, stdout, stderr) ->
		throw err if err

		console.log "Generated #{dest}"

		callback?()

task 'watch', 'Watch coffee/ directory and compile into js/', (options) ->
	# TODO

task 'build:js', 'Generate compressed JS', (options) ->
	browserify "coffee/init.coffee", "build/editor.js", ->
		result = UglifyJS.minify('build/editor.js')
		fs.writeFileSync 'build/editor.min.js', result.code, 'utf8'

		console.log "Generated build/editor.min.js"

task 'build:aloha', 'Generate Aloha plugin(s)', (options) ->
	plugin = 'wpImage-plugin'

	dir = "aloha-plugins/#{plugin}/lib"

	mkdirp dir

	browserify "coffee/aloha/#{plugin}.coffee", "#{dir}/#{plugin}.js"

task 'build', 'Generate a build for wp.org', (options) ->
	mkdirp 'build'
	invoke 'build:js'
	invoke 'build:aloha'
