@mod @mod_studentquiz
Feature: View comprehensive information about this studentquiz activity
  In order to see the important information
  As a user
  I need to see all of it when I open the studentquiz activity

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Terry1    | Teacher1 | teacher1@example.com |
      | student1 | Student   | One      | student1@example.com |
      | student2 | Student   | Two      | student2@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
    And the following "activities" exist:
      | activity    | name          | intro              | course | idnumber     | publishnewquestion |
      | studentquiz | StudentQuiz 1 | Quiz 1 description | C1     | studentquiz1 | 1                  |
    And I change window size to "large"

  @javascript
  Scenario: Check if the default filter settings are visible
    When I am on the "StudentQuiz 1" "mod_studentquiz > View" page logged in as "student1"

    # Student 1 create his question.
    And I click on "Create new question" "button"
    And I set the field "item_qtype_truefalse" to "1"
    And I click on "Add" "button" in the "Choose a question type to add" "dialogue"
    And I set the field "Question name" to "Question 1"
    And I set the field "Question text" to "The correct answer is false"
    And I press "id_submitbutton"
    And I log out

    # Student 2 create his question.
    And I am on the "StudentQuiz 1" "mod_studentquiz > View" page logged in as "student2"
    And I click on "Create new question" "button"
    And I set the field "item_qtype_truefalse" to "1"
    And I click on "Add" "button" in the "Choose a question type to add" "dialogue"
    And I set the field "Question name" to "Question 2"
    And I set the field "Question text" to "The correct answer is false"
    And I press "id_submitbutton"
    And I log out

    # Teacher approve a question.
    And I am on the "StudentQuiz 1" "mod_studentquiz > View" page logged in as "teacher1"
    And I choose "Preview" action for "Question 1" in the question bank
    And I switch to "questionpreview" window
    And I set the field "statetype" to "Approved"
    And I click on "Change state" "button"
    And I switch to the main window
    And I log out

    # Student 1 answer a question.
    And I am on the "StudentQuiz 1" "mod_studentquiz > View" page logged in as "student1"
    And I click on "Start Quiz" "button"
    And I set the field "True" to "1"
    And I press "Check"
    And I click on ".rateable[data-rate='5']" "css_element"
    And I click on "Abort" "button"

    # Check the Fast filter.
    Then "Filter" "fieldset" should be visible
    And I should see "Fast filter for questions"
    And I click on "Unanswered" "link"
    And I press "id_submitbutton"
    And I should see "Question 1"
    And I should not see "Question 2"
    And I click on "Reset" "button"

    And I click on "New" "link"
    And I press "id_submitbutton"
    And I should see "Question 2"
    And I should not see "Question 1"
    And I click on "Reset" "button"

    And I click on "Approved" "link"
    And I press "id_submitbutton"
    And I should see "Question 1"
    And I should not see "Question 2"
    And I click on "Reset" "button"

    And I click on "Disapproved" "link"
    And I click on "Changed" "link"
    And I click on "Reviewable" "link"
    And I press "id_submitbutton"
    And I should see "None of the questions matched your filter criteria. Reset the filter to see all."
    And I click on "Reset" "button"

    And I click on "Good" "link"
    And I press "id_submitbutton"
    And I should see "Question 2"
    And I should not see "Question 1"
    And I click on "Reset" "button"
    And I wait until the page is ready
    # Click the correct Mine link.
    And I click on "Mine" "link" in the ".containsadvancedelements" "css_element"
    And I press "id_submitbutton"
    And I should see "Question 1"
    And I should not see "Question 2"
    And I click on "Reset" "button"

    And I click on "Difficult for me" "link"
    And I press "id_submitbutton"
    And I should see "Question 2"
    And I should not see "Question 1"
    And I click on "Reset" "button"

    And I click on "Difficult for all" "link"
    And I press "id_submitbutton"
    And I should see "Question 2"
    And I should not see "Question 1"
