const Twig = require('twig')
const Field = require('./Field')

module.exports = {
  init () {
    // Markdown
    const hljs = require('highlight.js')
    global.marked = require('marked')
    global.marked.setOptions({
      highlight: (code, lang) => hljs.highlight(lang, code).value
    })

    Field.fields.markdown = Field_markdown
  }
}

class Field_markdown extends Field.Field {
  default_format (key) {
    if (key == null) {
      key = this.id
    }

    return '{{ ' + key + '|markdown }}'
  }
}

Twig.extendFilter("markdown", value => marked(value))
