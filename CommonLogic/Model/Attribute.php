<?php

namespace exface\Core\CommonLogic\Model;

use exface\Core\CommonLogic\UxonObject;
use exface\Core\Factories\DataTypeFactory;
use exface\Core\Factories\RelationPathFactory;
use exface\Core\Exceptions\UnexpectedValueException;
use exface\Core\Interfaces\DataTypes\DataTypeInterface;
use exface\Core\Interfaces\Model\MetaAttributeInterface;
use exface\Core\Interfaces\Model\MetaRelationPathInterface;
use exface\Core\Interfaces\Model\MetaObjectInterface;
use exface\Core\DataTypes\BooleanDataType;
use exface\Core\DataTypes\SortingDirectionsDataType;

/**
 * 
 * @author Andrej Kabachnik
 *
 */
class Attribute implements MetaAttributeInterface
{

    // Properties to be dublicated on copy()
    private $id;

    private $inherited_from_object_id = null;

    private $alias;

    private $name;

    private $data;

    private $data_address_properties;

    private $data_type;

    private $formatter;
    
    private $readable = true;
    
    private $writable = null;

    private $required = false;

    private $hidden = false;

    private $editable = false;

    private $system = false;

    private $default_display_order;

    private $is_relation;

    private $formula;

    private $default_value;

    private $fixed_value;
    
    private $value_list_delimiter = EXF_LIST_SEPARATOR;

    private $default_sorter_dir = 'ASC';

    private $short_description;

    private $defaul_aggregate_function = null;

    private $sortable;

    private $filterable;

    private $aggregatable;
    
    /** @var UxonObject|null */
    private $default_editor_uxon = null;

    /** @var UxonObject|null */
    private $custom_data_type_uxon = null;

    /** @var MetaRelationPathInterface|null */
    private $relation_path;

    // Properties NOT to be dublicated on copy()
    /** @var Model */
    private $object;

