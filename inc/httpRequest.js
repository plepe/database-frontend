/**
 * @param {string} url - The URL of the request
 * @param {Object} options - Options
 * @param {string} options.method=GET - Method of the request (e.g. POST, DELETE).
 * @param {string} options.body=null - Body of the request.
 * @param {boolean} options.forceServerLoad=false - Pass the request via the httpRequest.php server script to avoid CORS problems.
 * @param {function} callback - Callback which will be called when the request completes
 * @param {Error} callback.err - If an error occured, the error. Otherwise null.
 * @param {Object} callback.result - The result.
 * @param {string} callback.result.body - The result body.
 */
function httpRequest (url, options, callback) {
  let corsRetry = true
  var xhr

  function readyStateChange () {
    if (xhr.readyState === 4) {
      if (corsRetry && xhr.status === 0) {
        corsRetry = false
        return viaServer()
      }

      if (xhr.status === 200) {
        callback(null, { body: xhr.responseText })
      } else {
        callback(xhr.responseText)
      }
    }
  }

  function direct () {
    xhr = new XMLHttpRequest()
    xhr.open(options.method || 'GET', url, true)
    xhr.responseType = 'text'
    xhr.onreadystatechange = readyStateChange
    xhr.send(options.body)
  }

  function viaServer () {
    xhr = new XMLHttpRequest()
    xhr.open(options.method || 'GET', 'httpGet.php?url=' + encodeURIComponent(url), true)
    xhr.responseType = 'text'
    xhr.onreadystatechange = readyStateChange
    xhr.send(options.body)
  }

  if (options.forceServerLoad) {
    viaServer()
  } else {
    direct()
  }
}

module.exports = httpRequest
