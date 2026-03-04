@mod @mod_studentquiz
Feature: Bulk move questions in StudentQuiz
  In order to ensure only staff can move questions and students cannot
  As a staff member
  I want to move questions from the StudentQuiz private bank to any shared question bank I have access to

  Background:
    Given I make sure the current Moodle branch is greater or equal "50"
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And the following "activities" exist:
      | activity    | name            | course | idnumber |
      | studentquiz | SQ1             | C1     | sq1      |
      | qbank       | Question bank 1 | C1     | qbank1   |
    And the following "question categories" exist:
      | contextlevel    | reference | name             |
      | Activity module | sq1       | Test questions 1 |
      | Activity module | qbank1    | Test questions 2 |
      | Course          | C1        | Shared Category  |
    And the following "questions" exist:
      | questioncategory | qtype     | name          | questiontext     |
      | Default for SQ1  | truefalse | SQ Question 1 | SQ question text |
      | Default for SQ1  | truefalse | SQ Question 2 | SQ question text |

  @javascript
  Scenario: Staff can move questions from StudentQuiz private bank to another category.
    Given I log in as "teacher1"
    And I am on the "SQ1" "studentquiz activity" page
    And I click on "Move to" "button"
    And I open the autocomplete suggestions list in the ".search-categories" "css_element"
    And I click on "Test questions 1" item in the autocomplete list
    And I click on "Move questions" "button"
    And I should see "Are you sure you want to move these questions?"
    And I click on "Confirm" "button"
    And I wait until the page is ready
    And I should see "Questions successfully moved"
    # Verify that the questions have been moved.
    And I navigate to "Question bank" in current page administration
    When I apply question bank filter "Category" with value "Test questions 1"
    Then I should see "SQ Question 1"
    And I should see "SQ Question 2"

  @javascript
  Scenario: Staff can move questions from StudentQuiz private bank to shared bank
    Given I log in as "teacher1"
    And I am on the "SQ1" "studentquiz activity" page
    And I click on "Move to" "button"
    And I open the autocomplete suggestions list in the ".search-banks" "css_element"
    And I click on "C1 - System shared question bank" item in the autocomplete list
    And I should see "Shared Category" in the ".search-categories .form-autocomplete-selection" "css_element"
    And I click on "Move questions" "button"
    And I should see "Are you sure you want to move these questions?"
    And I click on "Confirm" "button"
    And I wait until the page is ready
    And I should see "There are no questions in this StudentQuiz. Feel free to add some questions."
    # Verify that the questions have been moved to the shared question bank.
    And I am on the "C1" "Course" page
    And I navigate to "Question banks" in current page administration
    When I follow "System shared question bank"
    Then I should see "SQ Question 1"
    And I should see "SQ Question 2"

  Scenario: Student cannot see move button
    Given I log in as "student1"
    And I am on the "SQ1" "studentquiz activity" page
    Then I should not see "Move to"
