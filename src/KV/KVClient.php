<?php namespace DCarbone\PHPConsulAPI\KV;

/*
   Copyright 2016 Daniel Carbone (daniel.p.carbone@gmail.com)

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

use DCarbone\PHPConsulAPI\AbstractApiClient;
use DCarbone\PHPConsulAPI\Error;
use DCarbone\PHPConsulAPI\HttpRequest;
use DCarbone\PHPConsulAPI\Hydrator;
use DCarbone\PHPConsulAPI\QueryOptions;
use DCarbone\PHPConsulAPI\WriteOptions;

/**
 * Class KVClient
 * @package DCarbone\PHPConsulAPI\KV
 */
class KVClient extends AbstractApiClient
{
    /**
     * @param string $key Name of key to retrieve value for
     * @param QueryOptions $queryOptions
     * @return array(
     *  @type KVPair|null kv object or null on error
     *  @type \DCarbone\PHPConsulAPI\QueryMeta|null query metadata object or null on error
     *  @type \DCarbone\PHPConsulAPI\Error|null error, if any
     * )
     */
    public function get($key, QueryOptions $queryOptions = null)
    {
        if (!is_string($key))
        {
            return [null, null, new Error(sprintf(
                '%s::get - Key expected to be string, %s seen.',
                get_class($this),
                gettype($key)
            ))];
        }

        $r = new HttpRequest('get', sprintf('v1/kv/%s', $key), $this->_Config);
        $r->setQueryOptions($queryOptions);

        /** @var \DCarbone\PHPConsulAPI\HttpResponse $response */
        list($duration, $response, $err) = $this->doRequest($r);
        $qm = $this->buildQueryMeta($duration, $response);

        if (null !== $err)
            return [null, $qm, $err];

        if (200 === $response->httpCode)
        {
            list($data, $err) = $this->decodeBody($response);

            if (null !== $err)
                return [null, $qm, $err];

            $data = $data[0];

            return [Hydrator::KVPair($data), $qm, null];
        }

        if (404 === $response->httpCode)
            return [null, $qm, null];

        return [null, $qm, new Error(sprintf('%s: %s', $response->httpCode, $response->body))];
    }

    /**
     * @param string $prefix
     * @param QueryOptions|null $queryOptions
     * @return array(
     *  @type KVPair[]|null array of KVPair objects under specified prefix
     *  @type \DCarbone\PHPConsulAPI\QueryMeta|null query metadata
     *  @type \DCarbone\PHPConsulAPI\Error|null error, if any
     * )
     */
    public function valueList($prefix, QueryOptions $queryOptions = null)
    {
        if (!is_string($prefix) || '' === $prefix)
        {
            return [null, null, new Error(sprintf(
                '%s::valueList - Prefix expected to be non-empty string, "%s" seen.',
                get_class($this),
                is_string($prefix) ? $prefix : gettype($prefix)
            ))];
        }

        $r = new HttpRequest('get', sprintf('v1/kv/%s', $prefix), $this->_Config);
        $r->setQueryOptions($queryOptions);
        $r->params->set('recurse', '');

        list($duration, $response, $err) = $this->requireOK($this->doRequest($r));
        $qm = $this->buildQueryMeta($duration, $response);

        if (null !== $err)
            return [null, $qm, $err];

        list($data, $err) = $this->decodeBody($response);

        if (null !== $err)
            return [null, $qm, $err];

        $kvPairs = array();
        foreach($data as $v)
        {
            $kvp = Hydrator::KVPair($v);
            $kvPairs[$kvp->Key] = $kvp;
        }

        return [$kvPairs, $qm, null];
    }

    /**
     * @param string $prefix Prefix to search for.  Null returns all keys.
     * @param QueryOptions $queryOptions
     * @return array(
     *  @type string[]|null list of keys
     *  @type \DCarbone\PHPConsulAPI\QueryMeta|null query metadata
     *  @type \DCarbone\PHPConsulAPI\Error|null error, if any
     * )
     */
    public function keys($prefix = null, QueryOptions $queryOptions = null)
    {
        if (null !== $prefix && !is_string($prefix))
        {
            return [null, null, new Error(sprintf(
                '%s::keys - Prefix expected to be empty or string, %s seen.',
                get_class($this),
                gettype($prefix)
            ))];
        }

        if (null === $prefix)
            $r = new HttpRequest('get', 'v1/kv/', $this->_Config);
        else
            $r = new HttpRequest('get', sprintf('v1/kv/%s', $prefix), $this->_Config);

        $r->setQueryOptions($queryOptions);
        $r->params->set('keys', true);

        list($duration, $response, $err) = $this->requireOK($this->doRequest($r));
        $qm = $this->buildQueryMeta($duration, $response);

        if (null !== $err)
            return [null, $qm, $err];

        list($data, $err) = $this->decodeBody($response);

        return [$data, $qm, $err];
    }

    /**
     * @param KVPair $p
     * @param WriteOptions $writeOptions
     * @return array(
     *  @type \DCarbone\PHPConsulAPI\WriteMeta write metadata
     *  @type \DCarbone\PHPConsulAPI\Error|null error, if any
     * )
     */
    public function put(KVPair $p, WriteOptions $writeOptions = null)
    {
        $r = new HttpRequest('put', sprintf('v1/kv/%s', $p->Key), $this->_Config);
        $r->setWriteOptions($writeOptions);
        $r->body = $p->Value;
        if (0 !== $p->Flags)
            $r->params->set('flags', $p->Flags);

        list($duration, $_, $err) = $this->requireOK($this->doRequest($r));
        $wm = $this->buildWriteMeta($duration);

        return [$wm, $err];
    }

