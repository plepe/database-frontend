function DB_Entry(table, id, data) {
  this.table = table;
  this.id = id;
  this._data = data;
}

DB_Entry.prototype.data = function(key) {
  if(key)
    return this._data[key];

  return this._data;
}

DB_Entry.prototype.view = function() {
  return this._data;
}

DB_Entry.prototype.name = function() {
  return this.id;
}

DB_Entry.prototype.save = function(data, changeset, callback) {
}

DB_Entry.prototype.remove = function(changeset, callback) {
}
