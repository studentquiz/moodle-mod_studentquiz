@mod @mod_studentquiz
Feature: Backup and restore of moodle exports
  In order to reuse my studentquizzes
  As a admin
  I need to be able to use the moodles backup and restore features

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Sam1      | Student1 | student1@example.com |
    And I log in as "admin"

  @javascript @_file_upload
  Scenario Outline: Restore various moodle version backups containing studentquiz activity
    When I am on "Course 1" course homepage
    And I navigate to "Restore" node in "Course administration"
    And I press "Manage backup files"
    And I upload "mod/studentquiz/tests/fixtures/<file>" file to "Files" filemanager
    And I press "Save changes"
    And I restore "<file>" backup into a new course using this options:
    And "//*[contains(@href, '#section-999')]" "xpath_element" should not exist
    And I log out
    Then the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | MDK     | student       |
    And I log in as "student1"
    And I am on "Moodle Development" course homepage
    And I follow "Backup and Restore StudentQuiz Test"
    And I should see "Create new question"
    # The following creates a new quiz instance, because there is no existing quiz in the backup with the full question set.
    And I click on "Start Quiz" "button"
    And I should see "Attempt quiz now"

    Examples:
      | file                                             |
      | backup-moodle2-course_moodle30_sq203_nofiles.mbz |
      | backup-moodle2-course_moodle31_sq203_nofiles.mbz |
      | backup-moodle2-course_moodle32_sq203_nofiles.mbz |
      | backup-moodle2-course_moodle33_sq203_nofiles.mbz |
