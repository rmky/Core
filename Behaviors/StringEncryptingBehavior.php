<?php
namespace exface\Core\Behaviors;

use exface\Core\CommonLogic\Model\Behaviors\AbstractBehavior;
use exface\Core\Interfaces\Model\MetaAttributeInterface;
use exface\Core\Interfaces\Model\BehaviorInterface;
use exface\Core\Events\DataSheet\OnBeforeCreateDataEvent;
use exface\Core\Events\DataSheet\OnBeforeUpdateDataEvent;
use exface\Core\DataTypes\EncryptedStringDataType;
use exface\Core\Interfaces\DataSheets\DataSheetInterface;
use exface\Core\Interfaces\Model\ExpressionInterface;
use exface\Core\Exceptions\RuntimeException;
use exface\Core\Events\DataSheet\OnBeforeReadDataEvent;
use exface\Core\DataTypes\StringDataType;
use exface\Core\Events\DataSheet\AbstractDataSheetEvent;

/**
 * This behavior will encrypt strings in the given attribute when they are created or updated and decrypt them when read.
 * 
 * @author Andrej Kabachnik
 *
 */
class StringEncryptingBehavior extends AbstractBehavior
{    
    private $stringAttribute = null;
    
    private $key_attribute_alias = null;

    public function register() : BehaviorInterface
    {
        // Give the event handlers a hight priority to make sure, the strings are encoded/decoded before
        // any other behaviors get their hands on the data!
        $this->getWorkbench()->eventManager()
        ->addListener(OnBeforeCreateDataEvent::getEventName(), [$this, 'handleOnCreateEvent'], 1000)
        ->addListener(OnBeforeUpdateDataEvent::getEventName(), [$this, 'handleOnUpdateEvent'], 1000)     
        ->addListener(OnBeforeReadDataEvent::getEventName(), [$this, 'handleOnReadEvent'], 1000);
        $this->setRegistered(true);
        return $this;
    }
    
    public function handleOnCreateEvent(OnBeforeCreateDataEvent $event) 
    {
        return $this->handleOnCreateUpdateEvent($event);
    }
    
    public function handleOnUpdateEvent(OnBeforeUpdateDataEvent $event)
    {
        return $this->handleOnCreateUpdateEvent($event);
    }
    
    protected function handleOnCreateUpdateEvent(AbstractDataSheetEvent $event)
    {
        if ($this->isDisabled()) {
            return;
        }
        
        $dataSheet = $event->getDataSheet();
        
        // Do not do anything, if the base object of the data sheet is not the object with the behavior and is not
        // extended from it.
        if (! $dataSheet->getMetaObject()->is($this->getObject())) {
            return;
        }
        
        // Check if the column is present in the sheet
        if ($column = $dataSheet->getColumns()->getByAttribute($this->getStringAttribute())) {
            foreach ($column->getValues(false) as $rowNr => $value) {
                if ($value !== null && $value !== '') {
                    $column->setValue($rowNr, StringDataType::encrypt($this->getWorkbench(), $value));
                }
            }
        }
        return;
    }
    
    public function handleOnReadEvent(OnBeforeReadDataEvent $event)
    {
        if ($this->isDisabled()) {
            return;
        }
        
        $dataSheet = $event->getDataSheet();
        
        // Do not do anything, if the base object of the data sheet is not the object with the behavior and is not
        // extended from it.
        if (! $dataSheet->getMetaObject()->is($this->getObject())) {
            return;
        }
        
        if ($column = $dataSheet->getColumns()->getByAttribute($this->getStringAttribute())) {
            foreach ($column->getValues(false) as $rowNr => $value) {
                if ($value !== null && $value !== '') {
                    $column->setValue($rowNr, StringDataType::decrypt($this->getWorkbench(), $value));
                }
            }
        }
        return;
    }

    protected function getStringAttributeAlias()
    {
        return $this->stringAttribute;
    }
    
    /**
     *
     * @return MetaAttributeInterface
     */
    protected function getStringAttribute()
    {
        return $this->getObject()->getAttribute($this->getStringAttributeAlias());
    }

    /**
     * Alias of the attribute holding the string to be hashed.
     * 
     * @uxon-property string_attribute_alias
     * @uxon-type metamodel:attribute
     * 
     * @param string $value
     * @return PasswordHashingBehavior
     */
    public function setStringAttributeAlias(string $value) : StringEncryptingBehavior
    {
        $this->stringAttribute = $value;
        return $this;
    }

    /**
     *
     * {@inheritdoc}
     * @see \exface\Core\CommonLogic\Model\Behaviors\AbstractBehavior::exportUxonObject()
     */
    public function exportUxonObject()
    {
        $uxon = parent::exportUxonObject();
        $uxon->setProperty('string_attribute_alias', $this->getStringAttributeAlias());
        return $uxon;
    }
}