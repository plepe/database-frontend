class DB_TableExtract {
  constructor (table) {
    this.table = table
    this.filter = null
    this.sort = null
    this.ids = null
    this._ids = null
    this._get_ids_callbacks = []
  }

  set_sort (sort) {
    this.sort = sort
    this._ids = null
  }

  set_filter (filter) {
    this.filter = filter
    this._ids = null
  }

  set_ids (ids) {
    this.filter = null
    this.ids = ids
    this._ids = ids // TODO: set after sorting
  }

  count (callback) {
    // TODO
    this.table.get_entry_count(filter, callback)
  }

  get (offset, limit, callback) {
    if (this._ids) {
      let ids
      if (offset === null || offset === undefined) {
        offset = 0
      }
      if (limit === null || limit === undefined || limit === 0 || limit === '0') {
        ids = this._ids.slice(parseInt(offset))
      } else {
        ids = this._ids.slice(parseInt(offset), parseInt(offset) + parseInt(limit))
      }

      this.table.get_entries_by_id(ids, (err, result) => {
        if (err) {
          alert(err)
        }

        result = result.filter(entry => !!entry)

        // TODO: convert to (null, result) !!!
        callback(result)
      })
    } else {
      this.get_ids((err) => {
        if (err) {
          return callback(err)
        }

        this.get(offset, limit, callback)
      })
    }
  }

  get_ids (callback) {
    if (this.ids) {
      // TODO: sort list on server
      this._ids = this.ids
      return callback(null, this._ids)
    }

    this._get_ids_callbacks.push(callback)

    if (this._get_ids_callbacks.length > 1) {
      return
    }

    this.table.get_entry_ids(this.filter, this.sort, 0, null, (err, result) => {
      if (err) {
        return callback(err)
      }

      this._ids = result

      let callbacks = this._get_ids_callbacks
      this._get_ids_callbacks = []
      callbacks.forEach(cb => cb(null, result))
    })
  }

  pager_info (callback) {
    this.get_ids((err, list) => {
      if (err) {
        return callback(err)
      }

      let result = {
        result_count: list.length
      }

      callback(null, result)
    })
  }

  pager_info_show (id, callback) {
    this.get_ids((err, list) => {
      if (err) {
        return callback(err)
      }

      let index = list.indexOf(id)
      index = index === -1 ? null : index

      let result = {
        index,
        first: list[0],
        last: list[list.length - 1],
        result_count: list.length
      }

      if (index !== null) {
        result.prev = list[index - 1]
        result.next = list[index + 1]
      }

      callback(null, result)
    })
  }
}

module.exports = DB_TableExtract
