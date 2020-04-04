class DB_TableExtract {
  constructor (table) {
    this.table = table
    this.filter = null
    this.sort = null
    this.ids = null
  }

  set_sort (sort) {
    this.sort = sort
    this.ids = null
  }

  set_filter (filter) {
    this.filter = filter
    this.ids = null
  }

  set_ids (ids) {
    this.filter = null
    this.ids = ids
  }

  count (callback) {
    // TODO
    this.table.get_entry_count(filter, callback)
  }

  get (offset, limit, callback) {
    if (this.ids) {
      let ids
      if (offset === null || offset === undefined) {
        offset = 0
      }
      if (limit === null || limit === undefined) {
        ids = this.ids.slice(offset)
      } else {
        ids = this.ids.slice(offset, offset + limit)
      }

      this.table.get_entries_by_id(ids, (err, result) => {
        if (err) {
          alert(err)
        }

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
      return callback(null, this.ids)
    }

    this.table.get_entry_ids(this.filter, this.sort, 0, null, (err, result) => {
      if (err) {
        return callback(err)
      }

      this.ids = result

      callback(null, result)
    })
  }

  pager_info (callback) {
    this.table.get_entry_ids(this.filter, this.sort, null, null, (err, list) => {
      if (err) {
        return callback(err)
      }

      let result = {}

      result.result_count = list.length

      callback(null, result)
    })
  }
}

module.exports = DB_TableExtract
