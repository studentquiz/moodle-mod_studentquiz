@mod @mod_studentquiz
Feature: Preview a question as a student
  In order to verify my question is ready or review the comments
  As a student
  I need to be able to preview them

  # Unfotunately there seems no way to set a question to be created by a user, so the following is imitated by a admin
  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Sam1      | Student1 | student1@example.com |
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
      | questioncategory          | qtype     | name                          |
      | Default for StudentQuiz 1 | numerical | Test question to be previewed |

  # For faster processing these scenarios are concatenated, as $_switch_window seems to do a behat site reset
  @javascript @_switch_window
  Scenario: Question preview shows the question, can be answered and commented
    When I am on the "StudentQuiz 1" "mod_studentquiz > View" page logged in as "admin"
    When I choose "Preview" action for "Test question to be previewed" in the question bank
    And I switch to "questionpreview" window
    Then the state of "What is pi to two d.p.?" question is shown as "Not complete"
    And I should see "No comments"
    And I set the field "Answer:" to "3.14"
    And I press "Check"
    And I wait until the page is ready
    And the state of "What is pi to two d.p.?" question is shown as "Correct"
    And I enter the text "Very good question" into the "Add public comment" editor
    And I press "Add comment"
    And I wait until the page is ready
    And I should see "Very good question"
    # New comment feature, comment is not removed completely but show "Comment deleted".
    And I should see "Delete" in the ".studentquiz-comment-item:nth-child(1) .studentquiz-comment-commands-box" "css_element"
    And I should see "1 of 1"
    # Try to delete comment.
    And I click on "Delete" "button" in the ".studentquiz-comment-item:nth-child(1) .studentquiz-comment-commands-box" "css_element"
    And I click on "[title='Delete comment']" "css_element" in the ".modal.show" "css_element"
    And I wait until the page is ready
    # Check comment is render as deleted and global count updated.
    And I should see "Deleted post" in the ".studentquiz-comment-item:nth-child(1) .studentquiz-comment-item-outerbox" "css_element"
    And I should see "0 of 0"

  @javascript @_switch_window
  Scenario: Student post new question, and he/she can not see comment or rating box on new's one
    When I am on the "StudentQuiz 1" "mod_studentquiz > View" page logged in as "student1"
    # Create owned question by student role.
    Then I should see "Create new question"
    And I click on "Create new question" "button"
    And I set the field "item_qtype_truefalse" to "1"
    And I click on "Add" "button" in the "Choose a question type to add" "dialogue"
    And I should see "Adding a True/False question"
    And I set the field "Question name" to "Example question 1"
    And I set the field "Question text" to "The correct answer is true"
    And I press "id_submitbutton"
    # Turn back and click to Preview link to validate role.
    When I choose "Preview" action for "Example question 1" in the question bank
    And I switch to "questionpreview" window
    And I should not see "Rate"
    And I should not see "Add comment"
    And I should see "Rating and public commenting are not available for your own question in Preview mode."

  @javascript @_switch_window
  Scenario: User with higher student's role post new question, and he/she can comment or rating on new's one
    When I am on the "StudentQuiz 1" "mod_studentquiz > View" page logged in as "admin"
    # Create owned question by student role.
    And I click on "Create new question" "button"
    And I set the field "item_qtype_truefalse" to "1"
    And I click on "Add" "button" in the "Choose a question type to add" "dialogue"
    Then I should see "Adding a True/False question"
    And I set the field "Question name" to "Example question 2"
    And I set the field "Question text" to "The correct answer is true"
    And I press "id_submitbutton"
    # Turn back and click to Preview link to validate role.
    When I choose "Preview" action for "Example question 2" in the question bank
    And I switch to "questionpreview" window
    And "Add public comment" "field" should exist
    And I enter the text "Comment test" into the "Add public comment" editor
    And I press "Add comment"
    And I wait until the page is ready
    And I should see "Comment test"

  @javascript @_switch_window
  Scenario: Preview question display current state.
    When I am on the "StudentQuiz 1" "mod_studentquiz > View" page logged in as "admin"
    And I click on "Create new question" "button"
    And I set the field "item_qtype_truefalse" to "1"
    And I click on "Add" "button" in the "Choose a question type to add" "dialogue"
    Then I should see "Adding a True/False question"
    And I set the field "Question name" to "TF 01"
    And I set the field "Question text" to "The correct answer is true"
    And I press "id_submitbutton"

    And I log out
    And I am on the "StudentQuiz 1" "mod_studentquiz > View" page logged in as "admin"
    And I choose "Preview" action for "TF 01" in the question bank
    And I switch to "questionpreview" window
    And I should see "Change state from New to:"
    And I set the field "statetype" to "Approved"
    And I click on "Change state" "button"
    And I switch to the main window
    And I choose "Preview" action for "TF 01" in the question bank
    And I switch to "questionpreview" window
    And I should see "Change state from Approved to:"
