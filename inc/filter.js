const async = {
  parallel: require('async/parallel')
}

const state = require('./state')

const filters = {}

global.show_filter = function () {
  var div = document.getElementById("Filter");
  if(div.className == "hidden")
    div.className = "";
  else
    div.className = "hidden";
}

function get_filter_form (param, callback) {
  let async_functions = []

  if (param.table in filters) {
    return callback(null, filters[param.table])
  }

  DB_Table.get(param.table, (err, table) => {
    operators = {}
    let filter_form_def = {}
    let custom_filters = {}

    let table_fields = table.fields()
    for (let k in table_fields) {
      let field = table_fields[k]

      let f = {
        name: field.def.name,
        type: 'text'
      }

      if (field.def.values && ((Array.isArray(field.def.values) && field.def.values.length) || Object.keys(field.def.values).length)) {
        f.type = 'select'
        f.values = field.def.values
        f.values_mode = 'values'
      }

      if (field.def.reference) {
        f.type = 'select'
        f.values = {}
        f.values_mode = 'keys'
        let ref_table_id = field.def.reference.split(/:/)[0]
        async_functions.push(function (ref_table_id, values, done) {
          DB_Table.get(ref_table_id, (err, ref_table) => {
            if (err) { return callback(err) }
            ref_table.get_entries(null, null, null, null, (err, obs) => {
              if (err) { return callback(err) }
              obs.forEach(o => values[o.id] = o.title())
              done()
            })
          })
        }.bind(this, ref_table_id, f.values))
      }

      custom_filters[field.id] = f

      if (field.def.default_filter) {
        filter_form_def[field.id + '|' + field.def.default_filter] = {
          type: 'text',
          name: field.def.name,
          include_data: 'not_null'
        }
      }
    }

    filter_form_def.__custom__ = {
      name: 'Additional filters',
      type: 'form_chooser',
      def: custom_filters,
      hide_label: true,
      include_data: 'not_null',
      order: false,
      'button:add_element': 'Add filter'
    }

    async.parallel(async_functions,
      err => {
        filters[param.table] = new global.form('filter', filter_form_def)

        callback(null, filters[param.table])
      }
    )

  })
}

function connect (param, current_filter) {
  let choose_filter = document.getElementById('choose_filter')
  if (choose_filter) {
    choose_filter.onsubmit = () => {
      let filter = current_filter.get_data()
      state.change({filter})
      return false
    }
  }
}

function convert (table, form_filter) {
  let data = form_filter.get_data()
  form_filter.set_orig_data(data)

  let ret = []
  for (let k in data) {
    let v = data[k]

    if (k === '__custom__') {
      for (let vk in v) {
        let vv = v[vk]

        if (vv !== null) {
          ret.push({
            field: vk,
            op: 'contains',
            value: vv
          })
        }
      }
    } else {
      let [field, op] = k.split('|')
      ret.push({
        field,
        op,
        value: v
      })
    }
  }

  return ret
}

module.exports = {
  get: get_filter_form,
  convert,
  connect
}
