(function(){
  var rich, __extends = function(sub, sup){
    function ctor(){} ctor.prototype = (sub.superclass = sup).prototype;
    (sub.prototype = new ctor).constructor = sub;
    if (typeof sup.extended == 'function') sup.extended(sub);
    return sub;
  };
  FrontEndEditor.fieldTypes.rich = rich = (function(_super){
    rich.displayName = 'rich';
    var prototype = __extends(rich, _super).prototype;
    prototype.lastActiveEditable = null;
    prototype.create_input = jQuery.noop;
    prototype.ajax_get_handler = function(response){
      var $el;
      $el = this.error_handler(response);
      if (!$el) {
        return;
      }
      this.form_create();
      this.form.html(response.content);
      this.el.hide();
      this.form.insertAfter($el);
      this.form.aloha();
      this.form.focus();
      return GENTICS.Aloha.wpSaveCancel.current_field = this;
    };
    prototype.ajax_set = function(content){
      return rich.superclass.prototype.ajax_set.call(this, this.cleanup(content));
    };
    prototype.cleanup = function(content){
      var blocklist1, blocklist2;
      content = content.replace(/<(pre|script)[^>]*>[\s\S]+?<\/\1>/g, function(a){
        a = a.replace(/<br ?\/?>[\r\n]*/g, '<wp_temp>');
        return a.replace(/<\/?p( [^>]*)?>[\r\n]*/g, '<wp_temp>');
      });
      blocklist1 = 'blockquote|ul|ol|li|table|thead|tbody|tfoot|tr|th|td|div|h[1-6]|p|fieldset';
      content = content.replace(new RegExp('\\s*</(' + blocklist1 + ')>\\s*', 'g'), '</$1>\n');
      content = content.replace(new RegExp('\\s*<((' + blocklist1 + ')[^>]*)>', 'g'), '\n<$1>');
      content = content.replace(/(<p [^>]+>.*?)<\/p>/g, '$1</p#>');
      content = content.replace(/<div([^>]*)>\s*<p>/gi, '<div$1>\n\n');
      content = content.replace(/\s*<p>/gi, '');
      content = content.replace(/\s*<\/p>\s*/gi, '\n\n');
      content = content.replace(/\n[\s\u00a0]+\n/g, '\n\n');
      content = content.replace(/\s*<br ?\/?>\s*/gi, '\n');
      content = content.replace(/\s*<div/g, '\n<div');
      content = content.replace(/<\/div>\s*/g, '</div>\n');
      content = content.replace(/\s*\[caption([^\[]+)\[\/caption\]\s*/gi, '\n\n[caption$1[/caption]\n\n');
      content = content.replace(/caption\]\n\n+\[caption/g, 'caption]\n\n[caption');
      blocklist2 = 'blockquote|ul|ol|li|table|thead|tbody|tfoot|tr|th|td|h[1-6]|pre|fieldset';
      content = content.replace(new RegExp('\\s*<((' + blocklist2 + ') ?[^>]*)\\s*>', 'g'), '\n<$1>');
      content = content.replace(new RegExp('\\s*</(' + blocklist2 + ')>\\s*', 'g'), '</$1>\n');
      content = content.replace(/<li([^>]*)>/g, '\t<li$1>');
      if (content.indexOf('<object') != -1) {
        content = content.replace(/<object[\s\S]+?<\/object>/g, function(a){
          return a.replace(/[\r\n]+/g, '');
        });
      }
      content = content.replace(/<\/p#>/g, '</p>\n');
      content = content.replace(/\s*(<p [^>]+>[\s\S]*?<\/p>)/g, '\n$1');
      content = content.replace(/^\s+/, '');
      content = content.replace(/[\s\u00a0]+$/, '');
      content = content.replace(/<wp_temp>/g, '\n');
      return content;
    };
    function rich(){}
    return rich;
  }(FrontEndEditor.fieldTypes.textarea));
}).call(this);
