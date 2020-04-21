const Twig = require('twig')

module.exports = {
  init () {
    // Markdown
    const hljs = require('highlight.js')
    global.marked = require('marked')
    global.marked.setOptions({
      highlight: (code, lang) => hljs.highlight(lang, code).value
    })
  }
}

Twig.extendFilter("markdown", value => marked(value))
