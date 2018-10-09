#!/usr/bin/env bash
/usr/local/php-cgi/7.2/bin/php -ddisplay_startup_errors=off -ddisplay_errors=off -dhtml_errors=off -dlog_errors=on -dignore_repeated_errors=off -dignore_repeated_source=off -dreport_memleaks=on -dtrack_errors=on -ddocref_root=0 -ddocref_ext=0 -derror_reporting=2047 -dlog_errors_max_len=0 -derror_log=PHP_errors.log index.php
