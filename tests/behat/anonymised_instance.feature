@mod @mod_studentquiz
Feature: StudentQuiz anonymous mode
  In order for clear to user
  As a user
  I want to see (anonymised) in Ranking block when StudentQuiz is a anonymous instance.

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Sam1      | Student1 | student1@example.com |
      | student2 | Sam2      | Student2 | student2@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |
      | student2 | C1     | student |

  Scenario: Student should not see anonymised when StudentQuiz is a non-anonymous instance
    Given the following "activities" exist:
      | activity    | name          | intro              | course | idnumber     | anonymrank |
      | studentquiz | StudentQuiz 1 | Quiz 1 description | C1     | studentquiz1 | 0          |
    And I log in as "student1"
    And I am on "Course 1" course homepage
    When I follow "StudentQuiz 1"
    Then I should see "Ranking" in the "#mod_studentquiz_rankingblock" "css_element"
    And I should not see "Ranking (anonymised)" in the "#mod_studentquiz_rankingblock" "css_element"

  Scenario: Student should see anonymised when StudentQuiz is a anonymous instance
    Given the following "activities" exist:
      | activity    | name          | intro              | course | idnumber     | anonymrank |
      | studentquiz | StudentQuiz 1 | Quiz 1 description | C1     | studentquiz1 | 1          |
    And I log in as "student1"
    And I am on "Course 1" course homepage
    When I follow "StudentQuiz 1"
    Then I should see "Ranking (anonymised)" in the "#mod_studentquiz_rankingblock" "css_element"
