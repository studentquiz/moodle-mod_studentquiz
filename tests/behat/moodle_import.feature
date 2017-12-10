@mod @mod_studentquiz
Feature: Import course's with StudentQuiz contents into another course
  In order to move and copy studentquiz between courses
  As a teacher
  I need to import a course contents into another course

  @javascript
  Scenario: Import course's contents containing StudentQuiz into another course
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
      | Course 2 | C2        | 0        |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | HSR      | teacher1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | teacher1 | C2     | editingteacher |
    And the following "activities" exist:
      | activity    | name          | intro              | course | idnumber       |
      | studentquiz | StudentQuiz 1 | Quiz 1 description | C1     | studentquiz1   |
    And the following "questions" exist:
      | questioncategory          | qtype | name                       | questiontext                  |
      | Default for StudentQuiz 1 | essay | Test question to be copied | Write about whatever you want |
    When I log in as "teacher1"
    And I import "Course 1" course into "Course 2" course using this options:
    Then I should see "StudentQuiz 1"
    And I follow "StudentQuiz 1"
    And I should see "Create new question"
    And "Start Quiz" "button" should exist
