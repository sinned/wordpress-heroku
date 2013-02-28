<?php
echo "<pre>";

echo "hello!\n";
//echo getenv("CLEARDB_DATABASE_URL");

$host = "us-cdbr-east-02.cleardb.com";

$pingTime = shell_exec('ping -q -c1 ' . $host . ' | grep "packets transmitted" | sed s/"^[[:print:]]* time \([0-9]*\)ms$"/\\\\1/g');

echo "ping time:" . $pingTime . "ms\n";

echo "Full ping output:" . shell_exec('ping -q -c1 ' . $host) . "\n";

echo "</pre>";