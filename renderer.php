<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * Fileresponse question renderer class.
 *
 * @package qtype
 * @subpackage fileresponse
 * @copyright 2012 Luca Bösch luca.boesch@bfh.ch
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
defined('MOODLE_INTERNAL') || die();


/**
 * Generates the output for fileresponse questions.
 *
 * @copyright 2012 Luca Bösch luca.boesch@bfh.ch
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_fileresponse_renderer extends qtype_renderer {

    public function formulation_and_controls(question_attempt $qa, question_display_options $options) {
        $question = $qa->get_question();
        $responseoutput = $question->get_format_renderer($this->page);
        $answer = null; /* so it's initiated and doesn't trigger a PHP notice */

        // Answer field.
        $step = $qa->get_last_step_with_qt_var('answer');

        if (empty($options->readonly)) {
            if ($question->responsefieldlines > 0) {
                $answer = $responseoutput->response_area_input('answer', $qa, $step,
                        $question->responsefieldlines, $options->context);
            }
        } else {
            $answer = $responseoutput->response_area_read_only('answer', $qa, $step,
                    $question->responsefieldlines, $options->context);
        }

        $files = '';
        if ($question->attachments) {
            if (empty($options->readonly)) {
                $files = $this->files_input($qa, $question->attachments, $options,
                        $question->forcedownload, $question->allowpickerplugins);
            } else {

                $files = $this->files_read_only($qa, $options, $question);
            }
        }

        $result = '';
        $result .= html_writer::tag('div', $question->format_questiontext($qa),
                array('class' => 'qtext'
                ));

        $result .= html_writer::start_tag('div', array('class' => 'ablock'
        ));
        if ($answer) {
            $result .= html_writer::tag('div', $answer, array('class' => 'qtext'
            ));
        }

        /* how many files are expected, already uploaded and saved ? */
        $filecount = $this->get_already_uploaded_files_number($qa, $options);

        $expected_attachments = (int) $question->attachments;
        switch ($expected_attachments) {
            case -1: /* unlimited, but at least one file expected. */
                /* $result .= html_writer::tag('div', get_string('unlimitedattachmentsrequired', 'qtype_fileresponse'), array('class' => 'answer')); */
                /* no explanation is needed */
                break;
            case 1: /* one file required */
                $result .= html_writer::tag('div',
                        get_string('oneattachmentexpected', 'qtype_fileresponse'),
                        array('class' => 'answer'
                        ));
                break;
            default: /* two or three file required */
                $result .= html_writer::tag('div',
                        get_string('nattachmentsexpected', 'qtype_fileresponse',
                                $expected_attachments),
                        array('class' => 'answer'
                        ));
                break;
        }

        if ($expected_attachments == -1) { /*
                                            * unlimited number, but at least one attachment expected
                                            */
            switch ($filecount) {
                case 0: /* no file of unlimited submitted */
                    /* $result .= html_writer::tag('div', get_string('noattachmentsubmitted', 'qtype_fileresponse')."<br />&#160;<br />", array('class' => 'answer')); */
                    /* no explanation is needed */
                    break;
                case 1: /* exactly one file of unlimited submitted */
                    $result .= html_writer::tag('div',
                            get_string('oneattachmentsubmitted', 'qtype_fileresponse') .
                                     "<br />&#160;<br />",
                                    array('class' => 'answer'
                                    ));
                    break;
                default: /* exactly n > 1 files of unlimited submitted */
                    $result .= html_writer::tag('div',
                            get_string('nattachmentssubmitted', 'qtype_fileresponse', $filecount) .
                                     "<br />&#160;<br />",
                                    array('class' => 'answer'
                                    ));
                    break;
            }
        } elseif ($expected_attachments == 1) { /* exactly one attachment expected */
            if ($filecount == 0) { /* no file of 1 submitted */
                $result .= html_writer::tag('div',
                        get_string('noofoneattachmentsubmitted', 'qtype_fileresponse') .
                                 "<br />&#160;<br />",
                                array('class' => 'answer'
                                ));
            } elseif ($filecount == 1) { /* 1 file of 1 submitted */
                $result .= html_writer::tag('div',
                        get_string('oneofoneattachmentsubmitted', 'qtype_fileresponse') .
                                 "<br />&#160;<br />",
                                array('class' => 'answer'
                                ));
            } else { /* this should not happen: $filecount larger than $expected_attachments */
                $result .= html_writer::tag('div',
                        get_string('nattachmentssubmitted', 'qtype_fileresponse', $filecount) .
                                 "<br />&#160;<br />",
                                array('class' => 'answer'
                                ));
            }
        } else {
            /* exactly a certain amount (but more than one) of attachment expected */
            if ($filecount == 0) { /* no file of n > 1 submitted yet */

                $result .= html_writer::tag('div',
                        get_string('noofnattachmentsubmitted', 'qtype_fileresponse',
                                $expected_attachments) . "<br />&#160;<br />",
                        array('class' => 'answer'
                        ));
            } elseif (($expected_attachments > 1) && ($filecount == 1)) { /*
                                                                           * exactly one file of
                                                                           * n >
                                                                           * 1 submitted
                                                                           */

                $result .= html_writer::tag('div',
                        get_string('oneofnattachmentssubmitted', 'qtype_fileresponse',
                                $expected_attachments) . "<br />&#160;<br />",
                        array('class' => 'answer'
                        ));
            } elseif ($filecount > $expected_attachments) { /*
                                                             * this should not happen:
                                                             * $filecount
                                                             * larger than $expected_attachments
                                                             */
                $result .= html_writer::tag('div',
                        get_string('nattachmentssubmitted', 'qtype_fileresponse', $filecount) .
                                 "<br />&#160;<br />",
                                array('class' => 'answer'
                                ));
            } else { /* n > 1 files of n > 1 submitted yet */
                $result .= html_writer::tag('div',
                        $filecount . get_string('ofnattachmentssubmitted', 'qtype_fileresponse',
                                $expected_attachments) . "<br />&#160;<br />",
                        array('class' => 'answer'
                        ));
            }
        }
        $result .= html_writer::tag('div', $files, array('class' => 'attachments'
        ));
        $result .= html_writer::end_tag('div');

        return $result;
    }

    public function get_uploader_name($userid, $question) {
        global $DB, $COURSE;
        $student_name_qid_arr = array();
        $examination_user = $DB->get_record('user', array('id' => $userid
        ));
        if (!$examination_user) {
            $student_name_qid_arr[0] = 'uknown';
            $student_name_qid_arr[1] = 'uknown';
            $student_name_qid_arr[2] = 'uknown';
            $student_name_qid_arr[3] = 0;
            $student_name_qid_arr[4] = 'uknown';
            return $student_name_qid_arr;
        }
        // Remove anything which isn't a word, whitespace, number
        // or any of the following caracters -_~,;[]().
        // If you don't need to handle multi-byte characters
        // you can use preg_replace rather than mb_ereg_replace
        $cleanedfname = mb_ereg_replace("([^\w\s\d\-_~,;\[\]\(\).])", '',
                $examination_user->firstname);
        // Remove any runs of periods
        $cleanedfname = mb_ereg_replace("([\.]{2,})", '', $cleanedfname);
        $cleanedlname = mb_ereg_replace("([^\w\s\d\-_~,;\[\]\(\).])", '',
                $examination_user->lastname);
        // Remove any runs of periods
        $cleanedlname = mb_ereg_replace("([\.]{2,})", '', $cleanedlname);

        $student_name_qid_arr[0] = '_' . $cleanedfname . '_' . $cleanedlname . '_' . $question->id;
        $student_name_qid_arr[1] = $examination_user->firstname;
        $student_name_qid_arr[2] = $examination_user->lastname;
        $student_name_qid_arr[3] = $examination_user->id;
        $student_name_qid_arr[4] = $examination_user->email;
        return $student_name_qid_arr;
    }

    /**
     * Displays any attached files when the question is in read-only mode.
     *
     * @param question_attempt $qa the question attempt to display.
     * @param question_display_options $options controls what should and should
     *        not be displayed. Used to get the context.
     */
    public function files_read_only(question_attempt $qa, question_display_options $options,
            $question) {
        global $DB, $COURSE, $skipfile;
        $files = $qa->get_last_qt_files('attachments', $options->context->id);
        $step = $qa->get_last_step_with_qt_var('answer');
        $output = array();
        $returnfiles = array();
        $zipper = new zip_packer();
        $infocreated = 0;
        foreach ($files as $file) {
            $student_name_qid_arr = $this->get_uploader_name($file->get_userid(), $question);

            $skipfile = 'All_Files' . $student_name_qid_arr[0] . '.zip';

            if (strpos($file->get_filename(), 'All_Files') !== false) { // Avoid Restore duplicates
                                                                        // Get old zipped file
                $fs = get_file_storage();
                $itemid = $file->get_itemid();
                $skipzippedfile = $fs->get_file($options->context->id, 'question',
                        'response_attachments', $itemid, '/qtype_fileresponse_zipped/',
                        $file->get_filename());

                if ($skipzippedfile) {
                    $skipzippedfile->delete();
                }
                continue;
            }
            $output[] = html_writer::tag('p',
                    html_writer::link($qa->get_response_file_url($file),
                            $this->output->pix_icon(file_file_icon($file),
                                    get_mimetype_description($file), 'moodle',
                                    array('class' => 'icon'
                                    )) . ' ' . s($file->get_filename())));
            $returnfiles[$file->get_filepath() . $file->get_filename()] = $file;
            $itemid = $file->get_itemid();

            if (!$infocreated) {
                // Create info file.
                $infocontent = get_string('user') . ': ' . $student_name_qid_arr[1] . ' ' .
                         $student_name_qid_arr[2] . " \r\n";
                $infocontent .= get_string('email') . ': ' . $student_name_qid_arr[4] . ' (ID: ' .
                         $student_name_qid_arr[3] . ') ' . "\r\n";
                $infocontent .= get_string('question') . ': ' . $question->name . ' (ID: ' .
                         $question->id . ') ' . "\r\n";
                $infocontent .= get_string('course') . ': ' . $COURSE->fullname . ' (ID: ' .
                         $COURSE->id . ') ';

                $fs = get_file_storage();

                // Prepare file record object
                $fileinfo = array('contextid' => $options->context->id, // ID of context
'component' => 'question', // usually = table name
'filearea' => 'response_attachments', // usually = table name
'itemid' => $itemid, // usually = ID of row in table
'filepath' => '/', // any path beginning and ending in /
'filename' => '_user_info.txt'
                ); // any filename

                // Create file containing text infocontent
                $v = $fs->create_file_from_string($fileinfo, $infocontent);
                $returnfiles[$v->get_filepath() . $v->get_filename()] = $v;
                // Get info file
                $infotxtfile = $fs->get_file($options->context->id, 'question',
                        'response_attachments', $itemid, '/', '_user_info.txt');

                $infocreated = 1;
            }
        }
        if (count($returnfiles) > 0) {

            $final_zipped_file = $zipper->archive_to_storage($returnfiles, $options->context->id,
                    'question', 'response_attachments', $itemid, '/qtype_fileresponse_zipped/',
                    $skipfile, $file->get_userid());
            // Delete info text if it exists
            if ($infotxtfile) {
                $infotxtfile->delete();
            }

            // Add Zipped to all links
            $output[] = html_writer::tag('hr', '');
            $output[] = html_writer::tag('p',
                    html_writer::link($qa->get_response_file_url($final_zipped_file),
                            $this->output->pix_icon(file_file_icon($final_zipped_file),
                                    get_mimetype_description($final_zipped_file), 'moodle',
                                    array('class' => 'icon'
                                    )) . ' ' . s($final_zipped_file->get_filename())));
        }

        return implode($output);
    }

    /**
     * Displays the input control for when the student should upload a single file.
     *
     * @param question_attempt $qa the question attempt to display.
     * @param int $numallowed the maximum number of attachments allowed. -1 = unlimited.
     * @param question_display_options $options controls what should and should
     *        not be displayed. Used to get the context.
     */
    public function files_input(question_attempt $qa, $numallowed, question_display_options $options,
            $forcedownload, $allowpickerplugins) {
        global $CFG;
        require_once ($CFG->dirroot . '/repository/lib.php');
        $pickeroptions = new stdClass();
        $pickeroptions->mainfile = null;
        $pickeroptions->maxfiles = $numallowed;
        $pickeroptions->itemid = $qa->prepare_response_files_draft_itemid('attachments',
                $options->context->id);
        $pickeroptions->context = $options->context;
        $pickeroptions->return_types = FILE_INTERNAL;

        $pickeroptions->itemid = $qa->prepare_response_files_draft_itemid('attachments',
                $options->context->id);
        $pickeroptions->allowpickerplugins = $allowpickerplugins;
        if ($forcedownload) { /* don't download fix */
            require_once ('fileresponsesimplifiedfilemanager.php'); /* don't download fix */
            $frsfm = new form_fileresponsesimplifiedfilemanager($pickeroptions);
            $filesrenderer = $this->page->get_renderer('qtype_fileresponse',
                    'fileresponsesimplifiedfilemanager');
            return $filesrenderer->render($frsfm) . html_writer::empty_tag('input',
                    array('type' => 'hidden', 'name' => $qa->get_qt_field_name('attachments'),
                        'value' => $pickeroptions->itemid
                    )); /* render(renderable $widget) */
        } else { /* allow download */
            require_once ('fileresponsefilemanager.php'); // Check allowed repositories.
            $frfm = new form_fileresponsefilemanager($pickeroptions);
            $filesrenderer = $this->page->get_renderer('qtype_fileresponse',
                    'fileresponsefilemanager');
            return $filesrenderer->render($frfm) . html_writer::empty_tag('input',
                    array('type' => 'hidden', 'name' => $qa->get_qt_field_name('attachments'),
                        'value' => $pickeroptions->itemid
                    ));
        }
    }

    public function get_already_uploaded_files_number($qa, $options) {
        global $skipfile;
        $files = $qa->get_last_qt_files('attachments', $options->context->id);
        $counter = 0;
        foreach ($files as $file) {
            if ($file->get_filename() != $skipfile) { // minus the zipped file!
                $counter++;
            }
        }
        if ($counter < 0) {
            $counter = 0;
        }
        return $counter;
    }

    public function manual_comment(question_attempt $qa, question_display_options $options) {
        if ($options->manualcomment != question_display_options::EDITABLE) {
            return '';
        }

        $question = $qa->get_question();
        return html_writer::nonempty_tag('div',
                $question->format_text($question->graderinfo, $question->questiontextformat, $qa,
                        'qtype_fileresponse', 'graderinfo', $question->id),
                array('class' => 'graderinfo'
                ));
    }
}