    public function __construct(MetaObjectInterface $object)
    {
        $this->object = $object;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\MetaAttributeInterface::setRelationFlag()
     */
    public function setRelationFlag($value)
    {
        $this->is_relation = $value;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\MetaAttributeInterface::isRelation()
     */
    public function isRelation()
    {
        return $this->is_relation;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\MetaAttributeInterface::getRelation()
     */
    public function getRelation()
    {
        return $this->getObject()->getRelation($this->getAlias());
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\MetaAttributeInterface::getAliasWithRelationPath()
     */
    public function getAliasWithRelationPath()
    {
        return RelationPath::relationPathAdd($this->getRelationPath()->toString(), $this->getAlias());
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\MetaAttributeInterface::getId()
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\MetaAttributeInterface::setId()
     */
    public function setId($value)
    {
        $this->id = $value;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\MetaAttributeInterface::getAlias()
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\MetaAttributeInterface::setAlias()
     */
    public function setAlias($value)
    {
        $this->alias = $value;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\MetaAttributeInterface::getDataType()
     */
    public function getDataType()
    {
        if (is_string($this->data_type)){
            $this->data_type = DataTypeFactory::createFromUidOrAlias($this->getModel(), $this->data_type);
            $this->data_type->importUxonObject($this->getCustomDataTypeUxon());
        }
        return $this->data_type;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\MetaAttributeInterface::setDataType()
     */
    public function setDataType($instance_or_resolvable_string)
    {
        if (is_string($instance_or_resolvable_string) || ($instance_or_resolvable_string instanceof DataTypeInterface)) {
            $this->data_type = $instance_or_resolvable_string;
        } else {
            throw new UnexpectedValueException('Invalid data type value given to attribute "' . $this->getAliasWithRelationPath() . '" of object "' . $this->getObject()->getAliasWithNamespace() . '": string or instantiated data type classes expected!');
        }
        return $this;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\MetaAttributeInterface::getDefaultDisplayOrder()
     */
    public function getDefaultDisplayOrder()
    {
        return $this->default_display_order;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\MetaAttributeInterface::setDefaultDisplayOrder()
     */
    public function setDefaultDisplayOrder($value)
    {
        $this->default_display_order = $value;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\MetaAttributeInterface::isEditable()
     */
    public function isEditable()
    {
        if ($this->getObject()->hasDataSource() && ! $this->getObject()->isWritable()) {
            return false;
        }
        return $this->editable;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\MetaAttributeInterface::setEditable()
     */
    public function setEditable($value)
    {
        $this->editable = BooleanDataType::cast($value);
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\MetaAttributeInterface::getFormatter()
     */
    public function getFormatter()
    {
        return $this->formatter;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\MetaAttributeInterface::setFormatter()
     */
    public function setFormatter($value)
    {
        $this->formatter = $value;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\MetaAttributeInterface::isHidden()
     */
    public function isHidden()
    {
        return $this->hidden;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\MetaAttributeInterface::setHidden()
     */
    public function setHidden($value)
    {
        $this->hidden = BooleanDataType::cast($value);
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\MetaAttributeInterface::getName()
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\MetaAttributeInterface::setName()
     */
    public function setName($value)
    {
        $this->name = $value;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\MetaAttributeInterface::isReadable()
     */
    public function isReadable()
    {
        return $this->readable;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\MetaAttributeInterface::setReadable()
     */
    public function setReadable($true_or_false)
    {
        $this->readable = BooleanDataType::cast($true_or_false);
        return $this;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\MetaAttributeInterface::isWritable()
     */
    public function isWritable()
    {
        return $this->writable;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\MetaAttributeInterface::setWritable()
     */
    public function setWritable($true_or_false)
    {
        $this->writable = BooleanDataType::cast($true_or_false);
        return $this;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\MetaAttributeInterface::isRequired()
     */
    public function isRequired()
    {
        return $this->required;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\MetaAttributeInterface::setRequired()
     */
    public function setRequired($value)
    {
        $this->required = BooleanDataType::cast($value);
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\MetaAttributeInterface::getDataAddress()
     */
    public function getDataAddress()
    {
        return $this->data;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\MetaAttributeInterface::setDataAddress()
     */
    public function setDataAddress($value)
    {
        $this->data = $value;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\MetaAttributeInterface::getRelationPath()
     */
    public function getRelationPath()
    {
        if (is_null($this->relation_path)) {
            $this->relation_path = RelationPathFactory::createForObject($this->getObject());
        }
        return $this->relation_path;
    }

    /**
     * 
     * @param MetaRelationPathInterface $path
     */
    public function setRelationPath(MetaRelationPathInterface $path)
    {
        $this->relation_path = $path;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\MetaAttributeInterface::getObject()
     */
    public function getObject()
    {
        return $this->object;
    }
    
    /**
     * 
     * @param MetaObjectInterface $object
     * @return \exface\Core\CommonLogic\Model\Attribute
     */
    public function setObject(MetaObjectInterface $object)
    {
        $this->object = $object;
        return $this;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\MetaAttributeInterface::getObjectInheritedFrom()
     */
    public function getObjectInheritedFrom()
    {
        if ($this->isInherited()) {
            return $this->getModel()->getObjectById($this->getInheritedFromObjectId());
        } else {
            return $this->getObject();
        }
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\MetaAttributeInterface::getFormula()
     */
    public function getFormula()
    {
        return $this->formula;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\MetaAttributeInterface::setFormula()
     */
    public function setFormula($value)
    {
        if ($value) {
            $this->formula = $this->getModel()->parseExpression($value, $this->getObject());
        }
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\MetaAttributeInterface::getDefaultValue()
     */
    public function getDefaultValue()
    {
        if ($this->default_value && ! ($this->default_value instanceof expression)) {
            $this->default_value = $this->getModel()->parseExpression($this->default_value, $this->getObject());
        }
        return $this->default_value;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\MetaAttributeInterface::setDefaultValue()
     */
    public function setDefaultValue($value)
    {
        if ($value) {
            $this->default_value = $value;
        }
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\MetaAttributeInterface::getFixedValue()
     */
    public function getFixedValue()
    {
        if ($this->fixed_value && ! ($this->fixed_value instanceof expression)) {
            $this->fixed_value = $this->getModel()->parseExpression($this->fixed_value, $this->getObject());
        }
        return $this->fixed_value;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\MetaAttributeInterface::setFixedValue()
     */
    public function setFixedValue($value)
    {
        $this->fixed_value = $value;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\MetaAttributeInterface::getDefaultSorterDir()
     */
    public function getDefaultSorterDir()
    {
        return $this->default_sorter_dir ? $this->default_sorter_dir : $this->getDataType()->getDefaultSortingDirection();
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\MetaAttributeInterface::setDefaultSorterDir()
     */
    public function setDefaultSorterDir($value)
    {        
        if ($value instanceof SortingDirectionsDataType){
            // everything is OK
        } elseif (SortingDirectionsDataType::isValidStaticValue(strtoupper($value))){
            $value = new SortingDirectionsDataType($this->getWorkbench(), strtoupper($value));
        } else {
            throw new UnexpectedValueException('Invalid value "' . $value . '" for default sorting direction in attribute "' . $this->getName() . '": use ASC or DESC');
        }
        
        $this->default_sorter_dir = $value;
        return $this;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\MetaAttributeInterface::getObjectId()
     */
    public function getObjectId()
    {
        return $this->getObject()->getId();
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\MetaAttributeInterface::getModel()
     */
    public function getModel()
    {
        return $this->getObject()->getModel();
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\MetaAttributeInterface::getShortDescription()
     */
    public function getShortDescription()
    {
        return $this->short_description;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\MetaAttributeInterface::setShortDescription()
     */
    public function setShortDescription($value)
    {
        $this->short_description = $value;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\MetaAttributeInterface::getHint()
     */
    public function getHint()
    {
        return ($this->getShortDescription() ? $this->getShortDescription() : $this->getName()) . ' [' . $this->getDataType()->getName() . ']';
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\MetaAttributeInterface::getInheritedFromObjectId()
     */
    public function getInheritedFromObjectId()
    {
        return $this->inherited_from_object_id;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\MetaAttributeInterface::setInheritedFromObjectId()
     */
    public function setInheritedFromObjectId($value)
    {
        $this->inherited_from_object_id = $value;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\MetaAttributeInterface::isInherited()
     */
    public function isInherited()
    {
        return is_null($this->getInheritedFromObjectId()) ? true : false;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\MetaAttributeInterface::getDataAddressProperties()
     */
    public function getDataAddressProperties()
    {
        return $this->data_address_properties;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\MetaAttributeInterface::setDataAddressProperties()
     */
    public function setDataAddressProperties(UxonObject $value)
    {
        $this->data_address_properties = $value;
        return $this;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\MetaAttributeInterface::getDataAddressProperty()
     */
    public function getDataAddressProperty($id)
    {
        return $this->getDataAddressProperties()->getProperty($id);
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\MetaAttributeInterface::setDataAddressProperty()
     */
    public function setDataAddressProperty($id, $value)
    {
        $this->getDataAddressProperties()->setProperty($id, $value);
        return $this;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\MetaAttributeInterface::isLabelForObject()
     */
    public function isLabelForObject()
    {
        if ($this->getAlias() == $this->getObject()->getLabelAttributeAlias()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\MetaAttributeInterface::isUidForObject()
     */
    public function isUidForObject()
    {
        if ($this->getObject()->getUidAttributeAlias() === $this->getAlias()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\MetaAttributeInterface::isExactly()
     */
    public function isExactly(MetaAttributeInterface $attribute)
    {
        if ($this->getId() == $attribute->getId() && $this->getObject()->isExactly($attribute->getObject())) {
            return true;
        }
        return false;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\MetaAttributeInterface::is()
     */
    public function is(MetaAttributeInterface $attribute)
    {
        // IDEA perhaps it would be better to use $attribute->getAliasWithRelationPath() for comparison?
        if (strcasecmp($this->getAlias(), $attribute->getAlias()) === 0 && $this->getObject()->is($attribute->getObject())) {
            return true;
        }
        return false;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\iCanBeCopied::copy()
     */
    public function copy()
    {
        return $this->rebase($this->getRelationPath()->copy());
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\MetaAttributeInterface::rebase()
     */
    public function rebase(MetaRelationPathInterface $path)
    {
        $copy = clone $this;
        
        // Explicitly copy properties, that are objects themselves
        $copy->setRelationPath($path);
        
        // Do not use getDefaultEditorUxon() here as it already performs some enrichment
        if ($this->default_editor_uxon instanceof UxonObject){
            $copy->setDefaultEditorUxon($this->default_editor_uxon->copy());
        }
        
        return $copy;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\MetaAttributeInterface::isSystem()
     */
    public function isSystem()
    {
        return $this->system;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\MetaAttributeInterface::setSystem()
     */
    public function setSystem($value)
    {
        $this->system = \exface\Core\DataTypes\BooleanDataType::cast($value);
        return $this;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\ExfaceClassInterface::getWorkbench()
     */
    public function getWorkbench()
    {
        return $this->getModel()->getWorkbench();
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\MetaAttributeInterface::getDefaultAggregateFunction()
     */
    public function getDefaultAggregateFunction()
    {
        return $this->default_aggregate_function;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\MetaAttributeInterface::setDefaultAggregateFunction()
     */
    public function setDefaultAggregateFunction($value)
    {
        $this->default_aggregate_function = $value;
        return $this;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\MetaAttributeInterface::isSortable()
     */
    public function isSortable()
    {
        return $this->sortable;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\MetaAttributeInterface::setSortable()
     */
    public function setSortable($value)
    {
        $this->sortable = \exface\Core\DataTypes\BooleanDataType::cast($value);
        return $this;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\MetaAttributeInterface::isFilterable()
     */
    public function isFilterable()
    {
        return $this->filterable;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\MetaAttributeInterface::setFilterable()
     */
    public function setFilterable($value)
    {
        $this->filterable = \exface\Core\DataTypes\BooleanDataType::cast($value);
        return $this;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\MetaAttributeInterface::isAggregatable()
     */
    public function isAggregatable()
    {
        return $this->aggregatable;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\MetaAttributeInterface::setAggregatable()
     */
    public function setAggregatable($value)
    {
        $this->aggregatable = \exface\Core\DataTypes\BooleanDataType::cast($value);
        return $this;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\MetaAttributeInterface::getValueListDelimiter()
     */
    public function getValueListDelimiter()
    {
        return $this->value_list_delimiter;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\MetaAttributeInterface::setValueListDelimiter()
     */
    public function setValueListDelimiter($string)
    {
        if (!is_null($string) && $string !== ''){
            $this->value_list_delimiter = $string;
        }
        return $this;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\MetaAttributeInterface::getCustomDataTypeUxon()
     */
    public function getCustomDataTypeUxon()
    {
        if (is_null($this->custom_data_type_uxon)){
            return new UxonObject();
        }
        return $this->custom_data_type_uxon->copy();
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\MetaAttributeInterface::setCustomDataTypeUxon()
     */
    public function setCustomDataTypeUxon(UxonObject $uxon)
    {
        $this->custom_data_type_uxon = $uxon;
        $this->resetDataType();
        return $this;
    }
    
    /**
     * 
     * @return \exface\Core\CommonLogic\Model\Attribute
     */
    private function resetDataType()
    {
        // If the data type had already been instantiated, degrade it back to a string alias.
        // Next time the getDataType() is called, it will reinstantiate the data type uxing
        // the new custom setting.
        if ($this->data_type instanceof UxonObject){
            $this->data_type = $this->data_type->getAliasWithNamespace();
        }
        
        return $this;
    }

    /**
     *
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\MetaAttributeInterface::getDefaultEditorUxon()
     */
    public function getDefaultEditorUxon()
    {
        // If there is no default widget uxon defined, use the UXON from the data type
        if (is_null($this->default_editor_uxon)) {
            if ($this->isRelation()) {
                $this->default_editor_uxon = new UxonObject([
                    "widget_type" => $this->getWorkbench()->getConfig()->getOption('TEMPLATES.DEFAULT_WIDGET_FOR_RELATIONS')
                ]);
            } else {
                $this->default_editor_uxon = $this->getDataType()->getDefaultEditorUxon()->copy();
            }
        }
        
        $uxon = $this->default_editor_uxon->copy();
        
        if (! $uxon->hasProperty('attribute_alias')) {
            $uxon->setProperty('attribute_alias', $this->getAliasWithRelationPath());
        }
        
        return $uxon;
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\MetaAttributeInterface::setDefaultEditorUxon()
     */
    public function setDefaultEditorUxon(UxonObject $uxon)
    {
        $this->default_editor_uxon = $uxon;
        return $this;
    }
}
?>