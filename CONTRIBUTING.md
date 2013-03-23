This guide is meant for developers wanting to work on the plugin code.

The JavaScript files in the [wp.org repository](http://wordpress.org/extend/plugins/front-end-editor/) are generated from [CoffeeScript](http://coffeescript.org) files in this github repository.

### Initial Setup

* First, make a fork of this repo and clone it:

```bash
git clone --recurse-submodules git@github.com:{YOUR GITHUB USERNAME}/wp-front-end-editor.git front-end-editor
```

- [Install node.js](https://github.com/joyent/node/wiki/Installing-Node.js-via-package-manager) and [npm](http://npmjs.org/).

- Install CoffeeScript globally, which will also install the `cake` utility:

```bash
npm install -g coffee-script
```

- Install local dependencies:

```bash
cd front-end-editor
npm install
```

### Hacking

While working on CoffeeScript files (.coffee), you can set up automatic recompilation, like so:

```bash
cake watch
```

If you're working on the Aloha plugins, you will have to call:

```bash
cake build:aloha
```

### Building

This is necessary only for deploying to wordpress.org.

```bash
cake build
```