/**
 * A base class to abstract out the differences between different type of
 * response format.
 *
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class qtype_fileresponse_format_renderer_base extends plugin_renderer_base {

    /**
     * Render the students respone when the question is in read-only mode.
     *
     * @param string $name the variable name this input edits.
     * @param question_attempt $qa the question attempt being display.
     * @param question_attempt_step $step the current step.
     * @param int $lines approximate size of input box to display.
     * @param object $context the context teh output belongs to.
     * @return string html to display the response.
     */
    public abstract function response_area_read_only($name, question_attempt $qa,
            question_attempt_step $step, $lines, $context);

    /**
     * Render the students respone when the question is in read-only mode.
     *
     * @param string $name the variable name this input edits.
     * @param question_attempt $qa the question attempt being display.
     * @param question_attempt_step $step the current step.
     * @param int $lines approximate size of input box to display.
     * @param object $context the context teh output belongs to.
     * @return string html to display the response for editing.
     */
    public abstract function response_area_input($name, question_attempt $qa,
            question_attempt_step $step, $lines, $context);

    /**
     *
     * @return string specific class name to add to the input element.
     */
    protected abstract function class_name();
}


/**
 * An essay format renderer for essays where the student should use the HTML
 * editor without the file picker.
 *
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_fileresponse_format_editor_renderer extends plugin_renderer_base {

    protected function class_name() {
        return 'qtype_fileresponse_editor';
    }

    public function response_area_read_only($name, $qa, $step, $lines, $context) {
        return html_writer::tag('div', $this->prepare_response($name, $qa, $step, $context),
                array('class' => $this->class_name() . ' qtype_fileresponse_response readonly'
                ));
    }

    public function response_area_input($name, $qa, $step, $lines, $context) {
        global $CFG;
        require_once ($CFG->dirroot . '/repository/lib.php');

        $inputname = $qa->get_qt_field_name($name);
        $responseformat = $step->get_qt_var($name . 'format');
        $id = $inputname . '_id';

        $editor = editors_get_preferred_editor($responseformat);
        $strformats = format_text_menu();
        $formats = $editor->get_supported_formats();
        foreach ($formats as $fid) {
            $formats[$fid] = $strformats[$fid];
        }

        list($draftitemid, $response) = $this->prepare_response_for_editing($name, $step, $context);

        $editor->use_editor($id, $this->get_editor_options($context),
                $this->get_filepicker_options($context, $draftitemid));

        $output = '';
        if ($lines > 0) {
            $output .= html_writer::start_tag('div',
                    array('class' => $this->class_name() . ' qtype_fileresponse_response'
                    ));

            $output .= html_writer::tag('div',
                    html_writer::tag('textarea', s($response),
                            array('id' => $id, 'name' => $inputname, 'rows' => $lines, 'cols' => 60
                            )));

            $output .= html_writer::start_tag('div');
            if (count($formats) == 1) {
                reset($formats);
                $output .= html_writer::empty_tag('input',
                        array('type' => 'hidden', 'name' => $inputname . 'format',
                            'value' => key($formats)
                        ));
            } else {
                $output .= html_writer::select($formats, $inputname . 'format', $responseformat, '');
            }
            $output .= html_writer::end_tag('div');

            $output .= $this->filepicker_html($inputname, $draftitemid);

            $output .= html_writer::end_tag('div');
        }
        return $output;
    }

    /**
     * Prepare the response for read-only display.
     *
     * @param string $name the variable name this input edits.
     * @param question_attempt $qa the question attempt being display.
     * @param question_attempt_step $step the current step.
     * @param object $context the context the attempt belongs to.
     * @return string the response prepared for display.
     */
    protected function prepare_response($name, question_attempt $qa, question_attempt_step $step,
            $context) {
        if (!$step->has_qt_var($name)) {
            return '';
        }

        $formatoptions = new stdClass();
        $formatoptions->para = false;
        return format_text($step->get_qt_var($name), $step->get_qt_var($name . 'format'),
                $formatoptions);
    }

    /**
     * Prepare the response for editing.
     *
     * @param string $name the variable name this input edits.
     * @param question_attempt_step $step the current step.
     * @param object $context the context the attempt belongs to.
     * @return string the response prepared for display.
     */
    protected function prepare_response_for_editing($name, question_attempt_step $step, $context) {
        return array(0, $step->get_qt_var($name)
        );
    }

    /**
     *
     * @param object $context the context the attempt belongs to.
     * @return array options for the editor.
     */
    protected function get_editor_options($context) {
        // Disable the text-editor autosave because quiz has it's own auto save function.
        return array('context' => $context, 'autosave' => false
        );
    }

    /**
     *
     * @param object $context the context the attempt belongs to.
     * @param int $draftitemid draft item id.
     * @return array filepicker options for the editor.
     */
    protected function get_filepicker_options($context, $draftitemid) {
        return array();
    }

    /**
     *
     * @param string $inputname input field name.
     * @param int $draftitemid draft file area itemid.
     * @return string HTML for the filepicker, if used.
     */
    protected function filepicker_html($inputname, $draftitemid) {
        return '';
    }
}


