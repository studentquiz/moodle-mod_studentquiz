@mod @mod_studentquiz
Feature: As a user I can add private comment and view private comment in my own question.

  Background:
    # 'I set the field' doesn't work on Moodle <= 35
    Given I make sure the current Moodle branch is greater or equal "36"
    And the following config values are set as admin:
      | showprivatecomment | 1 | studentquiz |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | The       | Teacher  | teacher1@example.com |
      | student1 | Student   | One      | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And the following "activities" exist:
      | activity    | name          | intro              | course | idnumber     | forcecommenting | publishnewquestion | anonymrank |
      | studentquiz | StudentQuiz 1 | Quiz 1 description | C1     | studentquiz1 | 1               | 1                  | 0          |

  @javascript
  Scenario: Students can create and view private comment in his own question
    When I am on the "StudentQuiz 1" "mod_studentquiz > View" page logged in as "student1"
    And I click on "Create new question" "button"
    And I set the field "item_qtype_truefalse" to "1"
    And I click on "Add" "button" in the "Choose a question type to add" "dialogue"
    And I should see "Adding a True/False question"
    And I set the field "Question name" to "Question 1"
    And I set the field "Question text" to "The correct answer is true"
    And I press "id_submitbutton"
    And I choose "Preview" action for "Question 1" in the question bank
    And I switch to "questionpreview" window
    And I enter the text "Submitted for approval" into the "Add private comment (these are between the student and tutor only)" editor
    And I press "Add comment"
    Then I should see "Submitted for approval" in the ".studentquiz-comment-item:nth-child(1) .studentquiz-comment-text" "css_element"
    And I click on "Public comments" "link"
    And I should see "Rating and public commenting are not available for your own question in Preview mode."
    And I switch to the main window
    And I log out
    And I am on the "StudentQuiz 1" "mod_studentquiz > View" page logged in as "teacher1"
    And I choose "Preview" action for "Question 1" in the question bank
    And I switch to "questionpreview" window
    And I should see "Submitted for approval"
    And I enter the text "A private comment from teacher" into the "Add private comment (these are between the student and tutor only)" editor
    And I press "Add comment"
    And I switch to the main window
    And I log out
    And I am on the "StudentQuiz 1" "mod_studentquiz > View" page logged in as "student1"
    And I choose "Preview" action for "Question 1" in the question bank
    And I switch to "questionpreview" window
    And I should see "Rating and public commenting are not available for your own question in Preview mode."
    And I click on "Private comments" "link"
    And I should see "A private comment from teacher"
    And I enter the text "Updated for approval again" into the "Add private comment (these are between the student and tutor only)" editor
    And I press "Add comment"
    And I switch to the main window
    And I log out
    And I am on the "StudentQuiz 1" "mod_studentquiz > View" page logged in as "teacher1"
    And I choose "Preview" action for "Question 1" in the question bank
    And I switch to "questionpreview" window
    And I should see "Updated for approval again"
    And I enter the text "Approved the question" into the "Add private comment (these are between the student and tutor only)" editor
    And I press "Add comment"
    And I set the field "statetype" to "Approved"
    And I click on "Change state" "button"
    And I switch to the main window
    And I log out
    And I am on the "StudentQuiz 1" "mod_studentquiz > View" page logged in as "student1"
    And I choose "Preview" action for "Question 1" in the question bank
    And I switch to "questionpreview" window
    And I should see "Approved the question"
    And I should see "No further private comments are allowed once a question is 'Approved'"
