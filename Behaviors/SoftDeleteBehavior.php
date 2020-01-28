<?php
namespace exface\Core\Behaviors;

use exface\Core\CommonLogic\Model\Behaviors\AbstractBehavior;
use exface\Core\Events\DataSheet\OnBeforeDeleteDataEvent;
use exface\Core\Interfaces\Model\BehaviorInterface;
use exface\Core\Exceptions\DataSheets\DataSheetColumnNotFoundError;
use exface\Core\Interfaces\Model\MetaAttributeInterface;
use exface\Core\CommonLogic\UxonObject;
use exface\Core\CommonLogic\DataSheets\DataColumn;
use exface\Core\Events\DataSheet\OnDeleteDataEvent;
use exface\Core\Exceptions\DataSheets\DataSheetWriteError;
use exface\Core\Interfaces\DataSheets\DataSheetInterface;
use exface\Core\Exceptions\DataSheets\DataSheetStructureError;
use exface\Core\Exceptions\Behaviors\BehaviorConfigurationError;

/**
 * This behavior may be used for soft-deletion, i.e. for setting an specific value to an attribute of an object, instead of deleting it.
 * Therefore this behavior requires two attibutes in order to be used: 
 *      `$soft_delete_attribute_alias` - as the attribute of the object, in which the deletion is being flagged
 *      `$soft_delete_value` - as the value a deleted object has in that attribute, when it should be considered deleted.
 * 
 * @author tmc
 *
 */
class SoftDeleteBehavior extends AbstractBehavior
{
    private $soft_delete_attribute_alias = null;
    
    private $soft_delete_value = null;
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\CommonLogic\Model\Behaviors\AbstractBehavior::register()
     */
    public function register() : BehaviorInterface
    {
        $this->getSoftDeleteAttribute()->setSystem(true)->setDefaultAggregateFunction('MAX');
       
        $this->getWorkbench()->eventManager()->addListener(OnBeforeDeleteDataEvent::getEventName(), [$this, 'setFlagOnDelete']);

        $this->setRegistered(true);
        return $this;
    }
    
    /**
     * This function contains all the logic for setting the given soft-delete-value into the given soft-delete-attribute.
     * The entries which shall be marked as deleted are read from the datasheet passed with the event.
     * The rows to set deleted may be passed in two different ways, and have to be handled differently:
     *      - rows are passed as actual rows in the datasheet:
     *          The columns of the datasheet are being stripped down to the essential ones (`uid`, `modified_on`
     *          and the softDeleteAttribute), then the soft-delete-value is set to the soft-delete-attribute,
     *          and the data is updated to the metaobject.
     *          
     *      - there are no rows in the events datasheet, only filters:
     *          Firstly, all rows which match the filters passed in the datasheet are read from the metaobject,
     *          then handle the datasheet as described above.
     * 
     * @param OnBeforeDeleteDataEvent $event
     * @throws DataSheetColumnNotFoundError
     * @throws DataSheetWriteError
     * @return void|number
     */
    public function setFlagOnDelete(OnBeforeDeleteDataEvent $event)
    {
        if ($this->isDisabled())
            return;
            
        $eventData = $event->getDataSheet();
        
        // Do not do anything, if the base object of the data sheet is not the object with the behavior and is not
        // extended from it.
        if (! $eventData->getMetaObject()->isExactly($this->getObject())) {
            return;
        }

        // prevent deletion of the main object, but dont prevent the cascading deletion
        $event->preventDelete(false);

        $transaction = $event->getTransaction();

        $affected_rows = 0;

        $updateData = $eventData->copy();
        
        // add all data, that fits the filters, or else update wont work when a datasheet with no rows, but only filters is given. (FIXME?)
        if ($updateData->isEmpty() && $updateData->getFilters() !== null){
            $updateData->addRow([]);
        }
        
        if ($updateData->hasUidColumn()){
            $uidCol = $updateData->getUidColumn();
        }
        
        // remove all columns, except of system columns
        foreach ($updateData->getColumns() as $col){
            if ($updateData->getMetaObject()->hasAttribute($col->getAttributeAlias())){
                switch (true){
                    case ($col->getAttribute()->isSystem()):
                    case ($col === $uidCol):
                        break;
                    default:
                        $updateData->getColumns()->remove($col);
                }
            } else {
                $updateData->getColumns()->remove($col);
            }
        }


        // first check if metaobject has soft-delete-attribute
        if ($eventData->getMetaObject()->hasAttribute($this->getSoftDeleteAttributeAlias())){
            
            // add the soft-delete-column
            $deletedCol = $updateData->getColumns()->addFromAttribute($this->getSoftDeleteAttribute());
            
            if ($updateData->isEmpty() === false){
                $updateData = $this->assignFlagsInDataSheetRows($updateData);
                $deletedCol->setValueOnAllRows($this->getSoftDeleteValue());
                $updatedRows = $updateData->dataUpdate(false, $transaction);
                $affected_rows += $updatedRows;
            } else {
                throw new DataSheetStructureError($updateData, 'Cannot set SoftDeleteFlag for current selection: no rows found in data sheet!');
            }
        }
            
        $eventData->setCounterForRowsInDataSource($updateData->countRowsInDataSource());

        if ($eventData->isEmpty() === false){
            $deletedCol = $eventData->getColumns()->getByAttribute($this->getSoftDeleteAttribute());
            $deletedCol->setValueOnAllRows($this->getSoftDeleteValue());
        }

        return $affected_rows;
    }
    
