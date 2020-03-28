## GET ?table=x

Returns the configuration of the table as JSON object

## GET ?table=x&list=1

Returns a JSON array with all entry ids.

## GET ?table=x&id=y

Returns the specified object as JSON

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
