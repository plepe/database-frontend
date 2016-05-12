data.php - Entry point for JSON requests

?table=TABLE&id=foo
-------------------
Return data of object foo

?table=TABLE&ids=foo,bar
------------------------
Return data of objects foo and bar. `ids` may also be an array (ids[]=foo&ids[]=bar).

?table=TABLE
------------
Return definition of table, incl. total count of elements

?table=TABLE&filter=&offset=0&count=25
--------------------------------------
Return total count of elements and first 25 (or so) ids

(offset and count are optional)

?table=TABLE&filter[]=year,=,2015&offset=0&count=25
---------------------------------------------------
Return all elements which match the query year=2015 and the first 25 ids

(offset and count are optional)
