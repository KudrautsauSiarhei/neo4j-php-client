<?php

declare(strict_types=1);

/*
 * This file is part of the Laudis Neo4j package.
 *
 * (c) Laudis technologies <http://laudis.tech>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Laudis\Neo4j;

use function in_array;
use function is_string;
use Laudis\Neo4j\Bolt\BoltDriver;
use Laudis\Neo4j\Common\Uri;
use Laudis\Neo4j\Contracts\AuthenticateInterface;
use Laudis\Neo4j\Contracts\DriverInterface;
use Laudis\Neo4j\Contracts\FormatterInterface;
use Laudis\Neo4j\Databags\DriverConfiguration;
use Laudis\Neo4j\Http\HttpDriver;
use Laudis\Neo4j\Neo4j\Neo4jDriver;
use Psr\Http\Message\UriInterface;

/**
 * @psalm-import-type OGMResults from \Laudis\Neo4j\Formatter\OGMFormatter
 */
final class DriverFactory
{
    /**
     * @template U
     *
     * @param FormatterInterface<U> $formatter
     * @param string|UriInterface   $uri
     *
     * @return (
     *           func_num_args() is 5
     *           ? DriverInterface<U>
     *           : DriverInterface<OGMResults>
     *           )
     */
    public static function create($uri, ?DriverConfiguration $configuration = null, ?AuthenticateInterface $authenticate = null, ?float $socketTimeout = null, FormatterInterface $formatter = null): DriverInterface
    {
        if (is_string($uri)) {
            $uri = Uri::create($uri);
        }
        $scheme = $uri->getScheme();
        $scheme = $scheme === '' ? 'bolt' : $scheme;

        if (in_array($scheme, ['bolt', 'bolt+s', 'bolt+ssc'])) {
            return self::createBoltDriver($uri, $configuration, $authenticate, $socketTimeout, $formatter);
        }

        if (in_array($scheme, ['neo4j', 'neo4j+s', 'neo4j+ssc'])) {
            return self::createNeo4jDriver($uri, $configuration, $authenticate, $socketTimeout, $formatter);
        }

        return self::createHttpDriver($uri, $configuration, $authenticate, $formatter);
    }

    /**
     * @template U
     *
     * @param FormatterInterface<U> $formatter
     * @param string|UriInterface   $uri
     *
     * @return (
     *           func_num_args() is 5
     *           ? DriverInterface<U>
     *           : DriverInterface<OGMResults>
     *           )
     * @psalm-mutation-free
     */
    private static function createBoltDriver($uri, ?DriverConfiguration $configuration, ?AuthenticateInterface $authenticate, ?float $socketTimeout, FormatterInterface $formatter = null): DriverInterface
    {
        if ($formatter !== null) {
            return BoltDriver::create($uri, $configuration, $authenticate, $socketTimeout, $formatter);
        }

        return BoltDriver::create($uri, $configuration, $authenticate, $socketTimeout);
    }

    /**
     * @template U
     *
     * @param FormatterInterface<U> $formatter
     * @param string|UriInterface   $uri
     *
     * @return (
     *           func_num_args() is 5
     *           ? DriverInterface<U>
     *           : DriverInterface<OGMResults>
     *           )
     * @psalm-mutation-free
     */
    private static function createNeo4jDriver($uri, ?DriverConfiguration $configuration, ?AuthenticateInterface $authenticate, ?float $socketTimeout = null, FormatterInterface $formatter = null): DriverInterface
    {
        if ($formatter !== null) {
            return Neo4jDriver::create($uri, $configuration, $authenticate, $socketTimeout, $formatter);
        }

        return Neo4jDriver::create($uri, $configuration, $authenticate, $socketTimeout);
    }

    /**
     * @template U
     *
     * @param FormatterInterface<U> $formatter
     * @param string|UriInterface   $uri
     *
     * @return (
     *           func_num_args() is 4
     *           ? DriverInterface<U>
     *           : DriverInterface<OGMResults>
     *           )
     * @psalm-mutation-free
     */
    private static function createHttpDriver($uri, ?DriverConfiguration $configuration, ?AuthenticateInterface $authenticate, FormatterInterface $formatter = null): DriverInterface
    {
        if ($formatter !== null) {
            return HttpDriver::create($uri, $configuration, $authenticate, $formatter);
        }

        return HttpDriver::create($uri, $configuration, $authenticate);
    }
}
