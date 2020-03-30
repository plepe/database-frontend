const View = require('./View')

class View_Table extends View {
  show_list (value, param) {
    return this.result
  }

  render_list (param, callback) {
    let fields = this.def.fields

    let view = new table(fields, this.extract, {template_engine: 'twig'})
    view.show('html', param, (result) => {
      this.result = result
      callback(null)
    })
  }
}

module.exports = View_Table
