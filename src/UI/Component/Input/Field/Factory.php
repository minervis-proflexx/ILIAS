<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

declare(strict_types=1);

namespace ILIAS\UI\Component\Input\Field;

use ILIAS\UI\Component\Input\Container\Form\FormInput;

/**
 * This is what a factory for input fields looks like.
 */
interface Factory
{
    /**
     * ---
     * description:
     *   purpose: >
     *     A text-field is intended for entering short single-line texts.
     *   composition: >
     *      Text fields will render an input-tag with type="text".
     *   effect: >
     *      Text inputs are restricted to one line of text.
     *   rivals:
     *      numeric field: Use a numeric field if users should input numbers.
     *      alphabet field: >
     *          Use an alphabet field if the user should input single letters.
     *
     * rules:
     *   usage:
     *     1: Text Input MUST NOT be used for choosing from predetermined options.
     *     2: >
     *         Text input MUST NOT be used for numeric input, a Numeric Field is
     *         to be used instead.
     *     3: >
     *         Text Input MUST NOT be used for letter-only input, an Alphabet Field
     *         is to be used instead.
     *   interaction:
     *     1: >
     *         Text Input MUST limit the number of characters, if a certain length
     *         of text-input may not be exceeded (e.g. due to database-limitations).
     *
     * ---
     * @param string      $label
     * @param string|null $byline
     * @return    \ILIAS\UI\Component\Input\Field\Text
     */
    public function text(string $label, ?string $byline = null): Text;

    /**
     * ---
     * description:
     *   purpose: >
     *      A numeric field is used to retrieve integer values from the user.
     *   composition: >
     *      Numeric inputs will render an input-tag with type="number".
     *   effect: >
     *      The field does not accept any data other than numeric values. When
     *      focused most browser will show a small vertical rocker to increase
     *      and decrease the value in the field.
     * rules:
     *   usage:
     *     1: Number Inputs MUST NOT be used for binary choices.
     *     2: >
     *         Magic numbers such as -1 or 0 to specify “limitless” or smoother
     *         options MUST NOT be used.
     *     3: A valid input range SHOULD be specified.
     *
     * ---
     * @param string      $label
     * @param string|null $byline
     * @return    \ILIAS\UI\Component\Input\Field\Numeric
     */
    public function numeric(string $label, ?string $byline = null): Numeric;

    /**
     * ---
     * description:
     *   purpose: >
     *      Input groups are an unlabeled collection of inputs. They are used to
     *      build logical units of other fields. Such units might be used to attach some
     *      constraints or transformations for multiple fields.
     *   composition: >
     *      Groups are composed of inputs. They do not contain a label. The grouping
     *      remains invisible for the client.
     *   effect: >
     *      There is no visible effect using groups.
     *   rivals:
     *      sections: Sections are used to generate a visible relation of fields.
     * rules: []
     * ---
     * @param array<mixed,\ILIAS\UI\Component\Input\Input> $inputs
     * @param string                                                 $label
     * @param string|null                                            $byline
     * @return    \ILIAS\UI\Component\Input\Field\Group
     */
    public function group(array $inputs, string $label = '', ?string $byline = null): Group;

    /**
     * ---
     * description:
     *   purpose: >
     *      An optional group is a collection of input where the user needs to make
     *      a conscious decision to use or not use the provided inputs.
     *   composition: >
     *      An optional group is composed of a checkbox that bears the label and
     *      the byline of the group and the contained inputs that are arranged in
     *      a way to make them visually belong to the checkbox.
     *   effect: >
     *      If the checkbox is checked, the contained inputs are revealed, while
     *      they are hidden when the checkbox is not checked.
     * rules:
     *  usage:
     *    1: >
     *      There MUST NOT be a nesting of more than one optional and/or switchable
     *      group. The only exception to this rule is the required quantification
     *      of a subsetting by a date or number. These exceptions MUST individually
     *      accept by the Jour Fixe.
     * ---
     * @param array<mixed,\ILIAS\UI\Component\Input\Input> $inputs
     * @param string                                                 $label
     * @param string|null                                            $byline
     * @return    \ILIAS\UI\Component\Input\Field\OptionalGroup
     */
    public function optionalGroup(array $inputs, string $label, ?string $byline = null): OptionalGroup;

