(function(){
  var widget, __extends = function(sub, sup){
    function ctor(){} ctor.prototype = (sub.superclass = sup).prototype;
    (sub.prototype = new ctor).constructor = sub;
    if (typeof sup.extended == 'function') sup.extended(sub);
    return sub;
  };
  FrontEndEditor.fieldTypes.widget = widget = (function(_super){
    widget.displayName = 'widget';
    var prototype = __extends(widget, _super).prototype;
    prototype.create_input = jQuery.noop;
    prototype.content_to_input = function(content){
      this.input = jQuery(content);
      return this.form.prepend(content);
    };
    prototype.ajax_args = function(args){
      var raw_data;
      raw_data;
      args = widget.superclass.prototype.ajax_args.apply(this, arguments);
      if ('get' === args.callback) {
        return args;
      }
      raw_data = this.form.find(':input').serializeArray();
      jQuery.each(args, function(name, value){
        return raw_data.push({
          'name': name,
          'value': value
        });
      });
      jQuery.each(args.data, function(name, value){
        return raw_data.push({
          'name': 'data[' + name + ']',
          'value': value
        });
      });
      return raw_data;
    };
    function widget(){}
    return widget;
  }(FrontEndEditor.fieldTypes.textarea));
}).call(this);
