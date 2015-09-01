function DB_Table(type, callback) {
  this.id = type;
  this._data = null;
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

  if(!this.request_additional_ids)
    this.request_additional_ids = [];
  this.request_additional_ids.push([ ids_todo, ret, callback ]);

  if(!this.request_additional_ids_timeout)
    this.request_additional_ids_timeout = window.setTimeout(this._request_additional_ids.bind(this), 1);
}

DB_Table.prototype._request_additional_ids = function() {
  var ids_todo = [];
  for(var i = 0; i < this.request_additional_ids.length; i++) {
    for(var j = 0; j < this.request_additional_ids[i][0].length; j++) {
      var id = this.request_additional_ids[i][0][j];

      if(ids_todo.indexOf(id) == -1)
        ids_todo.push(id);
    }
  }

  // request additional entries
  ajax("db_table_get_entries_by_id", {
    'table': this.id,
    'ids': ids_todo,
  }, function(request, result) {
    // load all information from database
    for(var i = 0; i < result.length; i++) {
      var entry = result[i];

      this.entries_cache[entry.id] = new DB_Entry(this, entry.id, entry);
    }

    // now process all requests and complete information if possible
    for(var i = 0; i < request.length; i++) {
      var ids_todo = request[i][0];
      var ret = request[i][1];
      var callback = request[i][2];

      for(var j = 0; j < ids_todo.length; j++) {
        if(ids_todo[j] in this.entries_cache)
          ret.push(this.entries_cache[ids_todo[j]]);
      }

      callback(ret);
    }
  }.bind(this, this.request_additional_ids));

  this.request_additional_ids = null;
  this.request_additional_ids_timeout = null;
}

DB_Table.prototype.get_entry_ids = function(filter, sort, offset, limit, callback) {
  if(typeof filter == 'function') {
    callback = filter;
    filter = null;
  }
  if(typeof sort == 'function') {
    callback = sort;
    sort = null;
  }
  if(typeof offset == 'function') {
    callback = offset;
    offset = null;
  }
  if(typeof limit == 'function') {
    callback = limit;
    limit = null;
  }

  ajax("db_table_get_entry_ids", {
    'table': this.id,
    'filter': filter,
    'sort': sort,
    'offset': offset,
    'limit': limit
  }, function(callback, result) {
    callback(result);
  }.bind(this, callback));
}

DB_Table.prototype.get_entries = function(filter, sort, offset, limit, callback) {
  if(typeof filter == 'function') {
    callback = filter;
    filter = null;
  }
  if(typeof sort == 'function') {
    callback = sort;
    sort = null;
  }
  if(typeof offset == 'function') {
    callback = offset;
    offset = null;
  }
  if(typeof limit == 'function') {
    callback = limit;
    limit = null;
  }

  this.get_entry_ids(filter, sort, offset, limit,
    function(callback, result) {
      this.get_entries_by_id(result, callback);
    }.bind(this, callback));
}

DB_Table.prototype.get_entry_count = function(filter, callback) {
  if(typeof filter == 'function') {
    callback = filter;
    filter = null;
  }

  ajax("db_table_get_entry_count", {
    'table': this.id,
    'filter': filter,
  }, function(callback, result) {
    callback(result);
  }.bind(this, callback));
}

function get_db_table(type, callback) {
  new DB_Table(type, callback);
}
