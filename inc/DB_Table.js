function DB_Table(id, data) {
  this.id = id;
  this.data = data;
  this.def = data.fields;
}

DB_Table.prototype.name = function() {
  return this.data.name || this.id;
}

DB_Table.prototype.data = function(key) {
  if(typeof key === 'undefined')
    return this.data;

  return this.data[key];
}

DB_Table.prototype.fields = function() {
}

DB_Table.prototype.field = function(field_id) {
  if(field_id in this._fields)
    return this._fields[field_id];

  return null;
}

DB_Table.prototype.def = function() {
}

DB_Table.prototype.views = function(type) { // 'list' or 'show'
  var views = {};

  if(this.data.views)
    views = this.data.views;

  views.default = {
    "title": "Default"
  };
  views.default["weight_" + type] = -1;

  if(type == 'show') {
    views.json = {
      "title": 'JSON',
      "class": 'JSON'
    }
    views.json["weight_" + type] = 100;
  }

  views = weight_sort(views, "weight_" + type);

  return views;
}

DB_Table.prototype.view_def = function(k) {
}

DB_Table.prototype.default_view = function(type) {
  var views = this.views(type);
  return array_keys(views)[0];
}

DB_Table.prototype.view = function() {
  return this.data;
}

DB_Table.prototype.save = function() {
  console.log("not implemented yet");
}

DB_Table.prototype.remove = function() {
  console.log("not implemented yet");
}

DB_Table.prototype.load_entries_data = function(ids, callback) {
}

DB_Table.prototype.get_entry = function(id, callback) {
}

DB_Table.prototype.get_entries_by_id = function(ids, callback) {
}

DB_Table.prototype.get_entry_ids = function(filter, sort, offset, limit, callback) {
}

DB_Table.prototype.get_entries = function(filter, sort, offset, limit, callback) {
}

DB_Table.prototype.get_entry_count = function(ids) {
}

function get_db_table(type, callback) {
}

function get_db_tables() {
}
