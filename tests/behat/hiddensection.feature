@mod @mod_studentquiz
Feature: Manage hidden section setting of a StudentQuiz Activity
  As a teacher
  In order to save and change the section StudentQuiz stores the Quiz
  instances I use the select box in Activity settings.

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Terry1    | Teacher1 | teacher1@example.com |
      | student1 | Sam1      | Student1 | student1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And the following "activities" exist:
      | activity    | name          | intro              | course | idnumber        |
      | studentquiz | StudentQuiz 1 | Quiz 1 description | C1     | studentquiz1    |
    And the following "questions" exist:
      | questioncategory          | qtype     | name       | questiontext  |
      | Default for studentquiz 0 | essay     | Question 1 | Description 1 |
      | Default for studentquiz 0 | truefalse | Question 2 | Description 2 |
    And I log in as "teacher1"

    #Scenario:
    #And |I should see <number> "<string>" elements
    # Create StudentQuiz with 2 questions
    # Run Quiz with both questions selected
    # Change hidden section of StudentQuiz activity to another section.
    # Run Quiz with only one question selected
    # Verify: - both quiz instance are in new newly selected section.

  @javascript
  Scenario: Two quiz instances are created
    Given I am on "Course 1" course homepage
    And I follow "studentquiz 0"
    And I click on "Start Quiz" "button"
    Then I am on "Course 1" course homepage
    And I follow "studentquiz 0"
    # Deselect one of the two questions
    And I click on "Start Quiz" "button"
    Then I am on "Course 1" course homepage
    And I should see "studentquiz 0" in the "studentquiz quizzes" "section"
    # See two quiz instances in studentquiz quizzes section

  Scenario: The default setting of hidden section is 0
    # Go into Activity Edit Settings, Check what section is selected for hidden section
    # Expectation: "Create new Section"  (because default value was not changed on add_instance)
    Given I am on "Course 1" course homepage
    And I follow "studentquiz 0"
    And I navigate to "Edit settings" in current page administration
    Then the field "hiddensection" matches value "0"

  Scenario: A new Quiz was instanced in a section which has no id 999
    Given I am on "Course 1" course homepage
    And I follow "studentquiz 0"
    And I click on "Start Quiz" "button"
    And I wait until the page is ready
    Then I am on "Course 1" course homepage
    And I should see "studentquiz quizzes"
    And I should see "studentquiz 0" in the "studentquiz quizzes" "section"
    And "//*[contains(@href, '#section-999')]" "xpath_element" should not exist