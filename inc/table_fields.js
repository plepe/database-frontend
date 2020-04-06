const forEach = require('foreach')
const queryString = require('qs')

const state = require('./state.js')

const table_fields_forms = {}
const table_fields_values = {}

function submit () {
  let url = []
  let choose_fields = document.getElementById('choose_table_fields')

  for (let i = 0; i < choose_fields.elements.length; i++) {
    let element = choose_fields.elements[i]

    if (element.name && !element.name.match(/__[a-z_]+\](\[[0-9]+\])?$/)) {
      url.push(encodeURIComponent(element.name) + '=' + encodeURIComponent(element.value))
    }
  }

  url = url.join('&')
  url = queryString.parse(url)
  console.log(url)

  state.change({table_fields: url.table_fields})

  return false
}

function get_table_fields_form (param, callback) {
  if (param.table in table_fields_forms) {
    return callback(null, table_fields_forms[param.table])
  }

  DB_Table.get(param.table, (err, table) => {
    if (err) { return callback(err) }

    let view_field_names = {}
    table.view_fields((err, view_fields) => {
      forEach(view_fields, (view_field, view_field_id) => {
        view_field_names[view_field_id] = view_field.def.name
      })

      let def = {
        table_fields: {
          name: 'Additional table fields',
          type: 'select_other',
          count: {
            index_type: 'array',
            default: 1
          },
          'button:other': 'Custom',
          other_def: {
            type: 'form',
            def: {
              name: {
                name: 'Title',
                type: 'text'
              },
              format: {
                name: 'Format',
                type: 'textarea',
                desc: 'Specify a different format for this field (mandatory for custom fields). This field uses the <a href="http://twig.sensiolabs.org/">Twig template engine</a>. You can use replacement patterns.',
              }
            }
          },
          values: view_field_names,
          values_mode: 'keys'
        }
      }

      form_table_fields = new form('table_fields', def, {
        var_name: ''
      })

      table_fields_forms[param.table] = form_table_fields

      callback(null, form_table_fields)
    })
  })
}

global.show_table_fields = function () {
  var div = document.getElementById("Table_Fields");
  if(div.className == "hidden") {
    if (document.getElementById('table_fields_placeholder')) {
      create_table_fields_form()
    }

    div.className = '';
  } else
    div.className = "hidden";
}

function create_table_fields_form (param, callback) {
  if (!param) {
    param = state.data
  }

  var div = document.getElementById("Table_Fields");
  if(div.className == "hidden") {
    let dom_form = document.getElementById('choose_table_fields')
    if (dom_form) {
      div.removeChild(dom_form)
    }

    div.className = "";

    form_table_fields = get_table_fields_form(param, (err, table_fields_form) => {
      if (err) {
        if (callback) {
          return callback(err)
        } else {
          return alert(err)
        }
      }

      let data = table_fields_form.get_data()

      dom_form = document.createElement('form')
      dom_form.id = 'choose_table_fields'
      dom_form.method = 'get'
      div.appendChild(dom_form)

      dom_form.onsubmit = submit

      table_fields_form.show(dom_form)

      let input = document.createElement('input')
      input.type = 'hidden'
      input.name = 'page'
      input.value = 'list'
      dom_form.appendChild(input)

      input = document.createElement('input')
      input.type = 'hidden'
      input.name = 'table'
      input.value = param.table
      dom_form.appendChild(input)

      input = document.createElement('input')
      input.type = 'hidden'
      input.name = 'apply_table_fields'
      input.value = 'true'
      dom_form.appendChild(input)

      input = document.createElement('input')
      input.type = 'submit'
      input.value = 'Apply'
      dom_form.appendChild(input)

      if (callback) {
        callback(null)
      }
    })
  }
}

function init () {
  if (form_table_fields) {
    table_fields_forms[state.data.table] = form_table_fields
  }
}

function connect (param) {
  let choose_fields = document.getElementById('choose_table_fields')

  if (choose_fields) {
    choose_fields.onsubmit = submit
  }

  document.getElementById("Table_Fields").className = 'hidden'
  if ('table_fields' in param) {
    show_table_fields(param)
  }
}

function modify_viewdef (param, table, def, callback) {
  if (!param.table_fields) {
    return callback(null)
  }

  get_table_fields_form(param, (err, form_table_fields) => {
    let data = form_table_fields.get_data()
    table.view_fields((err, fields) => {
      if (err) { return callback(err) }

      data.table_fields.forEach((field_id, i) => {
        if (field_id === null) {
          // nothing
        } else if (typeof field_id === 'object') {
          def.fields['__table_fields:' + i + '__'] = field_id
        } else {
          def.fields['__table_fields:' + i + '__'] = fields[field_id].view_def()
        }
      })

      callback(null)
    })
  })
}

module.exports = {
  init,
  connect,
  modify_viewdef
}
