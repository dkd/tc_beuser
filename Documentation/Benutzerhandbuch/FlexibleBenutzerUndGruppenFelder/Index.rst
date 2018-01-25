.. include:: Images.txt

.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. ==================================================
.. DEFINE SOME TEXTROLES
.. --------------------------------------------------
.. role::   underline
.. role::   typoscript(code)
.. role::   ts(typoscript)
   :class:  typoscript
.. role::   php(code)


Flexible Benutzer und Gruppen Felder
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Über Ausschlussfelder lassen sich die Felder bestimmen, welche über
das SubModul „Benutzer-Admin“ und „Gruppen-Admin“ editiert werden
können.

Um z.B. die Standardfelder (disable,username,password,usergroup,realName,
email,lang,name,first_name,last_name) beim  „Benutzer-Admin“ um DB-Mount
und File-Mount zu erweitern müssen die Standardfelder sowie db_mountpoints
und file_mountpoints in den Ausschlussfeldern ausgewählt werden.

|img-3|

