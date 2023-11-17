@mod @mod_studentquiz

Feature: Backup and restore activity studentquiz
  In order to reuse my studentquizzes has availability setting
  As a admin
  I need to be able to use the moodles backup and restore features and
  keep availability setting.

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |

  @javascript @_file_upload
  Scenario: Restore moodle backups containing old StudentQuiz activity has availability and question publishing setting.
    When I am on the "Course 1" "restore" page logged in as "admin"
    And I press "Manage backup files"
    And I upload "mod/studentquiz/tests/fixtures/backup-moodle2-availability-setting.mbz" file to "Files" filemanager
    And I press "Save changes"
    And I restore "backup-moodle2-availability-setting.mbz" backup into a new course using this options:
    And I am on the "Studentquiz test" "mod_studentquiz > Edit" page
    And I expand all fieldsets
    Then the following fields match these values:
      | Question publishing            | 1    |
      | id_opensubmissionfrom_day      | 21   |
      | id_opensubmissionfrom_month    | 2    |
      | id_opensubmissionfrom_year     | 2023 |
      | id_opensubmissionfrom_hour     | 17   |
      | id_opensubmissionfrom_minute   | 0    |
      | id_opensubmissionfrom_enabled  | 1    |
      | id_closesubmissionfrom_day     | 22   |
      | id_closesubmissionfrom_month   | 2    |
      | id_closesubmissionfrom_year    | 2023 |
      | id_closesubmissionfrom_hour    | 17   |
      | id_closesubmissionfrom_minute  | 0    |
      | id_closesubmissionfrom_enabled | 1    |
      | id_openansweringfrom_day       | 23   |
      | id_openansweringfrom_month     | 2    |
      | id_openansweringfrom_year      | 2023 |
      | id_openansweringfrom_hour      | 17   |
      | id_openansweringfrom_minute    | 0    |
      | id_openansweringfrom_enabled   | 1    |
      | id_closeansweringfrom_day      | 24   |
      | id_closeansweringfrom_month    | 2    |
      | id_closeansweringfrom_year     | 2023 |
      | id_closeansweringfrom_hour     | 17   |
      | id_closeansweringfrom_minute   | 0    |
      | id_closeansweringfrom_enabled  | 1    |
