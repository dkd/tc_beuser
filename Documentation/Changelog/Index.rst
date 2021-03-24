.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


.. _changelog:

ChangeLog
=========

2021-03-24 Jan Haffner <jan.haffner@dkd.de>
-----------------------------------------------------

* compatible with TYPO3 CMS 10.4 LTS only

2018-03-28 Markus Friedrich <markus.friedrich@dkd.de>
-----------------------------------------------------

* compatible with TYPO3 CMS 8.7 LTS only

2016-07-19 Ivan Kartolo <ivan.kartolo@dkd.de>
---------------------------------------------

* Fix composer formatting

2016-07-01 Ivan Kartolo <ivan.kartolo@dkd.de>
---------------------------------------------

* rewriting modules.
* compatible only with TYPO3 CMS 7.6 LTS
* sponsored by RRZN Uni Hannover

2014-10-06 Ivan Kartolo <ivan.kartolo@dkd.de>
---------------------------------------------

* compatible only with TYPO3 6.2
* introducing namespacing
* doubling Access module for non admin User (thanks to RRZN Hannover for sponsoring)

2014-06-27 Ivan Kartolo <ivan@kartolo.de>
-----------------------------------------

* Task #59765: 6.2 LTS compatibility, thanks to Nicole Cordes

2013-06-20 Ivan Kartolo <ivan.kartolo@dkd.de>
---------------------------------------------

* Bug #49283: compatibility with 6.0

2012-06-24 Ivan Kartolo <ivan.kartolo@dkd.de>
---------------------------------------------

* Bug #37394: remove deprecated view_array calls
* Bug #31855: hide _cli User from non admin user

2007-09-05 Ingo Renner <ingo.renner@dkd.de>
-------------------------------------------

* class.tx_tcbeuser_editform.php could not get loaded in mod5

2007-05-04 Ingo Renner <ingo.renner@dkd.de>
-------------------------------------------

* fixed bug with missing hook class when in FE edit mode

2007-02-26 Ingo Renner <ingo.renner@dkd.de>
-------------------------------------------

* class.tx_tcbeuser_editform.php could not get loaded in mod3

2007-01-26 Ingo Renner <ingo.renner@dkd.de>
-------------------------------------------

* non admin user can't see admin users anymore

2006-11-03 Thorsten Kahler <thorsten.kahler@dkd.de>
---------------------------------------------------

* tx_tcbeuser_overview::makeUserControl(): su-Links/-Buttons nur anzeigen, wenn BE_USER Admin ist
* tx_tcbeuser_module4::makeUserControl(): su-Links/-Buttons nur anzeigen, wenn BE_USER Admin ist

06-10-10 Ivan Kartolo <ivan.kartolo@dkd.de>
-------------------------------------------

* add, list and edit wizards are not shown
* Status is set to stable and version is set to 1.0.0

06-09-26 Ivan Kartolo <ivan.kartolo@dkd.de>
-------------------------------------------

* Checkboxes in overview are not shown, as long as no user or group is chosen
* If user is hidden, switch icon is not linked.

06-08-29 Ivan Kartolo <ivan.kartolo@dkd.de>
-------------------------------------------

* User Configuration per TS

06-08-24 Olivier Dobberkau <olivier.dobberkau@dkd.de>
-----------------------------------------------------

* First Beta Version for Client due on 06-08-25

06-07-28 Ingo Renner  <ingo.renner@dkd.de>
------------------------------------------

* Initial release
