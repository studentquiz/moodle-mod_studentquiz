@mod @mod_studentquiz
Feature: Set a studentquiz to be marked complete when the student meets the conditions of the completion
  In order to ensure a student has completed the quiz before being marked complete
  As a teacher
  I will be able to see that studentquiz activity completed when the student completes

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Terry1    | Teacher1 | teacher1@example.com |
      | student1 | student1  | Student1 | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category | enablecompletion |
      | Course 1 | C1        | 0        | 1                |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on

  @javascript
  Scenario: Check studentquiz mark done when the student meets the conditions of the completion point
    Given the following "activities" exist:
      | course | activity    | name          | intro                        | completion | completionpointenabled | completionpoint | publishnewquestion | questionquantifier |
      | C1     | studentquiz | StudentQuiz 1 | Test studentquiz description | 2          | 1                      | 10              | 1                  | 10                 |
    # Create owned question by student role.
    And I am on the "StudentQuiz 1" "mod_studentquiz > View" page logged in as "student1"
    And I click on "Create new question" "button"
    And I set the field "item_qtype_truefalse" to "1"
    And I click on "Add" "button" in the "Choose a question type to add" "dialogue"
    And I should see "Adding a True/False question"
    And I set the field "Question name" to "Example question 1"
    And I set the field "Question text" to "The correct answer is true"
    And I press "id_submitbutton"
    Then the "Minimum amount of points: 10" completion condition of "StudentQuiz 1" is displayed as "done"

  @javascript
  Scenario: Check studentquiz mark done when the student meets the conditions of the completion created
    Given the following "activities" exist:
      | course | activity    | name          | intro                        | completion | completionquestionpublishedenabled | completionquestionpublished | publishnewquestion |
      | C1     | studentquiz | StudentQuiz 1 | Test studentquiz description | 2          | 1                                  | 2                           | 1                  |
    # Create owned question by student role.
    And I am on the "StudentQuiz 1" "mod_studentquiz > View" page logged in as "student1"
    And I click on "Create new question" "button"
    And I set the field "item_qtype_truefalse" to "1"
    And I click on "Add" "button" in the "Choose a question type to add" "dialogue"
    And I should see "Adding a True/False question"
    And I set the field "Question name" to "Example question 1"
    And I set the field "Question text" to "The correct answer is true"
    And I press "id_submitbutton"
    And the "Minimum number of unique authored questions: 2" completion condition of "StudentQuiz 1" is displayed as "todo"
    And I click on "Create new question" "button"
    And I set the field "item_qtype_truefalse" to "1"
    And I click on "Add" "button" in the "Choose a question type to add" "dialogue"
    And I should see "Adding a True/False question"
    And I set the field "Question name" to "Example question 2"
    And I set the field "Question text" to "The correct answer is true"
    And I press "id_submitbutton"
    Then the "Minimum number of unique authored questions: 2" completion condition of "StudentQuiz 1" is displayed as "done"

  @javascript
  Scenario: Check studentquiz mark done when the student meets the conditions of the completion created approved
    Given the following "activities" exist:
      | course | activity    | name          | intro                        | completion | completionquestionapprovedenabled | completionquestionapproved | publishnewquestion | approvedquantifier |
      | C1     | studentquiz | StudentQuiz 1 | Test studentquiz description | 2          | 1                                 | 1                          | 1                  | 5                  |
    # Create owned question by student role.
    And I am on the "StudentQuiz 1" "mod_studentquiz > View" page logged in as "student1"
    And I click on "Create new question" "button"
    And I set the field "item_qtype_truefalse" to "1"
    And I click on "Add" "button" in the "Choose a question type to add" "dialogue"
    And I should see "Adding a True/False question"
    And I set the field "Question name" to "Example question 1"
    And I set the field "Question text" to "The correct answer is true"
    And I press "id_submitbutton"
    And I log out
    # Teacher approve a question.
    And I am on the "StudentQuiz 1" "mod_studentquiz > View" page logged in as "teacher1"
    And I choose "Preview" action for "Example question 1" in the question bank
    And I switch to "questionpreview" window
    And I set the field "statetype" to "Approved"
    And I click on "Change state" "button"
    And I switch to the main window
    And I log out
    And I am on the "StudentQuiz 1" "mod_studentquiz > View" page logged in as "student1"
    Then the "Minimum number of unique approved questions: 1" completion condition of "StudentQuiz 1" is displayed as "done"
