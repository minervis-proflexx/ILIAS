<?php

declare(strict_types=1);

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

/**
* Class ilStudyProgrammeAutoMembershipsDBRepository
*
* @author Nils Haagen <nils.haagen@concepts-and-training.de>
*/
class ilStudyProgrammeAutoMembershipsDBRepository implements ilStudyProgrammeAutoMembershipsRepository
{
    private const TABLE = 'prg_auto_membership';
    private const FIELD_PRG_OBJ_ID = 'prg_obj_id';
    private const FIELD_SOURCE_TYPE = 'source_type';
    private const FIELD_SOURCE_ID = 'source_id';
    private const FIELD_ENABLED = 'enabled';
    private const FIELD_EDITOR_ID = 'last_usr_id';
    private const FIELD_LAST_EDITED = 'last_edited';

    protected ilDBInterface $db;
    protected int$current_usr_id;

    public function __construct(ilDBInterface $db, int $current_usr_id)
    {
        $this->db = $db;
        $this->current_usr_id = $current_usr_id;
    }

    /**
     * @inheritdoc
     */
    public function getFor(int $prg_obj_id): array
    {
        $query = 'SELECT '
            . self::FIELD_PRG_OBJ_ID . ','
            . self::FIELD_SOURCE_TYPE . ','
            . self::FIELD_SOURCE_ID . ','
            . self::FIELD_ENABLED . ','
            . self::FIELD_EDITOR_ID . ','
            . self::FIELD_LAST_EDITED
            . PHP_EOL . 'FROM ' . self::TABLE
            . PHP_EOL . 'WHERE ' . self::FIELD_PRG_OBJ_ID . ' = '
            . $this->db->quote($prg_obj_id, 'integer');
        $res = $this->db->query($query);
        $ret = [];
        while ($rec = $this->db->fetchAssoc($res)) {
            $ret[] = $this->create(
                (int) $rec[self::FIELD_PRG_OBJ_ID],
                $rec[self::FIELD_SOURCE_TYPE],
                (int) $rec[self::FIELD_SOURCE_ID],
                (bool) $rec[self::FIELD_ENABLED],
                (int) $rec[self::FIELD_EDITOR_ID],
                new DateTimeImmutable($rec[self::FIELD_LAST_EDITED])
            );
        }
        return $ret;
    }

    public function create(
        int $prg_obj_id,
        string $source_type,
        int $source_id,
        bool $enabled,
        int $last_edited_usr_id = null,
        DateTimeImmutable $last_edited = null
    ): ilStudyProgrammeAutoMembershipSource {
        if (is_null($last_edited_usr_id)) {
            $last_edited_usr_id = $this->current_usr_id;
        }
        if (is_null($last_edited)) {
            $last_edited = new DateTimeImmutable();
        }
        return new ilStudyProgrammeAutoMembershipSource(
            $prg_obj_id,
            $source_type,
            $source_id,
            $enabled,
            $last_edited_usr_id,
            $last_edited
        );
    }

    /**
     * @inheritdoc
     */
    public function update(ilStudyProgrammeAutoMembershipSource $ams): void
    {
        $ilAtomQuery = $this->db->buildAtomQuery();
        $ilAtomQuery->addTableLock(self::TABLE);
        $current_usr_id = $this->current_usr_id;
        $ilAtomQuery->addQueryCallable(
            function (ilDBInterface $db) use ($ams, $current_usr_id) {
                $query = 'DELETE FROM ' . self::TABLE
                    . PHP_EOL . 'WHERE prg_obj_id = ' . $ams->getPrgObjId()
                    . PHP_EOL . 'AND ' . self::FIELD_SOURCE_TYPE . ' = ' . $this->db->quote($ams->getSourceType(), 'string')
                    . PHP_EOL . 'AND ' . self::FIELD_SOURCE_ID . ' = ' . $ams->getSourceId();
                $db->manipulate($query);
                $now = new DateTimeImmutable();
                $now = $now->format('Y-m-d H:i:s');
                $db->insert(
                    self::TABLE,
                    [
                        self::FIELD_PRG_OBJ_ID => ['integer', $ams->getPrgObjId()],
                        self::FIELD_SOURCE_TYPE => ['text', $ams->getSourceType()],
                        self::FIELD_SOURCE_ID => ['integer', $ams->getSourceId()],
                        self::FIELD_ENABLED => ['integer', $ams->isEnabled()],
                        self::FIELD_EDITOR_ID => ['integer', $current_usr_id],
                        self::FIELD_LAST_EDITED => ['timestamp', $now]
                    ]
                );
            }
        );
        $ilAtomQuery->run();
    }

    /**
     * @inheritdoc
     */
    public function delete(int $prg_obj_id, string $source_type, int $source_id): void
    {
        $query = 'DELETE FROM ' . self::TABLE
            . PHP_EOL . 'WHERE prg_obj_id = ' . $this->db->quote($prg_obj_id, 'integer')
            . PHP_EOL . 'AND ' . self::FIELD_SOURCE_TYPE . ' = ' . $this->db->quote($source_type, 'string')
            . PHP_EOL . 'AND ' . self::FIELD_SOURCE_ID . ' = ' . $this->db->quote($source_id, 'integer');

        $this->db->manipulate($query);
    }

    /**
     * @inheritdoc
     */
    public function deleteFor(int $prg_obj_id): void
    {
        $query = 'DELETE FROM ' . self::TABLE
            . PHP_EOL . 'WHERE prg_obj_id = ' . $this->db->quote($prg_obj_id, 'integer');
        $this->db->manipulate($query);
    }

    /**
     * @inheritdoc
     */
    public static function getProgrammesFor(string $source_type, int $source_id): array
    {
        global $ilDB;
        $query = 'SELECT ' . self::FIELD_PRG_OBJ_ID
            . PHP_EOL . 'FROM ' . self::TABLE . ' prgs'
            . PHP_EOL . 'INNER JOIN object_reference oref ON '
            . 'prgs.' . self::FIELD_PRG_OBJ_ID . ' =  oref.obj_id'
            . PHP_EOL . 'WHERE ' . self::FIELD_SOURCE_TYPE . ' = ' . $ilDB->quote($source_type, 'text')
            . PHP_EOL . 'AND ' . self::FIELD_SOURCE_ID . ' = ' . $ilDB->quote($source_id, 'integer')
            . PHP_EOL . 'AND ' . self::FIELD_ENABLED . ' = 1'
            . PHP_EOL . 'AND oref.deleted IS NULL';

        $res = $ilDB->query($query);
        return $ilDB->fetchAll($res);
    }
}
