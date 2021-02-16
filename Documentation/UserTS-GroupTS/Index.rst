.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


.. _userts-groupts:

UserTS und GroupTS
==================

Die Felder bei der Bearbeitung vom Benutzer bzw. Gruppen
können per UserTS oder GroupTS ausgeblendet werden.
Folgende TSConfig können in UserTS oder GroupTS verwendet
werden.

.. t3-field-list-table::
 :header-rows: 1

 - :Property,20:    Option
   :Description,40: Beschreibung

 - :Property:    hideColumnGroup
   :Description: entfernt aufgelistete Felder. Komma separiert
                 mögliche Werte:

                 - hidden
                 - title
                 - db_mountpoints
                 - file_mountpoints
                 - subgroup
                 - members
                 - description
                 - TSconfig

 - :Property:    hideColumnUser
   :Description: entfernt aufgelistete Felder. Komma separiert
                 mögliche Werte:

                 - disable
                 - username
                 - password
                 - usergroup
                 - realName
                 - email
                 - email
                 - lang

 - :Property:    passwordWizard
   :Description: wenn auf 0 gesetzt, dann ist das Passwort-Wizard
                 ausgeblendet.


Bsp:

.. code-block:: typoscript

   tc_beuser {
     // hide TSconfig from Group
     hideColumnGroup = TSconfig

     // hide email from User
     hideColumnUser = email
   }
