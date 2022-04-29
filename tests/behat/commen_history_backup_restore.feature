@mod @mod_studentquiz
Feature: Restore of studentquizzes in moodle exports with comment histories
  In order to reuse my studentquizzes
  As a admin
  I need to be able to restore the moodles backups from old studentquizzes, and the comment history feature work normally

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |

  @javascript @_file_upload @_switch_window
  Scenario: Restore moodle backups containing history comments.
    Given I am on the "C1" "Course" page logged in as "admin"
    And I navigate to "Course reuse" in current page administration
    And I select "Restore" from the "jump" singleselect
    And I press "Manage backup files"
    And I upload "mod/studentquiz/tests/fixtures/backup-moodle311-c1-historycomment.mbz" file to "Files" filemanager
    And I press "Save changes"
    And I restore "backup-moodle311-c1-historycomment.mbz" backup into a new course using this options:
    And I am on the "StudentQuiz 1" "mod_studentquiz > View" page
    And I choose "Preview" action for "Example question of Student 1" in the question bank
    When I switch to "questionpreview" window
    Then I should see "Comment 1 edited" in the ".studentquiz-comment-item:nth-child(1) .studentquiz-comment-text" "css_element"
    And I should see "Edited by the Author" in the ".studentquiz-comment-item:nth-child(1) .studentquiz-comment-text" "css_element"
    And I should see "Deleted post" in the ".studentquiz-comment-item:nth-child(2) .studentquiz-comment-item-outerbox" "css_element"
    And I click on "History" "link" in the ".studentquiz-comment-item:nth-child(1) .studentquiz-comment-text" "css_element"
    And I switch to "Comment history" window
    And I should see "Comment 1 edited"
    And I switch to "questionpreview" window
    And I press "Expand all comments"
    And I should see "Reply comment 1" in the ".studentquiz-comment-item:nth-child(1) .studentquiz-comment-replies .studentquiz-comment-item:nth-child(1) .studentquiz-comment-text" "css_element"
    And I should see "Teacher reply comment 1" in the ".studentquiz-comment-item:nth-child(1) .studentquiz-comment-replies .studentquiz-comment-item:nth-child(2) .studentquiz-comment-text" "css_element"
