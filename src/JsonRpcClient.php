<?php

namespace Actindo\Pim;

use Actindo\Pim\Exception\JsonRpcException;

/**
 * Class JsonRpcClient
 *
 * Provides a php implementation of a json rpc 2.0 client compatible with the
 * actindo json rpc server.  Actindo always expects exactly one argument (that
 * is an object or an associative array respectively) and never handles
 * multiple arguments for one method call, therefore only associative arrays
 * are accepted as argument for a method call.
 *
 * Example 1: single request
 *
 * $client = new JsonRpcClient('<api-url>');
 * try {
 *    $response = $client->call('remote.procedure', [
 *       'arg1' => 'value',
 *       'arg2' => 'other value',
 *    ]);
 * } catch (JsonRpcException $e) {
 *     // handle errors
 * }
 *
 * Example 2: batch requests
 *
 * $client = new JsonRpcClient('<api-url>');
 * $response = $client
 *    ->startBatch()
 *    ->call('remote.procedure', ['arg1' => 'value', 'arg2' => 'other value'])
 *    ->call('other.remote.procedure')
 *    ->executeBatch();
 *
 * $response is now an array with 2 elements, one for each request. Every
 * element may be an instance of an exception if the request failed or the
 * response of the remote procedure
 *
 * Example 3: notification requests
 *
 * $client = new JsonRpcClient('<api-url>');
 * $client->call('remote.procedure.with.no.return.value', [
 *    'arg' => 'val'
 * ], true);
 */
class JsonRpcClient {
   /**
    * url to the jsonrpc-server-endpoint
    * @var string
    */
   private $endpoint;

   /**
    * http timeout in seconds for http-requests to the json rpc server
    * @var int
    */
   private $httpTimeout = 30;

   /**
    * session id for all requests
    * @var string
    */
   private $auth = null;

   /**
    * flag if we're in batch mode
    * @var bool
    */
   private $isBatch = false;

   /**
    * store of requests when in batch mode
    * @var array
    */
   private $batchRequests = [];

   /**
    * whether to verify ssl certificates on the rpc endpoint (if applicable)
    * @var bool
    */
   private $verifyPeer = true;

   /**
    * http headers to be sent with all http requests to the json rpc server
    * @var array
    */
   private $httpHeaders = [
      'Accept'       => 'application/json',
      'Connection'   => 'close',
      'Content-Type' => 'application/json',
      'User-Agent'   => 'Actindo Json-RPC Client <http://www.actindo.de>',
      'Expect'       => '',
   ];

   /**
    * @var array $responseHeaders Response-Headers
    *    ['Header-Name' => 'Value', ... ]
    */
   private $responseHeaders = [];

   /**
    * Client constructor.
    * @param string $endpointUrl
    */
   public function __construct($endpointUrl) {
      $this->setEndpoint($endpointUrl);
   }

   /**
    * If we're in batch mode the request is attached to the list of batch
    * requests to be executed on executeBatch().
    *
    * If we're NOT in batch mode the request is performed immediately and its
    * response is returned (or exception raised if an error occurs).
    *
    * @param string $method method to be called on the server
    * @param array $param single associative array of params to be sent with
    *    the request
    * @param bool $isNotification Optional. Whether this is a notification
    *    request where no response is expected. Defaults to false
    *
    * @return JsonRpcClient|array
    */
   public function call($method, $param = [], $isNotification = false) {
      $request = $this->prepareRequest($method, $param, $isNotification);

      if ($this->isBatch) {
         $this->batchRequests[] = $request;
         return $this;
      }

      return $this->parseResponse($this->performRequest($request));
   }

   /**
    * If we're in batch mode the request is attached to the list of batch
    * requests to be executed on executeBatch().
    *
    * If we're NOT in batch mode the request is performed immediately and its
    * response is returned (or exception raised if an error occurs).
    *
    * @param string $method method to be called on the server
    * @param array $param single associative array of params to be sent with
    *    the request
    * @param string $httpMethod HTTP Method
    *
    * @return JsonRpcClient|array
    */
   public function callWithHttpMethod(
      $method,
      $param = [],
      $httpMethod = 'POST'
   ) {
      $request = $this->prepareRequest($method, $param, false);
      $request['_http_method'] = $httpMethod;

      if ($this->isBatch) {
         $this->batchRequests[] = $request;
         return $this;
      }

      return $this->parseResponse($this->performRequest($request));
   }