    /**
     * @param string $key
     * @param WriteOptions|null $writeOptions
     * @return array(
     *  @type \DCarbone\PHPConsulAPI\WriteMeta metadata about write
     *  @type \DCarbone\PHPConsulAPI\Error|null error, if any
     * )
     */
    public function delete($key, WriteOptions $writeOptions = null)
    {
        $r = new HttpRequest('delete', sprintf('v1/kv/%s', $key), $this->_Config);
        $r->setWriteOptions($writeOptions);

        list ($duration, $_, $err) = $this->requireOK($this->doRequest($r));
        $wm = $this->buildWriteMeta($duration);

        return [$wm, $err];
    }

    /**
     * @param KVPair $p
     * @param WriteOptions $writeOptions
     * @return array(
     *  @type \DCarbone\PHPConsulAPI\WriteMeta write metadata
     *  @type \DCarbone\PHPConsulAPI\Error|null error, if any
     * )
     */
    public function cas(KVPair $p, WriteOptions $writeOptions = null)
    {
        $r = new HttpRequest('put', sprintf('v1/kv/%s', $p->Key), $this->_Config);
        $r->setWriteOptions($writeOptions);
        $r->params->set('cas', $p->ModifyIndex);
        if (0 !== $p->Flags)
            $r->params->set('flags', $p->Flags);

        list($duration, $_, $err) = $this->requireOK($this->doRequest($r));
        $wm = $this->buildWriteMeta($duration);

        return [$wm, $err];
    }

    /**
     * @param KVPair $p
     * @param WriteOptions $writeOptions
     * @return array(
     *  @type \DCarbone\PHPConsulAPI\WriteMeta write metadata
     *  @type \DCarbone\PHPConsulAPI\Error|null error, if any
     * )
     */
    public function acquire(KVPair $p, WriteOptions $writeOptions = null)
    {
        $r = new HttpRequest('put', sprintf('v1/kv/%s', $p->Key), $this->_Config);
        $r->setWriteOptions($writeOptions);
        $r->params->set('acquire', $p->Session);
        if (0 !== $p->Flags)
            $r->params->set('flags', $p->Flags);

        list($duration, $_, $err) = $this->requireOK($this->doRequest($r));
        $wm = $this->buildWriteMeta($duration);

        return [$wm, $err];
    }

    /**
     * @param KVPair $p
     * @param WriteOptions $writeOptions
     * @return array(
     *  @type \DCarbone\PHPConsulAPI\WriteMeta write metadata
     *  @type \DCarbone\PHPConsulAPI\Error|null error, if any
     * )
     */
    public function release(KVPair $p, WriteOptions $writeOptions = null)
    {
        $r = new HttpRequest('put', sprintf('v1/kv/%s', $p->Key), $this->_Config);
        $r->setWriteOptions($writeOptions);
        $r->params->set('release', $p->Session);
        if (0 !== $p->Flags)
            $r->params->set('flags', $p->Flags);

        list($duration, $_, $err) = $this->requireOK($this->doRequest($r));
        $wm = $this->buildWriteMeta($duration);

        return [$wm, $err];
    }

    /**
     * @param null|string $prefix
     * @param QueryOptions $queryOptions
     * @return array(
     *  @type KVPair[]|KVTree[]|null array of trees, values, or null on error
     *  @type \DCarbone\PHPConsulAPI\Error|null error, if any
     * )
     */
    public function tree($prefix = null, QueryOptions $queryOptions = null)
    {
        list($valueList, $_, $err) = $this->valueList($prefix, $queryOptions);

        if (null !== $err)
            return [null, $err];

        $treeHierarchy = array();
        foreach($valueList as $path=>$kvp)
        {
            $slashPos = strpos($path, '/');
            if (false === $slashPos)
            {
                $treeHierarchy[$path] = $kvp;
                continue;
            }

            $root = substr($path, 0, $slashPos + 1);

            if (!isset($treeHierarchy[$root]))
                $treeHierarchy[$root] = new KVTree($root);

            if ('/' === substr($path, -1))
            {
                $_path = '';
                foreach(explode('/', $prefix) as $part)
                {
                    if ('' === $part)
                        continue;

                    $_path .= "{$part}/";

                    if ($root === $_path)
                        continue;

                    if (!isset($treeHierarchy[$root][$_path]))
                        $treeHierarchy[$root][$_path] = new KVTree($_path);
                }
            }
            else
            {
                $kvPrefix = substr($path, 0, strrpos($path, '/') + 1);
                $_path = '';
                foreach(explode('/', $kvPrefix) as $part)
                {
                    if ('' === $part)
                        continue;

                    $_path .= "{$part}/";

                    if ($root === $_path)
                        continue;

                    if (!isset($treeHierarchy[$root][$_path]))
                        $treeHierarchy[$root][$_path] = new KVTree($_path);
                }

                $treeHierarchy[$root][$path] = $kvp;
            }
        }
        return [$treeHierarchy, null];
    }
}
