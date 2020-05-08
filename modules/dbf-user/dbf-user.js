const Field = require('../../inc/Field')

class Field_user extends Field.Field {
  form_type () {
    return 'text'
  }
}

Field.fields.user = Field_user
