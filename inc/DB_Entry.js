const forEach = require('foreach')

const httpRequest = require('./httpRequest')
const Changeset = require('./Changeset')

class DB_Entry {
  constructor (table, id, data) {
    this.table = table
    if (id) {
      this.table.entries_cache[id] = this
      this.id = id
      this._load_callbacks = []

      if (data) {
        this._data = data
        this._load_callbacks = null
      }
    } else {
      this.id = null
      this._load_callbacks = null
    }
  }

  title () {
    return this.table.title_template().render(this.view())
  }

  _load () {
    httpRequest(
      'api.php?table=' + encodeURIComponent(this.table.id) + '&id=' + encodeURIComponent(this.id),
      {
        responseType: 'json'
      },
      (err, result) => {
        if (err) {
          this.table.entries_cache[this.id] = null
          err = new Error('Error loading entry ' + this.table.id + '/' + this.id + ': ' + err)
          return callback(err)
        }

        this._data = result.body

        let load_callbacks = this._load_callbacks
        this._load_callbacks = null
        load_callbacks.forEach(cb => cb(err, this.table.entries_cache[this.id]))
      }
    )
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

  save (data, changeset, callback) {
    let url
    let method

    if (this.id === null) {
      url = 'api.php?table=' + encodeURIComponent(this.table.id)
      method = 'POST'
    } else {
      url = 'api.php?table=' + encodeURIComponent(this.table.id) + '&id=' + encodeURIComponent(this.id)
      method = 'PATCH'
    }

    if (!(changeset instanceof Changeset)) {
      changeset = new Changeset(changeset)
    }
    url = changeset.modify_url(url)

    httpRequest(url,
      {
        responseType: 'json',
        method,
        body: JSON.stringify(data)
      },
      (err, result) => {
        if (err) {
          return callback(new Error('entry updating object: ' + err))
        }

        DB_Table.invalidate_entries(this.referenced_entries())
        if (this.id !== null) {
          DB_Table.invalidate_entries([[this.table.id, this.id]])
        }

        this._data = result.body

        if ((this.id === null) || (this.id !== this._data.id)) {
          this.id = this._data.id
        }
        this.table.entries_cache[this.id] = this

        DB_Table.invalidate_entries(this.referenced_entries())

        return callback(null)
      }
    )
  }

  remove (data, changeset, callback) {
    httpRequest('api.php?table=' + encodeURIComponent(this.table.id) + '&id=' + encodeURIComponent(this.id),
      {
        method: 'DELETE'
      },
      (err) => {
        if (err) { return callback(err) }

        DB_Table.invalidate_entries(this.referenced_entries())
        DB_Table.invalidate_entries([[this.table.id, this.id]])

        callback(null)
      }
    )
  }

  /**
   * return a list of all referenced entries
   * @returns string[][] - Array of referenced entries where each entry is an array with [table_id, entry_id]
   */
  referenced_entries () {
    let fields = this.table.fields()
    let result = []

    forEach(fields, (field) => {
      if (this._data[field.id] && field.def.backreference) {
        let ref_table = field.def.backreference.split(/:/)[0]
        if (field.is_multiple()) {
          forEach(this._data[field.id], (d) => {
            result.push([ref_table, d])
          })
        } else {
          result.push([ref_table, this._data[field.id]])
        }
      } else if (this._data[field.id] && field.def.reference) {
        let ref_table = field.def.reference
        if (field.is_multiple()) {
          forEach(this._data[field.id], (d) => {
            result.push([ref_table, d])
          })
        } else {
          result.push([ref_table, this._data[field.id]])
        }
      }
    })

    return result
  }
}

module.exports = DB_Entry