/**
 * An essay format renderer for essays where the student should use the HTML
 * editor with the file picker.
 *
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_fileresponse_format_editorfilepicker_renderer extends qtype_fileresponse_format_editor_renderer {

    protected function class_name() {
        return 'qtype_fileresponse_editorfilepicker';
    }

    protected function prepare_response($name, question_attempt $qa, question_attempt_step $step,
            $context) {
        if (!$step->has_qt_var($name)) {
            return '';
        }

        $formatoptions = new stdClass();
        $formatoptions->para = false;
        $text = $qa->rewrite_response_pluginfile_urls($step->get_qt_var($name), $context->id,
                'answer', $step);
        return format_text($text, $step->get_qt_var($name . 'format'), $formatoptions);
    }

    protected function prepare_response_for_editing($name, question_attempt_step $step, $context) {
        return $step->prepare_response_files_draft_itemid_with_text($name, $context->id,
                $step->get_qt_var($name));
    }

    protected function get_editor_options($context) {
        // Disable the text-editor autosave because quiz has it's own auto save function.
        return array('subdirs' => 0, 'maxbytes' => 0, 'maxfiles' => -1, 'context' => $context,
            'noclean' => 0, 'trusttext' => 0, 'autosave' => false
        );
    }

    /**
     * Get the options required to configure the filepicker for one of the editor
     * toolbar buttons.
     *
     * @param mixed $acceptedtypes array of types of '*'.
     * @param int $draftitemid the draft area item id.
     * @param object $context the context.
     * @return object the required options.
     */
    protected function specific_filepicker_options($acceptedtypes, $draftitemid, $context) {
        $filepickeroptions = new stdClass();
        $filepickeroptions->accepted_types = $acceptedtypes;
        $filepickeroptions->return_types = FILE_INTERNAL | FILE_EXTERNAL;
        $filepickeroptions->context = $context;
        $filepickeroptions->env = 'filepicker';

        $options = initialise_filepicker($filepickeroptions);
        $options->context = $context;
        $options->client_id = uniqid();
        $options->env = 'editor';
        $options->itemid = $draftitemid;

        return $options;
    }

    protected function get_filepicker_options($context, $draftitemid) {
        global $CFG;

        return array(
            'image' => $this->specific_filepicker_options(array('image'
            ), $draftitemid, $context),
            'media' => $this->specific_filepicker_options(
                    array('video', 'audio'
                    ), $draftitemid, $context),
            'link' => $this->specific_filepicker_options('*', $draftitemid, $context)
        );
    }

    protected function filepicker_html($inputname, $draftitemid) {
        $nonjspickerurl = new moodle_url('/repository/draftfiles_manager.php',
                array('action' => 'browse', 'env' => 'editor', 'itemid' => $draftitemid,
                    'subdirs' => false, 'maxfiles' => -1, 'sesskey' => sesskey()
                ));

        return html_writer::empty_tag('input',
                array('type' => 'hidden', 'name' => $inputname . ':itemid', 'value' => $draftitemid
                )) . html_writer::tag('noscript',
                html_writer::tag('div',
                        html_writer::tag('object', '',
                                array('type' => 'text/html', 'data' => $nonjspickerurl,
                                    'height' => 160, 'width' => 600,
                                    'style' => 'border: 1px solid #000;'
                                ))));
    }
}


