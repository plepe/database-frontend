class View {
  constructor (def, param) {
    this.def = def
    this.id = def.title
    this.param = param
  }

  set_extract (extract) {
    this.extract = extract
  }

  render_list (param) {
    console.log('render_list: ' + this.id + ' ' + this.def.class)
    callback(null, '')
  }

  show_list () {
    console.log('show_list: ' + this.id + ' ' + this.def.class)
  }
}

module.exports = View
