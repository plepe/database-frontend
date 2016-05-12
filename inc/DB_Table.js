function DB_Table(id, data) {
  this.id = id;
  this.data = data;
  this.def = data.fields;
}

DB_Table.prototype.name = function() {
  return this.id;
}

DB_Table.prototype.data = function(key) {
  if(typeof key === 'undefined')
    return this.data;

  return this.data[key];
}

DB_Table.prototype.fields = function() {
}
