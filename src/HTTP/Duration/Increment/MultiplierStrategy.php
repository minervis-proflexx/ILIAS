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
 */

namespace ILIAS\HTTP\Duration\Increment;

use ILIAS\HTTP\Duration\Duration;

/**
 * @author Thibeau Fuhrer <thibeau@sr.solutions>
 */
class MultiplierStrategy implements IncrementStrategy
{
    protected float $multiplier;

    public function __construct(float $multiplier)
    {
        $this->multiplier = $multiplier;
    }

    public function increment(int $duration_in_ms): int
    {
        return ($duration_in_ms * $this->multiplier);
    }
}
