module.exports = function data_from_form (dom) {
  let data = {}
  for (let i = 0; i < dom.elements.length; i++) {
    if (dom.elements[i].name) {
      data[dom.elements[i].name] = dom.elements[i].value
    }
  }

  return data
}
