# Platform.sh API

A wrapper and an authentication key manager for `Platform.sh API client`.

  2023-04 tested against  : platformsh/client:2.5.1 (before "Organisations" was introduced) 2.6+ was still in beta, no stable.

## Configuration

Add your API key at `/admin/config/services/platformsh_api`

Test that the API key and the network connection are working using the "Test Platformsh API" tab.

The test actions are heavily logged, so clues may be seen in the recent log messages if you need to debug further.

## Usage

The `platformsh_api` library is used mostly as a conduit for other modules.

##
Development testing

To run tests:

```
php web/core/scripts/run-tests.sh --sqlite --module platformsh_api

```
