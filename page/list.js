register_hook("twig_init", function() {
  Twig.extendFunction('panel_items', function(data) {
    return 'FOO';
  });

  Twig.extendFilter('show', function(data) {
    return 'FOO';
  });

  Twig.extendFilter('show_list', function(view) {
    console.log(view);
    return view.show_list();
  });
});

function View_Table(def, param) {
  this.def = def;
  this.param = param;
}

View_Table.prototype.set_extract = function(extract) {
  this.extract = extract;
}

View_Table.prototype.show = function() {
}

View_Table.prototype.show_list = function() {
  this.view = new table(this.def.fields, this.extract, { "template_engine": "twig" });

  return this.view.show('html', this.param);
}

function Page_list() {
}

Page_list.prototype.content = function(param) {
  var ret = {
    template: "list.html"
  };
  
  ret.view = new View_Table({}, param);

  return ret;
}
