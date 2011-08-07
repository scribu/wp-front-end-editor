(function(){
  var input, checkbox, select, textarea, __extends = function(sub, sup){
    function ctor(){} ctor.prototype = (sub.superclass = sup).prototype;
    (sub.prototype = new ctor).constructor = sub;
    if (typeof sup.extended == 'function') sup.extended(sub);
    return sub;
  };
  FrontEndEditor.fieldTypes.input = input = (function(_super){
    input.displayName = 'input';
    var prototype = __extends(input, _super).prototype;
    prototype.input_tag = '<input type="text">';
    prototype.start_editing = function(ev){
      this.save_button = jQuery('<button>', {
        'class': 'fee-form-save',
        'text': FrontEndEditor.data.save_text,
        'click': jQuery.proxy(this.form_submit, this)
      });
      this.cancel_button = jQuery('<button>', {
        'class': 'fee-form-cancel',
        'text': FrontEndEditor.data.cancel_text,
        'click': jQuery.proxy(this.form_remove, this)
      });
      this.form_create();
      this.form.append(this.save_button).append(this.cancel_button);
      return this.ajax_get();
    };
    prototype.form_create = function(){
      this.form = this.el.is('span')
        ? jQuery('<span>')
        : jQuery('<div>');
      this.form.addClass('fee-form').addClass('fee-type-' + this.type).addClass('fee-filter-' + this.filter);
      return this.form.keypress(jQuery.proxy(this.keypress, this));
    };
    prototype.form_remove = function(ev){
      this.form.remove();
      this.el.show();
      FrontEndEditor.edit_unlock();
      return false;
    };
    prototype.form_submit = function(ev){
      this.ajax_set();
      return false;
    };
    prototype.keypress = function(ev){
      var keys, code;
      keys = {
        ENTER: 13,
        ESCAPE: 27
      };
      code = ev.keyCode || ev.which || ev.charCode || 0;
      if (code === keys.ENTER && 'input' === this.type) {
        this.save_button.click();
      }
      if (code === keys.ESCAPE) {
        return this.cancel_button.click();
      }
    };
    prototype.create_input = function(){
      this.input = jQuery(this.input_tag).attr({
        'id': 'fee-' + new Date().getTime(),
        'class': 'fee-form-content'
      });
      return this.input.prependTo(this.form);
    };
    prototype.content_to_input = function(content){
      this.input.val(content);
      return this.form.trigger('ready.fee', [this.data]);
    };
    prototype.content_from_input = function(){
      return this.input.val();
    };
    prototype.content_to_front = function(content){
      this.el.html(content);
      return this.form.trigger('saved.fee', [this.data]);
    };
    prototype.ajax_get = function(){
      FrontEndEditor.overlay.cover(this.el);
      this.create_input();
      return input.superclass.prototype.ajax_get.apply(this, arguments);
    };
    prototype.ajax_set = function(contentData){
      var data;
      if (0 == arguments.length) {
        contentData = this.content_from_input();
      }
      data = this.ajax_args({
        callback: 'save',
        content: contentData
      });
      FrontEndEditor.overlay.cover(this.form);
      FrontEndEditor.edit_unlock();
      return jQuery.post(FrontEndEditor.data.ajax_url, data, jQuery.proxy(this.ajax_set_handler, this), 'json');
    };
    prototype.ajax_get_handler = function(response){
      var $el;
      $el = this.error_handler(response);
      if (!$el) {
        return;
      }
      this.el.hide();
      $el.after(this.form);
      this.content_to_input(response.content);
      return this.input.focus();
    };
    prototype.ajax_set_handler = function(response){
      var $el;
      $el = this.error_handler(response);
      if (!$el) {
        return;
      }
      this.content_to_front(response.content);
      this.form.remove();
      return this.el.show();
    };
    prototype.error_handler = function(response){
      var $parent, $el;
      $parent = this.el.closest('a');
      $el = $parent.length
        ? $parent
        : this.el;
      FrontEndEditor.overlay.hide();
      if (response.error) {
        jQuery('<div class="fee-error">').append(jQuery('<span class="fee-message">').html(response.error)).append(jQuery('<span class="fee-dismiss">x</span>').click(function(){
          return $error_box.remove();
        })).insertBefore($el);
        return false;
      }
      return $el;
    };
    function input(){}
    return input;
  }(FrontEndEditor.fieldTypes.base));
  FrontEndEditor.fieldTypes.checkbox = checkbox = (function(_super){
    checkbox.displayName = 'checkbox';
    var prototype = __extends(checkbox, _super).prototype;
    prototype.input_tag = '<input type="checkbox">';
    prototype.content_to_input = function(content){
      return this.input.prop('checked', content);
    };
    prototype.content_from_input = function(){
      return Number(this.input.is(':checked'));
    };
    prototype.content_to_front = function(){
      return this.el.html(this.data.values[this.content_from_input()]);
    };
    function checkbox(){}
    return checkbox;
  }(FrontEndEditor.fieldTypes.input));
  FrontEndEditor.fieldTypes.select = select = (function(_super){
    select.displayName = 'select';
    var prototype = __extends(select, _super).prototype;
    prototype.input_tag = '<select>';
    prototype.content_to_input = function(content){
      var _this = this;
      return jQuery.each(this.data.values, function(value, title){
        return _this.input.append(jQuery('<option>', {
          value: value,
          html: title,
          selected: content === value ? 'selected' : ''
        }));
      });
    };
    prototype.content_from_input = function(){
      return this.input.find(':selected').val();
    };
    function select(){}
    return select;
  }(FrontEndEditor.fieldTypes.input));
  FrontEndEditor.fieldTypes.textarea = textarea = (function(_super){
    textarea.displayName = 'textarea';
    var prototype = __extends(textarea, _super).prototype;
    prototype.input_tag = '<textarea rows="10">';
    function textarea(){}
    return textarea;
  }(FrontEndEditor.fieldTypes.input));
}).call(this);
