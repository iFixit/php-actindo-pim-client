## Actindo PIM API

The PIM operates via JSON RPC requests made from the client, so anything you
can do via the web client, you can do via API requests. To find out which API
requests the client is making, just inspect network requests in Chrome while
you do whatever it is that you want to do in the API.

### Authentication

You authenticate by making a login request to an API endpoint with a user and
password. You get back a `sessionId`, which will last for two hours from the
time it's created. The token is passed in an `auth` property of the JSON RPC
request.

### Making a request

The top-level object properties that are relevant to the API are:

- `jsonrpc` (2.0)
- `method` (the API method to call)
- `params` (arguments to the method; always a single argument: null or an
  object with properties as expected by the particular method being called)
- `auth` (an auth token returned after successful login)
- `id` (a random number)

`JsonRpcClient.php` provides the basic JSON RPC functionality that Actindo has
implemented in order to interact with the API using PHP.

The JSON RPC client gives us back the API response as an associative array. It
checks for errors, both HTTP and application, and throws an exception if it
finds them.
