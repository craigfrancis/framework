#!/bin/sh

#--------------------------------------------------
# Project name
#--------------------------------------------------

	if [ -z $1 ]; then
		NAME=`pwd | awk '{ sub("^/Volumes/WebServer/Projects/", ""); sub("/.*$", ""); print }'`
	else
		NAME=`echo $1 | awk '{ sub("^[\.\/]*", ""); sub("[\.\/]*$", ""); print }'`
	fi

	if [ -z $NAME ]; then
		echo Module Not Specified;
	fi

	OLD_PATH="/Volumes/WebServer/Projects/$NAME";
	NEW_PATH="/Volumes/WebServer/Projects/$NAME.prime";

#--------------------------------------------------
# Init
#--------------------------------------------------

	rm -rf "$NEW_PATH";
	cp -r -p "/Volumes/WebServer/Projects/craig.framework.base/" "$NEW_PATH";

#--------------------------------------------------
# View
#--------------------------------------------------

	rm -rf "$NEW_PATH/app/view/";
	cp -r "$OLD_PATH/" "$NEW_PATH/app/view/";

#--------------------------------------------------
# Controllers
#--------------------------------------------------

	rm -rf "$NEW_PATH/app/controller/";
	mv "$NEW_PATH/app/view/a/inc/" "$NEW_PATH/app/controller/";

	for F in `find "$NEW_PATH/app/controller/" -name "main.php"`; do

		NEW=`echo $F | awk '{ sub("main.php$", "index.php"); print }'`;

		mv "$F" "$NEW";

		php "$NEW_PATH/init.php" "$NEW";

	done

#--------------------------------------------------
# System file
#--------------------------------------------------

	if [ -f "$NEW_PATH/app/view/a/php/system.php" ]; then
		mv "$NEW_PATH/app/view/a/php/system.php" "$NEW_PATH/app/support/core/main.php";
	fi

#--------------------------------------------------
# Layout
#--------------------------------------------------

	LAYOUT_PATH="$NEW_PATH/app/layouts/default.ctp";
	LAYOUT_TOP_PATH="$NEW_PATH/app/controller/pageTop.php";
	LAYOUT_BOTOM_PATH="$NEW_PATH/app/controller/pageBottom.php";

	if [ ! -f "$LAYOUT_TOP_PATH" ]; then
		LAYOUT_TOP_PATH="$NEW_PATH/app/controller/global/pageTop.php";
		LAYOUT_BOTOM_PATH="$NEW_PATH/app/controller/global/pageBottom.php";
	fi

	if [ -f "$LAYOUT_TOP_PATH" ]; then

		mv "$LAYOUT_TOP_PATH" "$LAYOUT_PATH";
		echo "" >> "$LAYOUT_PATH";
		echo "	<?= \$this->message_get_html() ?>" >> "$LAYOUT_PATH";
		echo "" >> "$LAYOUT_PATH";
		echo "	<?= \$this->view_get_html() ?>" >> "$LAYOUT_PATH";
		echo "" >> "$LAYOUT_PATH";
		cat "$LAYOUT_BOTOM_PATH" >> "$LAYOUT_PATH";
		rm "$LAYOUT_BOTOM_PATH";

		php "$NEW_PATH/init.php" "$LAYOUT_PATH";

	fi

#--------------------------------------------------
# Assets
#--------------------------------------------------

	rm -rf "$NEW_PATH/app/public/a/css/";
	mv "$NEW_PATH/app/view/a/css/" "$NEW_PATH/app/public/a/css/";

	rm -rf "$NEW_PATH/app/public/a/img/";
	mv "$NEW_PATH/app/view/a/img/" "$NEW_PATH/app/public/a/img/";

	rm -rf "$NEW_PATH/app/public/a/js/";
	mv "$NEW_PATH/app/view/a/js/" "$NEW_PATH/app/public/a/js/";

#--------------------------------------------------
# View extensions
#--------------------------------------------------

	mv "$NEW_PATH/app/view/index.php" "$NEW_PATH/app/view/home.ctp";
	php "$NEW_PATH/init.php" "$NEW_PATH/app/view/home.ctp";

	for F in `find "$NEW_PATH/app/view/" -name "index.php"`; do

		NEW=`echo $F | awk '{ sub("/index.php$", ".ctp"); print }'`;

		mv "$F" "$NEW";

		php "$NEW_PATH/init.php" "$NEW";

	done

#--------------------------------------------------
# Config
#--------------------------------------------------

	if [ -f "$NEW_PATH/app/view/a/php/config.new.php" ]; then

		mv "$NEW_PATH/app/view/a/php/config.new.php" "$NEW_PATH/app/support/core/config.php";

	fi

#--------------------------------------------------
# Cleanup
#--------------------------------------------------

	rm "$NEW_PATH/init.sh";
	rm "$NEW_PATH/init.php";

	for F in `find "$NEW_PATH/app/view/" -type d ! -ipath "*.svn*"`; do
		S=`ls "$F" | wc -l`;
		if [ $S -eq 0 ]; then
			rm -rf "$F";
		fi;
	done
