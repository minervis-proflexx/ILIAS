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

class ilDclDatetimeRecordRepresentation extends ilDclBaseRecordRepresentation
{
    use ilDclDatetimeRecordDateFormatter;

    /**
     * Outputs html of a certain field
     */
    public function getHTML(bool $link = true, array $options = []): string
    {
        $value = $this->getRecordField()->getValue();
        if ($value == '0000-00-00 00:00:00' || !$value) {
            return $this->lng->txt('no_date');
        }

        return $this->formatDateFromString($value);
    }

    protected function getUserDateFormat(): string
    {
        return (string) $this->user->getDateFormat();
    }

    /**
     * function parses stored value to the variable needed to fill into the form for editing.
     * @param string|int $value
     */
    public function parseFormInput($value): ?string
    {
        if (!$value || $value == "-") {
            return null;
        }

        return substr($value, 0, -9);
    }
}
