<?php namespace DCarbone\PHPConsulAPI\PreparedQuery;

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

use DCarbone\PHPConsulAPI\AbstractClient;
use DCarbone\PHPConsulAPI\QueryOptions;
use DCarbone\PHPConsulAPI\Request;
use DCarbone\PHPConsulAPI\WriteOptions;

/**
 * Class PreparedQueryClient
 * @package DCarbone\PHPConsulAPI\PreparedQuery
 */
class PreparedQueryClient extends AbstractClient {
    /**
     * @param PreparedQueryDefinition $query
     * @param \DCarbone\PHPConsulAPI\WriteOptions|null $writeOptions
     * @return array(
     * @type string prepared query id
     * @type \DCarbone\PHPConsulAPI\WriteMeta write meta data
     * @type \DCarbone\PHPConsulAPI\Error|null error, if any
     * )
     */
    public function create(PreparedQueryDefinition $query, WriteOptions $writeOptions = null) {
        $r = new Request('POST', 'v1/query', $this->c, $query);
        $r->setWriteOptions($writeOptions);

        /** @var \Psr\Http\Message\ResponseInterface $response */
        list($duration, $response, $err) = $this->requireOK($this->doRequest($r));
        if (null !== $err) {
            return ['', null, $err];
        }

        return [(string)$response->getBody(), $this->buildWriteMeta($duration), null];
    }

    /**
     * @param PreparedQueryDefinition $query
     * @param \DCarbone\PHPConsulAPI\WriteOptions|null $writeOptions
     * @return array(
     * @type \DCarbone\PHPConsulAPI\WriteMeta write metadata
     * @type \DCarbone\PHPConsulAPI\Error|null error, if any
     * )
     */
    public function update(PreparedQueryDefinition $query, WriteOptions $writeOptions = null) {
        $r = new Request('PUT', 'v1/query', $this->c, $query);
        $r->setWriteOptions($writeOptions);

        /** @var \Psr\Http\Message\ResponseInterface $response */
        list($duration, $_, $err) = $this->requireOK($this->doRequest($r));
        if (null !== $err) {
            return [null, $err];
        }

        return [$this->buildWriteMeta($duration), null];
    }

    /**
     * @param \DCarbone\PHPConsulAPI\QueryOptions|null $queryOptions
     * @return array(
     * @type \DCarbone\PHPConsulAPI\PreparedQuery\PreparedQueryDefinition[]|null
     * @type \DCarbone\PHPConsulAPI\QueryMeta|null
     * @type \DCarbone\PHPConsulAPI\Error|null
     * )
     */
    public function listQueries(QueryOptions $queryOptions = null) {
        $r = new Request('GET', 'v1/query', $this->c);
        $r->setQueryOptions($queryOptions);

        /** @var \Psr\Http\Message\ResponseInterface $response */
        list($duration, $response, $err) = $this->requireOK($this->doRequest($r));
        if (null !== $err) {
            return [null, null, $err];
        }

        $qm = $this->buildQueryMeta($duration, $response, $r->getUri());

        list($body, $err) = $this->decodeBody($response->getBody());
        if (null !== $err) {
            return [null, $qm, $err];
        }

        $list = [];
        foreach ($body as $d) {
            $list[] = new PreparedQueryDefinition($d);
        }
        return [$list, $qm, null];
    }

    /**
     * @param string $queryID
     * @param \DCarbone\PHPConsulAPI\QueryOptions|null $queryOptions
     * @return array(
     * @type \DCarbone\PHPConsulAPI\PreparedQuery\PreparedQueryDefinition[]|null
     * @type \DCarbone\PHPConsulAPI\QueryMeta|null
     * @type \DCarbone\PHPConsulAPI\Error|null
     * )
     */
    public function get($queryID, QueryOptions $queryOptions = null) {
        $r = new Request('GET', sprintf('v1/query/%s', $queryID), $this->c);
        $r->setQueryOptions($queryOptions);

        /** @var \Psr\Http\Message\ResponseInterface $response */
        list($duration, $response, $err) = $this->requireOK($this->doRequest($r));
        if (null !== $err) {
            return [null, null, $err];
        }

        $qm = $this->buildQueryMeta($duration, $response, $r->getUri());

        list($body, $err) = $this->decodeBody($response->getBody());
        if (null !== $err) {
            return [null, $qm, $err];
        }

        $queryDefinitions = [];
        foreach ($body as $d) {
            $queryDefinitions[] = new PreparedQueryDefinition($d);
        }
        return [$queryDefinitions, $qm, null];
    }

    /**
     * @param string $queryID
     * @param \DCarbone\PHPConsulAPI\WriteOptions|null $writeOptions
     * @return array(
     * @type \DCarbone\PHPConsulAPI\WriteMeta Write meta data
     * @type \DCarbone\PHPConsulAPI\Error|null error, if any
     * )
     */
    public function delete($queryID, WriteOptions $writeOptions = null) {
        $r = new Request('DELETE', sprintf('v1/query/%s', $queryID), $this->c);
        $r->setWriteOptions($writeOptions);

        /** @var \Psr\Http\Message\ResponseInterface $response */
        list($duration, $response, $err) = $this->requireOK($this->doRequest($r));
        if (null !== $err) {
            return [null, $err];
        }

        list($_, $err) = $this->decodeBody($response->getBody());

        return [$this->buildWriteMeta($duration), null];
    }

    /**
     * @param string $queryIDOrName
     * @param \DCarbone\PHPConsulAPI\QueryOptions|null $queryOptions
     * @return array(
     * @type \DCarbone\PHPConsulAPI\PreparedQuery\PreparedQueryExecuteResponse|null prepared query response or null
     * @type \DCarbone\PHPConsulAPI\QueryMeta Query meta data
     * @type \DCarbone\PHPConsulAPI\Error|null error, if any
     * )
     */
    public function execute($queryIDOrName, QueryOptions $queryOptions = null) {
        $r = new Request('GET', sprintf('v1/query/%s/execute', $queryIDOrName), $this->c);
        $r->setQueryOptions($queryOptions);

        /** @var \Psr\Http\Message\ResponseInterface $response */
        list($duration, $response, $err) = $this->requireOK($this->doRequest($r));
        if (null !== $err) {
            return [null, null, $err];
        }

        $qm = $this->buildQueryMeta($duration, $response, $r->getUri());

        list($body, $err) = $this->decodeBody($response->getBody());
        if (null !== $err) {
            return [null, $qm, $err];
        }

        return [new PreparedQueryExecuteResponse($body), $qm, null];
    }
}
