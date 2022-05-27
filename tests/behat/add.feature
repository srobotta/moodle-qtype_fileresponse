@qtype @qtype_fileresponse @qtype_fileresponse_add
Feature: Test creating a file response question
  As a teacher
  In order to test my students
  I need to be able to create a file response question

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email               |
      | teacher1 | T1        | Teacher1 | teacher1@moodle.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |

  @javascript
  Scenario: Create a File response question
    When I am on the "Course 1" "core_question > course question bank" page logged in as teacher1

  # Create a new question.
    And I add a "File Response" question filling the form with:
      | Question name    | fileresponse-001           |
      | Question text    | Upload a PDF file, please. |
      | General feedback | This is general feedback   |
    Then I should see "fileresponse-001"
