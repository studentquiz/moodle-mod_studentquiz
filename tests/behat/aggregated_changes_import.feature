@mod @mod_studentquiz
Feature: Restore of studentquizzes in moodle exports contain question answers
  In order to reuse my studentquizzes
  As a admin
  I need to be able to restore the moodles backups from studentquizzes before, during and after aggregated codebase

  Legend:
  - before: The StudentQuiz codebase before aggregated was introduced (<= v3.2.0)
  - during: The StudentQuiz codebase during aggregated was active (>= v3.2.1, <= v3.3.0), which had a setting aggregated:
  - 0: which was the old calculation using the question engine (equals before)
  - 1: which is the new calculation using studentquiz_progress table (equals after)
  - after: The StudentQuiz codebase where all activities are migrated to the new calculation and the codebase has no code from before except for the restore (>= 3.4.0)

  Background:
    # TODO: I don't think we need an extra course
    # Given the following "courses" exist:
    #   | fullname | shortname | category |
    #   | Course 1 | C1        | 0        |
    # TODO: I don't think we need here an extra student
    # And the following "users" exist:
    #   | username | firstname | lastname | email                |
    #   | student1 | Sam1      | Student1 | student1@example.com |
    And I log in as "admin"

  @javascript @_file_upload
  Scenario Outline: Restore moodle backups containing old StudentQuiz activity
    # TODO: I don't think we need an extra course
    # When I am on "Course 1" course homepage
    And I navigate to "Restore" in current page administration
    And I press "Manage backup files"
    And I upload "mod/studentquiz/tests/fixtures/<file>" file to "Files" filemanager
    And I press "Save changes"
    And I restore "<file>" backup into a new course using this options:
    # TODO: I don't think we need here an extra student
    # And I log out
    # Then the following "course enrolments" exist:
    #   | user     | course   | role    |
    #   | student1 | <course> | student |
    # And I log in as "student1"
    And I am on "<course>" course homepage
    And I follow "<studentquiz>"
    When I navigate to "Ranking" in current page administration
    # Then I should see ... TODO prosa:
    # ... first position correct answered is "<pos_1_correct_answered_points>"
    # ... first position total is "<pos_1_total_points>"
    # ... second position correct answered is "<pos_2_correct_answered_points>"
    # ... second position total is "<pos_2_total_points>"
    # ... third position correct answered is "<pos_3_correct_answered_points>"
    # ... third position total is "<pos_3_total_points>"

    Examples:
      | file                                         | course     | studentquiz   | pos_1_correct_answered_points | pos_1_total_points | pos_2_correct_answered_points | pos_2_total_points | pos_3_correct_answered_points | pos_3_total_points |
      | backup-moodle2-aggregated-before.mbz         | Course One | StudentQuiz 1 | 0                             | 0                  | 0                             | 0                  | 0                             | 0                  |
      | backup-moodle2-aggregated-during-value-0.mbz | Course One | StudentQuiz 1 | 0                             | 0                  | 0                             | 0                  | 0                             | 0                  |
      | backup-moodle2-aggregated-during-value-1.mbz | Course One | StudentQuiz 1 | 0                             | 0                  | 0                             | 0                  | 0                             | 0                  |
      | backup-moodle2-aggregated-after.mbz          | Course One | StudentQuiz 1 | 0                             | 0                  | 0                             | 0                  | 0                             | 0                  |