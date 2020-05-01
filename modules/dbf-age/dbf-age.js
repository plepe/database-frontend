const Twig = require('twig')
const htmlentities = require('html-escaper').escape

module.exports = {
  init () {
    Twig.extendFilter("age", date => {
      if (date == null) {
        return ''
      }

      let now = new Date()
      let diff = (now - new Date(date)) / 1000
      let text

      if (diff < 0) {
        text = 'not yet'
      } else if (diff < 2 * 60) {
        text = 'just now'
      } else if (diff < 45 * 60) {
        text = Math.round(diff / 60) + ' minutes ago'
      } else if (diff < 90 * 60) {
        text = 'an hour ago'
      } else if (diff < 86400) {
        text = Math.round(diff / 3600) + ' hours ago'
      } else if (diff < 2 * 86400) {
        text = 'yesterday'
      } else if (diff < 61 * 86400) {
        text = Math.round(diff / 86400) + ' days ago'
      } else if (diff < 380 * 86400) {
        text = Math.round(diff / 30.4 / 86400) + ' months ago'
      } else {
        text = Math.round(diff / 365.25 / 86400) + ' years ago'
      }

      return '<span title="' + htmlentities(date) + '">' + text + '</span>'
    })
  }
}