    /**
     * ---
     * description:
     *   purpose: >
     *      A switchable group is a collection of groups that makes the user decide
     *      which group he wants to fill with further input.
     *   composition: >
     *      A switchable group is composed of radiobuttons that bear the label and
     *      the byline of the according group and the inputs contained in that group
     *      in a way to make them visually belong to the radio group.
     *   effect: >
     *      If a radiobutton is selected, the according inputs are revealed and the
     *      other groups are hidden.
     * rules:
     *  usage:
     *    1: >
     *      There MUST NOT be a nesting of more than one optional and/or switchable
     *      group. The only exception to this rule is the required quantification
     *      of a subsetting by a date or number. These exceptions MUST individually
     *      accepted by the Jour Fixe.
     * ---
     * @param array<mixed,\ILIAS\UI\Component\Input\Field\Group> $inputs
     * @param string                                             $label
     * @param string|null                                        $byline
     * @return    \ILIAS\UI\Component\Input\Field\SwitchableGroup
     */
    public function switchableGroup(array $inputs, string $label, ?string $byline = null): SwitchableGroup;

    /**
     * ---
     * description:
     *   purpose: >
     *      Sections are used to visually group inputs to a common context.
     *   composition: >
     *      Sections are composed of inputs. They carry a label and are visible for
     *      the client.
     *   rivals:
     *      Groups: >
     *          Groups are used as purely logical units, while sections visualize
     *          the correlation of fields.
     * rules:
     *   composition:
     *     1: Sections SHOULD comprise 2 to 5 Settings.
     *     2: >
     *       More than 5 Settings SHOULD be split into two areas unless this would
     *       tamper with the “familiar” information architecture of forms.
     *     3: >
     *       In standard forms, there MUST NOT be a Setting without an enclosing Titled
     *       Form Section. If necessary a Titled Form Section MAY contain only one single
     *       Setting.
     *   wording:
     *     1: >
     *       The label SHOULD summarize the contained settings accurately from a
     *       user’s perspective.
     *     2: >
     *       The title SHOULD contain less than 30 characters.
     *     3: >
     *       The titles MUST be cross-checked with similar sections in other objects or
     *       services to ensure consistency throughout ILIAS.
     *     4: >
     *       In doubt consistency SHOULD be prioritized over accuracy in titles.
     * ---
     * @param array<mixed,\ILIAS\UI\Component\Input\Input> $inputs
     * @param string                                                 $label
     * @param string|null                                            $byline
     * @return    \ILIAS\UI\Component\Input\Field\Section
     */
    public function section(array $inputs, string $label, ?string $byline = null): Section;

    /**
     * ---
     * description:
     *   purpose: >
     *     A checkbox is used to govern a state, action, or set / not to set a value.
     *     Checkboxes are typically used to switch on some additional behaviour or services.
     *   composition: >
     *     Each Checkbox is labeled by an identifier stating something positive to
     *     describe the effect of checking the Checkbox.
     *   effect: >
     *     If used in a form, a checkbox may open a dependant section (formerly known
     *     as sub form).
     *
     * rules:
     *   usage:
     *     1: >
     *      A checkbox MUST NOT be used whenever a user has to perform a binary choice where
     *      option is not automatically the inverse of the other (such as 'Order by Date' and
     *      'Order by Name'). A Select Input or a Radio Group in MUST be
     *      used in this case.
     *   wording:
     *     1: The checkbox’s identifier MUST always state something positive.
     *
     * ---
     * @param string      $label
     * @param string|null $byline
     * @return    \ILIAS\UI\Component\Input\Field\Checkbox
     */
    public function checkbox(string $label, ?string $byline = null): Checkbox;


