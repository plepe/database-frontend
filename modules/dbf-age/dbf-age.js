const forEach = require('foreach')
const Twig = require('twig')
const htmlentities = require('html-escaper').escape

function text (date) {
  if (date == null) {
    return ''
  }

  let now = new Date()
  let diff = (now - new Date(date)) / 1000

  return _text(diff)
}

function _text (diff) {
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

  return text
}

function update () {
  let now = new Date() / 1000

  let age_fields = document.getElementsByClassName('age')
  forEach(age_fields, (span) => {
    if (!span.hasAttribute('value')) {
      return
    }

    if (!span.value) {
      span.value = new Date(span.getAttribute('value')) / 1000
    }

    if (span.value) {
      span.innerHTML = _text(now - span.value)
    }
  })
}

module.exports = {
  init () {
    Twig.extendFilter("age", (date) => {
      return '<span class="age" value="' + htmlentities(date) + '" title="' + htmlentities(date) + '">' + text(date) + '</span>'
    })

    global.setInterval(update, 30000) // every 30 sec
  }
}
