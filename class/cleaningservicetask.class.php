<?php

require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';

class CleaningServiceTask extends CommonObject
{
    /**
     * @var string ID of module.
     */
    public $module = 'cleaningservice';

    /**
     * @var array  Array with all extra fields and their property
     */
    public $array_options = array();

    /**
     * @var string Name of table without prefix where object is stored
     */
    public $table_element = 'cleaningservice_task';
    public $element = 'cleaningservice_task';
    /**
     * @var int  Does this object support multicompany module
     */
    public $ismultientitymanaged = 1;

    /**
     * @var string String with name of icon for cleaningservice
     */
    public $picto = 'task';

    public $fields = array(
        'ref' => array('type' => 'varchar(128)', 'label' => 'Ref', 'enabled' => 1),
        'label' => array('type' => 'varchar(255)', 'label' => 'Label', 'enabled' => 1),
        'description' => array('type' => 'text', 'label' => 'Description', 'enabled' => 1),
        'date_start' => array('type' => 'datetime', 'label' => 'DateStart', 'enabled' => 1),
        'date_end' => array('type' => 'datetime', 'label' => 'DateEnd', 'enabled' => 1),
        'fk_soc' => array('type' => 'integer', 'label' => 'ThirdParty', 'enabled' => 1),
        'status' => array('type' => 'integer', 'label' => 'Status', 'enabled' => 1),
    );

    const STATUS_DRAFT = 0;
    const STATUS_VALIDATED = 1;
    const STATUS_IN_PROGRESS = 2;
    const STATUS_DONE = 3;
    const STATUS_CANCELED = 9;

    public function __construct($db)
    {
        global $conf, $langs;

        $this->db = $db;

        $this->status = self::STATUS_DRAFT;
        $this->fields = array_merge($this->fields, array(
            'fk_user_creat' => array('type' => 'integer', 'label' => 'UserAuthor', 'enabled' => 1),
            'fk_user_modif' => array('type' => 'integer', 'label' => 'UserModif', 'enabled' => 1),
            'date_creation' => array('type' => 'datetime', 'label' => 'DateCreation', 'enabled' => 1),
            'tms' => array('type' => 'timestamp', 'label' => 'DateModification', 'enabled' => 1),
            'import_key' => array('type' => 'varchar(14)', 'label' => 'ImportId', 'enabled' => 1),
            'active' => array('type' => 'integer', 'label' => 'Active', 'enabled' => 1),
        ));
    }

    /**
     * Create object into database
     *
     * @param  User $user      User that creates
     * @param  bool $notrigger false=launch triggers after, true=disable triggers
     * @return int             <0 if KO, Id of created object if OK
     */
    public function create(User $user, $notrigger = false)
    {
        return $this->createCommon($user, $notrigger);
    }


    /**
     * Load object in memory from the database
     *
     * @param  int    $id   Id object
     * @param  string $ref  Ref
     * @return int         <0 if KO, 0 if not found, >0 if OK
     */
    public function fetch($id, $ref = '')
    {
        // Add debugging
        dol_syslog(get_class($this) . "::fetch id=" . $id . " ref=" . $ref, LOG_DEBUG);

        $sql = "SELECT t.rowid, t.ref, t.label, t.description, t.date_start, t.date_end,";
        $sql .= " t.fk_soc, t.status, t.date_creation, t.tms,";
        $sql .= " t.fk_user_creat, t.fk_user_modif, t.active";
        $sql .= " FROM " . MAIN_DB_PREFIX . $this->table_element . " as t";

        if (!empty($id)) {
            $sql .= " WHERE t.rowid = " . ((int) $id);
        } elseif (!empty($ref)) {
            $sql .= " WHERE t.ref = '" . $this->db->escape($ref) . "'";
        }

        dol_syslog(get_class($this) . "::fetch sql=" . $sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql) {
            if ($this->db->num_rows($resql)) {
                $obj = $this->db->fetch_object($resql);

                $this->id = $obj->rowid;
                $this->ref = $obj->ref;
                $this->label = $obj->label;
                $this->description = $obj->description;
                $this->date_start = $this->db->jdate($obj->date_start);
                $this->date_end = $this->db->jdate($obj->date_end);
                $this->fk_soc = $obj->fk_soc;
                $this->status = $obj->status;
                $this->date_creation = $this->db->jdate($obj->date_creation);
                $this->tms = $this->db->jdate($obj->tms);
                $this->fk_user_creat = $obj->fk_user_creat;
                $this->fk_user_modif = $obj->fk_user_modif;
                $this->active = $obj->active;

                $this->db->free($resql);

                // Retrieve all extrafields
                $this->fetch_optionals();

                return 1;
            }
            $this->db->free($resql);
            return 0;
        } else {
            $this->error = "Error " . $this->db->lasterror();
            return -1;
        }
    }

    /**
     * Update object into database
     *
     * @param  User $user      User that modifies
     * @param  bool $notrigger false=launch triggers after, true=disable triggers
     * @return int             <0 if KO, >0 if OK
     */
    public function update(User $user, $notrigger = false)
    {
        return $this->updateCommon($user, $notrigger);
    }

    /**
     * Delete object in database
     *
     * @param  User $user      User that deletes
     * @param  bool $notrigger false=launch triggers after, true=disable triggers
     * @return int             <0 if KO, >0 if OK
     */
    public function delete(User $user, $notrigger = false)
    {
        return $this->deleteCommon($user, $notrigger);
    }

    /**
     * Returns the reference to the following non used task depending on the active numbering module
     *
     * @return string            Reference for task
     */
    public function getNextNumRef()
    {
        global $conf, $langs;
        $ref = 'CS' . dol_print_date(dol_now(), '%y%m') . '-';
        $ref .= sprintf("%04d", $this->getNextNumRefFromMask());
        return $ref;
    }

    /**
     * Get next reference counter from mask
     *
     * @return int counter
     */
    private function getNextNumRefFromMask()
    {
        $sql = "SELECT MAX(CAST(SUBSTRING(ref, 9) AS SIGNED)) as max_ref";
        $sql .= " FROM " . MAIN_DB_PREFIX . $this->table_element;
        $sql .= " WHERE ref LIKE 'CS" . dol_print_date(dol_now(), '%y%m') . "%'";

        $resql = $this->db->query($sql);
        if ($resql) {
            $obj = $this->db->fetch_object($resql);
            if ($obj) return $obj->max_ref + 1;
            return 1;
        }
        return 0;
    }
}
