const Twig = require('twig')

const page = require('./page')

module.exports = {
  init () {
    Twig.extendFunction("panel_items", param => {
      let items = []

      call_hooks('panel_items', items, param)
      weight_sort(items)

      return items
        .map(item => '<a class="LinkButton" href="' + page_url(item.url) + '">' + item.title + '</a>')
        .join('\n')
    })
  }
}
