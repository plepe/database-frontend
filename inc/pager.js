const forEach = require('foreach')
const async = {
  parallel: require('async/parallel')
}

const httpRequest = require('./httpRequest')
const state = require('./state')
const templates = require('./templates')

function connect (param) {
  let pagers = document.getElementsByClassName('pager_gear')

  for (let i = 0; i < pagers.length; i++) {
    let pager = pagers[i]

    pager.onclick = function (pager) {
      if (pager.has_pager_options) { return }

      /// / Open window
      let pager_options = document.createElement('form')
      pager_options.method = 'get'
      pager_options.className = 'pager_options'

      pager_options.appendChild(document.createTextNode('Results per page: '))

      // Parameters
      let p = document.createElement('input')
      p.type = 'hidden'
      p.name = 'page'
      p.value = param.page
      pager_options.appendChild(p)

      p = document.createElement('input')
      p.type = 'hidden'
      p.name = 'table'
      p.value = param.table
      pager_options.appendChild(p)

      p = document.createElement('input')
      p.type = 'hidden'
      p.name = 'offset'
      p.value = 0
      pager_options.appendChild(p)

      /// / Limit select
      let select = document.createElement('select')
      select.name = 'limit'
      select.onchange = () => {
        user_settings.limit = select.value
        state.change({limit: select.value})
        httpRequest('user_settings.php', {limit: select.value},
          (err) => {
            if (err) {
              alert(err)
            } else {
              pager.removeChild(pager_options)
            }
          }
        )
      }

      let limits = [10, 25, 50, 100, 0]
      let limit = parseInt(global.user_settings.limit) || 0
      if ('limit' in param) {
        limit = parseInt(param.limit) || 0
      }

      for (let i in limits) {
        let option = document.createElement('option')
        option.value = limits[i]
        if (limits[i] === limit) {
          option.selected = true
        }

        option.appendChild(document.createTextNode(limits[i] === 0 ? '∞' : limits[i]))

        select.appendChild(option)
      }

      pager_options.appendChild(select)

      /// / Close button
      let close = document.createElement('span')
      close.appendChild(document.createTextNode('×'))
      close.onclick = function (pager, pager_options) {
        pager.removeChild(pager_options)

        // prevent re-creation of pager options window
        window.setTimeout(function (pager) {
          pager.has_pager_options = false
        }.bind(this, pager), 100)
      }.bind(this, pager, pager_options)
      pager_options.appendChild(close)

      pager.appendChild(pager_options)
      pager.has_pager_options = true
    }.bind(this, pager)
  }
}

function permalink (param) {
  param.limit = global.user_settings.limit
}

function update_list (param, table_extract, callback) {
  table_extract.get_ids((err, ids) => {
    let text = '' + ids.length
    if (param.limit) {
      text = (param.offset + 1) + '-' + Math.min(ids.length, param.offset + param.limit) + ' / ' + (ids.length)
    }

    let pagers = document.getElementsByClassName('pager')
    forEach(pagers, (pager) => {
      pager.innerHTML = text
    })

    callback(err)
  })
}

function update_single (param, table_extract, callback) {
  async.parallel({
    template: (done) => templates.get('show_pager', done),
    info: (done) => table_extract.pager_info_show(param.id, done)
  }, (err, {template, info}) => {
    let pagers = document.getElementsByClassName('Pager')
    forEach(pagers, (pager) => {
      let div = document.createElement('div')
      div.innerHTML = template.render({pager: info})
      pager.parentNode.insertBefore(div, pager)
      pager.parentNode.removeChild(pager)
    })

    callback(err)
  })
}

module.exports = {
  init: () => {},
  connect_server_rendered: connect,
  post_render: (param, page_data, done) => {
    connect(param)
    done()
  },
  post_update: (param, page_data, done) => {
    connect(param)
    done()
  },
  pre_render: (param, page_data, done) => done(),
  permalink,
  update_list,
  update_single
}
