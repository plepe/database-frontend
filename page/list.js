function Page_list() {
}

Page_list.prototype.content = function(param, callback) {
  this.param = param;

  if(!this.param.limit)
    this.param.limit = 25;
  if(!this.param.offset)
    this.param.offset = 0;

  get_db_table(this.param.table, function(callback, ob) {
    this.db_table = ob;
    this.data = new DB_TableExtract(this.db_table);

    if(!this.param.view)
      this.param.view = this.db_table.default_view('list');

    var def = this.db_table.view_def(this.param.view);
    var t = new table(def.fields, this.data, { 'template_engine': 'twig' });

    this.result = {
      'param': this.param,
      'template': 'list.html',
      'table': this.param.table,
      'views': this.db_table.views('list')
    };

    // Execute several functions parallel:
    async.parallel([
      // 1. render table into 'content'
      function(t, callback) {
        t.show('html',
        {
          'limit': this.param.limit,
          'offset': this.param.offset
        }, function(callback, r) {
          this.result.content = r;
          callback(null);
        }.bind(this, callback))
      }.bind(this, t)

    ],
    // finally, call the callback with the completed this.result
    function(callback) {
      callback(this.result);
    }.bind(this, callback));

  }.bind(this, callback));
}

Page_list.prototype.connect = function(param, callback) {
  var obs = document.getElementsByClassName('Pager');
  new Pager(this, obs);
}
