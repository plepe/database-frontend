function Page_list() {
}

Page_list.prototype.content = function(param) {
  return {
    'template': 'list.html'
  };
}

Page_list.prototype.connect = function(param) {
  get_db_table(param.table, function(param, ob) {
    this.db_table = ob;
    this.data = new DB_TableExtract(this.db_table);

    var view = this.db_table.default_view('list');
    var def = this.db_table.view_def(view);
    var t = new table(def.fields, this.data, { 'template_engine': 'twig' });

    t.show('html',
    {
      'limit': param.limit || 25,
      'offset': param.offset || 0
    },
    function(r) {
      var x = document.getElementsByClassName('table_wrapper');
      x[0].innerHTML = r;
    });

  }.bind(this, param));
}
