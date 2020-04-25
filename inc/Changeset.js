module.exports = class Changeset {
  constructor (message = null) {
    if (typeof message === 'object') {
      this.message = message.message
    } else {
      this.message = message
    }
  }

  modify_url (url) {
    if (this.message) {
      url += '&message=' + encodeURIComponent(this.message)
    }

    return url
  }
}
