@mod @mod_studentquiz
Feature: Testing overview integration in studentquiz activity
  In order to summarize the studentquiz activity
  As a user
  I need to be able to see the studentquiz activity overview

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | The       | Teacher  | teacher1@example.com |
      | student1 | Student   | One      | student1@example.com |
      | student2 | Student   | Two      | student2@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
    And the following "activities" exist:
      | activity    | name             | intro                   | course | idnumber     |
      | studentquiz | StudentQuiz Test | StudentQuiz description | C1     | studentquiz1 |
    And the following "questions" exist:
      | questioncategory             | qtype     | name       |
      | Default for StudentQuiz Test | truefalse | Question 1 |
      | Default for StudentQuiz Test | truefalse | Question 2 |
      | Default for StudentQuiz Test | truefalse | Question 3 |
    And I am on the "StudentQuiz Test" "mod_studentquiz > Edit" page logged in as "admin"
    And I expand all fieldsets
    And I set the field "id_opensubmissionfrom_enabled" to "1"
    And I set the availability field "opensubmissionfrom" to "+1" days from now
    And I set the field "id_closesubmissionfrom_enabled" to "1"
    And I set the availability field "closesubmissionfrom" to "+2" days from now
    And I set the field "id_openansweringfrom_enabled" to "1"
    And I set the availability field "openansweringfrom" to "+3" days from now
    And I set the field "id_closeansweringfrom_enabled" to "1"
    And I set the availability field "closeansweringfrom" to "+4" days from now
    And I press "Save and display"

  @javascript
  Scenario: The studentquiz activity overview report should generate log events
    Given the site is running Moodle version 5.0 or higher
    And I am on the "Course 1" "course > activities > studentquiz" page logged in as "teacher1"
    When I am on the "Course 1" "course" page logged in as "teacher1"
    And I navigate to "Reports" in current page administration
    And I click on "Logs" "link"
    And I click on "Get these logs" "button"
    Then I should see "Course activities overview page viewed"
    And I should see "viewed the instance list for the module 'studentquiz'"

  @javascript
  Scenario: The studentquiz activity index redirect to the activities overview
    Given the site is running Moodle version 5.0 or higher
    When I am on the "C1" "course > activities > studentquiz" page logged in as "admin"
    Then I should see "Name" in the "studentquiz_overview_collapsible" "region"
    And I should see "Submissions from" in the "studentquiz_overview_collapsible" "region"
    And I should see "Submissions until" in the "studentquiz_overview_collapsible" "region"
    And I should see "Answering from" in the "studentquiz_overview_collapsible" "region"
    And I should see "Answering until" in the "studentquiz_overview_collapsible" "region"
    And I should see "Action" in the "studentquiz_overview_collapsible" "region"
