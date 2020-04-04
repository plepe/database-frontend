const async = {
  parallel: require('async/parallel')
}

const DB_TableExtract = require('../inc/DB_TableExtract')
const page_with_view = require('../inc/page_with_view.js')

function page (result, callback) {
  let extract = new DB_TableExtract(result.table_object)
  extract.set_ids([result.param.id])
  result.view.set_extract(extract)

  async.parallel([
    done => result.view.render_single(result.param, done),
    done => result.table_object.get_entry(result.param.id, (err, entry) => {
      if (err) {
        return done(err)
      }

      result.id = result.param.id
      result.title = entry.title()
      done()
    })
  ], callback)
}

module.exports = {
  get (param, callback) {
    page_with_view.get(param, page, callback)
  },

  connect (param) {
    page_with_view.connect(param)
  }
}
