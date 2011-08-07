(function(){
  var base, terminput, termselect, __extends = function(sub, sup){
    function ctor(){} ctor.prototype = (sub.superclass = sup).prototype;
    (sub.prototype = new ctor).constructor = sub;
    if (typeof sup.extended == 'function') sup.extended(sub);
    return sub;
  };
  FrontEndEditor.fields.base = base = (function(){
    base.displayName = 'base';
    var prototype = base.prototype;
    prototype.dependency = null;
    prototype.start = jQuery.noop;
    prototype.ajax_get_handler = function(){
      return false;
    };
    prototype.ajax_set_handler = function(){
      return false;
    };
    prototype.ajax_get = function(){
      var data;
      data = this.ajax_args({
        callback: 'get'
      });
      FrontEndEditor.edit_lock();
      return FrontEndEditor.sync_load(this.ajax_get_handler, data, this.dependency);
    };
    prototype.ajax_set = function(content){
      var data;
      data = this.ajax_args({
        callback: 'save',
        content: content
      });
      FrontEndEditor.edit_unlock();
      return jQuery.post(FrontEndEditor.data.ajax_url, data, this.ajax_set_handler, 'json');
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
  if (FrontEndEditor.data.suggest) {
    FrontEndEditor.fields.terminput = terminput = (function(_super){
      terminput.displayName = 'terminput';
      var prototype = __extends(terminput, _super).prototype;
      prototype.dependency = FrontEndEditor.data.suggest.src;
      prototype.content_to_input = function(content){
        terminput.superclass.prototype.content_to_input(content);
        return this.input.suggest(FrontEndEditor.data.ajax_url + '?action=ajax-tag-search&tax=' + this.data.taxonomy, {
          multiple: true,
          resultsClass: 'fee-suggest-results',
          selectClass: 'fee-suggest-over',
          matchClass: 'fee-suggest-match'
        });
      };
      function terminput(){}
      return terminput;
    }(FrontEndEditor.fields.input));
  }
  FrontEndEditor.fields.termselect = termselect = (function(_super){
    termselect.displayName = 'termselect';
    var prototype = __extends(termselect, _super).prototype;
    prototype.content_to_input = function(content){
      var $dropdown;
      $dropdown = jQuery(content);
      this.input.replaceWith($dropdown);
      return this.input = $dropdown;
    };
    function termselect(){}
    return termselect;
  }(FrontEndEditor.fields.select));
}).call(this);
