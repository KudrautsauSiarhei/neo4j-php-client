<?php

/*
 * This file is part of the Neo4j PHP Client and Driver package.
 *
 * (c) Nagels <https://nagels.tech>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Dotenv\Dotenv;

require __DIR__.'/../vendor/autoload.php';

/** @psalm-suppress UncaughtThrowInGlobalScope */
Dotenv::createMutable(__DIR__.'/../')->load();
