@mod @mod_studentquiz
Feature: Backup and restore of studentquizzes
  In order to reuse my studentquizzes
  As a teacher
  I need to be able to back them up and restore them.

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
    And I log in as "teacher1"

  @javascript @_file_upload
  Scenario: Restore a Studentquiz 2.0.3 xml backup
    When I am on "Course 1" course homepage
    And I follow "studentquiz 0"
    And I follow "Import"
    And I set the field "format" to "xml"
    And I upload "mod/studentquiz/tests/fixtures/studentquiz-export-v2.0.3.xml" file to "Import" filemanager
    And I press "Import"
    Then I wait until the page is ready
    And I should see "Importing 323 questions from file"