/**
 * An essay format renderer for essays where the student should use a plain
 * input box, but with a normal, proportional font.
 *
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_fileresponse_format_plain_renderer extends plugin_renderer_base {

    /**
     *
     * @return string the HTML for the textarea.
     */
    protected function textarea($response, $lines, $attributes) {
        $attributes['class'] = $this->class_name() . ' qtype_fileresponse_response';
        $attributes['rows'] = $lines;
        $attributes['cols'] = 60;
        return html_writer::tag('textarea', s($response), $attributes);
    }

    protected function class_name() {
        return 'qtype_fileresponse_plain';
    }

    public function response_area_read_only($name, $qa, $step, $lines, $context) {
        return $this->textarea($step->get_qt_var($name), $lines,
                array('readonly' => 'readonly'
                ));
    }

    public function response_area_input($name, $qa, $step, $lines, $context) {
        $inputname = $qa->get_qt_field_name($name);
        return $this->textarea($step->get_qt_var($name), $lines,
                array('name' => $inputname
                )) . html_writer::empty_tag('input',
                array('type' => 'hidden', 'name' => $inputname . 'format', 'value' => FORMAT_PLAIN
                ));
    }
}


/**
 * An essay format renderer for essays where the student should use a plain
 * input box with a monospaced font.
 * You might use this, for example, for a
 * question where the students should type computer code.
 *
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_fileresponse_format_monospaced_renderer extends qtype_fileresponse_format_plain_renderer {

    protected function class_name() {
        return 'qtype_fileresponse_monospaced';
    }
}
