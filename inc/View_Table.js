const View = require('./View')
const page_url = require('./page_url')
const htmlentities = require('html-escaper').escape

class View_Table extends View {
  show (value, param) {
    return this.result
  }

  render_single (param, callback) {
    let fields = JSON.parse(JSON.stringify(this.def.fields))

    for (let key in fields) {
      fields[key].html_attributes = 'data-table="' + htmlentities(param.table) + '" data-id="{{ id }}" data-field="' + htmlentities(key) + '" data-view="' + htmlentities(this.id) + '"'
    }

    let view = new table(fields, this.extract, {template_engine: 'twig'})
    view.show('html-transposed', param, (result) => {
      this.result = result
      callback(null)
    })
  }

  show_list (value, param) {
    return this.result
  }

  render_list (param, callback) {
    let fields = JSON.parse(JSON.stringify(this.def.fields))

    let first_field = fields[Object.keys(fields)[0]]
    first_field.format =
      "<a class='TableLink' href='" +
      page_url({page: 'show', table: param.table, id: "ID"}).replace(/ID/g, '{{ id }}') +
      "'>" +
      first_field.format +
      "</a>" +
      "<a title='edit' class='edit' href='" +
      page_url({page: 'edit', table: param.table, id: "ID"}).replace(/ID/g, "{{ id }}") +
      "'><img src='images/edit.png'></a>"

    for (let key in fields) {
      fields[key].html_attributes = 'data-table="' + htmlentities(param.table) + '" data-id="{{ id }}" data-field="' + htmlentities(key) + '" data-view="' + htmlentities(this.id) + '"'
    }

    let view = new table(fields, this.extract, {template_engine: 'twig'})
    view.show('html', param, (result) => {
      this.result = result
      callback(null)
    })
  }
}

module.exports = View_Table
