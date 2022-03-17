@mod @mod_studentquiz
Feature: Question states and visibility
  In order not to change the state and visibility of questions
  As a teacher
  I need a question publishing option, a select box allow to change the question state and visibility and filter for states and visibility

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "activities" exist:
      | activity    | name               | intro                     | course | idnumber     | publishnewquestion |
      | studentquiz | StudentQuiz Test 1 | StudentQuiz description 1 | C1     | studentquiz1 | 0                  |
      | studentquiz | StudentQuiz Test 2 | StudentQuiz description 2 | C1     | studentquiz2 | 1                  |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Sam1      | Student1 | student1@example.com |
      | student2 | Sam2      | Student2 | student2@example.com |
      | teacher1 | David     | Teacher1 | teacher1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |
      | student2 | C1     | student |
      | teacher1 | C1     | teacher |
    # Set window size to large so we can see the navigation.
    And I change window size to "large"

  @javascript
  Scenario: Test Publish new questions setting
    When I am on the "StudentQuiz Test 1" "mod_studentquiz > View" page logged in as "student1"
    And I click on "Create new question" "button"
    And I set the field "item_qtype_truefalse" to "1"
    And I click on "Add" "button" in the "Choose a question type to add" "dialogue"
    And I set the field "Question name" to "TF 01"
    And I set the field "Question text" to "The correct answer is false"
    And I press "id_submitbutton"
    And I am on the "StudentQuiz Test 2" "mod_studentquiz > View" page
    And I click on "Create new question" "button"
    And I set the field "item_qtype_truefalse" to "1"
    And I click on "Add" "button" in the "Choose a question type to add" "dialogue"
    And I set the field "Question name" to "TF 01"
    And I set the field "Question text" to "The correct answer is false"
    And I press "id_submitbutton"
    And I log out
    And I am on the "StudentQuiz Test 1" "mod_studentquiz > View" page logged in as "student2"
    Then I should not see "TF 01"
    And I am on the "StudentQuiz Test 2" "mod_studentquiz > View" page
    And I should see "TF 01"
    And I log out
    And I am on the "StudentQuiz Test 1" "mod_studentquiz > View" page logged in as "admin"
    And I should see "TF 01"
    And I am on the "StudentQuiz Test 2" "mod_studentquiz > View" page
    And I should see "TF 01"

  @javascript @_switch_window
  Scenario: Test filter
    When I am on the "StudentQuiz Test 2" "mod_studentquiz > View" page logged in as "student1"

    And I click on "Create new question" "button"
    And I set the field "item_qtype_truefalse" to "1"
    And I click on "Add" "button" in the "Choose a question type to add" "dialogue"
    And I set the field "Question name" to "TF 01"
    And I set the field "Question text" to "The correct answer is false"
    And I press "id_submitbutton"

    And I click on "Create new question" "button"
    And I set the field "item_qtype_truefalse" to "1"
    And I click on "Add" "button" in the "Choose a question type to add" "dialogue"
    And I set the field "Question name" to "TF 02"
    And I set the field "Question text" to "The correct answer is false"
    And I press "id_submitbutton"

    And I click on "Create new question" "button"
    And I set the field "item_qtype_truefalse" to "1"
    And I click on "Add" "button" in the "Choose a question type to add" "dialogue"
    And I set the field "Question name" to "TF 03"
    And I set the field "Question text" to "The correct answer is false"
    And I press "id_submitbutton"

    And I click on "Create new question" "button"
    And I set the field "item_qtype_truefalse" to "1"
    And I click on "Add" "button" in the "Choose a question type to add" "dialogue"
    And I set the field "Question name" to "TF 04"
    And I set the field "Question text" to "The correct answer is false"
    And I press "id_submitbutton"

    And I log out
    And I am on the "StudentQuiz Test 2" "mod_studentquiz > View" page logged in as "admin"

    And I choose "Preview" action for "TF 01" in the question bank
    And I switch to "questionpreview" window
    And I set the field "statetype" to "Disapproved"
    And I click on "Change state" "button"
    And I switch to the main window

    And I choose "Preview" action for "TF 02" in the question bank
    And I switch to "questionpreview" window
    And I set the field "statetype" to "Approved"
    And I click on "Change state" "button"
    And I switch to the main window

    And I choose "Preview" action for "TF 03" in the question bank
    And I switch to "questionpreview" window
    And I set the field "statetype" to "Changed"
    And I click on "Change state" "button"
    And I switch to the main window

    And I click on "//a[text() = 'New']" "xpath_element"
    And I press "id_submitbutton"
    Then I should see "TF 04"
    And I should not see "TF 01"
    And I should not see "TF 02"
    And I should not see "TF 03"
    And I click on "Reset" "button"

    And I click on "//a[text() = 'Approved']" "xpath_element"
    And I press "id_submitbutton"
    And I should see "TF 02"
    And I should not see "TF 01"
    And I should not see "TF 03"
    And I should not see "TF 04"
    And I click on "Reset" "button"

    And I click on "//a[text() = 'Disapproved']" "xpath_element"
    And I press "id_submitbutton"
    And I should see "TF 01"
    And I should not see "TF 02"
    And I should not see "TF 03"
    And I should not see "TF 04"
    And I click on "Reset" "button"

    And I click on "//a[text() = 'Changed']" "xpath_element"
    And I press "id_submitbutton"
    And I should see "TF 03"
    And I should not see "TF 01"
    And I should not see "TF 02"
    And I should not see "TF 04"
    And I click on "Reset" "button"

  @javascript
  Scenario: Hide question
    When I am on the "StudentQuiz Test 1" "mod_studentquiz > View" page logged in as "admin"
    And I click on "Create new question" "button"
    And I set the field "item_qtype_truefalse" to "1"
    And I click on "Add" "button" in the "Choose a question type to add" "dialogue"
    And I set the field "Question name" to "TF 01"
    And I set the field "Question text" to "The correct answer is false"
    And I press "id_submitbutton"
    And I log out
    And I am on the "StudentQuiz Test 1" "mod_studentquiz > View" page logged in as "student1"
    Then I should not see "TF 01"
    And I log out
    And I am on the "StudentQuiz Test 1" "mod_studentquiz > View" page logged in as "admin"
    And I choose "Show" action for "TF 01" in the question bank
    And I log out
    And I am on the "StudentQuiz Test 1" "mod_studentquiz > View" page logged in as "student1"
    And I should see "TF 01"

  @javascript @_switch_window
  Scenario: Test Studentquiz cannot edit approved/disapproved question
    When I am on the "StudentQuiz Test 2" "mod_studentquiz > View" page logged in as "student1"

    And I click on "Create new question" "button"
    And I set the field "item_qtype_truefalse" to "1"
    And I click on "Add" "button" in the "Choose a question type to add" "dialogue"
    And I set the field "Question name" to "TF 01"
    And I set the field "Question text" to "The correct answer is false"
    And I press "id_submitbutton"

    And I click on "Create new question" "button"
    And I set the field "item_qtype_truefalse" to "1"
    And I click on "Add" "button" in the "Choose a question type to add" "dialogue"
    And I set the field "Question name" to "TF 02"
    And I set the field "Question text" to "The correct answer is false"
    And I press "id_submitbutton"

    And I should see "Edit question" action for "TF 01" in the question bank
    And I should see "Edit question" action for "TF 02" in the question bank

    And I log out
    And I am on the "StudentQuiz Test 2" "mod_studentquiz > View" page logged in as "admin"
    And I choose "Preview" action for "TF 01" in the question bank
    And I switch to "questionpreview" window
    And I set the field "statetype" to "Approved"
    And I click on "Change state" "button"
    And I switch to the main window

    And I choose "Preview" action for "TF 02" in the question bank
    And I switch to "questionpreview" window
    And I set the field "statetype" to "Disapproved"
    And I click on "Change state" "button"
    And I switch to the main window

    And I log out
    And I am on the "StudentQuiz Test 2" "mod_studentquiz > View" page logged in as "student1"

    And I should not see "Edit question" action for "TF 01" in the question bank
    And I should not see "Edit question" action for "TF 02" in the question bank

  @javascript
  Scenario: Pin question
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "StudentQuiz Test 1"
    And I click on "Create new question" "button"
    And I set the field "item_qtype_truefalse" to "1"
    And I click on "Add" "button" in the "Choose a question type to add" "dialogue"
    And I set the field "Question name" to "TF 01"
    And I set the field "Question text" to "The correct answer is false"
    And I press "id_submitbutton"
    And I click on "Create new question" "button"
    And I set the field "item_qtype_truefalse" to "2"
    And I click on "Add" "button" in the "Choose a question type to add" "dialogue"
    And I set the field "Question name" to "TF 02"
    And I set the field "Question text" to "The correct answer is false"
    And I press "id_submitbutton"
    And I log out
    And I log in as "admin"
    And I am on "Course 1" course homepage
    When I follow "StudentQuiz Test 1"
    Then I should see "TF 01"
    And I choose "Pin question" action for "TF 01" in the question bank
    And I should see "Unpin question" action for "TF 01" in the question bank
    And I should not see "Unpin question" action for "TF 02" in the question bank
    And "Pinned" "icon" should exist in the "TF 01" "table_row"
    And "Pinned" "icon" should not exist in the "TF 02" "table_row"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "StudentQuiz Test 1"
    And I should see "TF 01"
    And I should not see "Pin question" action for "TF 01" in the question bank
    And I should not see "Pin question" action for "TF 02" in the question bank
    And "Pinned" "icon" should exist in the "TF 01" "table_row"
    And "Pinned" "icon" should not exist in the "TF 02" "table_row"

  @javascript @_switch_window
  Scenario: The student can not delete the question when the question has been approved state
    When I am on the "StudentQuiz Test 2" "mod_studentquiz > View" page logged in as "student1"

    And I click on "Create new question" "button"
    And I set the field "item_qtype_truefalse" to "1"
    And I click on "Add" "button" in the "Choose a question type to add" "dialogue"
    And I set the field "Question name" to "TF 01"
    And I set the field "Question text" to "The correct answer is false"
    And I press "id_submitbutton"

    And I choose "Preview" action for "TF 01" in the question bank
    And I switch to "questionpreview" window
    And I set the field "statetype" to "Reviewable"
    And I click on "Change state" "button"
    And I switch to the main window

    And I log out
    And I am on the "StudentQuiz Test 2" "mod_studentquiz > View" page logged in as "admin"
    And I choose "Preview" action for "TF 01" in the question bank
    And I switch to "questionpreview" window
    And I set the field "statetype" to "Approved"
    And I click on "Change state" "button"
    And I switch to the main window

    And I log out
    And I am on the "StudentQuiz Test 2" "mod_studentquiz > View" page logged in as "student1"
    And I choose "Preview" action for "TF 01" in the question bank
    And I switch to "questionpreview" window
    And I set the field "statetype" to "Deleted"
    And I click on "Change state" "button"
    And I should see "This question cannot be deleted because it has been approved."

  @javascript
  Scenario: The teacher have the 'changestate' capability.
    Given the following "permission overrides" exist:
      | capability                  | permission | role    | contextlevel | reference |
      | mod/studentquiz:changestate | Allow      | teacher | Course       | C1        |
      | mod/studentquiz:manage      | Allow      | teacher | Course       | C1        |
    When I am on the "StudentQuiz Test 1" "mod_studentquiz > View" page logged in as "teacher1"
    And I click on "Create new question" "button"
    And I set the field "item_qtype_truefalse" to "1"
    And I click on "Add" "button" in the "Choose a question type to add" "dialogue"
    And I set the field "Question name" to "TF 01"
    And I set the field "Question text" to "The correct answer is false"
    And I press "id_submitbutton"

    And "Change state" "button" should exist
    And I choose "Preview" action for "TF 01" in the question bank
    And I switch to "questionpreview" window
    And the "menustatetype" select box should contain "Disapproved"
    And the "menustatetype" select box should contain "Approved"
    And the "menustatetype" select box should contain "Changed"
    And the "menustatetype" select box should contain "Reviewable"
    And the "menustatetype" select box should contain "Hidden"
    And the "menustatetype" select box should contain "Deleted"

    And I switch to the main window
    Then the following "permission overrides" exist:
      | capability                  | permission | role    | contextlevel | reference |
      | mod/studentquiz:changestate | Prevent    | teacher | Course       | C1        |
    And I reload the page
    And "Change state" "button" should not exist
    And I choose "Preview" action for "TF 01" in the question bank
    And I switch to "questionpreview" window
    And the "menustatetype" select box should contain "Changed"
    And the "menustatetype" select box should contain "Reviewable"
    And the "menustatetype" select box should contain "Deleted"
    And the "menustatetype" select box should not contain "Disapproved"
    And the "menustatetype" select box should not contain "Approved"
    And the "menustatetype" select box should not contain "Hidden"