   /**
    * Discards all queued batch requests without executing them.
    *
    * @return array all batch requests that were discarded
    *
    * @throws JsonRpcException if there is currently no batch operation in progress
    */
   public function discardBatch() {
      if (!$this->isBatch) {
         throw new JsonRpcException('Client is not in batch mode, start a batch operation by calling startBatch() first');
      }

      $batchRequests = $this->batchRequests;
      $this->batchRequests = [];
      $this->isBatch = false;

      return $batchRequests;
   }

   /**
    * Executes all queued batch requests and returns the response or exception
    * for each request (that is not a notification)
    *
    * @return array an array of responses
    *
    * @throws JsonRpcException if we're not in batch mode or if no batch requests are
    *    queued
    */
   public function executeBatch() {
      if (!$this->isBatch) {
         throw new JsonRpcException('Client is not in batch mode, start a batch operation by calling startBatch() first');
      }

      if (empty($this->batchRequests)) {
         throw new JsonRpcException('No batch requests are attached. Use call() first');
      }

      $batchRequests = $this->batchRequests;
      $this->discardBatch();

      return $this->parseBatchResponse($this->performRequest($batchRequests));
   }

   /**
    * Starts a new batch operation.
    *
    * @return $this
    *
    * @throws JsonRpcException if a batch operation is already in progress
    */
   public function startBatch() {
      if ($this->isBatch) {
         throw new JsonRpcException('Batch operation already in progress, execute it by calling executeBatch() or cancel it by calling discardBatch()');
      }

      $this->batchRequests = [];
      $this->isBatch = true;

      return $this;
   }

   /**
    * @param string $auth
    *
    * @return $this
    */
   public function setAuth($auth) {
      $this->auth = $auth;
      return $this;
   }

   /**
    * @return string
    */
   public function getAuth() {
      return $this->auth;
   }

   /**
    * @return bool if we're currently in batch mode
    */
   public function inBatchMode() {
      return $this->isBatch;
   }

   /**
    * @param string $endpoint
    *
    * @return $this
    */
   public function setEndpoint($endpoint) {
      $this->endpoint = $endpoint;
      return $this;
   }

   /**
    * @return string
    */
   public function getEndpoint() {
      return $this->endpoint;
   }

   /**
    * returns the requests header part
    *
    * @param string $key the name of the header to get
    *
    * @return null|string
    */
   public function getHeader($key) {
      if (isset($this->httpHeaders[$key])) {
         return $this->httpHeaders[$key];
      }
      return null;
   }

   /**
    * @param string $key the name of the header part to set
    * @param string $value the value to set the header part to
    *
    * @return $this
    */
   public function setHeader($key, $value) {
      $this->httpHeaders[$key] = $value;
      return $this;
   }

   /**
    * @param int $httpTimeout
    *
    * @return $this
    */
   public function setHttpTimeout($httpTimeout) {
      $this->httpTimeout = $httpTimeout;
      return $this;
   }

   /**
    * @return int
    */
   public function getHttpTimeout() {
      return $this->httpTimeout;
   }

   /**
    * @param bool $verify
    *
    * @return $this
    */
   public function setVerifyPeer($verify) {
      $this->verifyPeer = (bool)$verify;
      return $this;
   }

   /**
    * @return bool
    */
   public function getVerifyPeer() {
      return $this->verifyPeer;
   }

   /**
    * @return array Response-Headers ['Header-Name' => 'Value', ...]
    */
   public function getResponseHeaders() {
      return $this->responseHeaders;
   }

   /**
    * Checks a JSON RPC response for errors and throws exceptions if an error
    * occured.
    *
    * @param array $response json rpc response of a single request
    *
    * @throws JsonRpcException if the called method does not exist
    * @throws JsonRpcException if the given arguments do not match the
    *    method signature
    * @throws JsonRpcException if any error is returned from the server
    */
   protected function handleJsonRpcErrors($response) {
      if (isset($response->error)) {
         $error = $response->error;
         switch($error->code) {
         case -32601:
            throw new JsonRpcException($error->message, $error->code);
         case -32602:
            throw new JsonRpcException(
               $error->message, $error->code);
         default:
            throw new JsonRpcException(
               $error->message."\nStacktrace : " . $error->data->stacktrace,
               $error->code);
         }
      }
   }

