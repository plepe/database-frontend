function Page_list() {
}

Page_list.prototype.content = function(param) {
  return {
    'template': 'list.html'
  };
}

Page_list.prototype.connect = function(param) {
  get_db_table('computer', function(ob) {
    this.db_table = ob;
    this.data = new DB_TableExtract(this.db_table);

    var def = this.db_table.view_def('Overview');
    console.log(def);
    var t = new table(def.fields, this.data, { 'template_engine': 'twig' });
    console.log(this.db_table.default_view('show'));

    t.show(function(r) {
      var x = document.getElementsByClassName('table_wrapper');
      x[0].innerHTML = r;
    }, { 'limit': 1, 'offset': 0 });

  }.bind(this));
}
