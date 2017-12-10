@mod @mod_studentquiz
Feature: Quizzes can be startet
  In order to use this plugin
  As a student
  I need the quiz run of an activity to work

  Scenario: An already logged in user can participate a studentquiz meanwhile created
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Sam1      | Student1 | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C1     | student        |
    Then I log in as "student1"
    When the following "activities" exist:
      | activity    | name          | intro              | course | idnumber       |
      | studentquiz | StudentQuiz 1 | Quiz 1 description | C1     | studentquiz1   |
    And the following "questions" exist:
      | questioncategory          | qtype | name                       | questiontext                  |
      | Default for StudentQuiz 1 | essay | Test question to be copied | Write about whatever you want |
    And I am on "Course 1" course homepage
    And I follow "StudentQuiz 1"
    And I should see "Create new question"
    And "Start Quiz" "button" should exist

  @javascript
  Scenario: A student can start a quiz on a fresh new activity and can follow the rating rules
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
    #And the following "questions" exist:
    #  | questioncategory          | qtype       | name             | template    |
    #  | Default for StudentQuiz 1 | truefalse   | 1. Can next?     |             |
    #  | Default for StudentQuiz 1 | multichoice | 2. Can previous? | two_of_four |
    #  | Default for StudentQuiz 1 | numerical   | 3. Can finish?   |             |
    And the following "questions" exist:
      | questioncategory          | qtype       | name             | template    |
      | Default for StudentQuiz 1 | truefalse   | Exampe question  |             |
      | Default for StudentQuiz 1 | truefalse   | Exampe question  |             |
      | Default for StudentQuiz 1 | truefalse   | Exampe question  |             |
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "StudentQuiz 1"
    And I should see "Create new question"
    # To sort the question table by question name
    #And I change window size to "large"
    #And I click on ".questionname a" "css_element"
    #And I wait "5" seconds
    #And I change window size to "medium"
    And I click on "Start Quiz" "button"
    # QUESTION 1 mainly
    #Then I should see "Can next?"
    Then I should see "1" in the ".qno" "css_element"
    And I should not see "Please Rate"
    # Can't navigate before answering question
    And "Previous" "button" should not exist
    And "Next" "button" should not exist
    And "Finish" "button" should exist
    # Must rate before go next
    When I set the field "True" to "1"
    And I press "Check"
    Then "Next" "button" should exist
    When I click on "Next" "button"
    Then I should see "Please Rate"
    # Must also rate when now trying to early finish
    When I click on "Finish" "button"
    Then I should see "Please Rate"
    When I click on ".rateable[data-rate='1']" "css_element"
    And I click on "Next" "button"
    # QUESTION 2 mainly
    #Then I should see "Can previous?"
    Then I should see "2" in the ".qno" "css_element"
    And I should not see "Please Rate"
    # Can navigate back when not answered yet
    And "Previous" "button" should exist
    And "Next" "button" should not exist
    And "Finish" "button" should exist
    When I click on "Previous" "button"
    #Then I should see "Can next?"
    Then I should see "1" in the ".qno" "css_element"
    # Can navigate forth because already answered this question
    And I click on "Next" "button"
    #Then I should see "Can previous?"
    Then I should see "2" in the ".qno" "css_element"
    # After answering can only navigate back after rating
    #When I click on "One" "checkbox"
    #And I click on "Two" "checkbox"
    When I set the field "False" to "1"
    And I press "Check"
    And I click on "Previous" "button"
    Then I should see "Please Rate"
    When I click on ".rateable[data-rate='2']" "css_element"
    And I click on "Previous" "button"
    #Then I should see "Can next?"
    Then I should see "1" in the ".qno" "css_element"
    And I click on "Next" "button"
    And I click on "Next" "button"
    # QUESTION 3 mainly
    #Then I should see "Can finish?"
    Then I should see "3" in the ".qno" "css_element"
    And I should not see "Please Rate"
    # When answered can only finish when rated
    And "Previous" "button" should exist
    And "Next" "button" should not exist
    And "Finish" "button" should exist
    # After answering can only finish after rating
    #When I set the field "Answer:" to "3.14"
    When I set the field "True" to "1"
    And I press "Check"
    And I click on "Finish" "button"
    Then I should see "Please Rate"
    When I click on ".rateable[data-rate='3']" "css_element"
    And I click on "Finish" "button"
    # Back to main view and check the result numbers
    Then "Create new question" "button" should exist
    And I should see "2" in the ".stat.last-attempt-correct" "css_element"
    And I should see "1" in the ".stat.last-attempt-incorrect" "css_element"
    And I should see "0" in the ".stat.never-answered" "css_element"
    And I should see "3" in the ".stat.questions-available" "css_element"
