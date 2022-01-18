const forEach = require('foreach')

module.exports = {
  post_render (page_data, done) {
    if (!page_data.param || page_data.param.page !== 'show') {
      return done()
    }

    if (page_data.table_object && !page_data.table_object.data('history_git_enabled')) {
      return done()
    }

    let panels = document.getElementsByClassName('Panel')
    forEach(panels, (div) => {
      let a = document.createElement('a')
      a.className = 'LinkButton'
      a.href = '?page=history&table=' + page_data.param.table + '&id=' + page_data.param.id
      a.innerHTML = 'History'

      div.appendChild(a)
    })

    done()
  }
}
