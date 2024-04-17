@mod @mod_studentquiz
Feature: Activities can be created
  In order to use this plugin
  As a teacher
  I need the creation of an activity to work

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Terry1    | Teacher1 | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on

  @javascript
  Scenario: Check an Activity can be created
    When I add a studentquiz activity to course "Course 1" section "1" and I fill the form with:
      | StudentQuiz Name | Test quiz name        |
      | Description      | Test quiz description |
    And I am on the "Test quiz name" "mod_studentquiz > View" page
    Then I should see "Create new question"

  @javascript
  Scenario: Check an Activity can be created with comment deletion period = 0.
    When I add a studentquiz activity to course "Course 1" section "1" and I fill the form with:
      | StudentQuiz Name                          | Test quiz name        |
      | Description                               | Test quiz description |
      | Comment editing/deletion period (minutes) | 0                     |
    And I am on the "Test quiz name" "mod_studentquiz > View" page
    And I navigate to "Settings" in current page administration
    And I expand all fieldsets
    Then the field "commentdeletionperiod" matches value "0"

  @javascript
  Scenario: Check an Activity can not be created with invalid date restriction.
    Given I add a studentquiz activity to course "Course 1" section "1"
    And I set the following fields to these values:
      | StudentQuiz Name | Test SQ name        |
      | Description      | Test SQ description |
    And I expand all fieldsets
    And I press "Add restriction..."
    And I click on "Date" "button" in the "Add restriction..." "dialogue"
    And I set the field "direction" to "from"
    And I set the field "x[year]" to "2014"
    And I set the field "x[month]" to "March"
    And I press "Add restriction..."
    And I click on "Date" "button" in the "Add restriction..." "dialogue"
    And I set the field with xpath "(//select[@name='direction'])[1]" to "until"
    And I set the field with xpath "(//select[@name='x[year]'])[1]" to "2014"
    And I set the field with xpath "(//select[@name='x[month]'])[1]" to "January"
    When I press "Save and display"
    Then I should see "Conflicts with other date restrictions"
