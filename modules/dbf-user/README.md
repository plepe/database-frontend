Module 'dbf-user' adds a Field type which references a user, saved as text value with 'user@domain'.

When saving a field of type text, the value '@current@' will be overwritten by the id of the current logged in user.

== Twig filters ==
The module defines the following twig filters:

* `{{ value|user_username }}`: Username of the user specified in 'value'
* `{{ value|user_domain }}`: Domain of the user specified in 'value'
