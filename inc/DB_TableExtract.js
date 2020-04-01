class DB_TableExtract {
  constructor (table) {
    this.table = table
    this.filter = null
    this.sort = null
    this.ids = null
  }

  set_sort (sort) {
    this.sort = sort
  }

  set_filter (filter) {
    this.filter = filter
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
      this.table.get_entries_by_id(this.ids, (err, result) => {
        if (err) {
          return alert(err)
        }

        callback(result)
      })
    } else {
      this.table.get_entries(this.filter, this.sort, offset, limit, (err, result) => {
        if (err) {
          return alert(err)
        }

        callback(result)
      })
    }
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
