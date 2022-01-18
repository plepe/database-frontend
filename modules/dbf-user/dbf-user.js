const Field = require('../../inc/Field')

class Field_user extends Field.Field {
  form_type () {
    return 'text'
  }
}

Field.fields.user = Field_user

Twig.extendFilter("user_username", value => value ? value.split(/@/)[0] : '')
Twig.extendFilter("user_domain", value => value ? value.split(/@/)[1] : '')
