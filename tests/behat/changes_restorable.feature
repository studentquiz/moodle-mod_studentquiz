@mod @mod_studentquiz
Feature: Stable restore of moodle course backups
  In order to keep the studentquiz repository healthy
  As a code contributor
  I need to be able to verify my changes are still restorable

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "activities" exist:
      | activity    | name          | intro              | course | idnumber     | publishnewquestion |
      | studentquiz | StudentQuiz 1 | Quiz 1 description | C1     | studentquiz1 | 1                  |
    And the following "questions" exist:
      | questioncategory          | qtype | name                       | questiontext                  |
      | Default for StudentQuiz 1 | essay | Test question to be copied | Write about whatever you want |

  @javascript @_file_upload
  Scenario: Backup and restore of the current code base
    When I log in as "admin"
    And I backup "Course 1" course using this options:
      | Confirmation | Filename | test_backup.mbz |
    And I restore "test_backup.mbz" backup into a new course using this options:
    And I am on "Course 1" course homepage
    And I follow "StudentQuiz 1"
    And I should see "Create new question"
    And "Start Quiz" "button" should exist
