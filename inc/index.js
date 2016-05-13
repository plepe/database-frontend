window.onload = function() {
  call_hooks("init");

  var page = new Page_list();

  twig_render_into(document.body, 'list.html', page.content(param));
}
