const Twig = require('twig')
const Field = require('../../inc/Field')

const hljs = require('highlight.js')
global.marked = require('marked')
global.marked.setOptions({
  highlight: (code, lang) => hljs.getLanguage(lang) ? hljs.highlight(lang, code).value : code
})

class Field_markdown extends Field.Field {
  default_format (key) {
    if (key == null) {
      key = this.id
    }

    return '{{ ' + key + '|markdown }}'
  }
}

Field.fields.markdown = Field_markdown

Twig.extendFilter("markdown", value => value ? marked(value) : '')
