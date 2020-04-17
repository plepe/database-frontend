const async = {
  parallel: require('async/parallel')
}

const page_with_view = require('../inc/page_with_view.js')
const editable = require('../inc/editable.js')

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
        param.limit = global.user_settings.limit
      }
    }
    if (!('offset' in param)) {
      param.offset = 25
    }

    page_with_view.get(param, page, callback)
  },

  post_render (param, page_data, callback) {
    page_with_view.connect(param)
    editable.connect(param)
    page_with_view.post_render(param, page_data, callback)
  },

  connect (param) {
    page_with_view.connect(param)
    editable.connect(param)
  }
}
