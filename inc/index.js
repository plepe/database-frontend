window.onload = function() {
  call_hooks("init");

  var param = page_resolve_url_params();

  if(param.page) {
    var page = "Page_" + param.page;

    if(window[page]) {
      var page = new window[page]();
      page.content(param, function(page, param, content) {
        twig_render_into(document.body, content.template, content, function(page, param) {
          page.connect(param);
        }.bind(this, page, param));
      }.bind(this, page, param));
    }
  }
}
