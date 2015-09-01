register_hook('twig_init', function() {
  Twig.extendFunction("panel_items", function(data) {
  });
});
