@mod @mod_studentquiz
Feature: Question states history

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "activities" exist:
      | activity    | name               | intro                     | course | idnumber     | publishnewquestion |
      | studentquiz | StudentQuiz Test 1 | StudentQuiz description 1 | C1     | studentquiz1 | 1                  |
      | studentquiz | StudentQuiz Test 2 | StudentQuiz description 2 | C1     | studentquiz1 | 0                  |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Sam1      | Student1 | student1@example.com |
      | student2 | Sam2      | Student2 | student2@example.com |
      | teacher  | teacher   | Teacher  | teacher@example.com  |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |
      | student2 | C1     | student |
      | teacher  | C1     | teacher |
    # Set window size to large so we can see the navigation.
    And I change window size to "large"

  @javascript @_switch_window
  Scenario: Teachers can see author of action in state history table
    When I am on the "StudentQuiz Test 1" "mod_studentquiz > View" page logged in as "student1"
    # Set window size to large so we can see the navigation.
    And I change window size to "large"
    And I click on "Create new question" "button"
    And I set the field "item_qtype_truefalse" to "1"
    And I click on "Add" "button" in the "Choose a question type to add" "dialogue"
    And I set the field "Question name" to "TF 01"
    And I set the field "Question text" to "The correct answer is false"
    And I press "id_submitbutton"

    Then I follow "StudentQuiz Test 2"
    And I click on "Create new question" "button"
    And I set the field "item_qtype_truefalse" to "1"
    And I click on "Add" "button" in the "Choose a question type to add" "dialogue"
    And I set the field "Question name" to "TF 01"
    And I set the field "Question text" to "The correct answer is false"
    And I press "id_submitbutton"

    And I log out
    Then I am on the "StudentQuiz Test 2" "mod_studentquiz > View" page logged in as "student2"
    Then I should not see "TF 01"

    And I log out
    And I am on the "StudentQuiz Test 1" "mod_studentquiz > View" page logged in as "admin"
    And I choose "Preview" action for "TF 01" in the question bank
    And I switch to "questionpreview" window
    And I set the field "statetype" to "Approved"
    And I click on "Change state" "button"
    And I switch to the main window

    And I choose "Preview" action for "TF 01" in the question bank
    And I switch to "questionpreview" window
    And I click on "History" "link"
    And I should see "Sam1 Student1" in the "Question saved ('Draft')" "table_row"
    And I should see "Admin User" in the "Question set to 'Approved'" "table_row"

    And I switch to the main window
    And I follow "StudentQuiz Test 2"
    And I choose "Preview" action for "TF 01" in the question bank
    And I switch to "questionpreview" window
    And I set the field "statetype" to "Approved"
    And I click on "Change state" "button"
    And I switch to the main window

    And I choose "Preview" action for "TF 01" in the question bank
    And I switch to "questionpreview" window
    And I click on "History" "link"
    And I should see "Sam1 Student1" in the "Question saved ('Draft')" "table_row"
    And I should see "Question set to 'Approved'"
    And I should see "-" in the "Question visibility set to 'Shown'" "table_row"

  @javascript @_switch_window
  Scenario: Student can see the state history table of his own question
    When I am on the "StudentQuiz Test 1" "mod_studentquiz > View" page logged in as "student1"

    And I click on "Create new question" "button"
    And I set the field "item_qtype_truefalse" to "1"
    And I click on "Add" "button" in the "Choose a question type to add" "dialogue"
    And I set the field "Question name" to "TF 01"
    And I set the field "Question text" to "The correct answer is false"
    And I press "id_submitbutton"

    And I choose "Preview" action for "TF 01" in the question bank
    And I switch to "questionpreview" window
    And I click on "History" "link"
    And I should see "Sam1 Student1" in the "Question saved ('Draft')" "table_row"
    And I should see "-" in the "Question visibility set to 'Shown'" "table_row"
