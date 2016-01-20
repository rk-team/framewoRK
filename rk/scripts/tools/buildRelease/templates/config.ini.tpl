[project]
default_application = front
languages = [fr, en]
default_language = fr
dbg_IPS = [127.0.0.1]
dev_IPS = [127.0.0.1]

[db]
default.type=mysql
default.host=localhost
default.user=rk
default.password=rk
default.database=rk

default.behaviours.creationDate.requires_field=date_added
default.behaviours.updateDate.requires_field=date_updated
