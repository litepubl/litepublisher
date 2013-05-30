/**
* Lite Publisher
* Copyright (C) 2010, 2012 Vladimir Yushko http://litepublisher.com/
* Dual licensed under the MIT (mit.txt)
* and GPL (gpl.txt) licenses.
**/

(function ($, window) {
  $(document).ready(function() {
    litepubl.uloginpopup = new litepubl.Uloginpopup();
  });
  
  litepubl.Uloginpopup= Class.extend({
script: false,
dialogopened: false,
    html: '<div style="display:block;overflow:hidden;width:300px;height:200px;">\
<div id="ulogin-holder" data-ulogin="display=small;fields=email,first_name,last_name;optional=phone,nickname;providers=vkontakte,odnoklassniki,mailru,yandex,facebook,google,twitter;hidden=other;redirect_uri=%%redirurl%%;"></div>',

    init: function() {
if ($.cookie('litepubl_user')) return;
      this.html = this.html.replace(/%%redirurl%%/gim, encodeURIComponent(ltoptions.url + "/admin/ulogin.htm?backurl="));
      var self = this;
      $('a[href^="' + ltoptions.url + '/admin/"], a[href^="/admin/"]').click(function() {
        self.open($(this).attr("href"));
        return false;
      });
    },
    
    open: function(url) {
if (this.dialogopened) return false;
set_cookie('backurl', url);
var self = this;
self.ready(function() {
self.dialogopened = true;

      $.prettyPhotoDialog({
        title: lang.ulogin.title,
        html: self.html.replace(/BACKURL=/gim, 'BACKURL=' + encodeURIComponent(url)),
        width: 300,
close: function() {
self.dialogopened = false;
},

open: function() {
uLogin.customInit('ulogin-holder');
},

        buttons: [{
          title: lang.dialog.close,
          click: $.proxy($.prettyPhoto.close, $.prettyPhoto)
        }]
      });
});
    },

ready: function(callback) {
if (this.script) return this.script.done(callback);
return this.script = $.load_script('http://ulogin.ru/js/ulogin.js', callback);
}
    
  });
  
}(jQuery, geo, window));