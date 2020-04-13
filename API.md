## GET ?list=1

Returns a JSON array with all table ids

## GET ?list=1&full=1

Returns a JSON array with all tables as JSON objects

## GET ?table=x

Returns the configuration of the table as JSON object

## GET ?table=x&list=1

Returns a JSON array with all entry ids.

## GET ?table=x&list=1&filter[0][field]=id&filter[0][op]=is&filter[0][value]=foo

Returns a JSON array with all entry ids matching the supplied filter(s).

`filter` is an array. each entry has field, op and value.

The following `op`s are available (depending on data type):
* contains (use mysql' like)
* is (exact match)
* >, >=, <, <=

## GET ?table=x&list=1&full=1

Returns a JSON array with all entry objects.

## GET ?table=x&id=y

Returns the specified object as JSON

## GET ?table=x&id[]=y&id[]=z

Returns a JSON array with all the specified objects. If an object does not exist, it will return `null` at that position.

## PATCH ?table=x

Update the configuration of the table. In PATCH data supply JSON hash array
(data) with key/values to update. if a key does not exist in data, it will not
be modified in the database.

Returns the updated table as JSON

## PATCH ?table=x&id=y

Update the entry. In PATCH data supply JSON hash array (data) with key/values
to update. if a key does not exist in data, it will not be modified in the
database.

Returns the updated object as JSON

## POST ?table=x

Create a new entry in table x. In POST data supply JSON data.

Returns the created object as JSON

## DELETE ?table=x

Delete the table.

## DELETE ?table=x&id=y

Delete the entry with the id y in table x.

## POST ?script=1

Execute a series of changes in a single changeset. Each change has an action ('select', 'create', 'update', 'delete' or 'query_ids'). Each change can reference the values of a previous change (e.g. in the example below, the 1st value of the array `tags` will get the `id` of the result of the 1st change).

The result of a 'select', 'create' or 'update' change will always be the full object. A 'query_ids' statement requires a table and optionally filter and sort.

```json
[
  {
    "action": "create",
    "table": "x",
    "data": {
      "foo": "bar"
    }
  },
  {
    "action": "update",
    "table": "x",
    "id": "y",
    "data": {
      "bar": "foo"
    },
    "references": {
      "tags.0": "0.id"
    }
  },
  {
    "action": "query_ids",
    "table": "x",
    "filter": [["date",">","2018"]],
  }
]
```
