#!/bin/bash

NEW_ABBR="LDD_ORDERING_"
NEW_BASE="ldd-ordering"
NEW_CLASS="LDD_Ordering"
NEW_FILTER="${NEW_ABBR,,}"
NEW_KB_PATH=""
NEW_SITE=""
NEW_SLUG="${NEW_FILTER}"
NEW_SLUG_LONG="${NEW_BASE/-/_}"
NEW_TITLE="Legal Document Deliveries - Ordering"
NEW_TITLE_SHORT="LDD Ordering"

OLD_ABBR="LDD_DELIVERIES_"
OLD_BASE="ldd-deliveries"
OLD_CLASS="LDD_Deliveries"
OLD_FILTER="${OLD_ABBR,,}"
OLD_KB_PATH=""
OLD_SITE="http://aihr.us"
OLD_SLUG="${OLD_FILTER}"
OLD_SLUG_LONG="${OLD_BASE/-/_}"
OLD_TITLE="Legal Document Deliveries - Core"
OLD_TITLE_SHORT="LDD Deliveries"

echo
echo "Begin converting ${OLD_TITLE} to ${NEW_TITLE} plugin"

FILES=`find . -type f \( -name "*.css" -o -name "*.md" -o -name "*.php" -o -name "*.txt" -o -name "*.xml" \)`
for FILE in ${FILES} 
do
	if [[ '' != ${NEW_ABBR} ]]
	then
		perl -pi -e "s#${OLD_ABBR}#${NEW_ABBR}#g" ${FILE}
		perl -pi -e "s#${NEW_ABBR}_#${NEW_ABBR}#g" ${FILE}
	fi

	if [[ '' != ${NEW_BASE} ]]
	then
		perl -pi -e "s#${OLD_BASE}#${NEW_BASE}#g" ${FILE}
	fi

	if [[ '' != ${NEW_CLASS} ]]
	then
		perl -pi -e "s#${OLD_CLASS}#${NEW_CLASS}#g" ${FILE}
	fi

	if [[ '' != ${NEW_FILTER} ]]
	then
		perl -pi -e "s#${OLD_FILTER}#${NEW_FILTER}#g" ${FILE}
	fi

	if [[ '' != ${NEW_KB_PATH} ]]
	then
		perl -pi -e "s#${OLD_KB_PATH}#${NEW_KB_PATH}#g" ${FILE}
	fi

	if [[ '' != ${NEW_SITE} ]]
	then
		perl -pi -e "s#${OLD_SITE}#${NEW_SITE}#g" ${FILE}
	fi

	if [[ '' != ${NEW_SLUG} ]]
	then
		perl -pi -e "s#${OLD_SLUG}#${NEW_SLUG}#g" ${FILE}
		perl -pi -e "s#${NEW_SLUG}_#${NEW_SLUG}#g" ${FILE}
	fi

	if [[ '' != ${NEW_SLUG_LONG} ]]
	then
		perl -pi -e "s#${OLD_SLUG_LONG}#${NEW_SLUG_LONG}#g" ${FILE}
	fi

	if [[ '' != ${NEW_TITLE} ]]
	then
		perl -pi -e "s#${OLD_TITLE}#${NEW_TITLE}#g" ${FILE}
	fi

	if [[ '' != ${NEW_TITLE_SHORT} ]]
	then
		perl -pi -e "s#${OLD_TITLE_SHORT}#${NEW_TITLE_SHORT}#g" ${FILE}
	fi
done

if [[ -e 000-code-qa.txt ]]
then
	rm 000-code-qa.txt
fi

mv ${OLD_BASE}.php ${NEW_BASE}.php
mv assets/css/${OLD_BASE}.css assets/css/${NEW_BASE}.css
mv includes/class-${OLD_BASE}-settings.php includes/class-${NEW_BASE}-settings.php
mv includes/class-${OLD_BASE}-widget.php includes/class-${NEW_BASE}-widget.php
mv includes/class-${OLD_BASE}.php includes/class-${NEW_BASE}.php
mv languages/${OLD_BASE}.pot languages/${NEW_BASE}.pot

if [[ -e .git ]]
then
	rm -rf .git
fi

LIB_AIHRUS="includes/libraries/aihrus"
if [[ -e ${LIB_AIHRUS} ]]
then
	rm -rf ${LIB_AIHRUS}
fi

git init
git add *
git add .gitignore
git commit -m "Initial plugin creation"
git remote add origin git@github.com:michael-cannon/${NEW_BASE}.git
echo "git push origin master"