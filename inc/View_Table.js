const View = require('./View')
const htmlentities = require('html-escaper').escape

class View_Table extends View {
  show (value, param) {
    return this.result
  }

  render_single (param, callback) {
    let fields = JSON.parse(JSON.stringify(this.def.fields))

    for (let key in fields) {
      fields[key].html_attributes = 'data-table="' + htmlentities(param.table) + '" data-id="{{ id }}" data-field="' + htmlentities(fields[key].field_id || key) + '" data-view="' + htmlentities(this.id) + '"'
    }

    this.view = new table(fields, this.extract, {template_engine: 'twig'})
    this.render_view(this.view, 'html-transposed', param, callback)
  }

  update_single (param, callback) {
    this.render_view(this.view, 'html-transposed', param, (err) => {
      let content = document.getElementsByClassName('Content')

      if (content.length) {
        content[0].innerHTML = this.result
      }

      callback(err)
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
      fields[key].html_attributes = 'data-table="' + htmlentities(param.table) + '" data-id="{{ id }}" data-field="' + htmlentities(fields[key].field_id || key) + '" data-view="' + htmlentities(this.id) + '"'
    }

    this.view = new table(fields, this.extract, {template_engine: 'twig'})
    this.render_view(this.view, 'html', param, callback)
  }

  update_list (param, callback) {
    this.render_view(this.view, 'html', param, (err) => {
      let content = document.getElementsByClassName('Content')

      if (content.length) {
        content[0].innerHTML = this.result
      }

      callback(err)
    })
  }

  render_view (view, type, param, callback) {
    view.show(type, param, (result) => {
      this.result = result

      if (DB_Table.has_missing()) {
        DB_Table.load_missing(
          (err) => {
            if (err) {
              return callback(err)
            }

            this.render_view(view, type, param, callback)
          }
        )
      } else {
        callback(null)
      }
    })
  }
}

module.exports = View_Table
