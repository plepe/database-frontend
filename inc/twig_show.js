register_hook('twig_init', function() {
  Twig.extendFilter("show", function(data) {
  });

  Twig.extendFilter("show_list", function(data) {
  });
});
