#!/usr/bin/env php
<?php
require __DIR__.'/vendor/autoload.php';

use D4rk0s\RewardsExplorer\Command\RewardsExplorer;
use D4rk0s\RewardsExplorer\Command\TernoaRewardsBot;
use Symfony\Component\Console\Application;

$application = new Application();

$application->add(new RewardsExplorer());
$application->add(new TernoaRewardsBot());

$application->run();