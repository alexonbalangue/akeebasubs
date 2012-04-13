#!/bin/bash

#
# Internal linking with the language files
#
# -- Component
rm component/language/backend/en-GB
ln -s `pwd`/translations/component/backend/en-GB component/language/backend/en-GB
rm component/language/frontend/en-GB
ln -s `pwd`/translations/component/frontend/en-GB component/language/frontend/en-GB
# -- Everything else
php ./langlink.php

#
# Link with Live Update
#
rm component/backend/liveupdate/LICENSE.txt
ln -s `pwd`/../liveupdate/code/LICENSE.TXT component/backend/liveupdate/LICENSE.txt
rm component/backend/liveupdate/assets
ln -s `pwd`/../liveupdate/code/assets component/backend/liveupdate/assets
rm component/backend/liveupdate/classes
ln -s `pwd`/../liveupdate/code/classes component/backend/liveupdate/classes
rm component/backend/liveupdate/liveupdate.php
ln `pwd`/../liveupdate/code/liveupdate.php component/backend/liveupdate/liveupdate.php
rm component/backend/liveupdate/language/en-GB
ln -s `pwd`/../liveupdate/code/language/en-GB component/backend/liveupdate/language/en-GB

#
# Link with the OTP plugin
#
# -- Files
rm plugins/system/oneclickaction/LICENSE.txt
ln -s `pwd`/../liveupdate/plugins/system/oneclickaction/LICENSE.txt plugins/system/oneclickaction/LICENSE.txt
rm plugins/system/oneclickaction/oneclickaction.php
ln -s `pwd`/../liveupdate/plugins/system/oneclickaction/oneclickaction.php plugins/system/oneclickaction/oneclickaction.php
rm plugins/system/oneclickaction/oneclickaction.xml
ln -s `pwd`/../liveupdate/plugins/system/oneclickaction/oneclickaction.xml plugins/system/oneclickaction/oneclickaction.xml
# -- Link external translations to the language directory
rm translations/plugins/system/oneclickaction
ln -s `pwd`/../liveupdate/plugins/system/oneclickaction/language translations/plugins/system/oneclickaction
# -- Link from the language directory to the plugin directory
rm plugins/system/oneclickaction/language/en-GB
ln -s `pwd`/translations/plugins/system/oneclickaction/en-GB plugins/system/oneclickaction/language/en-GB

#
# Link with Framework-on-Framework
#
rm component/fof
ln -s `pwd`/../fof/fof component/fof
