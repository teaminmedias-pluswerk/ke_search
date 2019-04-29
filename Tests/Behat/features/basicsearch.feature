Feature: Basic search
  As a website visitor,
  I want to use a fulltext search
  in order to find content on the website.

  Scenario: Looking for a search form
    Given I am on "/search/default-search"
    Then I should see "Search"