@mod @mod_studentquiz
Feature: validate pin and unpin functionalities in question list

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Terry1    | Teacher1 | teacher1@example.com |
      | student1 | Sam1      | Student1 | student1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And the following "activities" exist:
      | activity    | name             | intro                   | course | idnumber     |
      | studentquiz | StudentQuiz Test | StudentQuiz description | C1     | studentquiz1 |
    And the following "questions" exist:
      | questioncategory             | qtype     | name       |
      | Default for StudentQuiz Test | truefalse | Question 1 |
      | Default for StudentQuiz Test | truefalse | Question 2 |
      | Default for StudentQuiz Test | truefalse | Question 3 |

  @javascript
  Scenario: pin and unpin
    Given I am on the "StudentQuiz Test" "mod_studentquiz > View" page logged in as "teacher1"
    When I choose "Pin" action for "Question 2" in the question bank
    # Verify Question 2 is pinned and displayed at the top.
    And "i[title='Pinned']" "css_element" should exist
    Then "Question 2" "text" should appear before "Question 1" "text"
    And I choose "Unpin" action for "Question 2" in the question bank
    # Verify Question 2 is unpinned and displayed in order.
    And "Question 1" "text" should appear before "Question 2" "text"
    And "i[title='Pinned']" "css_element" should not exist