    /**
     * Function for setting the softdelete-flag in all the rows of an given datasheet.
     * 
     * @param DataSheetInterface $ds
     * @return DataSheetInterface
     */
    protected function assignFlagsInDataSheetRows(DataSheetInterface $ds) : DataSheetInterface
    {
        if ($deletedCol = $ds->getColumns()->getByExpression($this->getSoftDeleteAttributeAlias())){
            $deletedCol->setValueOnAllRows($this->getSoftDeleteValue());
        }
        return $ds;
    }
    
    /**
     * 
     * @return string
     */
    public function getSoftDeleteAttributeAlias() 
    {
        if ($this->getObject()->hasAttribute($this->soft_delete_attribute_alias)){
            return $this->soft_delete_attribute_alias;
        } else {
            throw new BehaviorConfigurationError($this->getObject(), 'Configuration error: no attribute ' . $this->getSoftDeleteAttributeAlias() . 'found in object ' . $this->getObject()->getAlias() . '.');
        }
    }
    
    /**
     * Alias of the attribute, where the deletion flag is being set.
     *
     * @uxon-property soft_delete_attribute_alias
     * @uxon-type metamodel:attribute
     * @uxon-required true
     *
     * @param string $value
     * @return \exface\Core\Behaviors\SoftDeleteBehavior
     */
    public function setSoftDeleteAttributeAlias(string $value) : SoftDeleteBehavior
    {
        $this->soft_delete_attribute_alias = $value;
        //check whether the getter throws en exeption, to ensure the attributename is valid for this object
        $this->getSoftDeleteAttributeAlias();
        return $this;
    }
        
    /**
     * 
     * @return MetaAttributeInterface
     */
    public function getSoftDeleteAttribute() : MetaAttributeInterface
    {
        return $this->getObject()->getAttribute($this->getSoftDeleteAttributeAlias()); 
    }
    
    /**
     * 
     * @return string
     */
    protected function getSoftDeleteValue() : string
    {
        return $this->soft_delete_value;
    }
    
    /**
     * Value, which should be filled into the flag attribute.
     *
     * @uxon-property soft_delete_value
     * @uxon-type string
     * @uxon-required true
     * 
     * @param string $value
     * @return SoftDeleteBehavior
     */
    public function setSoftDeleteValue(string $value) : SoftDeleteBehavior
    {
        $this->soft_delete_value = $value;
        return $this;
    }
    
    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\Core\CommonLogic\Model\Behaviors\AbstractBehavior::exportUxonObject()
     */
    public function exportUxonObject()
    {
        $uxon = parent::exportUxonObject();
        $uxon->setProperty('soft_delete_attribute_alias', $this->getSoftDeleteAttributeAlias());
        $uxon->setProperty('soft_delete_value', $this->getSoftDeleteValue);
        return $uxon;
    }
}