<?php

include('rk/scripts/tools/common.php');
use rk\scripts\tools;

tools\common::checkLauncher();

exec('rm -rf cache/*');
