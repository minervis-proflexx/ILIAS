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

use Psr\Http\Message\ServerRequestInterface;

/**
 * Class ilMailMemberSearchGUI
 * @author Nadia Matuschek <nmatuschek@databay.de>
 *
**/
class ilMailMemberSearchGUI
{
    private readonly ServerRequestInterface $httpRequest;
    /** @var array{role_id: int, mailbox: string, form_option_title: string, default_checked: bool}[] */
    private readonly array $mail_roles;
    private ?ilParticipants $objParticipants = null;
    private readonly ilCtrlInterface $ctrl;
    private readonly ilGlobalTemplateInterface $tpl;
    private readonly ilLanguage $lng;
    private readonly ilAccessHandler $access;

    /**
     * ilMailMemberSearchGUI constructor.
     * @param ilObjGroupGUI|ilObjCourseGUI|ilMembershipGUI $gui
     * @param ilAbstractMailMemberRoles $objMailMemberRoles
     */
    public function __construct(private readonly object $gui, public int $ref_id, private readonly ilAbstractMailMemberRoles $objMailMemberRoles)
    {
        global $DIC;

        $this->ctrl = $DIC['ilCtrl'];
        $this->tpl = $DIC['tpl'];
        $this->lng = $DIC['lng'];
        $this->access = $DIC['ilAccess'];
        $this->httpRequest = $DIC->http()->request();

        $this->lng->loadLanguageModule('mail');
        $this->lng->loadLanguageModule('search');
        $this->mail_roles = $objMailMemberRoles->getMailRoles($ref_id);
    }


    public function executeCommand(): bool
    {
        $cmd = $this->ctrl->getCmd();

        $this->ctrl->setReturn($this, '');

        switch ($cmd) {
            case 'sendMailToSelectedUsers':
                $this->sendMailToSelectedUsers();
                break;

            case 'showSelectableUsers':
                $this->showSelectableUsers();
                break;

            case 'nextMailForm':
                $this->nextMailForm();
                break;

            case 'cancel':
                $this->redirectToParentReferer();
                break;

            default:
                if (isset($this->httpRequest->getQueryParams()['returned_from_mail']) && $this->httpRequest->getQueryParams()['returned_from_mail'] === '1') {
                    $this->redirectToParentReferer();
                }
                $this->showSearchForm();
                break;
        }

        return true;
    }

    private function redirectToParentReferer(): void
    {
        $url = $this->getStoredReferer();
        $this->unsetStoredReferer();
        $this->ctrl->redirectToURL($url);
    }

    public function storeReferer(): void
    {
        $back_link = $this->ctrl->getParentReturn($this);

        if (isset($this->httpRequest->getServerParams()['HTTP_REFERER'])) {
            $referer = $this->httpRequest->getServerParams()['HTTP_REFERER'];
            $urlParts = parse_url($referer);

            if (isset($urlParts['path'])) {
                $url = ltrim(basename($urlParts['path']), '/');
                if (isset($urlParts['query'])) {
                    $url .= '?' . $urlParts['query'];
                }
                if ($url !== '') {
                    $back_link = $url;
                }
            }
        }

        ilSession::set('ilMailMemberSearchGUIReferer', $back_link);
    }

    private function getStoredReferer(): string
    {
        return (string) ilSession::get('ilMailMemberSearchGUIReferer');
    }

    private function unsetStoredReferer(): void
    {
        ilSession::set('ilMailMemberSearchGUIReferer', '');
    }

    protected function nextMailForm(): void
    {
        $form = $this->initMailToMembersForm();
        if ($form->checkInput()) {
            if ($form->getInput('mail_member_type') === 'mail_member_roles') {
                if (is_array($form->getInput('roles')) && $form->getInput('roles') !== []) {
                    $role_mail_boxes = [];
                    $roles = $form->getInput('roles');
                    foreach ($roles as $role_id) {
                        $mailbox = $this->objMailMemberRoles->getMailboxRoleAddress((int) $role_id);
                        $role_mail_boxes[] = $mailbox;
                    }

                    ilSession::set('mail_roles', $role_mail_boxes);

                    $this->ctrl->redirectToURL(ilMailFormCall::getRedirectTarget(
                        $this,
                        'showSearchForm',
                        ['type' => ilMailFormGUI::MAIL_FORM_TYPE_ROLE],
                        [
                            'type' => ilMailFormGUI::MAIL_FORM_TYPE_ROLE,
                            'rcp_to' => implode(',', $role_mail_boxes),
                            'sig' => $this->gui->createMailSignature()
                        ],
                        $this->generateContextArray()
                    ));
                } else {
                    $form->setValuesByPost();
                    $this->tpl->setOnScreenMessage('failure', $this->lng->txt('no_checkbox'));
                    $this->showSearchForm();
                    return;
                }
            } else {
                $this->showSelectableUsers();
                return;
            }
        }

        $form->setValuesByPost();
        $this->showSearchForm();
    }

