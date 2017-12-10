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
      | user     | course | role           |
      | student1 | C1     | student        |
    And the following "activities" exist:
      | activity    | name          | intro              | course | idnumber       |
      | studentquiz | StudentQuiz 1 | Quiz 1 description | C1     | studentquiz1   |
    And the following "questions" exist:
      | questioncategory          | qtype     | name                          |
      | Default for StudentQuiz 1 | numerical | Test question to be previewed |
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "StudentQuiz 1"
    When I click on "Preview" "link" in the "Test question to be previewed" "table_row"
    And I switch to "questionpreview" window

  # For faster processing these scenarios are concatenated, as $_switch_window seems to do a behat site reset
  @javascript @_switch_window
  Scenario: Question preview shows the question, can be answered and commented
    Given the state of "What is pi to two d.p.?" question is shown as "Not complete"
    #Then "Rate" "field" should exist
    #Then I should see "Rate" in the ".rate" "css_element"
    Then ".rate" "css_element" should exist
    And "Add comment" "field" should exist
    #And I should see "Add comment" in the ".comments p" "css_element"
    #And ".comments" "css_element" should exist
    And I should see "No comments"
    When I set the field "Answer:" to "3.14"
    And I press "Check"
    Then the state of "What is pi to two d.p.?" question is shown as "Correct"
    When ".rateable[data-rate='4']" "css_element" should exist
    And I click on ".rateable[data-rate='4']" "css_element"
    Then ".star[data-rate='4']" "css_element" should exist
    And ".star-empty[data-rate='4']" "css_element" should not exist
    And ".star[data-rate='5']" "css_element" should not exist
    And ".star-empty[data-rate='5']" "css_element" should exist
    When I set the field "Add comment" to "Very good question"
    And I press "Add comment"
    Then I should see "Very good question"
    And I should see "remove"
    And I click on ".remove_action" "css_element"
    Then I should see "No comments"