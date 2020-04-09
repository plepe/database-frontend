class Field {
  constructor (columnId, columnDef, table) {
    this.id = columnId
    this.def = columnDef
    this.table = table
  }

  type () {
    return this.def.type
  }

  name () {
    return this.def.name
  }

  form_type () {
    return this.def.type
  }

  is_multiple () {
    return 'count' in this.def && this.def.count
  }

  default_format (key) {
    if (key == null) {
      key = this.id
    }

    return '{{ ' + key + ' }}'
  }

  view_def () {
    let ret = {}

    for (let k in this.def) {
      ret[k] = this.def[k]
    }

    if (this.def.reference || this.def.backreference) {
      let ref_table = null

      if (this.def.backreference) {
        ref_table = this.def.backreference.split(/:/)[0]
      }
      else {
        ref_table = this.def.reference
      }
      ref_table = JSON.stringify(ref_table)

      if (this.is_multiple()) {
        ret.format =
	  "<ul class='MultipleValues'>\n" +
	  "{% for _ in " + this.id + " %}\n" +
	  "<li><a href='{{ page_url({ \"page\": \"show\", \"table\": " + ref_table + ", \"id\": _ }) }}'>" +
          "{{ entry_title(" + ref_table + ", _) }}" +
	  "</a>" +
	  "{% endfor %}\n" +
	  "</ul>\n"
      } else {
        ret.format =
	  "<a href='{{ page_url({ \"page\": \"show\", \"table\": " + ref_table + ", \"id\": " + this.id + " }) }}'>" +
          "{{ entry_title(" + ref_table+ ", " + this.id + ") }}" +
	  "</a>";
      }
    } else {
      if (this.is_multiple() === true) {
        ret.format =
	  "<ul class='MultipleValues'>\n" +
	  "{% for _ in " + this.id + " %}\n" +
	  "<li>" + this.default_format("_") + "</li>\n" +
	  "{% endfor %}\n" +
	  "</ul>\n";
      } else {
        ret.format = this.default_format()
      }
    }

    return ret
    // TODO
  }
}

class FieldWithValues extends Field {
  default_format (key) {
    if (key == null) {
      key = this.id
    }

    if (this.def.reference) {
      return '{{ ' + key + ' }}'
    }
    if (this.def.values_mode === 'keys') {
      return '{{ ' + JSON.stringify(this.def.values) + '[' + key + ']|default(' + key + ') }}'
    }

    return '{{ ' + key + ' }}'
  }
}

class Field_textarea extends Field {
  default_format (key) {
    if (key == null) {
      key = this.id
    }

    return '{{ ' + key + '|nl2br }}'
  }
}

class Field_date extends Field {
  default_format (key) {
    if (key == null) {
      key = this.id
    }

    return '{% if ' + key + ' %}{{ ' + key + '|date("j.n.Y") }}{% endif %}'
  }
}

class Field_datetime extends Field {
  default_format (key) {
    if (key == null) {
      key = this.id
    }

    return '{% if ' + key + ' %}{{ ' + key + '|date("j.n.Y G:i:s") }}{% endif %}'
  }
}

class Field_boolean extends Field {
  default_format (key) {
    if (key == null) {
      key = this.id
    }

    return '{% if ' + key + ' is null %}{% elseif ' + key + ' == true %}true{% else %}false{% endif %}'
  }
}

class Field_radio extends FieldWithValues {
  is_multiple () {
    return false
  }
}

class Field_checkbox extends FieldWithValues {
  is_multiple () {
    return true
  }
}

class Field_select extends FieldWithValues {
}

class Field_backreference extends FieldWithValues {
  is_multiple () {
    return true
  }
}

module.exports = {
  default: Field,
  textarea: Field_textarea,
  date: Field_date,
  datetime: Field_datetime,
  boolean: Field_boolean,
  radio: Field_radio,
  checkbox: Field_checkbox,
  select: Field_select,
  backreference: Field_backreference
}
