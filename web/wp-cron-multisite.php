<?php
$output = shell_exec("wp cron event run --due-now --url='https://jotwpublic.prod.wp.dsd.io/playground/'");
$output = shell_exec("wp cron event run --due-now --url='https://magistrates.judiciary.uk'");
$output = shell_exec("wp cron event run --due-now --url='https://ccrc.gov.uk'");
$output = shell_exec("wp cron event run --due-now --url='https://victimscommissioner.org.uk'");
$output = shell_exec("wp cron event run --due-now --url='https://imb.org.uk'");
$output = shell_exec("wp cron event run --due-now --url='https://publicdefenderservice.org.uk'");
