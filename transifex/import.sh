#!/bin/bash

#
# Generates translation files from transifex outputs
#

if [ -z "${1}" ]; then
	echo "Usage: import.sh <ownnote folder> <translation folder>";
	exit;
fi;
if [ -z "${2}" ]; then
	echo "Usage: import.sh <ownnote folder> <translation folder>";
	exit;
fi;

cd "${2}/ownnote.server"
LANGUAGES=$(ls *.txt | sed -e 's/\..*$//')
cd "${1}"


# PHP include
echo "The following should be used in templates/main.php:"
echo ""
paste "${1}/transifex/server.txt" "${1}/transifex/server.txt" | sed 's/"/\\"/g' | sed 's/\t/"] = "<?php p(\$l->t("/g' | sed 's/^/l10n["/g' | sed 's/$/")); ?>";/g'
#paste "${1}/transifex/serveradmin.txt" "${1}/transifex/serveradmin.txt" | sed 's/"/\\"/g' | sed 's/\t/"] = "<?php p(\$l->t("/g' | sed 's/^/l10n["/g' | sed 's/$/")); ?>";/g'

# JS file
echo -n "" > l10n/en.js
echo "OC.L10N.register(" >> l10n/en.js
echo "\"ownnote\"," >> l10n/en.js
echo "{" >> l10n/en.js
paste "${1}/transifex/server.txt" "${1}/transifex/server.txt" | sed 's/"/\\"/g' | sed 's/\t/" : "/g' | sed 's/^/"/g' | sed 's/$/",/g' >> l10n/en.js
paste "${1}/transifex/serveradmin.txt" "${1}/transifex/serveradmin.txt" | sed 's/"/\\"/g' | sed 's/\t/" : "/g' | sed 's/^/"/g' | sed 's/$/",/g' >> l10n/en.js
sed -i '$s/,$//' l10n/en.js
echo "}," >> l10n/en.js
echo "\"nplurals=2; plural=(n > 1);\");" >> l10n/en.js

# JSON file
echo -n "" > l10n/en.json
echo "{ \"translations\": {" >> l10n/en.json
paste "${1}/transifex/server.txt" "${1}/transifex/server.txt" | sed 's/"/\\"/g' | sed 's/\t/" : "/g' | sed 's/^/"/g' | sed 's/$/",/g' >> l10n/en.json
paste "${1}/transifex/serveradmin.txt" "${1}/transifex/serveradmin.txt" | sed 's/"/\\"/g' | sed 's/\t/" : "/g' | sed 's/^/"/g' | sed 's/$/",/g' >> l10n/en.json
sed -i '$s/,$//' l10n/en.json
echo "},\"pluralForm\" :\"nplurals=2; plural=(n > 1);\"" >> l10n/en.json
echo "}" >> l10n/en.json

# PHP file
echo -n "" > l10n/en.php
echo "<?php" >> l10n/en.php
echo "\$TRANSLATIONS = array(" >> l10n/en.php
paste "${1}/transifex/server.txt" "${1}/transifex/server.txt" | sed 's/"/\\"/g' | sed 's/\t/" => "/g' | sed 's/^/"/g' | sed 's/$/",/g' >> l10n/en.php
paste "${1}/transifex/serveradmin.txt" "${1}/transifex/serveradmin.txt" | sed 's/"/\\"/g' | sed 's/\t/" => "/g' | sed 's/^/"/g' | sed 's/$/",/g' >> l10n/en.php
sed -i '$s/,$//' l10n/en.php
echo ");" >> l10n/en.php
echo "\$PLURAL_FORMS = \"nplurals=2; plural=(n > 1);\";" >> l10n/en.php

for L in ${LANGUAGES}; do
	# Fix pounds
	sed -i 's/＃/#/g' "${2}/ownnote.server/${L}.txt"
	sed -i 's/＃/#/g' "${2}/ownnote.serveradmin/${L}.txt"

	# JS file
	echo -n "" > l10n/${L}.js
	echo "OC.L10N.register(" >> l10n/${L}.js
    	echo "\"ownnote\"," >> l10n/${L}.js
	echo "{" >> l10n/${L}.js
	paste "${1}/transifex/server.txt" "${2}/ownnote.server/${L}.txt" | sed 's/"/\\"/g' | sed 's/\t/" : "/g' | sed 's/^/"/g' | sed 's/$/",/g' >> l10n/${L}.js
	paste "${1}/transifex/serveradmin.txt" "${2}/ownnote.serveradmin/${L}.txt" | sed 's/"/\\"/g' | sed 's/\t/" : "/g' | sed 's/^/"/g' | sed 's/$/",/g' >> l10n/${L}.js
	sed -i '$s/,$//' l10n/${L}.js
	echo "}," >> l10n/${L}.js
	echo "\"nplurals=2; plural=(n > 1);\");" >> l10n/${L}.js

	# JSON file
	echo -n "" > l10n/${L}.json
	echo "{ \"translations\": {" >> l10n/${L}.json
	paste "${1}/transifex/server.txt" "${2}/ownnote.server/${L}.txt" | sed 's/"/\\"/g' | sed 's/\t/" : "/g' | sed 's/^/"/g' | sed 's/$/",/g' >> l10n/${L}.json
	paste "${1}/transifex/serveradmin.txt" "${2}/ownnote.serveradmin/${L}.txt" | sed 's/"/\\"/g' | sed 's/\t/" : "/g' | sed 's/^/"/g' | sed 's/$/",/g' >> l10n/${L}.json
	sed -i '$s/,$//' l10n/${L}.json
	echo "},\"pluralForm\" :\"nplurals=2; plural=(n > 1);\"" >> l10n/${L}.json
	echo "}" >> l10n/${L}.json

	# PHP file
	echo -n "" > l10n/${L}.php
	echo "<?php" >> l10n/${L}.php
	echo "\$TRANSLATIONS = array(" >> l10n/${L}.php
	paste "${1}/transifex/server.txt" "${2}/ownnote.server/${L}.txt" | sed 's/"/\\"/g' | sed 's/\t/" => "/g' | sed 's/^/"/g' | sed 's/$/",/g' >> l10n/${L}.php
	paste "${1}/transifex/serveradmin.txt" "${2}/ownnote.serveradmin/${L}.txt" | sed 's/"/\\"/g' | sed 's/\t/" => "/g' | sed 's/^/"/g' | sed 's/$/",/g' >> l10n/${L}.php
	sed -i '$s/,$//' l10n/${L}.php
	echo ");" >> l10n/${L}.php
	echo "\$PLURAL_FORMS = \"nplurals=2; plural=(n > 1);\";" >> l10n/${L}.php
done;
