const Twig = require('twig')
const queryString = require('qs')
const async = {
  each: require('async/each'),
  eachOf: require('async/eachOf')
}

const httpRequest = require('./httpRequest')
const DB_Entry = require('./DB_Entry')
const Fields = require('./Fields')
const ViewField = require('./ViewField')

let db_table_cache = {}
let db_table_complete = false
let _load_tables_callbacks = null
const missing_entries = {}

class DB_Table {
  constructor (id, data) {
    this.id = id
    db_table_cache[id] = this
    this.entries_cache = {}
    this._load_callbacks = []

    if (data) {
      this._load_callbacks = null
      this._data = data
    }
  }

  _call_load_callbacks (err) {
    this._load_callbacks.forEach(cb => cb(err, db_table_cache[this.id]))
    this._load_callbacks = null
  }

  _load () {
    let req = new XMLHttpRequest()

    req.onreadystatechange = () => {
      if (req.readyState == 4) {
        let err = null

        if (req.status == 200) {
          this._data = JSON.parse(req.responseText)
        } else {
          delete db_table_cache[this.id]
          err = new Error('table ' + this.id + ' does not exist')
        }

        this._call_load_callbacks(err)
      }
    }

    req.open('GET', 'api.php?table=' + encodeURIComponent(this.id), true)
    req.send()
  }

  data (key) {
    if (key !== undefined) {
      return this._data[key]
    }

    return this._data
  }

  view (key) {
    return this.data(key)
  }

  name () {
    return this.data('name') || this.id
  }

  get_entry (id, callback) {
    if (id in this.entries_cache) {
      let entry = this.entries_cache[id]

      if (!entry) {
        return callback(new Error('entry ' + this.id + '/' + id + ' does not exist'), null)
      }

      if (entry._load_callbacks === null) {
        return callback(null, entry)
      }

      return entry._load_callbacks.push(callback)
    }

    let entry = new DB_Entry(this, id)
    entry._load_callbacks.push(callback)
    entry._load()
  }

  get_loaded_entry_sync (id) {
    if (id in this.entries_cache) {
      return this.entries_cache[id]
    } else {
      if (!(this.id in missing_entries)) {
        missing_entries[this.id] = {}
      }
      missing_entries[this.id][id] = true
    }
  }

  get_entries_by_id (ids, callback) {
    let toLoad = ids.filter(id => !(id in this.entries_cache))

    if (toLoad.length) {
      httpRequest('api.php?table=' + encodeURIComponent(this.id) + 
        toLoad.map(id => '&id[]=' + encodeURIComponent(id)).join('') +
        '&full=1',
        {},
        (err, req) => {
          if (err) { return callback(err) }

          let list = JSON.parse(req.body)

          list.forEach((entry, i) => {
            if (entry) {
              new DB_Entry(this, entry.id, entry)
            } else {
              this.entries_cache[ids[i]] = null
            }
          })

          let result = ids.map(id => this.entries_cache[id])
          callback(null, result)
        }
      )
    } else {
      let result = ids.map(id => this.entries_cache[id])
      callback(null, result)
    }
  }

  get_entry_ids (filter, sort, offset, limit, callback) {
    let param = {
      table: this.id,
      list: 1
    }

    if (filter != null) {
      param.filter = filter
    }
    if (sort != null) {
      param.sort = sort
    }
    if (offset != null) {
      param.offset = offset
    }
    if (limit != null) {
      param.limit = limit
    }

    httpRequest('api.php?' + queryString.stringify(param), {},
      (err, result) => {
        if (err) {
          return callback(err)
        }

        let ids = JSON.parse(result.body)

        callback(null, ids)
      }
    )
  }

  get_entries (filter, sort, offset, limit, callback) {
    this.get_entry_ids(filter, sort, offset, limit,
      (err, ids) => {
        if (err) {
          return callback(err)
        }

        this.get_entries_by_id(ids, callback)
      }
    )
  }

  create_entry (data, changeset, callback) {
    let entry = new DB_Entry(this)
    entry.save(data, changeset, callback)
  }

  title_template () {
    if (!this._title_template) {
      let data

      if (!(data = this.data('title'))) {
        data = '{{ id }}'
      }

      this._title_template = new Twig.twig({data})
    }

    return this._title_template
  }

  def (callback) {
    let ret = JSON.parse(JSON.stringify(this._data.fields))

    async.eachOf(ret, (d, k, done) => {
      let field = this.field(k)
      ret[k].type = field.form_type()

      if (d.reference) {
        if (!ret[k].format) {
          ret[k].format = '{{ ' + k + '.name }}'
        }

        get_table(d.reference, (err, table) => {
          if (err) { return done(err) }

          table.get_entries(null, null, null, null, (err, entries) => {
            if (err) { return done(err) }

            let values = {}
            entries.map(o => values[o.id] = o.title())
            ret[k].values = values

            done()
          })
        })
      } else if (d.backreference) {
        if (!ret[k].format) {
          ret[k].format = '{{ ' + k + '.name }}'
        }

        let ref_table = d.backreference.split(/:/)[0]

        get_table(ref_table, (err, table) => {
          if (err) { return done(err) }

          table.get_entries(null, null, null, null, (err, entries) => {
            if (err) { return done(err) }

            let values = {}
            entries.map(o => values[o.id] = o.title())
            ret[k].values = values

            done()
          })
        })
      } else {
        if ('additional_form_def' in field) {
          field.additional_form_def((err, d) => {
            if (err) { return callback(err) }

            for (let k1 in d) {
              ret[k1] = d[k1]
            }

            done()
          })
        } else {
          done()
        }
      }
    }, (err) => {
      callback(null, ret)
    })
  }

