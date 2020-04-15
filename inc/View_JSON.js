const View = require('./View')
const htmlentities = require('html-escaper').escape

class View_JSON extends View {
  render_single (param, callback) {
    this.extract.get(0, 1, (list) => {
      this.ob = list[0]
      this.result = '<pre class="view_json">' + htmlentities(json_readable_encode(this.ob.view())) + '</pre>'
      callback(null)
    })
  }

  show () {
    return this.result
  }
}

module.exports = View_JSON
