module.exports = function observe (div, filter, callback) {
  let observer = new MutationObserver(mutations => callback())
  observer.observe(div, filter)
}
