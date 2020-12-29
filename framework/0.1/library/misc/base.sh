#!/bin/bash

ROOT=$(dirname "$0");

echo '<?php' > "${ROOT}/base.php";
echo '// For IDEs' >> "${ROOT}/base.php";

grep --only-matching --no-filename -r -E '^\s*class +\S+_base' "${ROOT}/../../" | sed -E -e 's/^[[:space:]]*class (.*)_base/class \1 extends \1_base {}/' >> "${ROOT}/base.php";

echo -n '?>' >> "${ROOT}/base.php";
