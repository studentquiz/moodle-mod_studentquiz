@mod @mod_studentquiz
Feature: Restore of studentquizzes in moodle exports without state history table
  In order to reuse my studentquizzes
  As a admin
  I need to be able to restore the moodles backups from old studentquizzes, and the state history feature work normally

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And I log in as "admin"

  @javascript @_file_upload @_switch_window
  Scenario: Restore moodle backups containing old StudentQuiz activity without state history table
    When I am on "Course 1" course homepage
    And I navigate to "Course reuse" in current page administration
    And I select "Restore" from the "jump" singleselect
    And I press "Manage backup files"
    And I upload "mod/studentquiz/tests/fixtures/backup-moodle2-course-3-sqo-20211011-missing_state_history.mbz" file to "Files" filemanager
    And I press "Save changes"
    And I restore "backup-moodle2-course-3-sqo-20211011-missing_state_history.mbz" backup into a new course using this options:
    And I am on the "StudentQuiz One" "mod_studentquiz > View" page
    And I should see "Question Test 1"
    And I choose "Preview" action for "Question Test 1" in the question bank
    And I switch to "questionpreview" window
    Then I click on "History" "link"
    And I should see "James Potter" in the "Question saved ('Draft')" "table_row"
    And I should see "-" in the "Question set to 'Approved'" "table_row"
