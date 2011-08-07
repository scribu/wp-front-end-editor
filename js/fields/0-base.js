(function(){
  var base;
  FrontEndEditor.fieldTypes.base = base = (function(){
    base.displayName = 'base';
    var prototype = base.prototype;
    prototype.dependency = null;
    prototype.start = jQuery.noop;
    prototype.start_editing = null;
    prototype.ajax_get_handler = null;
    prototype.ajax_set_handler = null;
    prototype.ajax_get = function(){
      var data;
      data = this.ajax_args({
        callback: 'get'
      });
      FrontEndEditor.edit_lock();
      return FrontEndEditor.sync_load(jQuery.proxy(this.ajax_get_handler, this), data, this.dependency);
    };
    prototype.ajax_set = function(content){
      var data;
      data = this.ajax_args({
        callback: 'save',
        content: content
      });
      FrontEndEditor.edit_unlock();
      return jQuery.post(FrontEndEditor.data.ajax_url, data, jQuery.proxy(this.ajax_set_handler, this), 'json');
    };
    prototype.ajax_args = function(args){
      return jQuery.extend(args, {
        action: 'front-end-editor',
        nonce: FrontEndEditor.data.nonce,
        data: this.data
      });
    };
    function base(){}
    return base;
  }());
}).call(this);