  fields () {
    if (!this._fields) {
      this._fields = {}
      for (let columnId in this._data.fields) {
        let columnDef = this._data.fields[columnId]

        let type = 'default'
        if (columnDef.type in Fields) {
          type = columnDef.type
        }

        this._fields[columnId] = new Fields[type](columnId, columnDef, this)
      }
    }

    return this._fields
  }

  view_fields (callback) {
    let ret = {}

    let fields = this.fields()
    for (let f in fields) {
      ret[f] = fields[f]
    }

    // TODO: ViewBackreferenceField
    // get_db_tables(null, (table) => {
    // })

    let views = this.views()
    for (let view_id in views) {
      let view = views[view_id]

      if (view.class === 'Table') {
        for (let field_num in view.fields) {
          let field = JSON.parse(JSON.stringify(view.fields[field_num]))

          if (field.key === '__custom__' || field.format) {
            field.name = field.key === '__custom__'
              ? (field.title || '') + " (View: " + view.title + ")"
              : this.field(field.key).def.name + " (View: " + view.title + ")"

            field.id = '__custom:' + view_id + ':' + field_num + '__'
            ret[field.id] = new ViewField(field)
          }
        }
      }
    }

    callback(null, ret)
  }

  field (fieldId) {
    this.fields()

    if (fieldId in this._fields) {
      return this._fields[fieldId]
    }
  }

  views (type = 'list') { // type: 'list' or 'show'
    let views = {}

    if (this._data.views) {
      views = this._data.views
    }

    if (type == 'show') {
      views.json = {
        title: 'JSON',
        weight: 100,
        class: 'JSON'
      }
    }

    views = weight_sort(views, 'weight')

    return views
  }

  view_def (k, callback) {
    if (k == 'json') {
      return this.def((err, ret) => {
        if (err) { return callback(err) }

        callback(null, {
          title: 'JSON',
          class: 'JSON',
          weight: 100,
          fields: ret
        })
      })
    }

    if (!(k in this._data.views)) {
      alert('View does not exist!')
      return false
    }

    let ret = JSON.parse(JSON.stringify(this._data.views[k]))

    ret.fields = {}
    for (let i in this._data.views[k].fields) {
      let d = this._data.views[k].fields[i]
      let key = d.key
      let field

      if (key == '__custom__') {
        key = '__custom' + i + '__'
        field = new Fields.default(null, [], this)
      } else {
        field = this.field(d.key)
      }

      d.name = d.title || field.def.name

      if (!d.format) {
        d.format = field.view_def().format
      }

      if (!d.sortable) {
        d.sortable = key in this._data.fields ? this._data.fields[key].sortable : null
      }

      ret.fields[key] = d
    }

    callback(null, ret)
  }
}

function get_table (id, callback) {
  if (id in db_table_cache) {
    let table = db_table_cache[id]

    if (!table) {
      return callback(new Error('table ' + id + ' does not exist'), null)
    }

    if (table._load_callbacks === null) {
      return callback(null, table)
    }

    return table._load_callbacks.push(callback)
  }

  let table = new DB_Table(id)
  table._load_callbacks.push(callback)
  table._load()
}

function get_db_tables (query, callback) {
  if (db_table_complete) {
    return callback(null, Object.values(db_table_cache))
  }

  if (_load_tables_callbacks !== null) {
    return _load_tables_callbacks.push(callback)
  }

  _load_tables_callbacks = [callback]

  httpRequest('api.php?list=1&full=1', {}, (err, result) => {
    if (err) {
      return callback(err)
    }

    let data = JSON.parse(result.body)

    data.forEach(d => {
      if (!(d.id in db_table_cache)) {
        new DB_Table(d.id, d)
      } else if (db_table_cache[d.id]._load_callbacks !== null) {
        db_table_cache[d.id]._data = d
        db_table_cache[d.id]._call_load_callbacks(null)
      }
    })

    let r = Object.values(db_table_cache)
    _load_tables_callbacks.forEach(cb => cb(null, r))
    _load_tables_callbacks = null
    db_table_complete = true
  })
}

function get_table_list (callback) {
  get_db_tables(null, (err, tables) => {
    if (err) {
      return callback(err)
    }

    let result = {}

    tables.forEach(table => result[table.id] = table.name())

    callback(null, result)
  })
}

function get_loaded_entry_sync (table_id, id) {
  if (table_id in db_table_cache) {
    return db_table_cache[table_id].get_loaded_entry_sync(id)
  } else {
    if (!(table_id in missing_entries)) {
      missing_entries[table.id] = {}
    }
    missing_entries[table.id][id] = true
  }
}

function load_missing_entries (callback) {
  async.eachOf(missing_entries,
    (ids, table_id, done) => {
      get_table(table_id, (err, table) => {
        if (err) {
          return done(err)
        }

        table.get_entries_by_id(Object.keys(ids), done)
      })
    },
    (err) => {
      if (err) {
        return callback(err)
      }

      for (let k in missing_entries) {
        delete(missing_entries[k])
      }

      callback(null)
    }
  )
}

function has_missing_entries () {
  return !!Object.keys(missing_entries).length
}

function invalidate_entries (list) {
  list.forEach((entry) => {
    let [table_id, id] = entry
    if (table_id in db_table_cache) {
      delete db_table_cache[table_id].entries_cache[id]
    }
  })
}

module.exports = {
  get: get_table,
  get_all: get_db_tables,
  get_table_list,
  get_loaded_entry_sync,
  missing_entries,
  has_missing_entries,
  load_missing_entries,
  cache: db_table_cache,
  invalidate_entries
}
