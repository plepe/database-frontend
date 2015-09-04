function Field() {
}

Field.prototype.init = function(column_id, column_def, table) {
  this.id = column_id;
  this.def = column_def;
  this.table = table;
}

Field.prototype.is_multiple = function() {
  if(this.def.count)
    return true;

  return false;
}

Field.prototype.default_format = function(key) {
  if(!key)
    key = this.id;

  return "{{ " + key + " }}";
}

Field.prototype.view_def = function() {
  var ret = JSON.parse(JSON.stringify(this.def));

  if(this.def.reference) {
    if(this.is_multiple() === true) {
      ret.format =
	"<ul class='MultipleValues'>\n" +
	"{% for _ in " + this.id + " %}\n" +
	"<li><a href='{{ page_url({ \"page\": \"show\", \"table\": \"" + this.def.reference + "\", \"id\": _.id }) }}'>" +
	this.default_format("_.id") +
	"</a>" +
	"{% endfor %}\n" +
	"</ul>\n";
    }
    else {
      ret.format =
	"<a href='{{ page_url({ \"page\": \"show\", \"table\": \"" + this.def.reference + "\", \"id\": " + this.id + ".id }) }}'>" +
	this.default_format(this.id + ".id") +
	"</a>";
    }
  }
  else {
    if(this.is_multiple() === true) {
      ret.format =
	"<ul class='MultipleValues'>\n" +
	"{% for _ in " + this.id + " %}\n" +
	"<li>" + this.default_format("_") + "</li>\n" +
	"{% endfor %}\n" +
	"</ul>\n";
    }
    else {
      ret.format = this.default_format();
    }
  }

  if(!('sortable' in this.def)) {
    ret.sortable = {
      'type': 'nat'
    };
  }

  return ret;
}

Field.prototype.filters = function() {
  return {
    'contains': {
      'name': 'contains',
      'value_type': 'text'
    },
    'is': {
      'name': 'is',
      'value_type': 'text'
    }
  };
}

/* Field_text */
Field_text.inherits_from(Field);
function Field_text() {
}

/* Field_textarea */
Field_textarea.inherits_from(Field);
function Field_textarea() {
}

Field_textarea.prototype.default_format = function(key) {
  if(!key)
    key = this.id;

  return "{{ " + key + "|nl2br }}";
}

/* FieldWithValues */
FieldWithValues.inherits_from(Field);
function FieldWithValues() {
}

FieldWithValues.prototype.default_format = function(key) {
  if(!key)
    key = this.id;

  if(this.def.reference)
    return "{{ " + key + " }}";
  if(this.def.values_mode == 'keys')
    return "{{ " + JSON.stringify(this.def.values) + "[" + key + "]|default(" + key + ") }}";

  return "{{ " + key + " }}";
}

/* Field_radio */
Field_radio.inherits_from(FieldWithValues);
function Field_radio() {
}

Field_radio.prototype.is_multiple = function() {
  return false;
}

/* Field_checkbox */
Field_checkbox.inherits_from(FieldWithValues);
function Field_checkbox() {
}

Field_checkbox.prototype.is_multiple = function() {
  return true;
}

/* Field_select */
Field_select.inherits_from(FieldWithValues);
function Field_select() {
}
