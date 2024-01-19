@mod @mod_studentquiz

Feature: Restore specific studentquiz old backup to test UI feature

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |

  @javascript @_file_upload
  Scenario: Restore moodle backups containing history comments.
    Given I am on the "Course 1" "restore" page logged in as "admin"
    # Main branch has change the text to "Manage course backups" so we should use xpath.
    And I click on "(//*[@class='singlebutton']//button)[1]" "xpath_element"
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

  @javascript @_file_upload @_switch_window
  Scenario: Restore moodle backups containing old StudentQuiz activity without state history table.
    Given I am on the "Course 1" "restore" page logged in as "admin"
    # Main branch has change the text to "Manage course backups" so we should use xpath.
    And I click on "(//*[@class='singlebutton']//button)[1]" "xpath_element"
    And I upload "mod/studentquiz/tests/fixtures/backup-moodle2-course-3-sqo-20211011-missing_state_history.mbz" file to "Files" filemanager
    And I press "Save changes"
    And I restore "backup-moodle2-course-3-sqo-20211011-missing_state_history.mbz" backup into a new course using this options:
    And I am on the "StudentQuiz One" "mod_studentquiz > View" page
    And I should see "Question Test 1"
    And I choose "Preview" action for "Question Test 1" in the question bank
    And I switch to "questionpreview" window
    When I click on "History" "link"
    Then I should see "James Potter" in the "Question saved ('Draft')" "table_row"
    And I should see "-" in the "Question set to 'Approved'" "table_row"
