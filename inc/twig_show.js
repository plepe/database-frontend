register_hook('twig_init', function() {
  Twig.extendFilter("show", function(data) {
  });

  Twig.extendFilter("show_list", function(data) {
  });

  Twig.extendFunction("get_entry", function(data) {
  });

  Twig.extendFunction("get_entries", function(data) {
  });

  Twig.extendFunction("min", function() {
    var a = Array.prototype.slice.call(arguments);
    if(a.length == 0)
      return null;

    a.sort();
    return a[0];
  });

  Twig.extendFunction("max", function() {
    var a = Array.prototype.slice.call(arguments);
    if(a.length == 0)
      return null;

    a.sort();
    return a[a.length - 1];
  });
});