    /**
     * ---
     * description:
     *   purpose: >
     *     A Tag Input is used to choose a subset amount of tags (techn.: array of strings) out
     *     of a finite list of tags. The Tag Field SHOULD be used, whenever it is not required
     *     or not possible to display all available options, e.g. because the amount is too high
     *     when the options are "all users" or "all tags.
     *     Besides the tags to choose from, the user can provide own tags by typing them
     *     into the Input (@see Tag::withUserCreatedTagsAllowed).
     *   composition: >
     *     The Input is presented as a text-input and prepended by already selected tags
     *     presented as texts including a close-button.  (e.g. [ Amsterdam X ] )
     *     The input is labeled by the label given.
     *     Suggested tags are listed in a dropdown-list beneath the text-input.
     *     All mentioned elements are not taken from the UI-Service.
     *   effect: >
     *     As soon as the user types in the text-input, the Tag Input suggests matching tags from
     *     the given list of tags. Suggestions will appear after a defined
     *     amount of characters, one by default.
     *     Clicking on one of these tags closes the list and transfers the selected tag into
     *     the text-input, displayed as a tag with a close-button.
     *     By clicking on a close-button of a already selected tag, this tag will disappear
     *     from the Input.
     *     All mentioned elements are not taken from the UI-Service.
     *
     * rivals: >
     *     + SelectInput: Currently not part of the UI-Service.
     *     + Checkbox Group
     *
     * context:
     *   - Tag Input is used in forms.
     *
     * rules:
     *   usage:
     *     1: >
     *      A Tag Input MUST NOT be used whenever a user has to perform a binary choice where
     *      option is automatically the inverse of the other. A Checkbox MUST be used in this case.
     *     2: >
     *      A Tag Input MUST NOT be used whenever a user has to perform a choice from a list of
     *      options where only one Option has to be selected. A Select MUST be used in this case
     *      (Not yet part of the KitchenSink).
     *     3: >
     *      A Tag Input SHOULD be used whenever a User should be able to extend the list of given options.
     *     4: >
     *      A Tag Input MUST NOT be used when a User has to choose from a finite list of options
     *      which can't be extended by users Input, a Multi Select MUST be used in this case
     *     5: The tags provided SHOULD NOT have long titles (50 characters).
     *
     * ---
     * @param string      $label
     * @param string[]    $tags  List of tags to select from, given as a list of texts
     *                           such as [ 'Interesting', 'Boring', 'Animating', 'Repetitious' ]
     * @param string|null $byline
     * @return    \ILIAS\UI\Component\Input\Field\Tag
     */
    public function tag(string $label, array $tags, ?string $byline = null): Tag;

    /**
     * ---
     * description:
     *   purpose: >
     *     A password-field is intended for entering passwords.
     *   composition: >
     *      Text password will render an input-tag with type="password".
     *      Optionally, an eye-closed/open glyph is rendered above the input
     *      to toggle revelation/masking.
     *   effect: >
     *      Text password is restricted to one line of text and will
     *      mask the entered characters.
     *      When configured with the revelation-option, the clear-text
     *      password will be shown (respectively hidden) upon clicking the glyph.
     *   rivals:
     *      text field: >
     *          Use a text field for discloseable information (i.e.
     *          information that can safely be displayed to an audience)
     *
     * context:
     *    - Login-Form and own profile (change Password).
     *
     * rules:
     *   usage:
     *     1: Password Input MUST be used for passwords.
     *   composition:
     *     1: >
     *        The input MUST always be rendered with the attribute autocomplete="off".
     *        This advises browsers to NOT autofill the input field with cached passwords
     *        and avoids potential exposure of confidential data, especially in
     *        shared environments.
     *   interaction:
     *     1: >
     *         Password Input SHOULD NOT limit the number of characters.
     *     2: >
     *         When used for authentication, Password Input MUST NOT reveal any
     *         settings by placing constraints on it.
     *     3: >
     *         On the other hand, when setting a password, Password Input
     *         SHOULD enforce strong passwords by appropiate contraints.
     *
     * ---
     * @param string      $label
     * @param string|null $byline
     * @return    \ILIAS\UI\Component\Input\Field\Password
     */
    public function password(string $label, ?string $byline = null): Password;

    /**
     * ---
     * description:
     *   purpose: >
     *     A select is used to allow users to pick among a number of options.
     *   composition: >
     *     Select field will render a select-tag with a number of options.
     *     First option contains the string "-" and it is selectable depending on the required property.
     *   effect: >
     *     Only one option is selectable.
     *     If the property required is set as true, the first option will be hidden after clicking on the select input
     *     at the first time.
     *   rivals:
     *     Checkbox field: Use a checkbox field for a binary yes/no choice.
     *     Radio buttons: >
     *       Use radio buttons when the alternatives matter. When is wanted to user
     *       to see what they are not choosing.
     *       If it is a long list or the alternatives are not that important, use a select.
     *
     * rules:
     *   usage:
     *     1: Select Input MAY be used for choosing from predetermined options.
     *
     *   interaction:
     *     1: Only one option is selectable.
     *     2: First Option MAY be selectable when the field is not required.
     *
     * ---
     * @param string               $label
     * @param array<string,string> $options with the select options as key-value pairs.
     * @param string|null          $byline
     * @return \ILIAS\UI\Component\Input\Field\Select
     */
    public function select(string $label, array $options, ?string $byline = null): Select;

