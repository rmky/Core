<?php
namespace exface\Core\CommonLogic\Model;

use exface\Core\Interfaces\Model\UiPageInterface;
use exface\Core\Interfaces\WidgetInterface;
use exface\Core\Interfaces\TemplateInterface;
use exface\Core\Factories\WidgetFactory;
use exface\Core\Interfaces\UiManagerInterface;
use exface\Core\Exceptions\Widgets\WidgetIdConflictError;
use exface\Core\Interfaces\Widgets\iHaveChildren;
use exface\Core\DataTypes\StringDataType;
use exface\Core\Factories\EventFactory;
use exface\Core\Exceptions\Widgets\WidgetNotFoundError;
use exface\Core\CommonLogic\UxonObject;

/**
 * This is the default implementation of the UiPageInterface.
 * 
 * The first widget without a parent added to the page is concidered to be the
 * main root widget.
 * 
 * Widgets get cached in an internal array.
 * 
 * @see UiPageInterface
 * 
 * @author Andrej Kabachnik
 *
 */
class UiPage implements UiPageInterface
{

    private $widgets = array();

    private $id = null;

    private $template = null;

    private $ui = null;

    private $widget_root = null;

    private $context_bar = null;

    const WIDGET_ID_SEPARATOR = '_';

    const WIDGET_ID_SPACE_SEPARATOR = '.';

    /**
     *
     * @deprecated use UiPageFactory::create() instead!
     * @param TemplateInterface $template            
     */
    public function __construct(UiManagerInterface $ui)
    {
        $this->ui = $ui;
    }

    /**
     *
     * @param WidgetInterface $widget            
     * @throws WidgetIdConflictError
     * @return \exface\Core\CommonLogic\Model\UiPage
     */
    public function addWidget(WidgetInterface $widget)
    {
        $widget->setIdAutogenerated($this->generateId($widget));
        if ($widget->getIdSpecified() && $widget->getIdSpecified() != $this->sanitizeId($widget->getIdSpecified())) {
            throw new WidgetIdConflictError($widget, 'Explicitly specified id "' . $widget->getIdSpecified() . '" for widget "' . $widget->getWidgetType() . '" not unique on page "' . $this->getId() . '": please specify a unique id for the widget in the UXON description of the page!');
            return $this;
        }
        
        // Remember the first widget added automatically as the root widget of the page
        if (count($this->widgets) === 0 && !$widget->is('ContextBar')) {
            $this->widget_root = $widget;
        }
        
        $this->widgets[$widget->getId()] = $widget;
        return $this;
    }

