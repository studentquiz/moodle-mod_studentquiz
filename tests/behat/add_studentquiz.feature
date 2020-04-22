@mod @mod_studentquiz
Feature: Activities can be created
  In order to use this plugin
  As a teacher
  I need the creation of an activity to work

  Scenario: Check an Activity can be created
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Terry1    | Teacher1 | teacher1@example.com |
      | student1 | Sam1      | Student1 | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    When I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "StudentQuiz" to section "1" and I fill the form with:
      | StudentQuiz Name | Test quiz name        |
      | Description      | Test quiz description |
    And I am on "Course 1" course homepage
    And I follow "Test quiz name"
    Then I should see "Create new question"

  @javascript
  Scenario: Check an Activity can be created with comment deletion period = 0.
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
    And I add a "StudentQuiz" to section "1" and I fill the form with:
      | StudentQuiz Name                          | Test quiz name        |
      | Description                               | Test quiz description |
      | Comment editing/deletion period (minutes) | 0                     |
    When I am on "Course 1" course homepage
    Then I should see "Test quiz name"
    When I follow "Test quiz name"
    And I navigate to "Edit settings" in current page administration
    And I expand all fieldsets
    Then the field with xpath "//*[@id='id_commentdeletionperiod']" matches value "0"
