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

/**
 * Class ilADTLocalizedTextDBBridge
 * @author Stefan Meyer <smeyer.ilias@gmx.de>
 */
class ilADTLocalizedTextDBBridge extends ilADTDBBridge
{
    public function getTable(): ?string
    {
        return 'adv_md_values_ltext';
    }

    protected function isValidADT(ilADT $adt): bool
    {
        return $adt instanceof ilADTLocalizedText;
    }

    public function readRecord(array $a_row): void
    {
        $active_languages = $this->getADT()->getCopyOfDefinition()->getActiveLanguages();
        $default_language = $this->getADT()->getCopyOfDefinition()->getDefaultLanguage();
        $language = $a_row[$this->getElementId() . '_language'];

        if (!$this->getADT()->getCopyOfDefinition()->getMultilingualValueSupport()) {
            $this->getADT()->setText($a_row[$this->getElementId() . '_translation' ]);
        } elseif (strcmp($language, $default_language) === 0) {
            $this->getADT()->setText($a_row[$this->getElementId() . '_translation']);
        } elseif (!strlen($default_language)) {
            $this->getADT()->setText($a_row[$this->getElementId() . '_translation']);
        }
        if (in_array($language, $active_languages)) {
            $this->getADT()->setTranslation(
                $language,
                (string) $a_row[$this->getElementId() . '_translation']
            );
        }
    }

    public function prepareInsert(array &$a_fields): void
    {
        $a_fields[$this->getElementId()] = [ilDBConstants::T_TEXT, $this->getADT()->getText()];
    }

    /**
     *
     */
    public function afterInsert(): void
    {
        $this->afterUpdate();
    }

    public function getAdditionalPrimaryFields(): array
    {
        return [
            'value_index' => [ilDBConstants::T_TEXT, '']
        ];
    }

    public function afterUpdate(): void
    {
        if (!$this->getADT()->getCopyOfDefinition()->supportsTranslations()) {
            return;
        }
        $this->deleteTranslations();
        $this->insertTranslations();
    }

    protected function deleteTranslations(): void
    {
        $this->db->manipulate(
            $q =
            'delete from ' . $this->getTable() . ' ' .
            'where ' . $this->buildPrimaryWhere() . ' ' .
            'and value_index != ' . $this->db->quote('', ilDBConstants::T_TEXT)
        );
    }

    /**
     * Save all translations
     * TODO: Translations are always persisted for all active languages, even
     *  if the translation is an empty string. This shouldn't work that way.
     */
    protected function insertTranslations(): void
    {
        foreach ($this->getADT()->getTranslations() as $language => $value) {
            $fields = $this->getPrimary();
            $fields['value_index'] = [ilDBConstants::T_TEXT, $language];
            $fields['value'] = [ilDBConstants::T_TEXT, $value];
            $this->db->insert($this->getTable(), $fields);
        }
    }
}
