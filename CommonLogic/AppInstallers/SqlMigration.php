<?php

namespace exface\Core\CommonLogic\AppInstallers;

/**
 * Class for SQL Database Migrations.
 *
 * @author Ralf Mulansky
 *
 */

class SqlMigration
{
    private $id = null;
    
    private $migration_name= '';
    
    private $up_datetime = '';
    
    private $up_script = '';
    
    private $up_result = '';
    
    private $down_datetime = '';
    
    private $down_script = '';
    
    private $down_result = '';
    
    private $is_up = FALSE;
    
    
    /**
     * 
     * @param string $migration_name
     * @param string $up_script
     * @param string $down_script
     */
    public function __construct($migration_name, $up_script, $down_script)
    {
        $this->migration_name=$migration_name;
        $this->up_script=$up_script;
        $this->down_script=$down_script;
    }
    
    /**
     * Returns id of the SqlMigration
     * 
     * @return string
     */
    public function getId() : string
    {
        return $this->id;
    }
    
    /**
     * Returns name of the SqlMigration
     *
     * @return string
     */
    public function getMigrationName() : string
    {
        return $this->migration_name;
    }    
 
    /**
     * Returns Up Datetime ofthe SqlMigration
     *
     * @return string
     */
    public function getUpDatetime() : string
    {
        return $this->up_datetime;
    }
    
    /**
     * Returns Up script of the SqlMigration
     *
     * @return string
     */
    public function getUpScript() : string
    {
        return $this->up_script;
    }
   
    /**
     * Returns up result of the SqlMigration
     *
     * @return string
     */
    public function getUpResult() : string
    {
        return $this->up_result;
    }
    
    /**
     * Returns Down datetime of SqlMigration
     *
     * @return string
     */
    public function getDownDatetime() : string
    {
        return $this->down_datetime;
    }
    
    /**
     * Returns Down script of SqlMigration
     *
     * @return string
     */
    public function getDownScript() : string
    {
        return $this->down_script;
    }
    
    /**
     * Returns Down result of SqlMigration
     *
     * @return string
     */
    public function getDownResult() : string
    {
        return $this->down_result;
    }
    
    /**
     * Returns if Migration is up (TRUE) or not (FALSE)
     *
     * @return bool
     */
    public function isUp(): bool
    {
        return $this->is_up;
    }    
 
    /**
     * Function to compare if this migration equals other Migration.
     * They are equal when the Migration name is the same.
     * 
     * @param SqlMigration $otherMigration
     * @return bool
     */
    public function equals(SqlMigration $otherMigration) : bool
    {
        $thisFileName = pathinfo($this->getMigrationName(), PATHINFO_FILENAME);
        $otherFileName = pathinfo($otherMigration->getMigrationName(), PATHINFO_FILENAME);
        return $thisFileName === $otherFileName;
    }
    
    /**
     * Function to change the state of the migration to UP, meaning $is_up = TRUE.
     * 
     * @param int $id
     * @param string $dateTime
     * @param string $script
     * @param string $result
     * @return SqlMigration
     */
    public function setUp(int $id, string $dateTime, string $result) : SqlMigration
    {
        $this->is_up = true;
        $this->id = $id;
        $this->up_datetime = $dateTime;
        $this->up_result = $result;
        return $this;
    }
    
    /***
     * Function to change the state of migration to DOWN, meaning $is_up = FALSE.
     * 
     * @param string $dateTime
     * @param string $result
     * @return SqlMigration
     */
    public function setDown (string $dateTime, string $result) : SqlMigration
    {
        $this->is_up = false;
        $this->down_datetime = $dateTime;
        $this->down_result = $result;
        return $this;
    }
}
?>