    /**
     * ---
     * description:
     *   purpose: >
     *     A textarea is intended for entering multi-line texts.
     *   composition: >
     *      Textarea fields will render an textarea HTML tag.
     *      If a limit is set, a byline about limitation is automatically set.
     *   effect: >
     *      Textarea inputs are NOT restricted to one line of text.
     *      A textarea counts the amount of character input by user and displays the number.
     *   rivals:
     *      text field: Use a text field if users should input only one line of text.
     *      numeric field: Use a numeric field if users should input numbers.
     *      alphabet field: >
     *          Use an alphabet field if the user should input single letters.
     *
     * rules:
     *   usage:
     *     1: Textarea Input MUST NOT be used for choosing from predetermined options.
     *     2: >
     *         Textarea input MUST NOT be used for numeric input, a Numeric Field is
     *         to be used instead.
     *     3: >
     *         Textarea Input MUST NOT be used for letter-only input, an Alphabet Field
     *         is to be used instead.
     *     4: >
     *         Textarea Input MUST NOT be used for single-line input, a Text Field
     *         is to be used instead.
     *     5: >
     *         If a min. or max. number of characters is set for textarea, a byline MUST
     *         be added stating the number of min. and/or max. characters.
     *   interaction:
     *     1: >
     *         Textarea Input MAY limit the number of characters, if a certain length
     *         of text-input may not be exceeded (e.g. due to database-limitations).
     *
     * ---
     * @param string      $label
     * @param string|null $byline
     * @return    \ILIAS\UI\Component\Input\Field\Textarea
     */
    public function textarea(string $label, ?string $byline = null): Textarea;

    /**
     * ---
     * description:
     *   purpose: >
     *     A Radio Input is used to depict a choice of options excluding each other.
     *   composition: >
     *     The Radio is considered as one field with a label and a number of
     *     options. Each option in turn bears a label in form of a positive statement.
     *   effect: >
     *     If used in a form, each option of a Radio may open a Dependant Section (formerly known
     *     as Sub Form).
     *   rivals:
     *     Checkbox Field: Use a Checkbox Field for a binary yes/no choice.
     *     Select: >
     *       Use Selects to choose items from a longer list as the configuration of
     *       an aspect; when the choice has severe effects on, e.g. service behavior,
     *       or needs further configuration, stick to radios.
     *
     * rules:
     *   usage:
     *     1: >
     *       A Radio Input SHOULD contain 3 to 5 options.
     *       If there are more, the Select Input might be the better option.
     *     2: >
     *       Radios MAY also be used to select between two options
     *       where one is not automatically the inverse of the other
     *   wording:
     *     1: Each option MUST be labeled.
     *     2: The options' labels MUST state something positive.
     *     3: >
     *        An option's label SHOULD not simply repeat the label of the Radio.
     *        A meaningful labeling SHOULD be chosen instead.
     *   ordering:
     *     1: The presumably most relevant option SHOULD be the first option.
     *
     * ---
     * @param string      $label
     * @param string|null $byline
     * @return    \ILIAS\UI\Component\Input\Field\Radio
     */
    public function radio(string $label, ?string $byline = null): Radio;

    /**
     * ---
     * description:
     *   purpose: >
     *     A Multi Select is used to allow users to pick several options from a list.
     *   composition: >
     *     The Multi Select field will render labeled checkboxes according to given options.
     *   effect: >
     *
     *   rivals:
     *     Checkbox Field: Use a Checkbox Field for a binary yes/no choice.
     *     Tag Field: Use a Tag Input when the user is able to extend the list of given options.
     *     Select Field: >
     *       Use a Select Input when the user's choice is limited to one option
     *       or the options are mutually exclusive.
     *
     * rules:
     *   usage:
     *     1: >
     *      A Multi Select input SHOULD be used when a user has to choose from a finite list of options
     *      which cannot be extended by the user's input and where more than one choice can be made.
     *     2: >
     *      A Multi Select input MUST NOT be used whenever a user has to perform a binary choice where
     *      option is automatically the inverse of the other. A Checkbox MUST be used in this case.
     *     3: >
     *      A Multi Select input MUST NOT be used whenever a user has to perform a choice from a list of
     *      options where only one option can be selected. A Select MUST be used in this case
     *
     *   wording:
     *     1: Each option MUST be labeled.
     *     2: >
     *       If the option governs a change of (service-)behavior, the option's
     *       label MUST be in form of a positive statement.
     *
     * ---
     * @param string               $label
     * @param array<string,string> $options with the select options as value=>label.
     * @param string|null          $byline
     * @return \ILIAS\UI\Component\Input\Field\MultiSelect
     */
    public function multiSelect(string $label, array $options, ?string $byline = null): MultiSelect;

