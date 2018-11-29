@mod @mod_studentquiz
Feature: New activities instances setting will be inherited from Admin setting
  In order not to change the allowed question types every time it was created
  As a teacher
  I need a default allowed question types option Admin setting and new StudentQuiz will use it

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |

  @javascript
  Scenario: Check Default question types appear in Admin setting
    Given I log in as "admin"
    When I navigate to "Plugins > StudentQuiz" in site administration
    Then I should see "Default question types"
    And I should see "The following are default for a new activity"
    And the field "s_studentquiz_defaultqtypes[multichoice]" matches value "1"
    And the field "s_studentquiz_defaultqtypes[truefalse]" matches value "1"
    And the field "s_studentquiz_defaultqtypes[shortanswer]" matches value "1"

  @javascript
  Scenario: Check new instance will get the default question types from Admin setting
    Given I log in as "admin"
    And the following config values are set as admin:
      | defaultqtypes | truefalse | studentquiz |
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I add a "StudentQuiz" to section "1"
    When I expand all fieldsets
    Then the field "allowedqtypes[truefalse]" matches value "1"
    And the field "allowedqtypes[ALL]" matches value "0"
    And the field "allowedqtypes[multichoice]" matches value "0"
    And the field "allowedqtypes[shortanswer]" matches value "0"
    And I press "Cancel"
    And the following config values are set as admin:
      | defaultqtypes | truefalse,multichoice | studentquiz |
    And I add a "StudentQuiz" to section "1"
    And I expand all fieldsets
    And the field "allowedqtypes[truefalse]" matches value "1"
    And the field "allowedqtypes[multichoice]" matches value "1"
    And the field "allowedqtypes[ALL]" matches value "0"
    And the field "allowedqtypes[shortanswer]" matches value "0"
