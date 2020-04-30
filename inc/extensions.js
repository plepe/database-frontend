const extensions = require('./_extensions_.js')
extensions.push(
  /**
   * Enable markdown formatting for fields
   */
  require('./markdown')
)

const async = {
  each: require('async/each')
}

module.exports = {
  call (fun, param) {
    extensions.forEach(extension => {
      if (fun in extension) {
        extension[fun](param)
      }
    })
  },

  call_async (fun, param, callback) {
    async.each(extensions,
      (extension, done) => {
        if (fun in extension) {
          extension[fun](param, done)
        } else {
          done()
        }
      },
      callback
    )
  }
}
