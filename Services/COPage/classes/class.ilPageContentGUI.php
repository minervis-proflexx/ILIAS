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

use ILIAS\COPage\PC\EditGUIRequest;
use ILIAS\COPage\Editor\EditSessionRepository;

use ILIAS\Style;

/**
 * User Interface for Editing of Page Content Objects (Paragraphs, Tables, ...)
 *
 * @author Alexander Killing <killing@leifos.de>
 */
class ilPageContentGUI
{
    protected \ILIAS\COPage\Editor\GUIService $editor_gui;
    protected \ILIAS\COPage\InternalGUIService $gui;
    protected EditSessionRepository $edit_repo;
    protected string $pc_id = "";
    protected array $chars;
    protected ?ilObjStyleSheet $style = null;
    public ?ilPageContent $content_obj;
    public ilGlobalTemplateInterface $tpl;
    public ilLanguage $lng;
    public ilCtrl $ctrl;
    public ilPageObject $pg_obj;
    public string $hier_id = "";
    public DOMDocument $dom;
    /** @var array|bool */
    public $updated;
    public string $target_script = "";
    public string $return_location = "";
    public ?ilPageConfig $page_config = null;
    protected ilLogger $log;
    protected int $styleid = 0;
    protected EditGUIRequest $request;
    protected string $sub_command = "";
    protected int $requested_ref_id = 0;

    public static string $style_selector_reset = "margin-top:2px; margin-bottom:2px; text-indent:0px; position:static; float:none; width: auto;";

    protected \ILIAS\GlobalScreen\ScreenContext\ContextServices $tool_context;

    // common bb buttons (special ones are iln and wln)
    protected static array $common_bb_buttons = array(
        "str" => "Strong", "emp" => "Emph", "imp" => "Important",
        "sup" => "Sup", "sub" => "Sub",
        "com" => "Comment",
        "quot" => "Quotation", "acc" => "Accent", "code" => "Code", "tex" => "Tex",
        "fn" => "Footnote", "xln" => "ExternalLink"
        );
    protected Style\Content\CharacteristicManager $char_manager;

    public function __construct(
        ilPageObject $a_pg_obj,
        ?ilPageContent $a_content_obj,
        string $a_hier_id = "",
        string $a_pc_id = "0"
    ) {
        global $DIC;

        $lng = $DIC->language();
        $ilCtrl = $DIC->ctrl();

        $this->log = ilLoggerFactory::getLogger('copg');

        $this->tpl = $DIC->ui()->mainTemplate();
        $this->lng = $lng;
        $this->pg_obj = $a_pg_obj;
        $this->ctrl = $ilCtrl;
        $this->content_obj = $a_content_obj;
        $service = $DIC->copage()->internal();
        $this->request = $service
            ->gui()
            ->pc()
            ->editRequest();
        $this->edit_repo = $service
            ->repo()
            ->edit();
        $this->sub_command = $this->request->getSubCmd();
        $this->requested_ref_id = $this->request->getRefId();
        $this->gui = $service->gui();

        if ($a_hier_id !== "0") {
            $this->hier_id = $a_hier_id;
            $this->pc_id = $a_pc_id;
            //echo "-".$this->pc_id."-";
            $this->dom = $a_pg_obj->getDomDoc();
        }
        $this->tool_context = $DIC->globalScreen()->tool()->context();
        $this->editor_gui = $DIC->copage()->internal()->gui()->edit();
    }

    public function setContentObject(ilPageContent $a_val): void
    {
        $this->content_obj = $a_val;
    }

    public function getContentObject(): ?ilPageContent
    {
        return $this->content_obj;
    }

    public function setPage(ilPageObject $a_val): void
    {
        $this->pg_obj = $a_val;
    }

    public function getPage(): ilPageObject
    {
        return $this->pg_obj;
    }

    public function setPageConfig(ilPageConfig $a_val): void
    {
        $this->page_config = $a_val;
    }

    public function getPageConfig(): ilPageConfig
    {
        return $this->page_config;
    }

    public static function _getCommonBBButtons(): array
    {
        return self::$common_bb_buttons;
    }

    public function setStyleId(int $a_styleid): void
    {
        $this->styleid = $a_styleid;
    }

    public function getStyleId(): int
    {
        return $this->styleid;
    }

    public function getStyle(): ?ilObjStyleSheet
    {
        if ((!is_object($this->style) || $this->getStyleId() != $this->style->getId()) && $this->getStyleId() > 0) {
            if (ilObject::_lookupType($this->getStyleId()) == "sty") {
                $this->style = new ilObjStyleSheet($this->getStyleId());
            }
        }
        return $this->style;
    }

    /**
     * Get characteristics of current style and call
     * setCharacteristics, if style is given
     */
    public function getCharacteristicsOfCurrentStyle(array $a_type): void
    {
        global $DIC;
        $service = $DIC->contentStyle()->internal();
        $access_manager = $service->domain()->access(
            $this->requested_ref_id,
            $DIC->user()->getId()
        );

        if ($this->getStyleId() > 0 &&
            ilObject::_lookupType($this->getStyleId()) == "sty") {
            $char_manager = $service->domain()->characteristic(
                $this->getStyleId(),
                $access_manager
            );

            if (!is_array($a_type)) {
                $a_type = array($a_type);
            }
            $chars = $char_manager->getByTypes($a_type, false, false);
            $new_chars = array();
            foreach ($chars as $char) {
                if (($this->chars[$char->getCharacteristic()] ?? "") != "") {	// keep lang vars for standard chars
                    $title = $char_manager->getPresentationTitle(
                        $char->getType(),
                        $char->getCharacteristic()
                    );
                    if ($title == "") {
                        $title = $this->chars[$char->getCharacteristic()];
                    }
                    $new_chars[$char->getCharacteristic()] = $title;
                } else {
                    $new_chars[$char->getCharacteristic()] = $char_manager->getPresentationTitle(
                        $char->getType(),
                        $char->getCharacteristic()
                    );
                }
            }
            $this->setCharacteristics($new_chars);
        }
    }

