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
    And 24 "questions" exist with the following data:
      | questioncategory | Default for StudentQuiz 1     |
      | qtype            | essay                         |
      | name             | Test question [count]         |
      | questiontext     | Write about whatever you want |

  @javascript
  Scenario: Users can change the state right multi-question has been chosen after paging.
    Given I am on the "StudentQuiz 1" "mod_studentquiz > View" page logged in as "admin"
    And I should see "" in the "Test question 1" "table_row"
    And I click on "Sort by Question ascending" "link"
    And I set the field "qperpage" to "4"
    And I press enter
    And I click on "Question is new. Click here to change the state of this question" "link" in the "Test question 11" "table_row"
    And I should see "Test question 11"
    And I should not see "Test question 12"

  @javascript
  Scenario: Users edit question should keep the same pagination.
    Given I am on the "StudentQuiz 1" "mod_studentquiz > View" page logged in as "admin"
    And I set the field "qperpage" to "25"
    And I press enter
    Then I should see "v1"
    And I choose "Edit question" action for "Test question 5" in the question bank
    # This scenario will fail in 4.0.x until MDL-75917 is done #
    And I should not see "Question status"
    And I press "id_submitbutton"
    And I should see "v2"
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
