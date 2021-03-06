<?php namespace DCarbone\PHPConsulAPITests\Usage\KV;

/*
   Copyright 2016-2017 Daniel Carbone (daniel.p.carbone@gmail.com)

   Licensed under the Apache License, Version 2.0 (the "License");
   you may not use this file except in compliance with the License.
   You may obtain a copy of the License at

       http://www.apache.org/licenses/LICENSE-2.0

   Unless required by applicable law or agreed to in writing, software
   distributed under the License is distributed on an "AS IS" BASIS,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
   See the License for the specific language governing permissions and
   limitations under the License.
*/

use DCarbone\PHPConsulAPI\Config;
use DCarbone\PHPConsulAPI\KV\KVClient;
use DCarbone\PHPConsulAPI\KV\KVPair;
use DCarbone\PHPConsulAPI\QueryMeta;
use DCarbone\PHPConsulAPI\WriteMeta;

/**
 * Class KVClientUsageTest
 * @package DCarbone\PHPConsulAPITests\Usage\KV
 */
class KVClientUsageTest extends \PHPUnit_Framework_TestCase {
    /**
     * @return KVClient
     */
    public function testCanConstructClient() {
        $kv = new KVClient(Config::newDefaultConfig());
        $this->assertInstanceOf(KVClient::class, $kv);
        return $kv;
    }

    /**
     * @depends testCanConstructClient
     * @param KVClient $client
     */
    public function testKVLifecycle(KVClient $client) {
        $kvp = new KVPair(['Key' => 'testkey', 'Value' => 'testvalue']);

        list($wm, $err) = $client->put($kvp);;
        $this->assertNull($err, sprintf('Unable to set kvp: %s', (string)$err));
        $this->assertInstanceOf(WriteMeta::class, $wm);

        list($kvp, $qm, $err) = $client->get('testkey');
        $this->assertNull($err, sprintf('Unable to get kvp: %s', (string)$err));
        $this->assertInstanceOf(QueryMeta::class, $qm);
        $this->assertInstanceOf(KVPair::class, $kvp);
        $this->assertEquals('testvalue', $kvp->Value);

        list($wm, $err) = $client->delete('testkey');
        $this->assertNull($err, sprintf('Unable to delete kvp: %s', (string)$err));
        $this->assertInstanceOf(WriteMeta::class, $wm);

        list($kvp, $qm, $err) = $client->get('testkey');
        $this->assertNull($err);
        $this->assertInstanceOf(QueryMeta::class, $qm);
        $this->assertNull($kvp);
    }
}
