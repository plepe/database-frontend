module.exports = {
  get (param, callback) {
    callback(null, {app: {title: 'Foo'}, data: {tables: []}})
  }
}
