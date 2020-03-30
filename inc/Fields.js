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

  view_def () {
    return this.def
    // TODO
  }
}

class Field_textarea extends Field {
}

module.exports = {
  default: Field,
  textarea: Field_textarea
}
