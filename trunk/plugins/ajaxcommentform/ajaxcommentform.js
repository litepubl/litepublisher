var commentform = {
fields: ["name", "email", "url"],
subscribed: [],
unsubscribed: [],
error_dialog: false,

get: function(name) {
return $("input[name='" + name + "']").val();
},

set: function(name, value) {
$("input[name='" + name + "']").val(value);
},

find: function(name) {
if (name == 'content') return $("textarea[name='content']");
return $("input[name='" + name + "']");
},

init_field: function(name) {
var value = get_cookie("comuser_" + name);
if (!value ) return false;
this.set(name, value);
return true;
},

init_subscribe: function() {
var idpost = typeof ltoptions.idpost == "string" ? ltoptions.idpost : ltoptions.idpost.toString();
this.subscribed = get_cookie("comuser_subscribed").split(",");
this.unsubscribed = get_cookie("comuser_unsubscribed").split(",");
if ($.inArray(idpost, this.subscribed) >= 0) {
$("input[name='subscribe']").attr("checked", true);
} else if ($.inArray(idpost, this.unsubscribed) >= 0) {
$("input[name='subscribe']").attr("checked", false);
}
},

update_subscribe: function() {
var idpost = typeof ltoptions.idpost == "string" ? ltoptions.idpost : ltoptions.idpost.toString();
if ($("input[name='subscribe']").attr("checked")) {
if ($.inArray(idpost, this.subscribed) == -1) this.subscribed.unshift(idpost);
this.unsubscribed = $.grep(this.unsubscribed, function(val) { return val != idpost; }); 
} else {
if ($.inArray(idpost, this.unsubscribed) == -1) this.unsubscribed.unshift(idpost);
this.subscribed = $.grep(this.subscribed, function(val) { return val != idpost; }); 
}
set_cookie("comuser_subscribed", this.subscribed.join(","));
set_cookie("comuser_unsubscribed", this.unsubscribed.join(","));
},

init: function() {
$("input[name='name']").closest("form").submit(commentform.submit);
if (commentform.init_field("name")) {
commentform.init_field("email");
commentform.init_field("url");
commentform.init_subscribe();
} else {
var iduser = get_cookie("userid");
if (!iduser) return;
$.get(ltoptions.url + "/ajaxcommentform.htm", 
{getuser : iduser, idpost: ltoptions.idpost},
function (resp) {
var data = $.parseJSON(resp);
commentform.set("name", data.name);
commentform.set("email", data.email);
commentform.set("url", data.url);
set_cookie("comuser_name", data.name);
set_cookie("comuser_email", data.email);
set_cookie("comuser_url", data.url);
$("input[name='subscribe']").attr("checked", data.subscribe);
commentform.update_subscribe();
});
}
},

set_cookie: function(name) {
set_cookie("comuser_" + name, this.get(name));
},

error: function(name, msg) {
$.load_ui(function() {
if (!commentform.error_dialog) {
commentform.error_dialog = $('<div class="ui-helper-hidden" title="' + ltoptions.commentform.error_title  +  '"><h4></h4></div>')
.appendTo($("input[name='name']").closest("form"));
}
$("h4", commentform.error_dialog ).text(msg);
$(commentform.error_dialog ).dialog( {
    autoOpen: true,
    modal: true,
    buttons: [
    {
      text: "Ok",
      click: function() {
        $(this).dialog("close");
commentform.find(name).focus();
      }
    } ]
  } );
});
},

empty: function(name) {
if ("" == $.trim(this.get(name))) {
this.error(name, lang.comment.emptyname);
return true;
}
return false;
},

validemail: function() {
var filter = /^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
if (!filter.test(this.get("email"))) {
this.error("email", lang.comment.invalidemail);
return false;
}
return true;
},

validate: function() {
if (this.empty("name") || this.empty("email") || !this.validemail() ) return false;
if ("" == $.trim(this.find("content").val())) {
this.error("content", lang.comment.emptycontent);
return false;
}
return true;
},

submit: function() {
try {
if (!commentform.validate()) return false;
commentform.set_cookie("name");
commentform.set_cookie("email");
commentform.set_cookie("url");
commentform.update_subscribe();
alert('sent comment');
commentform.send();
} catch(e) { alert(e.message); }
return false;
},

send: function() {
var form = $("input[name='name']").closest("form");
$("input, textarea, checkbox", form).attr("disabled", true);
$.post(ltoptions.url + "/ajaxcommentform.htm", 
{getuser : iduser, idpost: ltoptions.idpost},
function (resp) {
try {
var data = $.parseJSON(resp);

$("input, textarea, checkbox", form).attr("disabled", false);
} catch(e) { alert(e.message); }
});

}

};

$(document).ready(commentform.init);