    /**
     *
     * @return \exface\Core\Interfaces\WidgetInterface
     */
    public function getWidgetRoot()
    {
        return $this->widget_root;
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\Core\Interfaces\Model\UiPageInterface::getWidget()
     */
    public function getWidget($id, WidgetInterface $parent = null)
    {
        // First check to see, if the widget id is already in the widget list. If so, return the corresponding widget.
        // Otherwise look throgh the entire tree to make sure, even subwidgets with late binding can be found (= that is
        // those, that are created if a certain property of another widget is accessed.
        if ($widget = $this->widgets[$id]) {
            // FIXME Check if one of the ancestors of the widget really is the given parent. Although this should always
            // be the case, but better doublecheck ist.
            return $widget;
        }
        
        // If the parent is null, look under the root widget
        // FIXME this makes a non-parent lookup in pages with multiple roots impossible.
        if (is_null($parent)) {
            if (StringDataType::startsWith($id . static::WIDGET_ID_SEPARATOR, $this->getContextBar()->getId())
            || StringDataType::startsWith($id . static::WIDGET_ID_SPACE_SEPARATOR, $this->getContextBar()->getId())){
                $parent = $this->getContextBar();
            } else {
                // If the page is empty, no widget can be found ;) ...except the widget, that are always there
                if ($this->isEmpty()) {
                    throw new WidgetNotFoundError('Widget "' . $id . '" not found in page "' . $this->getId() . '": page empty!');
                }
                $parent = $this->getWidgetRoot();
            }
        }
        
        if ($id_space_length = strpos($id, static::WIDGET_ID_SPACE_SEPARATOR)) {
            $id_space = substr($id, 0, $id_space_length);
            $id = substr($id, $id_space_length + 1);
            return $this->getWidgetFromIdSpace($id, $id_space, $parent);
        } else {
            return $this->getWidgetFromIdSpace($id, '', $parent);
        }
    }

    /**
     * 
     * @param string $id
     * @param string $id_space
     * @param WidgetInterface $parent
     * 
     * @throws WidgetNotFoundError if no matching widget was found
     * 
     * @return WidgetInterface
     */
    private function getWidgetFromIdSpace($id, $id_space, WidgetInterface $parent)
    {
        $id_with_namespace = static::addIdSpace($id_space, $id);
        if ($widget = $this->widgets[$id_with_namespace]) {
            // FIXME Check if one of the ancestors of the widget really is the given parent. Although this should always
            // be the case, but better doublecheck ist.
            return $widget;
        }
        
        if ($parent->getId() === $id) {
            return $parent;
        }
        
        if (StringDataType::startsWith($id_space, $parent->getId() . self::WIDGET_ID_SEPARATOR)) {
            $id_space_root = $this->getWidget($id_space, $parent);
            return $this->getWidgetFromIdSpace($id, $id_space, $id_space_root);
        }
        
        $id_is_path = false;
        if (StringDataType::startsWith($id_with_namespace, $parent->getId() . self::WIDGET_ID_SEPARATOR)) {
            $id_is_path = true;
        }
        
        if ($parent instanceof iHaveChildren) {
            foreach ($parent->getChildren() as $child) {
                $child_id = $child->getId();
                if ($child_id == $id_with_namespace) {
                    return $child;
                } else {
                    if (! $id_is_path || StringDataType::startsWith($id_with_namespace, $child_id . self::WIDGET_ID_SEPARATOR)) {
                        try {
                            return $this->getWidgetFromIdSpace($id, $id_space, $child);
                        } catch (WidgetNotFoundError $e){
                            // Continue with next branch of the tree
                        }
                    } elseif ($id_is_path) {
                        continue;
                    }
                }
            }
        }
        
        throw new WidgetNotFoundError('Widget "' . $id . '" not found in id space "' . $id_space . '" within parent "' . $parent->getId() . '" on page "' . $this->getId() . '"!');
        
        return;
    }

    private static function addIdSpace($id_space, $id)
    {
        return (is_null($id_space) || $id_space === '' ? '' : $id_space . static::WIDGET_ID_SPACE_SEPARATOR) . $id;
    }

    /**
     * Generates an unique id for the given widget.
     * If the widget has an id already, this is merely sanitized.
     *
     * @param WidgetInterface $widget            
     * @return string
     */
    protected function generateId(WidgetInterface $widget)
    {
        if (! $id = $widget->getId()) {
            if ($widget->getParent()) {
                $id = $widget->getParent()->getId() . self::WIDGET_ID_SEPARATOR;
            }
            $id .= $widget->getWidgetType();
        }
        return $this->sanitizeId($id);
    }

    /**
     * Makes sure, the given widget id is unique in this page.
     * If not, the id gets a numeric index, which makes it unique.
     * Thus, the returned value is guaranteed to be unique!
     *
     * @param string $string            
     * @return string
     */
    protected function sanitizeId($string)
    {
        if ($this->widgets[$string]) {
            $index = substr($string, - 2);
            if (is_numeric($index)) {
                $index_new = str_pad(intval($index + 1), 2, 0, STR_PAD_LEFT);
                $string = substr($string, 0, - 2) . $index_new;
            } else {
                $string .= '02';
            }
            
            return $this->sanitizeId($string);
        }
        return $string;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\UiPageInterface::getId()
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\UiPageInterface::setId()
     */
    public function setId($value)
    {
        $this->id = $value;
        return $this;
    }

    /**
     *
     * @return \exface\Core\Interfaces\TemplateInterface
     */
    public function getTemplate()
    {
        if (is_null($this->template)) {
            // FIXME need a method to get the template from the CMS page here somehow. It should probably become a method of the CMS-connector
            // The mapping between CMS-templates and ExFace-templates needs to move to a config variable of the CMS-connector app!
        }
        return $this->template;
    }

    /**
     *
     * @param TemplateInterface $template            
     * @return \exface\Core\CommonLogic\Model\UiPage
     */
    protected function setTemplate(TemplateInterface $template)
    {
        $this->template = $template;
        return $this;
    }

    /**
     *
     * @param string $widget_type            
     * @param WidgetInterface $parent_widget            
     * @param string $widget_id            
     * @return WidgetInterface
     */
    public function createWidget($widget_type, WidgetInterface $parent_widget = null, UxonObject $uxon = null)
    {
        if ($uxon) {
            $uxon->setProperty('widget_type', $widget_type);
            $widget = WidgetFactory::createFromUxon($this, $uxon, $parent_widget);
        } else {
            $widget = WidgetFactory::create($this, $widget_type, $parent_widget);
        }
        return $widget;
    }

    /**
     *
     * @param string $widget_id            
     * @return \exface\Core\CommonLogic\Model\UiPage
     */
    public function removeWidgetById($widget_id)
    {
        unset($this->widgets[$widget_id]);
        return $this;
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\Core\Interfaces\Model\UiPageInterface::removeWidget()
     */
    public function removeWidget(WidgetInterface $widget, $remove_children_too = true)
    {
        if ($remove_children_too) {
            foreach ($this->widgets as $cached_widget){
                if ($cached_widget->getParent() === $widget){
                    $this->removeWidget($cached_widget, true);
                }
            }
        }
        $result = $this->removeWidgetById($widget->getId());
        
        $this->getWorkbench()->eventManager()->dispatch(EventFactory::createWidgetEvent($widget, 'Remove.After'));
        
        return $result;
    }

    /**
     *
     * @return UiManagerInterface
     */
    public function getUi()
    {
        return $this->ui;
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\Core\Interfaces\ExfaceClassInterface::getWorkbench()
     */
    public function getWorkbench()
    {
        return $this->getUi()->getWorkbench();
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\UiPageInterface::getWidgetIdSeparator()
     */
    public function getWidgetIdSeparator()
    {
        return self::WIDGET_ID_SEPARATOR;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\UiPageInterface::getWidgetIdSpaceSeparator()
     */
    public function getWidgetIdSpaceSeparator()
    {
        return self::WIDGET_ID_SPACE_SEPARATOR;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\UiPageInterface::isEmpty()
     */
    public function isEmpty()
    {
        return $this->getWidgetRoot() ? false : true;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\UiPageInterface::getContextBar()
     */
    public function getContextBar()
    {
        if (is_null($this->context_bar)) {
            $this->context_bar = WidgetFactory::create($this, 'ContextBar');
        }
        return $this->context_bar;
    }
    
    /**
     * TODO #ui-page-installer
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\UiPageInterface::setName()
     */
    public function setName($string)
    {}

    /**
     * TODO #ui-page-installer
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\UiPageInterface::setShortDescription()
     */
    public function setShortDescription($string)
    {}

    /**
     * TODO #ui-page-installer
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\UiPageInterface::getName()
     */
    public function getName()
    {}

    /**
     * TODO #ui-page-installer
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\UiPageInterface::setParentPageAlias()
     */
    public function setParentPageAlias($alias_with_namespace)
    {}

    /**
     * TODO #ui-page-installer
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\iCanBeConvertedToUxon::exportUxonObject()
     */
    public function exportUxonObject()
    {}

    /**
     * TODO #ui-page-installer
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\UiPageInterface::setParentPage()
     */
    public function setParentPage(UiPageInterface $page)
    {}

    /**
     * TODO #ui-page-installer
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\UiPageInterface::getParentPageAlias()
     */
    public function getParentPageAlias()
    {}

    /**
     * TODO #ui-page-installer
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\UiPageInterface::isUpdateable()
     */
    public function isUpdateable()
    {}

    /**
     * TODO #ui-page-installer
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\UiPageInterface::setUpdateable()
     */
    public function setUpdateable($true_or_false)
    {}

    /**
     * TODO #ui-page-installer
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\UiPageInterface::getApp()
     */
    public function getApp()
    {}

    /**
     * TODO #ui-page-installer
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\UiPageInterface::getParentPage()
     */
    public function getParentPage()
    {}

    /**
     * TODO #ui-page-installer
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\iCanBeConvertedToUxon::importUxonObject()
     */
    public function importUxonObject(UxonObject $uxon)
    {}

    /**
     * TODO #ui-page-installer
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\AliasInterface::getAliasWithNamespace()
     */
    public function getAliasWithNamespace()
    {}

    /**
     * TODO #ui-page-installer
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\UiPageInterface::getShortDescription()
     */
    public function getShortDescription()
    {}

    /**
     * TODO #ui-page-installer
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\AliasInterface::getNamespace()
     */
    public function getNamespace()
    {}

    /**
     * TODO #ui-page-installer
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\AliasInterface::getAlias()
     */
    public function getAlias()
    {}
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\UiPageInterface::setReplacesPageAlias()
     */
    public function setReplacesPageAlias($alias_with_namespace)
    {
        // TODO #ui-page-installer
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Model\UiPageInterface::getReplacesPageAlias()
     */
    public function getReplacesPageAlias()
    {    
        // TODO #ui-page-installer
    }


}

?>
