function mousetrap_catch(ob, e) {
  if(ob.tagName == 'A') {
    window.location = ob.href;
    return false;
  }

  if((ob.tagName == 'INPUT') && (ob.type == 'submit')) {
    ob.click();
    return false;
  }
}

register_hook('init', function() {
  var obs = document.getElementsByTagName('*');
  for(var i = 0; i < obs.length; i++) {
    var ob = obs[i];
    var t;

    if(t = ob.getAttribute('mousetrap')) {
      Mousetrap.bind(t, mousetrap_catch.bind(this, ob));
    }
  }
});