    protected function generateContextArray(): array
    {
        $contextParameters = [];

        $type = ilObject::_lookupType($this->ref_id, true);
        switch ($type) {
            case 'grp':
            case 'crs':
                if ($this->access->checkAccess('write', '', $this->ref_id)) {
                    $contextParameters = [
                        'ref_id' => $this->ref_id,
                        'ts' => time(),
                        ilMail::PROP_CONTEXT_SUBJECT_PREFIX => ilContainer::_lookupContainerSetting(
                            ilObject::_lookupObjId($this->ref_id),
                            ilObjectServiceSettingsGUI::EXTERNAL_MAIL_PREFIX,
                            ''
                        )
                    ];

                    if ('crs' === $type) {
                        $contextParameters[ilMailFormCall::CONTEXT_KEY] = ilCourseMailTemplateTutorContext::ID;
                    }
                }
                break;

            case 'sess':
                if ($this->access->checkAccess('write', '', $this->ref_id)) {
                    $contextParameters = [
                        ilMailFormCall::CONTEXT_KEY => ilSessionMailTemplateParticipantContext::ID,
                        'ref_id' => $this->ref_id,
                        'ts' => time()
                    ];
                }
                break;
        }

        return $contextParameters;
    }

    protected function showSelectableUsers(): void
    {
        $this->tpl->loadStandardTemplate();
        $tbl = new ilMailMemberSearchTableGUI($this, 'showSelectableUsers');
        $provider = new ilMailMemberSearchDataProvider($this->getObjParticipants(), $this->ref_id);
        $tbl->setData($provider->getData());

        $this->tpl->setContent($tbl->getHTML());
    }


    protected function sendMailToSelectedUsers(): void
    {
        if (!isset($this->httpRequest->getParsedBody()['user_ids']) || !is_array($this->httpRequest->getParsedBody()['user_ids']) || [] === $this->httpRequest->getParsedBody()['user_ids']) {
            $this->tpl->setOnScreenMessage('failure', $this->lng->txt("no_checkbox"));
            $this->showSelectableUsers();
            return;
        }

        $rcps = [];
        foreach ($this->httpRequest->getParsedBody()['user_ids'] as $usr_id) {
            $rcps[] = ilObjUser::_lookupLogin((int) $usr_id);
        }

        if (array_filter($rcps) === []) {
            $this->tpl->setOnScreenMessage('failure', $this->lng->txt("no_checkbox"));
            $this->showSelectableUsers();
            return;
        }

        ilMailFormCall::setRecipients($rcps);

        $this->ctrl->redirectToURL(ilMailFormCall::getRedirectTarget(
            $this,
            'members',
            [],
            [
                'type' => ilMailFormGUI::MAIL_FORM_TYPE_NEW,
                'sig' => $this->gui->createMailSignature(),
            ],
            $this->generateContextArray()
        ));
    }

    protected function showSearchForm(): void
    {
        $this->storeReferer();

        $form = $this->initMailToMembersForm();
        $this->tpl->setContent($form->getHTML());
    }


    protected function getObjParticipants(): ?ilParticipants
    {
        return $this->objParticipants;
    }

    public function setObjParticipants(ilParticipants $objParticipants): void
    {
        $this->objParticipants = $objParticipants;
    }


    protected function initMailToMembersForm(): ilPropertyFormGUI
    {
        $this->lng->loadLanguageModule('mail');

        $form = new ilPropertyFormGUI();
        $form->setTitle($this->lng->txt('mail_members'));

        $form->setFormAction($this->ctrl->getFormAction($this, 'nextMailForm'));

        $radio_grp = $this->getMailRadioGroup();

        $form->addItem($radio_grp);
        $form->addCommandButton('nextMailForm', $this->lng->txt('mail_members_search_continue'));
        $form->addCommandButton('cancel', $this->lng->txt('cancel'));

        return $form;
    }

    /**
     * @return array{role_id: int, mailbox: string, form_option_title: string, default_checked?: bool}[]
     */
    private function getMailRoles(): array
    {
        return $this->mail_roles;
    }


    protected function getMailRadioGroup(): ilRadioGroupInputGUI
    {
        $mail_roles = $this->getMailRoles();

        $radio_grp = new ilRadioGroupInputGUI($this->lng->txt('mail_sel_label'), 'mail_member_type');

        $radio_sel_users = new ilRadioOption($this->lng->txt('mail_sel_users'), 'mail_sel_users');

        $radio_roles = new ilRadioOption($this->objMailMemberRoles->getRadioOptionTitle(), 'mail_member_roles');
        foreach ($mail_roles as $role) {
            $chk_role = new ilCheckboxInputGUI($role['form_option_title'], 'roles[' . $role['role_id'] . ']');

            if (isset($role['default_checked']) && $role['default_checked']) {
                $chk_role->setChecked(true);
            }
            $chk_role->setValue((string) $role['role_id']);
            $chk_role->setInfo($role['mailbox']);
            $radio_roles->addSubItem($chk_role);
        }

        $radio_grp->setValue('mail_member_roles');

        $radio_grp->addOption($radio_sel_users);
        $radio_grp->addOption($radio_roles);

        return $radio_grp;
    }
}
