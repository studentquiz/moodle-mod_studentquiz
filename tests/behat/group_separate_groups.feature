@mod @mod_studentquiz
Feature: Students can create questions and practice in separate groups.

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | One      | student1@example.com |
      | student2 | Student   | Two      | student2@example.com |
      | student3 | Student   | Three    | student3@example.com |
      | student4 | Student   | Four     | student4@example.com |
      | teacher  | Teacher   | One      | teacher@example.com  |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |

    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
      | student3 | C1     | student        |
      | student4 | C1     | student        |
      | teacher  | C1     | editingteacher |
    And the following "groups" exist:
      | name    | course | idnumber |
      | Group 1 | C1     | G1       |
      | Group 2 | C1     | G2       |
    And the following "group members" exist:
      | user     | group |
      | student1 | G1    |
      | student2 | G1    |
      | student1 | G2    |
      | student3 | G2    |
    And the following "activities" exist:
      | activity    | name          | intro              | course | idnumber     | forcerating | publishnewquestion | groupmode | questionquantifier | correctanswerquantifier |
      | studentquiz | StudentQuiz 1 | Quiz 1 description | C1     | studentquiz1 | 1           | 1                  | 1         | 10                 | 10                      |

  @javascript
  Scenario: Students can create questions and practice in separate groups
    Given I am on the "C1" "Course" page logged in as "student1"
    # Set window size to large so we can see the navigation.
    And I change window size to "large"
    And I follow "StudentQuiz 1"
    And I set the field "Separate groups" to "Group 1"
    And I click on "Create new question" "button"
    And I set the field "item_qtype_truefalse" to "1"
    And I click on "Add" "button" in the "Choose a question type to add" "dialogue"
    And I set the field "Question name" to "Question of Student 1"
    And I set the field "Question text" to "The correct answer is true"
    And I press "id_submitbutton"
    And I log out

    Given I am on the "C1" "Course" page logged in as "student2"
    And I am on "Course 1" course homepage
    And I follow "StudentQuiz 1"
    Then I should see "Separate groups: Group 1"
    And I click on "Create new question" "button"
    And I set the field "item_qtype_truefalse" to "1"
    And I click on "Add" "button" in the "Choose a question type to add" "dialogue"
    And I should see "Adding a True/False question"
    And I set the field "Question name" to "Question of Student 2"
    And I set the field "Question text" to "The correct answer is true"
    And I press "id_submitbutton"
    And I log out

    Given I am on the "C1" "Course" page logged in as "student3"
    And I follow "StudentQuiz 1"
    And I should see "Separate groups: Group 2"
    And I wait until the page is ready
    And I click on "Create new question" "button"
    And I set the field "item_qtype_truefalse" to "1"
    And I click on "Add" "button" in the "Choose a question type to add" "dialogue"
    And I should see "Adding a True/False question"
    And I set the field "Question name" to "Question of Student 3"
    And I set the field "Question text" to "The correct answer is true"
    And I press "id_submitbutton"
    And I log out

    Given I am on the "C1" "Course" page logged in as "student1"
    And I follow "StudentQuiz 1"
    And I set the field "Separate groups" to "Group 1"
    And I wait until the page is ready
    And I should see "Question of Student 1"
    And I should see "Question of Student 2"
    And I should not see "Question of Student 3"
    And I should see "0" in the ".stat.last-attempt-correct" "css_element"
    And I should see "0" in the ".stat.last-attempt-incorrect" "css_element"
    And I should see "2" in the ".stat.never-answered" "css_element"

    And I set the field "Separate groups" to "Group 2"
    And I wait until the page is ready
    And I should not see "Question of Student 1"
    And I should not see "Question of Student 2"
    And I should see "Question of Student 3"
    And I should see "0" in the ".stat.last-attempt-correct" "css_element"
    And I should see "0" in the ".stat.last-attempt-incorrect" "css_element"
    And I should see "1" in the ".stat.never-answered" "css_element"

    # Start the quiz in Group 1.
    And I set the field "Separate groups" to "Group 1"
    And I wait until the page is ready
    And I click on "Start Quiz" "button"
    And I set the field "True" to "1"
    And I press "Check"
    And I click on ".rateable[data-rate='1']" "css_element"
    And I click on "Next" "button"
    And I set the field "False" to "1"
    And I press "Check"
    And I click on ".rateable[data-rate='3']" "css_element"
    And I click on "Finish" "button"

    And I should see "1" in the ".stat.last-attempt-correct" "css_element"
    And I should see "1" in the ".stat.last-attempt-incorrect" "css_element"
    And I should see "0" in the ".stat.never-answered" "css_element"
    And I should see "Student One" in the "Ranking" "block"
    And I should see "Student Two" in the "Ranking" "block"
    And I should not see "Student Three" in the "Ranking" "block"

    And I click on "More" "link" in the "My Progress" "block"
    And I should see "Separate groups: Group 1"
    And I should see "2" in the "td.c4 span[title*='Number of questions created by the community']" "css_element"
    And I should see "0" in the "td.c4 span[title*='This is the number of all approved questions within this StudentQuiz']" "css_element"
    And I should see "2" in the "td.c4 span[title*='The rating of each question is the average of stars it received from the community']" "css_element"
    And I should see "1" in the "td.c4 span[title*='Average number of answers given by all community members.']" "css_element"
    And I should see "50" in the "td.c4 span[title*='Sum of correct answers / sum of all answers.']" "css_element"
    And I should see "25" in the "td.c4 span[title*='Average community progress based on all community members.']" "css_element"

    And I follow "StudentQuiz 1"
    And I click on "More" "link" in the "Ranking" "block"
    And I should see "Separate groups: Group 1"
    And "Student One" "text" should appear before "Student Two" "text"
    And I should not see "Student Three"

  @javascript
  Scenario: Students without a group will not be able to access the Student Quiz activity in separate groups.
    Given I am on the "C1" "Course" page logged in as "student4"
    And I follow "StudentQuiz 1"
    Then I should see "Sorry, but you need to be part of a group to see this page."
    And "Back to course" "button" should exist

  @javascript
  Scenario: Teacher without a group but has the capability 'moodle/site:accessallgroups' can write a comment on Student Quiz in separate groups.
    Given I am on the "C1" "Course" page logged in as "admin"
    And I follow "StudentQuiz 1"
    And I click on "Create new question" "button"
    And I set the field "item_qtype_truefalse" to "1"
    And I click on "Add" "button" in the "Choose a question type to add" "dialogue"
    And I set the field "Question name" to "Question of Student 1"
    And I set the field "Question text" to "The correct answer is true"
    And I press "id_submitbutton"
    And I log out
    And I am on the "C1" "Course" page logged in as "teacher"
    And I follow "StudentQuiz 1"
    And I click on "Start Quiz" "button"
    And I set the field "True" to "1"
    And I press "Check"
    And I enter the text "Comment 1" into the "Add public comment" editor
    And I press "Add comment"
    And I wait until ".studentquiz-comment-item:nth-child(1)" "css_element" exists
    Then I should see "Comment 1" in the ".studentquiz-comment-item:nth-child(1) .studentquiz-comment-text" "css_element"
