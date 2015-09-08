function url_local_href(a) {
  var m = a.href.match(/^(.*\/)?(([^\/]*)\.([^\/\?]*))?(\?.*)?$/);
  if(m) {
    return {
      'server_path': m[1],
      'script': m[3] || 'index',
      'ext': m[4],
      'param': m[5]
    };
  }

  return {};
}

function follow_link(a) {
  var url = url_local_href(a);
  if(url && url.param) {
    param = page_resolve_url_params(url.param);

    var ret = open_page(param);

    if(ret == true) {
      history.pushState(param, 'List', a.href);
    }

    return !ret;
  }

  return true;
}

function follow_form(a) {
  var params = [];

  for(var i=0; i<a.elements.length; i++) {
    var e = a.elements[i];

    if(e.name && e.value)
      params.push(encodeURIComponent(e.name) + "=" + encodeURIComponent(e.value));
  }

  var params = page_resolve_url_params('?' + params.join('&'));

  return !open_page(params);
}

function mangle_links() {
  var as = document.body.getElementsByTagName("a");
  for(var i = 0; i < as.length; i++) {
    var a = as[i];
    var href = a.getAttribute("href"); // a.href would include full url; we are only interested in local urls.

    if(href.match(/^\?.*$/))
      a.onclick = follow_link.bind(this, a);
  }

  var as = document.body.getElementsByTagName("form");
  for(var i = 0; i < as.length; i++) {
    var a = as[i];

    if(a.enctype == "multipart/form-data")
      continue;

    if(!a.onsubmit)
      a.onsubmit = follow_form.bind(this, a);
  }
}

register_hook("init", function() {
  mangle_links(document.body);
  document.addEventListener("DOMNodeInserted", mangle_links);
});

window.onpopstate = function(event) {
  var a = { href: document.location.href, no_push: true };
  follow_link(a);
  return true;
}