   /**
    * Parses a single RPC method call response. Exceptions are thrown if any
    * error occurs.
    *
    * @see handleJsonRpcErrors()
    *
    * @param array $response the json-rpc response of a single request
    *
    * @return array
    */
   protected function parseResponse($response) {
      $this->handleJsonRpcErrors($response);
      return isset($response->result) ? $response->result : null;
   }

   /**
    * Parses all results from a batch call. Exception objects are returned
    * instead of JSON RPC results if any error occurs.
    *
    * @see handleJsonRpcErrors()
    *
    * @param array $responses array of json rpc responses
    *
    * @return array
    */
   protected function parseBatchResponse($responses) {
      $results = [];
      foreach ($responses as $response) {
         try {
            $results[] = $this->parseResponse($response);
         } catch (JsonRpcException $e) {
            $results[] = $e;
         }
      }

      return $results;
   }

   protected function setResponseHeader($curl, $headerLine) {
      $headerLine1 = explode(':', trim($headerLine));
      $headerName = array_shift($headerLine1);
      $headerValue = trim(implode(':', $headerLine1));
      $this->responseHeaders[$headerName] = $headerValue;
      return strlen($headerLine);
   }

   /**
    * Sends a JSON RPC request to the server and returns its response.
    *
    * @param array $request request object from prepareRequest()
    *
    * @return string the plain response from the server (which *should* contain
    *    a json-rpc-response)
    *
    * @throws Exception if the request could not be sent to the json-rpc-endpoint
    * @throws JsonRpcException if the response can not be json-decoded
    */
   protected function performRequest($request) {
      $this->responseHeaders = [];

      $ch = curl_init($this->getEndpoint());
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->getHttpTimeout());
      curl_setopt($ch, CURLOPT_TIMEOUT, $this->getHttpTimeout());
      curl_setopt($ch, CURLINFO_HEADER_OUT, true);
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
      curl_setopt($ch, CURLOPT_MAXREDIRS, 2);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->verifyPeer);
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $this->verifyPeer ? 2 : 0);
      curl_setopt($ch, CURLOPT_VERBOSE, false);

      $headers = [];
      foreach ($this->httpHeaders as $key => $value) {
         $headers[] = "{$key}: {$value}";
      }
      curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

      if (isset($request['_http_method'])) {
         curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $request['_http_method']);
      } else {
         curl_setopt($ch, CURLOPT_POST, true);
      }

      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request));
      curl_setopt($ch, CURLOPT_HEADERFUNCTION, [$this,'setResponseHeader']);

      $response = curl_exec($ch);
      $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      $error = curl_error($ch);

      curl_close($ch);

      if ($error) {
         throw new JsonRpcException("cURL error: {$error}");
      }

      if ($httpCode !== 200 && $httpCode !== 204) {
         throw new JsonRpcException(
            "Unexpected HTTP status code: {$httpCode}",
            $httpCode);
      }

      if (strlen($response) === 0) {
         // No response at all is fine; happens when we send notification
         // requests.
         return [];
      }

      $response = json_decode($response);

      if (json_last_error() !== JSON_ERROR_NONE) {
         $jsonError = json_last_error_msg();
         $message = "Unable to decode json response: {$jsonError}";
         throw new JsonRpcException($message, json_last_error());
      }

      return $response;
   }

   /**
    * Takes a method and param and creates a JSON-RPC-compatible request array.
    *
    * @param string $method
    * @param array $param
    * @param bool $isNotification Optional. Defaults to false. If true a
    *    notification request is created (without id where no response is
    *    expected)
    *
    * @return array
    */
   protected function prepareRequest(
      $method,
      $param = null,
      $isNotification = false
   ) {
      $request = [
         'jsonrpc' => '2.0',
         'method'  => $method,
      ];

      if ($param !== null) {
         $request['params'] = [$param];
      }

      if (!$isNotification) {
         $request['id'] = mt_rand();
      }

      if ($this->getAuth() !== null) {
         $request['auth'] = $this->getAuth();
      }

      return $request;
   }
}
