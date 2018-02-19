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


Flexible Benutzer- und Gruppenfelder
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Über die Gruppeneinstellungen des aktuellen Users lassen sich die
Felder bestimmen, welche über die Module „Benutzer-Admin“ und
„Gruppen-Admin“ editiert werden können.

Die Extension stellt beim Bearbeiten einer Backend-Benutzergruppe unter
dem Feld "Erlaubte Ausschlussfelder" Einstellungsmöglichkeiten für
"Backend-Benutzer" und "Backend-Benutzergruppe" zur Verfügung. Die hier
ausgewählten Felder stehen den Benutzern der Gruppe als Felder in den
"TC Tools"-Modulen zur Verfügung.

Um z.B. die Standardfelder (Inaktiv, Benutzername, Passwort, Gruppe,
Name, E-Mail, Standardsprache) beim „Benutzer-Admin“ um
Datenbankfreigaben (db_mountpoints) und Dateifreigaben (file_mountpoints)
zu erweitern, müssen die Standardfelder, sowie die Felder
Datenbankfreigaben und Dateifreigaben unter dem Punkt
"Erlaubte Ausschlussfelder" für Backend-Benutzer ausgewählt werden.

|img-3|

