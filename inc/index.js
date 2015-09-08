var current_page = null;

function open_page(param) {
  if(param.page) {
    var page = "Page_" + param.page;

    if(window[page]) {
      current_page = new window[page]();
      current_page.content(param, function(page, param, content) {
        twig_render_into(document.body, content.template, content, function(page, param) {
          page.connect(param);
        }.bind(this, page, param));
      }.bind(this, current_page, param));

      return true;
    }
  }

  return false;
}

window.onload = function() {
  call_hooks("init");

  var param = page_resolve_url_params();
  open_page(param);
}
