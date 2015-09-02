function DB_TableExtract(table) {
  this.table = table;
  this.filter = null;
  this.sort = null;
  this.ids = null;
}

DB_TableExtract.prototype.count = function() {
  return this.table.get_entry_count(this.filter);
}

DB_TableExtract.prototype.set_sort = function(sort) {
  this.sort = sort;
}

DB_TableExtract.prototype.set_filter = function(filter) {
  this.filter = filter;
}

DB_TableExtract.prototype.set_ids = function(ids) {
  this.filter = null;
  this.ids = ids;
}

DB_TableExtract.prototype.get = function(offset, limit, callback) {
  if(this.ids)
    this.table.get_entries_by_id(this.ids, callback);
  else
    this.table.get_entries(this.filter, this.sort, offset, limit, callback);
}

/**
 * pass index of the given id in the filtered and sorted list to callback
 * @param string id id of the object
 * @param callable callback {
 *   @param integer|false index in the list or false if object is not in list
 * }
 */
DB_TableExtract.prototype.index = function(id, callback) {
  this.table.get_entry_ids(this.filter, this.sort, null, null, function(id, callback, ids) {
    var index = ids.indexOf(id);
    if(index == -1)
      callback(false);
    else
      callback(index);
  }.bind(this, id, callback));
}