    /**
     * ---
     * description:
     *   purpose: >
     *     A DateTime Input is used to enter dates and/or times.
     *   composition: >
     *     DateTime Input will render a text field with the placeholder-attribute
     *     indicating the specified format.
     *     Next to the text field, a Calendar Glyph will trigger a popover containing
     *     a graphical selector/date-picker.
     *     Depending on configuration (withTimeOnly), next to the date-picker a
     *     time-picker will be shown.
     *   effect: >
     *     When clicking the glyph, a popover is shown with the days of the month.
     *     Within the popover, the user may navigate to prior and following months.
     *   rivals:
     *     Text field: Text Felds MUST NOT be used to input date-strings.
     *
     * context:
     *   - DateTime Input is used in forms.
     *
     * rules:
     *   usage:
     *     1: When used as a time-only input, the glyph MUST be Time Glyph.
     *
     * ---
     * @param string      $label
     * @param string|null $byline
     * @return \ILIAS\UI\Component\Input\Field\DateTime
     */
    public function dateTime(string $label, ?string $byline = null): DateTime;

    /**
     * ---
     * description:
     *   purpose: >
     *     A Duration Input is used to enter a time span.
     *   composition: >
     *     A Duration Input is composed as a group of two DateTime Inputs.
     *   effect: >
     *     According to configuration, the inputs will accept dates, times or datetimes.
     *     Invalid input will be corrected automatically.
     *     The start point must take place before the endpoint; an error-message is
     *     shown if this is not the case.
     *
     * context:
     *   - Duration Input is used in forms.
     *
     * rules:
     *   usage:
     *     1: When used with time-only inputs, the glyph MUST be Time Glyph.
     *
     * ---
     * @param string      $label
     * @param string|null $byline
     * @return \ILIAS\UI\Component\Input\Field\Duration
     */
    public function duration(string $label, ?string $byline = null): Duration;

    /**
     * ---
     * description:
     *   purpose: >
     *     A File Input is used to upload one or multiple files, using the native
     *     filebrowser of a browser or Drag&Drop.
     *   composition: >
     *     A File Input is composed as a Dropzone and a list of files. The
     *     Dropzone contains a Shy Button for file selection.
     *   effect: >
     *     According to configuration, the input will accept files of certain
     *     types and sizes. Dragging files from a folder on the comuter to the
     *     Page in ILIAS will highlight the Dropzone.
     *     Clicking the Shy Button which starts the native browser file selection.
     *     Droppping the file onto the Dropzone or selecting a file in native
     *     browser will directly upload the file and add a info-line beneath
     *     the dropzone with the title and the size of the file and a Remove
     *     Glyph once the upload has finished.
     *     Clicking the Remove Glyph will remove the file-info and calls the
     *     upload-handler to delete the already uploaded file.
     *     Invalid files will lead to a error message in the dropzone.
     *
     * rules:
     *   usage:
     *     1: The consuming component MUST handle uploads and deletions of files.
     *
     * context:
     *   - Upload icons for items in the MainBar (https://docu.ilias.de/goto_docu_wiki_wpage_3993_1357.html)
     *
     * ---
     * @param UploadHandler $handler
     * @param string        $label
     * @param string|null   $byline
     * @param FormInput|null    $metadata_input
     * @return \ILIAS\UI\Component\Input\Field\File
     */
    public function file(
        UploadHandler $handler,
        string $label,
        ?string $byline = null,
        FormInput $metadata_input = null
    ): File;

    /**
     * ---
     * description:
     *   purpose: >
     *      The URL Input is intended for entering a single URL.
     *   composition: >
     *      URL Inputs will render an input-tag with type="url".
     *   effect: >
     *      URL Inputs are restricted to a single URL without a label.
     *      A URI check will ensure a correct URL format (e.g. 'https://www.ilias.de/') is
     *      inserted.
     *   rivals:
     *      Text Input: use a Text Input if users should input texts.
     *      Link Input: use a Link Input if users may also set a label for the URL
     *
     * context:
     *   - The single URL Input is used in UI-forms.
     *
     * rules:
     *   usage:
     *      1: The URL Input MUST NOT be used if a URL label has to be set.
     *
     * ---
     * @param string      $label
     * @param string|null $byline
     * @return \ILIAS\UI\Component\Input\Field\Url
     */
    public function url(string $label, ?string $byline = null): Url;

