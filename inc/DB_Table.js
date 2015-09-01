function DB_Table(type, callback) {
  this.id = type;
  this.data = null;
  this.entries_cache = {};
  this.load(callback);
}

DB_Table.prototype.load = function(callback) {
  ajax('db_table_load', { 'table': this.id }, null, function(callback, result) {
    this._data = result;

    if(callback)
      callback(this);
  }.bind(this, callback));
}

DB_Table.prototype.name = function() {
  return this.id;
}

DB_Table.prototype.data = function(key) {
  if(key)
    return this._data[key];

  return this._data;
}

DB_Table.prototype.view = function() {
  return this._data;
}

/**
 * return list of fields
 * @return Field[] all fields of the table
 */
DB_Table.prototype.fields = function() {
  return [];
}

/**
 * return the specified field or null
 * @param string field_id field id
 * @return Field the specified field
 */
DB_Table.prototype.field = function(field_id) {
}

DB_Table.prototype.def = function() {
  var ret = this._data.fields;

//  for(var k in this._data.fields) {
//    var d = this._data.fields[k];
//  }
  // TODO:
  // * reference
  // * format

  return ret;
}

DB_Table.prototype.views = function() {
}

DB_Table.prototype.view_def = function() {
}

DB_Table.prototype.default_view = function(type) {
}

DB_Table.prototype.save = function(data, changeset, callback) {
}

DB_Table.prototype.remove = function(changeset, callback) {
}

DB_Table.prototype.get_entry = function(id, callback) {
  this.get_entries_by_id([ id ], function(callback, result) {
    callback(result[0]);
  }.bind(this, callback));
}

DB_Table.prototype.get_entries_by_id = function(ids, callback) {
  var ids_todo = [];
  var ids_loaded = [];
  var ret = [];

  // check which entries are already loaded
  for(var i = 0; i < ids.length; i++) {
    if(ids[i] in this.entries_cache) {
      ret.push(this.entries_cache[ids[i]]);
    }
    else
      ids_todo.push(ids[i]);
  }

  // all entries here -> no need for an ajax request
  if(ids_todo.length == 0) {
    callback(ret);
    return;
  }

  // request additional entries
  ajax("db_table_get_entries_by_id", {
    'table': this.id,
    'ids': ids_todo,
  }, function(callback, ret, result) {
    // ret is already populated with known ids
    for(var i = 0; i < result.length; i++) {
      var entry = result[i];

      this.entries_cache[entry.id] = new DB_Entry(this, entry.id, entry);
      ret.push(this.entries_cache[entry.id]);

      callback(ret);
    }
  }.bind(this, callback, ret));
}

DB_Table.prototype.get_entry_ids = function(filter, sort, offset, limit, callback) {
}

DB_Table.prototype.get_entries = function(filter, sort, offset, limit, callback) {
}

DB_Table.prototype.get_entry_count = function(filter, callback) {
}

function get_db_table(type, callback) {
  new DB_Table(type, callback);
}
