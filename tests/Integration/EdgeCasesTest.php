<?php

declare(strict_types=1);

/*
 * This file is part of the Neo4j PHP Client and Driver package.
 *
 * (c) Nagels <https://nagels.tech>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Laudis\Neo4j\Tests\Integration;

use Dotenv\Dotenv;
use Laudis\Neo4j\ClientBuilder;
use Laudis\Neo4j\Common\Uri;
use Laudis\Neo4j\Contracts\ClientInterface;
use Laudis\Neo4j\Databags\Statement;
use Laudis\Neo4j\Tests\Fixtures\MoviesFixture;
use Laudis\Neo4j\Types\Node;
use PHPUnit\Framework\TestCase;

final class EdgeCasesTest extends TestCase
{
    private ?ClientInterface $client = null;

    protected function setUp(): void
    {
        parent::setUp();
        if (($uri = $this->getBoltUri()) !== null) {
            $this->client = ClientBuilder::create()
                ->withDriver('neo4j', $uri)
                ->build();
        }
    }

    private function getBoltUri(): ?string
    {
        /** @var string|mixed $connections */
        $connections = $_ENV['NEO4J_CONNECTIONS'] ?? false;
        if (!is_string($connections)) {
            Dotenv::createImmutable(__DIR__ . '/../../')->load();
            /** @var string|mixed $connections */
            $connections = $_ENV['NEO4J_CONNECTIONS'] ?? false;
            if (!is_string($connections)) {
                $connections = 'bolt://neo4j:test@neo4j,neo4j://neo4j:test@core1,http://neo4j:test@neo4j';
            }
        }
        foreach (explode(',', $connections) as $uri) {
            $psrUri = Uri::create($uri);
            if ($psrUri->getScheme() === 'neo4j') {
                return $psrUri->__toString();
            }
        }

        return null;
    }

    public function testRunALotOfStatements(): void
    {
        if ($this->client === null) {
            self::markTestSkipped('No neo4j uri provided');
        }

        $this->client->run('MATCH (n) DETACH DELETE n');
        $this->client->run(MoviesFixture::CQL);

        $persons = $this->client->run('MATCH (p:Person) RETURN p');
        $movies = $this->client->run('MATCH (m:Movie) RETURN m');

        $personIds = [];
        foreach ($persons->toArray() as $record) {
            foreach ($record->keys() as $key) {
                /** @var Node $node */
                $node = $record->get($key);
                $personIds[] = $node->getId();
            }
        }

        $movieIds = [];
        foreach ($movies->toArray() as $record) {
            foreach ($record->keys() as $key) {
                /** @var Node $node */
                $node = $record->get($key);
                $movieIds[] = $node->getId();
            }
        }

        $statements = [];
        foreach ($personIds as $personId) {
            foreach ($movieIds as $movieId) {
                $statements[] = Statement::create(
                    'MATCH (a), (b) WHERE id(a) = $ida AND id(b) = $idb MERGE (a) <-[r:ACTED_IN]- (b) RETURN id(r)',
                    ['ida' => $personId, 'idb' => $movieId]
                );
            }
        }

        $this->client->runStatements($statements);
        self::assertCount(4978, $statements);
    }

    public function testGettingKeysFromArraylist(): void
    {
        if ($this->client === null) {
            self::markTestSkipped('No neo4j uri provided');
        }

        $this->client->run('MATCH (n) DETACH DELETE n');
        $this->client->run(MoviesFixture::CQL);

        $result = $this->client->run('MATCH (n:Person)-[r:ACTED_IN]->(m)
        RETURN n, {roles: r.roles, movie: m} AS actInfo LIMIT 1');

        $resultKeys = [];
        foreach ($result->toArray() as $record) {
            $keys = $record->keys();
            foreach ($keys as $key) {
                // Calling count inside foreach breaks it
                count($keys);
                $resultKeys[] = $key;
            }
        }

        self::assertCount(2, $resultKeys);
    }
}
