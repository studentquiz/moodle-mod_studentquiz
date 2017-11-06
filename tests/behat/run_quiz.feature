@mod @mod_studentquiz
Feature: Quizzes can be startet
  In order to use this plugin
  As a student
  I need the quiz run of an activity to work

  @javascript
  Scenario: A student can start a quiz on a fresh new activity
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
      | questioncategory          | qtype | name                       | questiontext                  |
      | Default for studentquiz 0 | essay | Test question to be copied | Write about whatever you want |
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "studentquiz 0"
    Then I should see "Create new question"
    And I click on "Start Quiz" "button"
    And I should see "Attempt quiz now"

  @javascript
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
      | Default for studentquiz 0 | essay | Test question to be copied | Write about whatever you want |
    And I am on "Course 1" course homepage
    And I follow "studentquiz 0"
    And I should see "Create new question"
    And I click on "Start Quiz" "button"
    And I should see "Attempt quiz now"