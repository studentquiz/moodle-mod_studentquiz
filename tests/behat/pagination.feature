@mod @mod_studentquiz
Feature: Test pagination for StudentQuiz

  Background: An already logged in user can participate a studentquiz meanwhile created
    Given the following "users" exist:
      | username | firstname | lastname |
      | student1 | Sam1      | Student1 |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |
    And the following "activities" exist:
      | activity    | name          | intro              | course | idnumber     | publishnewquestion |
      | studentquiz | StudentQuiz 1 | Quiz 1 description | C1     | studentquiz1 | 1                  |
    And the following "questions" exist:
      | questioncategory          | qtype | name             | questiontext                  |
      | Default for StudentQuiz 1 | essay | Test question 1  | Write about whatever you want |
      | Default for StudentQuiz 1 | essay | Test question 2  | Write about whatever you want |
      | Default for StudentQuiz 1 | essay | Test question 3  | Write about whatever you want |
      | Default for StudentQuiz 1 | essay | Test question 4  | Write about whatever you want |
      | Default for StudentQuiz 1 | essay | Test question 5  | Write about whatever you want |
      | Default for StudentQuiz 1 | essay | Test question 6  | Write about whatever you want |
      | Default for StudentQuiz 1 | essay | Test question 7  | Write about whatever you want |
      | Default for StudentQuiz 1 | essay | Test question 8  | Write about whatever you want |
      | Default for StudentQuiz 1 | essay | Test question 9  | Write about whatever you want |
      | Default for StudentQuiz 1 | essay | Test question 10 | Write about whatever you want |
      | Default for StudentQuiz 1 | essay | Test question 11 | Write about whatever you want |
      | Default for StudentQuiz 1 | essay | Test question 12 | Write about whatever you want |
      | Default for StudentQuiz 1 | essay | Test question 13 | Write about whatever you want |
      | Default for StudentQuiz 1 | essay | Test question 14 | Write about whatever you want |
      | Default for StudentQuiz 1 | essay | Test question 15 | Write about whatever you want |
      | Default for StudentQuiz 1 | essay | Test question 16 | Write about whatever you want |
      | Default for StudentQuiz 1 | essay | Test question 17 | Write about whatever you want |
      | Default for StudentQuiz 1 | essay | Test question 18 | Write about whatever you want |
      | Default for StudentQuiz 1 | essay | Test question 19 | Write about whatever you want |
      | Default for StudentQuiz 1 | essay | Test question 20 | Write about whatever you want |
      | Default for StudentQuiz 1 | essay | Test question 21 | Write about whatever you want |
      | Default for StudentQuiz 1 | essay | Test question 22 | Write about whatever you want |
      | Default for StudentQuiz 1 | essay | Test question 23 | Write about whatever you want |
      | Default for StudentQuiz 1 | essay | Test question 24 | Write about whatever you want |

  @javascript
  Scenario: Users can change the state right multi-question has been chosen after paging.
    Given I am on the "StudentQuiz 1" "mod_studentquiz > View" page logged in as "admin"
    And I should see "" in the "Test question 1" "table_row"
    And I set the field "qperpage" to "4"
    And I press enter
    And I click on "Question is new. Click here to change the state of this question" "link" in the "Test question 2" "table_row"
    And I should see "Test question 2"
    And I should not see "Test question 1"
    And I should not see "Test question 3"

  @javascript
  Scenario: Users edit question should keep the same pagination.
    Given I am on the "StudentQuiz 1" "mod_studentquiz > View" page logged in as "admin"
    And I set the field "qperpage" to "25"
    And I press enter
    And I choose "Edit question" action for "Test question 5" in the question bank
    And I press "id_submitbutton"
    Then "input[name='changepagesize']" "css_element" should not exist
    And I should see "Test question 24"

  @javascript
  Scenario: Users create question should keep the same pagination.
    Given I am on the "StudentQuiz 1" "mod_studentquiz > View" page logged in as "admin"
    And I set the field "qperpage" to "25"
    And I press enter
    And I click on "Create new question" "button"
    And I set the field "item_qtype_truefalse" to "1"
    And I click on "Add" "button" in the "Choose a question type to add" "dialogue"
    And I set the field "Question name" to "TF 01"
    And I set the field "Question text" to "The correct answer is false"
    And I press "id_submitbutton"
    Then "input[name='changepagesize']" "css_element" should not exist
    And I should see "TF 01"
    And I should see "Test question 24"

  @javascript
  Scenario: Users using filter should keep the same pagination.
    Given I am on the "StudentQuiz 1" "mod_studentquiz > View" page logged in as "admin"
    And I set the field "qperpage" to "25"
    And I press enter
    And I click on "New" "link"
    And I press "id_submitbutton"
    Then "input[name='changepagesize']" "css_element" should not exist
    And I should see "Test question 24"

  @javascript
  Scenario: Users using start quiz button should keep the same pagination.
    Given I am on the "StudentQuiz 1" "mod_studentquiz > View" page logged in as "admin"
    And I set the field "qperpage" to "25"
    And I press enter
    And I click on "#qbheadercheckbox" "css_element"
    And I click on "tr.r0 > td" "css_element" in the "Test question 1" "table_row"
    And I click on "Start Quiz" "button"
    And I press "Abort"
    Then "input[name='changepagesize']" "css_element" should not exist
    And I should see "Test question 24"
