Feature:
  I want to list all products

  Scenario: Visit endpoint to get information about all products
    When a user sends request to get all products
    Then the response should be received with status code 200
    And there should be 3 products in response
