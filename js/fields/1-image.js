(function(){
  var image_base, image, thumbnail, image_rich, __extends = function(sub, sup){
    function ctor(){} ctor.prototype = (sub.superclass = sup).prototype;
    (sub.prototype = new ctor).constructor = sub;
    if (typeof sup.extended == 'function') sup.extended(sub);
    return sub;
  };
  FrontEndEditor.fieldTypes.image_base = image_base = (function(_super){
    image_base.displayName = 'image_base';
    var _ref, prototype = __extends(image_base, _super).prototype;
    prototype.button_text = (_ref = FrontEndEditor.data.image) != null ? _ref.change : void 8;
    prototype.start_editing = function(){
      var _this = this;
      tb_show(this.button_text, FrontEndEditor.data.image.url);
      jQuery('#TB_closeWindowButton img').attr('src', FrontEndEditor.data.image.tb_close);
      return jQuery('#TB_iframeContent').load(function(ev){
        var $thickbox;
        $thickbox = jQuery(ev.currentTarget).contents();
        _this.thickbox_load($thickbox);
        if (jQuery.noop !== _this.media_item_manipulation) {
          return _this.media_item_handler($thickbox);
        }
      });
    };
    prototype.thickbox_load = function($thickbox){
      var _this = this;
      return $thickbox.delegate('.media-item :submit', 'click', function(ev){
        var $button, data;
        $button = jQuery(ev.currentTarget);
        data = $button.closest('form').serializeArray();
        data.push({
          name: $button.attr('name'),
          value: $button.attr('name')
        });
        data.push({
          name: 'action',
          value: 'fee_image_insert'
        });
        jQuery.post(FrontEndEditor.data.ajax_url, data, jQuery.proxy(_this.image_html_handler, _this));
        return false;
      });
    };
    prototype.media_item_handler = function($thickbox){
      var _this = this;
      return $thickbox.delegate('.media-item', 'mouseenter', function(ev){
        var $item;
        $item = jQuery(ev.currentTarget);
        if (!$item.find('.pinkynail').length) {
          return;
        }
        if ($item.data('fee_altered')) {
          return;
        }
        _this.media_item_manipulation($item);
        return $item.data('fee_altered', true);
      });
    };
    prototype.media_item_manipulation = function($item){
      $item.find('#go_button').remove();
      return $item.find(':submit').val(this.button_text);
    };
    function image_base(){}
    return image_base;
  }(FrontEndEditor.fieldTypes.base));
  FrontEndEditor.fieldTypes.image = image = (function(_super){
    image.displayName = 'image';
    var prototype = __extends(image, _super).prototype;
    prototype.start_editing = function(){
      var _this = this;
      image.superclass.prototype.start_editing.apply(this, arguments);
      return jQuery('<a id="fee-img-revert" href="#">').text(FrontEndEditor.data.image.revert).click(function(ev){
        _this.ajax_set(-1);
        return false;
      }).insertAfter('#TB_ajaxWindowTitle');
    };
    prototype.media_item_manipulation = function($item){
      $item.find('tbody tr').not('.image-size, .submit').remove();
      return image.superclass.prototype.media_item_manipulation.apply(this, arguments);
    };
    prototype.image_html_handler = function(html){
      var $html;
      $html = jQuery(html);
      if ($html.is('a')) {
        $html = $html.find('img');
      }
      return this.ajax_set($html.attr('src'));
    };
    prototype.ajax_set_handler = function(response){
      var url;
      url = response.content;
      if ('-1' === url) {
        return location.reload(true);
      } else {
        this.el.find('img').attr('src', url);
        return tb_remove();
      }
    };
    function image(){}
    return image;
  }(FrontEndEditor.fieldTypes.image_base));
  FrontEndEditor.fieldTypes.thumbnail = thumbnail = (function(_super){
    thumbnail.displayName = 'thumbnail';
    var prototype = __extends(thumbnail, _super).prototype;
    prototype.thickbox_load = function($thickbox){
      var _this = this;
      $thickbox.find('#tab-type_url').remove();
      return $thickbox.delegate('.media-item :submit', 'click', function(ev){
        var $item, attachment_id;
        $item = jQuery(ev.currentTarget).closest('.media-item');
        attachment_id = $item.attr('id').replace('media-item-', '');
        _this.ajax_set(attachment_id);
        return false;
      });
    };
    prototype.media_item_manipulation = function($item){
      $item.find('tbody tr').not('.submit').remove();
      return thumbnail.superclass.prototype.media_item_manipulation.apply(this, arguments);
    };
    function thumbnail(){}
    return thumbnail;
  }(FrontEndEditor.fieldTypes.image));
  if (typeof GENTICS != 'undefined' && GENTICS !== null) {
    FrontEndEditor.fieldTypes.image_rich = image_rich = (function(_super){
      image_rich.displayName = 'image_rich';
      var _ref, prototype = __extends(image_rich, _super).prototype;
      prototype.button_text = (_ref = FrontEndEditor.data.image) != null ? _ref.insert : void 8;
      prototype.start_editing = function(){
        jQuery('#GENTICS_floatingmenu_shadow, .GENTICS_floatingmenu').hide();
        return image_rich.superclass.prototype.start_editing.apply(this, arguments);
      };
      prototype.media_item_manipulation = jQuery.noop;
      prototype.image_html_handler = function(html){
        GENTICS.Utils.Dom.insertIntoDOM(jQuery(html), GENTICS.Aloha.Selection.getRangeObject(), jQuery(GENTICS.Aloha.activeEditable.obj));
        tb_remove();
        return jQuery('#GENTICS_floatingmenu_shadow, .GENTICS_floatingmenu').show();
      };
      function image_rich(){}
      return image_rich;
    }(FrontEndEditor.fieldTypes.image_base));
  }
}).call(this);
