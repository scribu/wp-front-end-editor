(function(){
  var terminput, termselect, __extends = function(sub, sup){
    function ctor(){} ctor.prototype = (sub.superclass = sup).prototype;
    (sub.prototype = new ctor).constructor = sub;
    if (typeof sup.extended == 'function') sup.extended(sub);
    return sub;
  };
  if (FrontEndEditor.data.suggest) {
    FrontEndEditor.fieldTypes.terminput = terminput = (function(_super){
      terminput.displayName = 'terminput';
      var prototype = __extends(terminput, _super).prototype;
      prototype.dependency = FrontEndEditor.data.suggest.src;
      prototype.content_to_input = function(content){
        terminput.superclass.prototype.content_to_input.apply(this, arguments);
        return this.input.suggest(FrontEndEditor.data.ajax_url + '?action=ajax-tag-search&tax=' + this.data.taxonomy, {
          multiple: true,
          resultsClass: 'fee-suggest-results',
          selectClass: 'fee-suggest-over',
          matchClass: 'fee-suggest-match'
        });
      };
      function terminput(){}
      return terminput;
    }(FrontEndEditor.fieldTypes.input));
  }
  FrontEndEditor.fieldTypes.termselect = termselect = (function(_super){
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
  }(FrontEndEditor.fieldTypes.select));
}).call(this);
