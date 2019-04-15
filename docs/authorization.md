# Authorization

Glued has a RBAC authorization mechanism uses a single SQL query to determine if <SUBJECT> (user)
can perform an <ACTION> on an <OBJECT>.


## Database storage

### Roles

 * implemented as t_privileges.c_role
   * `user`
   * `self`
   * `group`
 * implemented only as a denormalized row privilege, not as t_privileges.c_role
   * `owner-user` - see [t_foo.c_owner](#privileges_denormalization)
   * `owner-group` - see [t_foo.c_group](#privileges_denormalization)
   *  `other` - see [t_foo.c_other](#privileges_denormalization)

### Privileges storage

- `t_action` (actions/api verbs definitions)
- `t_authentication` (stores user passwords and other access credentials)
  - :warning: c_uid, c_user_id, c_type, c_username, c_pasword, c_domain, c_limits, c_status)
- `t_implemented_action` (stores what actions are allowed in case of a status of an object)
- `t_privileges` (gives privileges)
- `t_users` (lists users)

### Privileges denormailzation

All data tables (with the exception of `t_action`) include the following mandatory columns:

```
create table t_foo (
    c_uid             int not null auto_increment primary key,
    c_owner           int not null default 1,
    c_group           int not null default 1,
    c_unixperms       int not null default 500,
    c_status          int not null default 0,
    -- data columns ...
);
```

which are used for basic authorization. While the data could be stored in the t_privileges table
this denormalization makes the t_privileges table lighter by orders of magnitude.

### Statuses

* implemented
  * `1: opened / closed` i.e. issues
  * `2: published / unpublished` i.e. articles, events, etc.
  * `4: available / unavailable` i.e. resource
* aproved
  * `8: enabled / disabled` [TODO], i.e. membership, form


### Actions

  * implemented
    * delete
    * list
    * read
    * write
  * considered
    * join (i.e. join a group)
    * manage (i.e. issue, group members, ...)
    * invite

### Privilege types

  * `object` applies on a single row in a given table
  * `global` applies on all rows in a given table
  * `table` applies on the table itself
  * `code` applies to user interface code (i.e. show/hide control elements, for example show a `sudo-like` link only to wheel group members)


### Database tables

#### *t_privileges

| column | value | description |
|--|--|--|
| `c_id` | 
| `c_role`
| `c_who`
| `c_action`
| `c_type`
| `c_neg` |<0,allow> <1,deny> | 
| `c_related_table`
| `c_related_uid`
| `c_protected` |<0,user_defined> <1,distributed_default>  | is `1` when privilege is a sane default that you don't want to mess with.

 * [TODO] add `c_domain`, put it into the queries too
   * Asi bude muset být ve všech tabulkách
   * Domain inharitance ... tedy, přihlásím-li se do industra domény, uvidím poddomény industra art, industra stage, ...
   * Asi tak, že do subdomen se nepujde prihlasit primo, ale budes muset nejdriv do industry, ze ktere dostanes prava na subdomenu?
   * By default nevidis subdomeny v domene
   * Co virtuální domény typu "všichni mí přátelé?"
   * Jak to budeme zapisovat?


**Terms**

 * **privilege:** a single row in the t_privileges table which applies
   * to a to

