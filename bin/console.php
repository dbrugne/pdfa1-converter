#!/usr/bin/env php
<?php

set_time_limit(0);

$app = require_once __DIR__. '/../app/bootstrap.php';

use Eyefinity\Console\Test;

$application = $app['console'];
$application->add(new Test());
$application->run();