const async = {
  parallel: require('async/parallel')
}

const page_with_view = require('../inc/page_with_view.js')
const pager = require('../inc/pager.js')

function page (result, callback) {
  result.view.set_extract(result.table_extract)

  async.parallel([
    done => result.view.render_list(result.param, done),
    done => result.table_extract.pager_info((err, info) => {
      if (info) {
        result.result_count = info.result_count
      }

      done(err)
    })
  ], callback)
}

module.exports = {
  get (param, callback) {
    if (!('limit' in param)) {
      if ('limit' in global.user_settings) {
        param.limit = 'limit' in global.user_settings ? global.user_settings.limit : 25
      }
    }
    if (!('offset' in param)) {
      param.offset = 0
    }

    page_with_view.get(param, page, callback)
  },

  post_render (param, page_data, callback) {
    page_with_view.connect(param)
    page_with_view.post_render(param, page_data, callback)
  },

  connect_server_rendered (param) {
    page_with_view.connect(param)
  },

  update (page_data, callback) {
    async.parallel([
      (done) => page_data.view.update_list(page_data.param, done),
      (done) => pager.update_list(page_data.param, page_data.table_extract, done)
    ], (err) => {
      if (err) { return callback(err) }

      page_with_view.post_update(page_data.param, page_data, callback)
    })
  }
}