    public function setCharacteristics(array $a_chars): void
    {
        $this->chars = $a_chars;
    }

    public function getCharacteristics(): array
    {
        return $this->chars ?? [];
    }

    public function getHierId(): string
    {
        return $this->hier_id;
    }

    /**
     * set hierarchical id in dom object
     */
    public function setHierId(string $a_hier_id): void
    {
        $this->hier_id = $a_hier_id;
    }

    // delete content element
    public function delete(): void
    {
        $updated = $this->pg_obj->deleteContent($this->hier_id);
        if ($updated !== true) {
            $this->edit_repo->setPageError($updated);
        } else {
            $this->edit_repo->clearPageError();
        }
        $this->ctrl->returnToParent($this, "jump" . $this->hier_id);
    }

    public function displayValidationError(): void
    {
        if (is_array($this->updated)) {
            $error_str = "<strong>Error(s):</strong><br>";
            foreach ($this->updated as $error) {
                $err_mess = implode(" - ", $error);
                if (!is_int(strpos($err_mess, ":0:"))) {
                    $error_str .= htmlentities($err_mess) . "<br />";
                }
            }
            $this->tpl->setOnScreenMessage('failure', $error_str);
        } elseif ($this->updated != "" && $this->updated !== true) {
            $this->tpl->setOnScreenMessage('failure', "<strong>Error(s):</strong><br />" .
                $this->updated);
        }
    }

    /**
     * cancel creating page content
     */
    public function cancelCreate(): void
    {
        $this->ctrl->returnToParent($this, "jump" . $this->hier_id);
    }

    /**
     * cancel update
     */
    public function cancelUpdate(): void
    {
        $this->ctrl->returnToParent($this, "jump" . $this->hier_id);
    }

    /**
     * Cancel
     */
    public function cancel(): void
    {
        $this->ctrl->returnToParent($this, "jump" . $this->hier_id);
    }

    /**
     * gui function
     * set enabled if is not enabled and vice versa
     */
    public function deactivate(): void
    {
        $obj = &$this->content_obj;

        if ($obj->isEnabled()) {
            $obj->disable();
        } else {
            $obj->enable();
        }
        $this->updateAndReturn();
    }

    /**
     * Cut single element
     */
    public function cut(): void
    {
        $updated = $this->pg_obj->cutContents(array($this->hier_id . ":" . $this->pc_id));
        if ($updated !== true) {
            $this->edit_repo->setPageError($updated);
        } else {
            $this->edit_repo->clearPageError();
        }

        $this->log->debug("return to parent jump" . $this->hier_id);
        $this->ctrl->returnToParent($this, "jump" . $this->hier_id);
    }

    /**
     * Copy single element
     */
    public function copy(): void
    {
        $this->pg_obj->copyContents(array($this->hier_id . ":" . $this->pc_id));
        $this->ctrl->returnToParent($this, "jump" . $this->hier_id);
    }


    /**
     * Get table templates
     */
    public function getTemplateOptions(string $a_type = ""): array
    {
        $style = $this->getStyle();

        if (is_object($style)) {
            $ts = $style->getTemplates($a_type);
            $options = array();
            foreach ($ts as $t) {
                $options["t:" . $t["id"] . ":" . $t["name"]] = $t["name"];
            }
            return $options;
        }
        return array();
    }

    protected function redirectToParent(string $hier_id = ""): void
    {
        $ilCtrl = $this->ctrl;
        if ($hier_id == "") {
            $hier_id = $this->hier_id;
        }
        $pcid = $this->pg_obj->getPCIdForHierId($hier_id);
        $ilCtrl->returnToParent($this, "add" . $pcid);
    }

    protected function getParentReturn(string $hier_id = ""): string
    {
        if ($hier_id == "") {
            $hier_id = $this->hier_id;
        }
        $ilCtrl = $this->ctrl;
        $pcid = $this->pg_obj->getPCIdForHierId($hier_id);
        return $ilCtrl->getParentReturn($this) . "#add" . $pcid;
    }

    protected function updateAndReturn(): void
    {
        $up = $this->pg_obj->update();
        if ($up === true) {
            $this->edit_repo->clearPageError();
        } else {
            $this->edit_repo->setPageError($this->pg_obj->update());
        }
        $this->redirectToParent();
    }

    protected function setCurrentTextLang(string $lang_key): void
    {
        $this->edit_repo->setTextLang($this->requested_ref_id, $lang_key);
    }

    protected function getCurrentTextLang(): string
    {
        return $this->edit_repo->getTextLang($this->requested_ref_id);
    }

    protected function setEditorToolContext(): void
    {
        $collection = $this->tool_context->current()->getAdditionalData();
        if ($collection->exists(ilCOPageEditGSToolProvider::SHOW_EDITOR)) {
            $collection->replace(ilCOPageEditGSToolProvider::SHOW_EDITOR, true);
        } else {
            $collection->add(ilCOPageEditGSToolProvider::SHOW_EDITOR, true);
        }
    }

    protected function initEditor(): void
    {
        $this->setEditorToolContext();
        $this->editor_gui->init()->initUI($this->tpl);
    }

    protected function getEditorScriptTag(string $form_pc_id = "", string $form_cname = ""): string
    {
        return $this->editor_gui->init()->getInitHtml("", $form_pc_id, $form_cname);
    }

}
