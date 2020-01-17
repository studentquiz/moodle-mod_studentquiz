@mod @mod_studentquiz
Feature: Create comment as an user
  In order to join the comment area
  As a user
  I need to be able to create comment

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher  | The       | Teacher  | teacher1@example.com |
      | student1 | Student   | One      | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher  | C1     | editingteacher |
      | student1 | C1     | student        |
    And the following "activities" exist:
      | activity    | name          | intro              | course | idnumber     | forcecommenting | publishnewquestion |
      | studentquiz | StudentQuiz 1 | Quiz 1 description | C1     | studentquiz1 | 1               | 1                  |
    And the following "questions" exist:
      | questioncategory          | qtype     | name                          | questiontext          |
      | Default for StudentQuiz 1 | truefalse | Test question to be previewed | Answer the question 1 |

  @javascript
  Scenario: Test show initital view and Expand all comment/ Collapse all comment button. Check both start quiz and preview mode
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    # Prepare comments and replies.
    And I follow "StudentQuiz 1"
    When I click on "Start Quiz" "button"
    Then I set the field "True" to "1"
    And I press "Check"
    # Wait for comment area init.
    And I wait until the page is ready
    # Enter "Comment 1".
    When I enter the text "Comment 1" into the "Add comment" editor
    Then I press "Add comment"
    And I wait until the page is ready
    And I wait until ".studentquiz-comment-item:nth-child(1)" "css_element" exists
    And I should see "Comment 1" in the ".studentquiz-comment-item:nth-child(1) .studentquiz-comment-text" "css_element"
    # Enter "Comment 2"
    When I enter the text "Comment 2" into the "Add comment" editor
    Then I press "Add comment"
    And I wait until the page is ready
    And I wait until ".studentquiz-comment-item:nth-child(2)" "css_element" exists
    And I should see "Comment 2" in the ".studentquiz-comment-item:nth-child(2) .studentquiz-comment-text" "css_element"
    # Enter "Comment 3"
    When I enter the text "Comment 3" into the "Add comment" editor
    Then I press "Add comment"
    And I wait until the page is ready
    And I wait until ".studentquiz-comment-item:nth-child(3)" "css_element" exists
    And I should see "Comment 3" in the ".studentquiz-comment-item:nth-child(3) .studentquiz-comment-text" "css_element"
    # Enter "Comment 4"
    When I enter the text "Comment 4" into the "Add comment" editor
    Then I press "Add comment"
    And I wait until the page is ready
    And I wait until ".studentquiz-comment-item:nth-child(4)" "css_element" exists
    And I should see "Comment 4" in the ".studentquiz-comment-item:nth-child(4) .studentquiz-comment-text" "css_element"
    # Enter "Comment 5"
    When I enter the text "Comment 5" into the "Add comment" editor
    Then I press "Add comment"
    And I wait until the page is ready
    And I wait until ".studentquiz-comment-item:nth-child(5)" "css_element" exists
    And I should see "Comment 5" in the ".studentquiz-comment-item:nth-child(5) .studentquiz-comment-text" "css_element"
    # Enter "Comment 6"
    When I enter the text "Comment 6" into the "Add comment" editor
    And I press "Add comment"
    And I wait until the page is ready
    And I wait until ".studentquiz-comment-item:nth-child(6)" "css_element" exists
    And I should see "Comment 6" in the ".studentquiz-comment-item:nth-child(6) .studentquiz-comment-text" "css_element"
    Then I should see "Collapse all comments"
    # Click "Collapse all comments" button, page should render like initial view.
    When I press "Collapse all comments"
    And I wait until the page is ready
    Then I should see "Expand all comments"
    And I should not see "Collapse all comments"
    And I should see "5 of 6" in the ".studentquiz-comment-postcount" "css_element"
    And I should not see "Comment 1" in the ".studentquiz-comment-item:nth-child(1) .studentquiz-comment-text" "css_element"
    And I should see "Comment 2" in the ".studentquiz-comment-item:nth-child(1) .studentquiz-comment-text" "css_element"
    And I should see "Comment 3" in the ".studentquiz-comment-item:nth-child(2) .studentquiz-comment-text" "css_element"
    And I should see "Comment 4" in the ".studentquiz-comment-item:nth-child(3) .studentquiz-comment-text" "css_element"
    And I should see "Comment 5" in the ".studentquiz-comment-item:nth-child(4) .studentquiz-comment-text" "css_element"
    And I should see "Comment 6" in the ".studentquiz-comment-item:nth-child(5) .studentquiz-comment-text" "css_element"
    # Click "Expand all comments" button, check that all comments and replies is show.
    When I press "Expand all comments"
    And I wait until the page is ready
    Then I should see "Collapse all comments"
    And I should not see "Expand all comments"
    And I should see "6 of 6" in the ".studentquiz-comment-postcount" "css_element"
    And I should see "Comment 1" in the ".studentquiz-comment-item:nth-child(1) .studentquiz-comment-text" "css_element"
    And I should see "Comment 2" in the ".studentquiz-comment-item:nth-child(2) .studentquiz-comment-text" "css_element"
    And I should see "Comment 3" in the ".studentquiz-comment-item:nth-child(3) .studentquiz-comment-text" "css_element"
    And I should see "Comment 4" in the ".studentquiz-comment-item:nth-child(4) .studentquiz-comment-text" "css_element"
    And I should see "Comment 5" in the ".studentquiz-comment-item:nth-child(5) .studentquiz-comment-text" "css_element"
    And I should see "Comment 6" in the ".studentquiz-comment-item:nth-child(6) .studentquiz-comment-text" "css_element"
    And I should see "0" in the ".studentquiz-comment-item:nth-child(1) .studentquiz-comment-totalreply" "css_element"
    And I should see "Replies" in the ".studentquiz-comment-item:nth-child(1) .studentquiz-comment-totalreply" "css_element"
    And I should see "0" in the ".studentquiz-comment-item:nth-child(2) .studentquiz-comment-totalreply" "css_element"
    And I should see "Replies" in the ".studentquiz-comment-item:nth-child(2) .studentquiz-comment-totalreply" "css_element"
    And I should see "0" in the ".studentquiz-comment-item:nth-child(3) .studentquiz-comment-totalreply" "css_element"
    And I should see "Replies" in the ".studentquiz-comment-item:nth-child(3) .studentquiz-comment-totalreply" "css_element"
    And I should see "0" in the ".studentquiz-comment-item:nth-child(4) .studentquiz-comment-totalreply" "css_element"
    And I should see "Replies" in the ".studentquiz-comment-item:nth-child(4) .studentquiz-comment-totalreply" "css_element"
    And I should see "0" in the ".studentquiz-comment-item:nth-child(5) .studentquiz-comment-totalreply" "css_element"
    And I should see "Replies" in the ".studentquiz-comment-item:nth-child(5) .studentquiz-comment-totalreply" "css_element"
    And I should see "0" in the ".studentquiz-comment-item:nth-child(6) .studentquiz-comment-totalreply" "css_element"
    And I should see "Replies" in the ".studentquiz-comment-item:nth-child(6) .studentquiz-comment-totalreply" "css_element"
    # Check in preview.
    When I am on "Course 1" course homepage
    And I follow "StudentQuiz 1"
    Then I click on "Preview" "link" in the "Test question to be previewed" "table_row"
    And I switch to "questionpreview" window
    And I wait until the page is ready
    # We only show max 5 latest comments.
    Then I should not see "Comment 1" in the ".studentquiz-comment-item:nth-child(1) .studentquiz-comment-text" "css_element"
    And I should see "Comment 2" in the ".studentquiz-comment-item:nth-child(1) .studentquiz-comment-text" "css_element"
    And I should see "Comment 3" in the ".studentquiz-comment-item:nth-child(2) .studentquiz-comment-text" "css_element"
    And I should see "Comment 4" in the ".studentquiz-comment-item:nth-child(3) .studentquiz-comment-text" "css_element"
    And I should see "Comment 5" in the ".studentquiz-comment-item:nth-child(4) .studentquiz-comment-text" "css_element"
    And I should see "Comment 6" in the ".studentquiz-comment-item:nth-child(5) .studentquiz-comment-text" "css_element"

  @javascript
  Scenario: Test reply comment.
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    # Prepare comments and replies.
    And I follow "StudentQuiz 1"
    When I click on "Start Quiz" "button"
    Then I set the field "True" to "1"
    And I press "Check"
    # Wait for comment area init.
    And I wait until the page is ready
    When I enter the text "Comment 1" into the "Add comment" editor
    And I press "Add comment"
    And I wait until the page is ready
    Then I should see "Comment 1" in the ".studentquiz-comment-item:nth-child(1) .studentquiz-comment-text" "css_element"
    # Check can reply
    When I click on "Reply" "button" in the ".studentquiz-comment-item:nth-child(1) .studentquiz-comment-commands-buttons" "css_element"
    # Wait for reply init.
    And I wait until the page is ready
    And I enter the text "Reply comment 1" into the "Add reply" editor
    And I press "Add reply"
    And I wait until the page is ready
    Then I should see "1" in the ".studentquiz-comment-item:nth-child(1) .studentquiz-comment-totalreply" "css_element"
    And I should see "Reply" in the ".studentquiz-comment-item:nth-child(1) .studentquiz-comment-totalreply" "css_element"

  @javascript
  Scenario: Test delete comment feature.
    # Save document into course 1.
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "StudentQuiz 1"
    When I click on "Start Quiz" "button"
    Then I set the field "True" to "1"
    And I press "Check"
    # Wait for comment area init.
    And I wait until the page is ready
    Then I enter the text "Comment 1" into the "Add comment" editor
    And I press "Add comment"
    And I wait until the page is ready
    Then I should see "Comment 1" in the ".studentquiz-comment-item:nth-child(1) .studentquiz-comment-text" "css_element"
    # Check if delete button visible
    And I should see "Delete" in the ".studentquiz-comment-item:nth-child(1) .studentquiz-comment-commands-box" "css_element"
    And I should see "1 of 1"
    # Try to delete comment.
    When I click on "Delete" "button" in the ".studentquiz-comment-item:nth-child(1) .studentquiz-comment-commands-box" "css_element"
    # Sometime behat env seems very slow.
    And I wait until the page is ready
    And I click on "[title='Delete comment']" "css_element" in the ".modal.show" "css_element"
    And I wait until the page is ready
    # Check comment is render as deleted and global count updated.
    Then I should see "Comment deleted" in the ".studentquiz-comment-item:nth-child(1) .studentquiz-comment-item-outerbox" "css_element"
    And I should see "0 of 0"

  @javascript
  Scenario: Test force comment (as student)
    # Save document into course 1.
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "StudentQuiz 1"
    When I click on "Start Quiz" "button"
    Then I set the field "True" to "1"
    And I press "Check"
    And I wait until the page is ready
    When I press "Finish"
    Then I should see "Please comment"
    When I enter the text "Comment 1" into the "Add comment" editor
    Then I press "Add comment"
    And I wait until the page is ready
    When I press "Finish"
    Then I should not see "Please comment"

  @javascript
  Scenario: Admin delete comment and check if student can view.
    # Save document into course 1.
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "StudentQuiz 1"
    When I click on "Start Quiz" "button"
    Then I set the field "True" to "1"
    And I press "Check"
    # Wait for comment area init.
    And I wait until the page is ready
    When I enter the text "Comment 1" into the "Add comment" editor
    And I press "Add comment"
    And I wait until the page is ready
    Then I should see "Comment 1" in the ".studentquiz-comment-item:nth-child(1) .studentquiz-comment-text" "css_element"
    # Check if delete button visible
    And I should see "Delete" in the ".studentquiz-comment-item:nth-child(1) .studentquiz-comment-commands-box" "css_element"
    And I should see "1 of 1"
    # Try to delete comment.
    When I click on "Delete" "button" in the ".studentquiz-comment-item:nth-child(1) .studentquiz-comment-commands-box" "css_element"
    And I click on "[title='Delete comment']" "css_element" in the ".modal.show" "css_element"
    And I wait until the page is ready
    # Check comment is render as deleted and global count updated.
    Then I should see "Comment deleted" in the ".studentquiz-comment-item:nth-child(1) .studentquiz-comment-item-outerbox" "css_element"
    And I should see "0 of 0"
    And I log out
    # Student log in and see it or not
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "StudentQuiz 1"
    When I click on "Start Quiz" "button"
    Then I set the field "True" to "1"
    And I press "Check"
    And I wait until the page is ready
    And I should not see "Comment 1"