    /**
     * ---
     * description:
     *   purpose: >
     *      Link Inputs are used to enter URLs in conjunction with a label.
     *   composition: >
     *      Link Inputs are Input Groups consiting of a Text- and an Url Input.
     *   effect: >
     *      Two Text Inputs are rendered, of which the first one will accept all kinds of text
     *      while the second one will be restricted to URLs.
     *   rivals:
     *      Url Input: use a Url Input if users should input a URL only (without a label or similar)
     *
     * context:
     *   - The Link Input is used in UI-forms.
     *
     * rules:
     *   usage:
     *      1: >
     *        The URL Input MUST be used if a URL is to be entered together with an assigned label
     *
     * ---
     * @param string      $label
     * @param string|null $byline
     * @return \ILIAS\UI\Component\Input\Field\Link
     */
    public function link(string $label, ?string $byline = null): Link;

    /**
     * ---
     * description:
     *   purpose: >
     *      Hidden inputs are used for transmitting persistent data which the user should not manipulate.
     *   composition: >
     *      Hidden inputs consist of a html-input type hidden.
     *   effect: >
     *      A hidden input is rendered where developers can set any kind of value that should be transmitted.
     *
     * context:
     *   - The Hidden input is used in UI-forms.
     *
     * rules:
     *   usage:
     *      1: >
     *        Use this input for persistent data which the user should not manipulate.
     *
     * ---
     * @return \ILIAS\UI\Component\Input\Field\Hidden
     */
    public function hidden(): Hidden;

    /**
     * ---
     * description:
     *   purpose: >
     *     A Color Picker Input is used to select a color value.
     *   composition: >
     *      Color Picker will render an input-tag with type="color".
     *   effect: >
     *     As soon as the Color Picker is clicked, a pop-up window opens, which contains the individual options of the color selection.
     * context:
     *   - The Color Picker input is used in UI-forms.
     * rules:
     *   usage:
     *     1: The Color Picker should be used to select an individual color value.
     * accessibility:
     *     1: >
     *        As with all Inputs, the Color Picker Input MUST be operable by only using inputs.
     *        If HTML5 Standards are used, it is the responsibility of the Browser to provide this functionality.
     * ---
     * @param string      $label
     * @param string|null $byline
     * @return \ILIAS\UI\Component\Input\Field\ColorPicker
     */
    public function colorPicker(string $label, ?string $byline = null): ColorPicker;

    /**
     * ---
     * description:
     *   purpose: >
     *      Markdown inputs are used when formatted text should be submitted by using markdown-syntax.
     *      The input does support a user in writing markdown by providing action-buttons that insert the corresponding
     *      characters. It also provides a preview section where the already formatted text will be displayed.
     *   composition: >
     *      The Markdown input consists of two tabs (edit and preview) which will be implemented as buttons (ViewControl).
     *      The action-buttons either consist of a button or a Glyph, to better illustrate its intention. The editable
     *      area will be a simple textarea. If a limit is set, a byline about limitation is automatically set.
     *   effect: >
     *      Markdown inputs will render a textarea HTML tag which is decorated with action-buttons. Markdown inputs are
     *      NOT restricted to one line of text and counts the amount of character input by user and displays the number.
     *      The following formatting options will have a special effect on the input's preview:
     *          - Headings (# text)
     *          - Links ([text](url))
     *          - Bold (**text**)
     *          - Italic (_text_)
     *          - Ordered list (1. text)
     *          - Unordered list (- text)
     *   rivals:
     *      Text input: use a text-input if the content should not be formatted one line only.
     *      Textarea input: use a text-input if the content should not be formatted and on multiple lines.
     *
     * context:
     *   - The Markdown input is used in UI-Forms.
     *
     * rules:
     *   usage:
     *      1: >
     *        This input MUST be used whenever markdown-syntax is supported, e.g. if the user should be able to submit
     *        formatted text.
     *
     * ---
     * @param MarkdownRenderer $md_renderer
     * @param string           $label
     * @param string|null      $byline
     * @return \ILIAS\UI\Component\Input\Field\Markdown
     */
    public function markdown(MarkdownRenderer $md_renderer, string $label, string $byline = null): Markdown;
}
