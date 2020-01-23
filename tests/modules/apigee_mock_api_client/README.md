Apigee Edge Mock API Client Testing Module
---

This module allows you to test apigee_edge functionality without making external API calls, by providing response templates
that can be queued up in a mock Guzzle client stack handler.

### Usage

Response templates are stored in the [MODULE_NAME]/tests/response-templates directory. The Apigee Edge module provides
some base responses, but these can be extended by any module simply by creating a response-templates directory in the
module's test directory. The `apigee_mock_api_client` module should be enabled in the setup of your test, and then responses
can be queued using the `apigee_mock_api_client.mock_http_handler_stack` service `::queueMockResponse` method. Responses
will then be returned from the mock handler stack in the order in which they were queued, i.e. if I make 3 calls to
Apigee Edge in a test, then I want to make sure that the appropriate responses are queued up in that same order.

### Installing

This module should not installed on a standard drupal site, it should only be enabled during testing. In a Kernel or
Functional test this is achieved by overriding the `protected static $modules` array and listing the machine names of
modules that should be enabled.

### Requirements

* This module depends on the `apigee_edge` module.