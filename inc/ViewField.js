module.exports = class ViewField {
  constructor (def) {
    this.def = def
    this.id = this.def.id
  }

  id () {
    return this.def.id
  }

  view_def () {
    return this.def
  }
}
